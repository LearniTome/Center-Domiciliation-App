<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Cloisonnement effectif des listes, verifie contre une vraie base.
 *
 * Ces tests creent deux tenants, deux comptes et des dossiers de chaque cote,
 * puis appellent les fonctions de liste avec une session de cabinet simulee.
 * Ils etaient impossibles avant le deploiement des migrations : sans
 * `cabinet_id` en base, le comportement correct et le comportement fail-open
 * sont identiques.
 */
final class TenantIsolationTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static array $ids = [];

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=127.0.0.1;dbname=center_domiciliation;charset=utf8mb4',
                'root',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            self::markTestSkipped('Base de developpement injoignable : ' . $e->getMessage());
        }

        $pdo = self::$pdo;

        // Nettoyage d'une execution precedente interrompue.
        $pdo->exec("DELETE FROM users WHERE email LIKE 'tst-%@example.test'");
        $pdo->exec("DELETE FROM societes WHERE societe_ice LIKE 'TST-%'");
        $pdo->exec("DELETE FROM cabinets WHERE id IN (90001, 90002)");

        // Cabinet A = 90001, cabinet B = 90002 : hors plage des donnees reelles.
        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES
            (90001, 'Cabinet test A', 'TSTA', 'actif'),
            (90002, 'Cabinet test B', 'TSTB', 'actif')");

        $role = (int) $pdo->query("SELECT id FROM roles WHERE nom = 'Collaborateur Cabinet'")->fetchColumn();
        if ($role === 0) {
            self::fail('Role "Collaborateur Cabinet" introuvable : la matrice RBAC a change.');
        }

        // Deux comptes INFORMATION, volontairement SANS fiche collaborateur :
        // c'est le cas qui faisait passer l'ancien code de liste a `$userId = null`
        // et donc a ne plus filtrer du tout.
        $pdo->exec("INSERT INTO users
            (nom_complet, email, password_hash, cabinet_id, collaborateur_id, role_id, statut)
            VALUES
            ('Adherent Test A', 'tst-a@example.test', '!', 90001, NULL, $role, 'actif'),
            ('Adherent Test B', 'tst-b@example.test', '!', 90002, NULL, $role, 'actif')");
        self::$ids['user_a'] = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO users
            (nom_complet, email, password_hash, cabinet_id, collaborateur_id, role_id, statut)
            VALUES ('Adherent Test B', 'tst-b2@example.test', '!', 90002, NULL, $role, 'actif')");
        self::$ids['user_b'] = (int) $pdo->lastInsertId();

        // Dossiers : un par cabinet, `created_by` NULL = produits par le Centre.
        $pdo->exec("INSERT INTO societes
            (societe_raison_sociale, societe_ice, societe_ville, created_by, cabinet_id) VALUES
            ('SOCIETE TEST ALPHA', 'TST-A', 'Casablanca', NULL, 90001),
            ('SOCIETE TEST BRAVO', 'TST-B', 'Rabat',       NULL, 90002)");

        foreach ($pdo->query("SELECT id, cabinet_id FROM societes WHERE societe_ice IN ('TST-A', 'TST-B')") as $r) {
            self::$ids[(int) $r['cabinet_id'] === 90001 ? 'soc_a' : 'soc_b'] = (int) $r['id'];
        }

        // Un document genere par dossier : la liste des documents est la
        // surface la plus exposee (elle accepte des ID forges).
        foreach (['soc_a' => 90001, 'soc_b' => 90002] as $k => $cid) {
            $pdo->exec("INSERT INTO documents_generes
                (societe_id, template_source, doc_type, fichier_docx, taille_ko, valide, cabinet_id)
                VALUES (" . self::$ids[$k] . ", 'test', 'Statuts', 'dossiers_generer/test.docx', 1, 0, $cid)");
            self::$ids[$k === 'soc_a' ? 'doc_a' : 'doc_b'] = (int) $pdo->lastInsertId();
        }

        // Une etape de suivi administratif par dossier. `societe_suivi_etapes`
        // est la table que `societe_suivi.php` mettait a jour par
        // `WHERE id = :id` seul, sans contrainte de dossier.
        foreach (['soc_a' => 90001, 'soc_b' => 90002] as $k => $cid) {
            $pdo->exec("INSERT INTO societe_suivi_etapes
                (societe_id, etape, ordre, statut, cabinet_id)
                VALUES (" . self::$ids[$k] . ", 'TST etape', 1, 'en_attente', $cid)");
            self::$ids[$k === 'soc_a' ? 'etape_a' : 'etape_b'] = (int) $pdo->lastInsertId();
        }

        // Une cession par cabinet : cible de `cession_details_dossier.php` et
        // `cession_suivi.php`, qui lisaient la ligne sans aucun cloisonnement.
        foreach (['soc_a' => 90001, 'soc_b' => 90002] as $k => $cid) {
            $pdo->exec("INSERT INTO cessions
                (societe_id, cession_dossier, cession_date, cabinet_id)
                VALUES (" . self::$ids[$k] . ", 'TST-CES-001', '2026-01-01', $cid)");
            self::$ids[$k === 'soc_a' ? 'ces_a' : 'ces_b'] = (int) $pdo->lastInsertId();
        }

        // Une etape de suivi de cession + un document rattache, par cabinet.
        // Ces deux tables portent chacune `cabinet_id` : une jointure sans
        // alias de colonne rend le predicat ambigu (erreur 1052).
        foreach (['soc_a' => 90001, 'soc_b' => 90002] as $k => $cid) {
            $pdo->exec("INSERT INTO cession_suivi_etapes
                (cession_id, etape, ordre, statut, cabinet_id)
                VALUES (" . self::$ids['ces_' . substr($k, 4)] . ", 'TST etape cession', 1, 'en_attente', $cid)");
            $etape = (int) $pdo->lastInsertId();
            self::$ids[$k === 'soc_a' ? 'ces_etape_a' : 'ces_etape_b'] = $etape;

            $pdo->exec("INSERT INTO cession_suivi_documents
                (etape_id, nom, fichier, cabinet_id)
                VALUES ($etape, 'TST doc', 'test.pdf', $cid)");
            self::$ids[$k === 'soc_a' ? 'ces_doc_a' : 'ces_doc_b'] = (int) $pdo->lastInsertId();
        }

        // Un compte Centre : `cabinet_id` NULL, c'est le seul cas non confine.
        self::$ids['centre'] = (int) $pdo->query(
            "SELECT id FROM users WHERE cabinet_id IS NULL AND statut = 'actif' LIMIT 1"
        )->fetchColumn();

        // `current_user()` lit le global : sans cela les helpers renverraient
        // toujours l'etat d'un utilisateur inexistant.
        $GLOBALS['pdo'] = $pdo;
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$pdo instanceof PDO) {
            return;
        }
        $pdo = self::$pdo;
        foreach (['doc_a', 'doc_b'] as $k) {
            $pdo->exec("DELETE FROM documents_generes WHERE id = " . (int) self::$ids[$k]);
        }
        foreach (['etape_a', 'etape_b'] as $k) {
            $pdo->exec("DELETE FROM societe_suivi_etapes WHERE id = " . (int) self::$ids[$k]);
        }
        // Les documents de suivi dependent des etapes, elles-memes des
        // cessions : suppression dans l'ordre inverse des insertions.
        $pdo->exec("DELETE d FROM cession_suivi_documents d
                    INNER JOIN cession_suivi_etapes e ON e.id = d.etape_id
                    INNER JOIN cessions c ON c.id = e.cession_id
                    WHERE c.cession_dossier = 'TST-CES-001'");
        $pdo->exec("DELETE e FROM cession_suivi_etapes e
                    INNER JOIN cessions c ON c.id = e.cession_id
                    WHERE c.cession_dossier = 'TST-CES-001'");
        foreach (['ces_a', 'ces_b'] as $k) {
            $pdo->exec("DELETE FROM cessions WHERE id = " . (int) self::$ids[$k]);
        }
        // Les contrats de test sont rattaches aux societes TST : ils doivent
        // partir AVANT les societes, sinon la cle etrangere les retient.
        $pdo->exec("DELETE c FROM contrats c
                    INNER JOIN societes s ON s.id = c.societe_id
                    WHERE s.societe_ice LIKE 'TST-%'");
        $pdo->exec("DELETE FROM users WHERE email LIKE 'tst-%@example.test'");
        $pdo->exec("DELETE FROM societes WHERE societe_ice LIKE 'TST-%'");
        $pdo->exec("DELETE FROM cabinets WHERE id IN (90001, 90002)");
        unset($GLOBALS['pdo']);
        $_SESSION = [];
    }

    /** Bascule la session sur un compte reel et purge le cache utilisateur. */
    private static function loginAs(string $key): void
    {
        $_SESSION = ['user_id' => self::$ids[$key]];
        unset($_SESSION['_user_cache']);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ------------------------------------------------------------------

    public function testLeCentreVoitLesDossiersDeTousLesTenants(): void
    {
        self::loginAs('centre');

        $options = fetch_societes_options(self::$pdo);

        $ids = array_map('intval', array_column($options, 'id'));
        $this->assertContains(self::$ids['soc_a'], $ids, 'Le Centre perd le dossier du cabinet A.');
        $this->assertContains(self::$ids['soc_b'], $ids, 'Le Centre perd le dossier du cabinet B.');
    }

    public function testUnAdherentNeVoitQueLesDossiersDeSonCabinet(): void
    {
        self::loginAs('user_a');

        $options = fetch_societes_options(self::$pdo);

        $ids = array_map('intval', array_column($options, 'id'));
        $this->assertContains(self::$ids['soc_a'], $ids);
        $this->assertNotContains(
            self::$ids['soc_b'],
            $ids,
            'Fuite inter-cabinet : le cabinet A voit la liste deroulante du cabinet B.'
        );
    }

    public function testLaListeDesDocumentsEstCloisonnee(): void
    {
        self::loginAs('user_a');

        $docs = fetch_all_documents(self::$pdo);

        $societes = array_map(static fn($d) => (int) $d['societe_id'], $docs);
        $this->assertContains(self::$ids['soc_a'], $societes, 'Le cabinet A perd ses propres documents.');
        $this->assertNotContains(
            self::$ids['soc_b'],
            $societes,
            'Fuite : le cabinet A recoit un document du cabinet B dans la liste.'
        );
    }

    public function testUnAdherentSansFicheCollaborateurNeVoitPasLaProductionDocumentaire(): void
    {
        // Cas le plus dangereux : compte cabinet SANS fiche collaborateur, donc
        // `current_collaborateur_id()` renvoie null. L'ancien code passait alors
        // `$userId = null` a `fetch_all_documents()`, qui ne filtrait plus rien.
        self::loginAs('user_a');
        $this->assertNull(current_collaborateur_id(), 'Le jeu de test ne reproduit plus le cas vise.');

        $docs = fetch_all_documents(self::$pdo, null, null, null, null);

        $societes = array_map(static fn($d) => (int) $d['societe_id'], $docs);
        $this->assertNotContains(
            self::$ids['soc_b'],
            $societes,
            'Fuite : un compte cabinet sans fiche collaborateur voit la production d\'un autre cabinet.'
        );
    }

    public function testFilterAccessibleIdsEcarteLesIdsDUnAutreTenant(): void
    {
        self::loginAs('user_a');

        $lot = filter_accessible_ids(self::$pdo, 'documents_generes', [self::$ids['doc_a']]);

        $this->assertNotEmpty($lot['ids'], 'Le cabinet A doit pouvoir agir sur ses propres documents.');
        $this->assertSame(0, $lot['ignores']);
    }

    public function testFilterAccessibleIdsSignaleLeLotMixteSansLe_laisserPasser(): void
    {
        self::loginAs('user_a');

        // Le cabinet A cible son document ET celui du cabinet B.
        $lot = filter_accessible_ids(
            self::$pdo,
            'documents_generes',
            [self::$ids['doc_a'], self::$ids['doc_b']]
        );

        $this->assertSame([self::$ids['doc_a']], $lot['ids'], 'Le document du cabinet B a survecu au filtre.');
        $this->assertSame(1, $lot['ignores'], 'Le nombre d\'elements refuses doit etre annonce.');
    }

    public function testAssertTenantAccessRefuseUnDocumentDUnAutreCabinet(): void
    {
        self::loginAs('user_a');

        $this->assertFalse(
            assert_tenant_access(self::$pdo, 'documents_generes', self::$ids['doc_b']),
            'assert_tenant_access() laisse passer un document d\'un autre cabinet.'
        );
    }

    public function testLeCentreNEstPasConfine(): void
    {
        self::loginAs('centre');

        $this->assertTrue(assert_tenant_access(self::$pdo, 'documents_generes', self::$ids['doc_a']));
        $this->assertTrue(assert_tenant_access(self::$pdo, 'societes', self::$ids['soc_b']));
    }

    /**
     * Regression de la panne la plus grave du lot.
     *
     * Reproduit la requete de `pages/dossiers/contrat.php` a l'identique :
     * `INNER JOIN societes s`. Le filtre doit pouvoir s'y inserer. Tant que
     * `contrat_user_filter()` nommait la table complete, MySQL levait
     * « Unknown column 'societes.cabinet_id' » : la page remontait une
     * PDOException, et surtout le cloisonnement n'etait jamais applique.
     *
     * Deux defauts se cumulaient sur ces pages :
     *   1. le fragment nommait `societes.` alors que la requete aliase `s` ;
     *   2. le predicat nu etait concatene brut, sans le `AND` qui doit le
     *      separer du `WHERE` (cf. `testChaqueAppelantAjouteLeAnd`).
     */
    public function testLaRequeteDeLaFicheContratSexecuteEtCloisonne(): void
    {
        self::loginAs('user_a');

        $filtre = contrat_user_filter(current_user(), 's');
        // Assemblage reel des pages : le predicat nu recoit son `AND`.
        $userFilter = $filtre['sql'] !== '' ? ' AND ' . $filtre['sql'] : '';
        $sql = '
            SELECT c.id, s.societe_raison_sociale
              FROM contrats c
              INNER JOIN societes s ON s.id = c.societe_id
             WHERE 1 = 1
            ' . $userFilter . '
        ';

        // L'execution est l'assertion : une exception MySQL ici reproduit la
        // panne de production.
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($filtre['params']);
        $rows = $stmt->fetchAll();
        $this->assertIsArray($rows, 'La requete de la fiche contrat doit s\'executer.');
    }

    /** L'alias seul ne suffit pas : le resultat doit etre vide, pas restrictif. */
    public function testLaFicheContratMasqueLesContratsDUnAutreCabinet(): void
    {
        self::loginAs('user_a');

        // Contrat du cabinet B rattache a sa societe : visible pour le Centre,
        // invisible pour le cabinet A.
        $stmt = self::$pdo->prepare(
            "INSERT INTO contrats (societe_id, contrat_statut, cabinet_id) VALUES (?, 'actif', 90002)"
        );
        $stmt->execute([self::$ids['soc_b']]);
        $contratB = (int) self::$pdo->lastInsertId();

        $filtre = contrat_user_filter(current_user(), 's');
        $userFilter = $filtre['sql'] !== '' ? ' AND ' . $filtre['sql'] : '';
        $stmt = self::$pdo->prepare(
            'SELECT c.id FROM contrats c INNER JOIN societes s ON s.id = c.societe_id'
            . ' WHERE c.id = :id' . $userFilter
        );
        $stmt->execute(['id' => $contratB] + $filtre['params']);

        $this->assertFalse(
            (bool) $stmt->fetch(),
            'Fuite : le cabinet A ouvre en direct la fiche d\'un contrat du cabinet B.'
        );

        self::$pdo->prepare('DELETE FROM contrats WHERE id = ?')->execute([$contratB]);
    }

    public function testAssertTenantAccessRefuseUneCessionDUnAutreCabinet(): void
    {
        self::loginAs('user_a');

        $this->assertTrue(
            assert_tenant_access(self::$pdo, 'cessions', self::$ids['ces_a']),
            'Le cabinet A doit pouvoir agir sur sa propre cession.'
        );
        $this->assertFalse(
            assert_tenant_access(self::$pdo, 'cessions', self::$ids['ces_b']),
            'assert_tenant_access() laisse passer une cession d\'un autre cabinet.'
        );
    }

    public function testAssertTenantAccessRefuseUneEtapeDeSuiviDUnAutreCabinet(): void
    {
        // Cible de `societe_suivi.php`, dont l'UPDATE portait `WHERE id = :id`
        // seul : un adherent pouvait avancer une etape d'un dossier etranger.
        self::loginAs('user_a');

        $this->assertTrue(assert_tenant_access(self::$pdo, 'societe_suivi_etapes', self::$ids['etape_a']));
        $this->assertFalse(
            assert_tenant_access(self::$pdo, 'societe_suivi_etapes', self::$ids['etape_b']),
            'assert_tenant_access() laisse passer une etape de suivi d\'un autre cabinet.'
        );
    }

    public function testFilterAccessibleIdsEcarteUneEtapeDeSuiviEtrangere(): void
    {
        self::loginAs('user_a');

        $lot = filter_accessible_ids(
            self::$pdo,
            'societe_suivi_etapes',
            [self::$ids['etape_a'], self::$ids['etape_b']]
        );

        $this->assertSame([self::$ids['etape_a']], $lot['ids'], 'L\'etape du cabinet B a survecu au filtre.');
        $this->assertSame(1, $lot['ignores']);
    }

    /**
     * La jointure du suivi de cession doit porter un predicat QUALIFIE.
     *
     * `cession_suivi_documents` et `cession_suivi_etapes` ont chacune leur
     * colonne `cabinet_id`. Un predicat nu (`cabinet_id = :tenant_id`) y est
     * ambigu : MySQL repond « Column 'cabinet_id' in 'where clause' is
     * ambiguous » (erreur 1052) et la page est hors service.
     */
    public function testLeSuiviDeCessionQualifieLaColonneCabinet(): void
    {
        self::loginAs('user_a');

        $base = '
            SELECT d.*, e.etape
              FROM cession_suivi_documents d
              JOIN cession_suivi_etapes e ON e.id = d.etape_id
             WHERE e.cession_id = :id';

        // Variante non qualifiee : doit etre rejetee par MySQL. C'est ce
        // qui rendait la page inutilisable, on le verifie pour ne pas
        // « re-fixer » une contrainte qui n'existe pas.
        $nu = tenant_scope();
        $erreur = null;
        try {
            self::$pdo->prepare($base . ' AND ' . $nu['sql'])->execute(
                ['id' => self::$ids['ces_a']] + $nu['params']
            )->fetchAll();
        } catch (PDOException $e) {
            $erreur = $e->getMessage();
        }
        $this->assertStringContainsString(
            'ambiguous',
            (string) $erreur,
            'La colonne cabinet_id devrait etre ambiguë dans cette jointure. Si ce '
            . 'test echoue, verifier le schema : une seule des deux tables la porte, '
            . 'et l\'alias devient sans objet.'
        );

        // Variante qualifiee : c'est celle des pages, elle doit s'executer
        // et cloisonner.
        $scope = tenant_scope('d');
        $this->assertStringContainsString('d.cabinet_id', $scope['sql']);

        $stmt = self::$pdo->prepare($base . ($scope['sql'] !== '' ? ' AND ' . $scope['sql'] : ''));
        $stmt->execute(['id' => self::$ids['ces_a']] + $scope['params']);
        $rows = $stmt->fetchAll();

        $this->assertCount(1, $rows, 'Le cabinet A doit voir le document de son dossier.');
        $this->assertSame(self::$ids['ces_doc_a'], (int) $rows[0]['id']);
    }

    /** Un compte Centre n'est pas confine : il voit les deux cessions. */
    public function testLeCentreVoitLesDocumentsDeSuiviDesDeuxCabinets(): void
    {
        self::loginAs('centre');

        $scope = tenant_scope('d');
        $this->assertSame('', $scope['sql'], 'Le Centre ne doit pas etre filtre.');

        $ids = self::$pdo->query(
            'SELECT d.id FROM cession_suivi_documents d
               JOIN cession_suivi_etapes e ON e.id = d.etape_id
              WHERE e.cession_id IN (' . self::$ids['ces_a'] . ', ' . self::$ids['ces_b'] . ')'
        )->fetchAll(PDO::FETCH_COLUMN);

        $this->assertContains(self::$ids['ces_doc_a'], array_map('intval', $ids));
        $this->assertContains(self::$ids['ces_doc_b'], array_map('intval', $ids));
    }

    // ------------------------------------------------------------------
    // Tableau de bord
    // ------------------------------------------------------------------

    /**
     * Le defaut exact du dashboard, verifie sur le contrat de portee.
     *
     * La page separait ses requetes en `if ($userId !== null) { ... } else
     * { ... }`, avec `created_by = :uid` dans la premiere branche et AUCUN
     * filtre dans la seconde. Or un adherent de cabinet n'a pas de fiche
     * collaborateur par conception : `current_collaborateur_id()` valait null,
     * la page tombait donc dans la branche non filtree, et le tableau de bord
     * exposait les dossiers, contrats, revenus et documents de tous les
     * cabinets. C'est le cas le plus grave car il ne demande aucune
     * forgerie d'identifiant : un simple affichage suffit.
     */
    public function testLeDashboardFiltreUnAdherentSansFicheCollaborateur(): void
    {
        self::loginAs('user_a');
        $this->assertNull(
            current_collaborateur_id(),
            'Le jeu de test ne reproduit plus le cas vise : ce compte DOIT rester sans fiche collaborateur.'
        );

        $scope = list_scope('s');
        $this->assertNotSame(
            '',
            $scope['sql'],
            'list_scope(\'s\') ne restreint plus rien : le dashboard redeviendrait fail-open pour tout adherent sans fiche collaborateur.'
        );

        $stmt = self::$pdo->prepare(
            'SELECT s.id FROM societes s WHERE 1 = 1' . ($scope['sql'] !== '' ? ' AND ' . $scope['sql'] : '')
        );
        $stmt->execute($scope['params']);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $this->assertContains(self::$ids['soc_a'], $ids, 'Le cabinet A perd ses propres dossiers.');
        $this->assertNotContains(
            self::$ids['soc_b'],
            $ids,
            'Fuite inter-cabinet : le tableau de bord du cabinet A compte les dossiers du cabinet B.'
        );
    }

    /**
     * Compter n'est pas exposer : une absence de chaine ne prouve rien si
     * l'agragat reste global. Ce test rejoue la requete de revenu du
     * dashboard a l'identique et verifie le MONTANT, pas seulement les libelles.
     */
    public function testLeRevenuDuDashboardIgnoreLesContratsDUnAutreCabinet(): void
    {
        self::loginAs('user_a');

        $insert = self::$pdo->prepare(
            "INSERT INTO contrats (societe_id, contrat_statut, contrat_loyer_ttc, cabinet_id)
             VALUES (?, 'actif', ?, ?)"
        );
        $insert->execute([self::$ids['soc_a'], 1000.00, 90001]);
        $insert->execute([self::$ids['soc_b'], 900000.00, 90002]);

        // Requete du dashboard, scope compris.
        $scope = list_scope('s');
        $stmt = self::$pdo->prepare(
            "SELECT COALESCE(SUM(c.contrat_loyer_ttc), 0) FROM contrats c
               INNER JOIN societes s ON s.id = c.societe_id
              WHERE c.contrat_statut = 'actif'"
            . ($scope['sql'] !== '' ? ' AND ' . $scope['sql'] : '')
        );
        $stmt->execute($scope['params']);

        $this->assertEqualsWithDelta(
            1000.00,
            (float) $stmt->fetchColumn(),
            0.01,
            'Fuite : le revenu du cabinet A incorpore le contrat du cabinet B.'
        );
    }

    /** Le dashboard n'est pas le seul a alimenter ses compteurs en documents. */
    public function testLesDocumentsDuDashboardSontCloisonnes(): void
    {
        self::loginAs('user_a');

        $scope = list_scope('s');
        $stmt = self::$pdo->prepare(
            'SELECT d.id FROM documents_generes d
               INNER JOIN societes s ON s.id = d.societe_id
              WHERE 1 = 1' . ($scope['sql'] !== '' ? ' AND ' . $scope['sql'] : '')
        );
        $stmt->execute($scope['params']);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $this->assertContains(self::$ids['doc_a'], $ids);
        $this->assertNotContains(
            self::$ids['doc_b'],
            $ids,
            'Fuite : les documents d\'un autre cabinet remontent sur le tableau de bord.'
        );
    }

    /** Le Centre doit garder sa vue globale : le correctif ne doit rien lui retirer. */
    public function testLeCentreVoitLesDossiersDesDeuxCabinetsSurLeDashboard(): void
    {
        self::loginAs('centre');

        $scope = list_scope('s');
        $this->assertSame('', $scope['sql'], 'Le Centre ne doit pas etre filtre.');

        $ids = array_map(
            'intval',
            self::$pdo->query('SELECT id FROM societes')->fetchAll(PDO::FETCH_COLUMN)
        );
        $this->assertContains(self::$ids['soc_a'], $ids);
        $this->assertContains(self::$ids['soc_b'], $ids);
    }

    /**
     * Garde de forme sur la page elle-meme.
     *
     * Les tests ci-dessus couvrent `list_scope()`, pas le SQL redactionnel du
     * dashboard. Or c'est precisement la page qui reintroduisait le defaut :
     * une future requete ajoutee sans passer par le helper recreerait la fuite
     * sans qu'aucun test ne le remarque. On fige donc l'invariant structurel :
     * plus de branche `$userId`, plus de fragment `$sf` concatene a la main.
     */
    public function testLeDashboardNestPlusOrganiseAutourDUneBrancheNonFiltrees(): void
    {
        $src = file_get_contents(dirname(__DIR__) . '/pages/accueil/dashboard.php');
        $this->assertIsString($src, 'Le dashboard est illisible.');

        // Le controle porte sur le CODE, commentaires retires : le defaut doit
        // pouvoir rester documente dans la page sans ressusciter le test.
        $code = '';
        foreach (token_get_all($src) as $token) {
            if (!is_array($token)) {
                $code .= $token;
                continue;
            }
            // On conserve les retours a la ligne pour ne pas recoller deux
            // jetons de part et d'autre d'un commentaire.
            $code .= in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)
                ? str_repeat("\n", substr_count($token[1], "\n"))
                : $token[1];
        }

        $this->assertStringNotContainsString(
            '$userId !== null',
            $code,
            'Le dashboard reintroduit sa bifurcation $userId : un adherent sans fiche collaborateur retomberait dans la branche non filtree.'
        );
        $this->assertStringNotContainsString(
            '$sf',
            $code,
            'Le dashboard reintroduit le fragment $sf concatene a la main, non parametrise.'
        );
        $this->assertStringContainsString(
            '$runScoped(',
            $code,
            'Le dashboard ne passe plus par son helper de requete cloisonnee.'
        );
    }
}
