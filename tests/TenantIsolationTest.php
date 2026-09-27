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
        foreach (['doc_a', 'doc_b'] as $k) {
            self::$pdo->exec("DELETE FROM documents_generes WHERE id = " . (int) self::$ids[$k]);
        }
        self::$pdo->exec("DELETE FROM users WHERE email LIKE 'tst-%@example.test'");
        self::$pdo->exec("DELETE FROM societes WHERE societe_ice LIKE 'TST-%'");
        self::$pdo->exec("DELETE FROM cabinets WHERE id IN (90001, 90002)");
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
}
