<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou du cloisonnement multi-tenants.
 *
 * `tenant_scoped_tables()` est une liste fermee, et `assert_tenant_access()`
 * laisse passer toute table qui n y figure pas. Une table metier omise de la
 * liste n est donc pas refusee, elle est silently traitee comme globale : le
 * defaut est silencieux, ce qui est le pire mode de defaut possible.
 *
 * C est exactement ce qui s est produit avec le suivi des cessions : les tables
 * `cession_suivi_etapes` et `cession_suivi_documents` avaient ete oubliees lors
 * de la migration initiale. Elles ne contenaient aucune ligne, donc aucun test
 * fonctionnel n avait pu les faire echouer.
 *
 * Ces tests n ont pas vocation a deviner la liste : ils verifient que les
 * tables porteuses de `cabinet_id` dans le schema de production sont bien
 * declarees, et que les tables de reference ne le sont pas.
 */
final class TenantScopeTest extends TestCase
{
    /**
     * Tables metier qui doivent imperativement etre declarees cloisonnees.
     *
     * Liste en dur volontairement : c est un second regard independant de
     * `tenant_scoped_tables()`, sinon le test reproduirait l oubli.
     */
    private const TABLES_METIER_ATTENDUES = [
        'societes', 'associes', 'contrats', 'collaborateurs',
        'cessions', 'cession_parts', 'cession_suivi_etapes', 'cession_suivi_documents',
        'pv_ago', 'documents_generes', 'uploaded_docs',
        'societe_suivi_etapes', 'societe_suivi_documents',
    ];

    public function testLesTablesMetierSontToutesDeclareesCloisonnees(): void
    {
        $declarees = tenant_scoped_tables();

        $manquantes = array_diff(self::TABLES_METIER_ATTENDUES, $declarees);

        $this->assertSame(
            [],
            array_values($manquantes),
            'Table(s) metier absente(s) de tenant_scoped_tables() : elle(s) '
            . 'serait(ont) traitee(s) comme globale(s) sans aucune erreur. '
            . 'Ajouter la table ET une migration ajoutant cabinet_id.'
        );
    }

    /**
     * Les tables d'infrastructure SaaS et le RBAC ne doivent PAS etre
     * declarees : le centre tient les comptes de tous les tenants, et un role
     * n'appartient pas a un cabinet.
     */
    public function testLesTablesDInfrastructureNeSontPasCloisonnees(): void
    {
        $declarees = tenant_scoped_tables();

        foreach (['cabinets', 'plans', 'abonnements', 'users', 'roles', 'permissions'] as $table) {
            $this->assertNotContains(
                $table,
                $declarees,
                "« $table » est une table d'infrastructure : la declarer cloisonnee "
                . 'la rendrait invisible aux adherents ou au Centre.'
            );
        }
    }

    /**
     * Une table declaree deux fois casserait silencieusement l'affichage des
     * aides de l'interface de configuration.
     */
    public function testAucunDoublonDansLaListe(): void
    {
        $declarees = tenant_scoped_tables();

        $this->assertSame(
            $declarees,
            array_values(array_unique($declarees)),
            'tenant_scoped_tables() contient un doublon.'
        );
    }

    /**
     * La liste ne doit contenir ni table vide ni nom injecte par requete : elle
     * est concatenee dans du SQL par certains appelants.
     */
    public function testLaListeEstComposeeDeNomsSimples(): void
    {
        foreach (tenant_scoped_tables() as $table) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+$/',
                $table,
                "Nom de table invalide : « $table »."
            );
        }
    }

    /**
     * `list_scope()` renvoie un PREDICAT NU : ni `AND` en tete, ni `WHERE`.
     *
     * Chaque appelant doit donc fournir lui-meme la conjonction. C est le
     * contrat observe par `associes_liste.php`, `societes_liste.php` et
     * `modifications_juridiques.php`.
     *
     * Trois pages le violaient : `contrats_liste.php`, `contrat.php` et
     * `contrats_suivi.php` concatenaient le fragment brut apres un `WHERE`,
     * produisant `WHERE 1=1 societes.cabinet_id = :x`. MySQL levait une erreur
     * 1064 et la page entiere etait hors service — le cloisonnement n'etait
     * donc jamais exerce, ni en production ni dans les tests, qui n'examinaient
     * que la chaine du fragment sans jamais l'executer.
     */
    public function testLeFragmentEstUnPredicatNuSansConjonction(): void
    {
        $_SESSION = ['user_id' => 0, '_user_cache' => [
            'id' => 0, 'cabinet_id' => 4, 'collaborateur_id' => null, 'role_is_system' => 0,
        ]];

        $fragment = list_scope('s')['sql'];

        $this->assertNotSame('', $fragment, 'Le fragment est vide : le test ne prouve rien.');
        $this->assertStringStartsNotWith(
            'AND',
            $fragment,
            'list_scope() a gagne un AND en tete : les trois appelants qui ajoutent '
            . 'le leur produiraient « AND AND ... ».'
        );
        $this->assertStringStartsNotWith(
            'WHERE',
            $fragment,
            'Le fragment ne doit pas contenir WHERE : l\'appelant en fournit deja un.'
        );
    }

    /**
     * Chaque page qui consomme le fragment doit l'assembler correctement.
     *
     * Liste en dur, comme `TABLES_METIER_ATTENDUES` : c est un second regard
     * independant, sinon le test reproduirait l'oubli qu'il traque.
     */
    public function testChaqueAppelantAjouteLeAnd(): void
    {
        $pages = [
            'pages/dossiers/contrats_liste.php',
            'pages/dossiers/contrat.php',
            'pages/dossiers/contrats_suivi.php',
            'pages/dossiers/associes_liste.php',
            'pages/dossiers/societes_liste.php',
        ];

        foreach ($pages as $page) {
            $chemin = dirname(__DIR__) . '/' . $page;
            $this->assertFileExists($chemin, "Page attendue absente : $page");

            $source = (string) file_get_contents($chemin);
            $this->assertMatchesRegularExpression(
                "/' AND '\s*\.\s*\\\$(?:filtreUser|userFilter|scope)\[/",
                $source,
                "$page concatene le fragment de list_scope() sans ajouter le AND : "
                . 'MySQL refuse la requete (erreur 1064) et la page est hors service.'
            );
        }
    }

    /**
     * `build_scoped_sql()` doit donner a chaque `{{SCOPE}}` ses propres noms de
     * parametres.
     *
     * L'application tourne en prepares natifs
     * (`PDO::ATTR_EMULATE_PREPARES => false`), or PDO refuse qu'un parametre
     * nomme apparaisse deux fois dans une requete : `HY093 Invalid parameter
     * number`. Or le fil d'activite du tableau de bord est un `UNION ALL` de
     * trois branches portant chacune son `{{SCOPE}}`.
     *
     * Injecter le meme `:scope_cabinet` aux trois endroits faisait echouer le
     * tableau de bord en FATAL pour tout utilisateur cloisonne : adherent de
     * cabinet, ou employe du Centre sans `dossiers.view_all`. Le defaut ne se
     * voyait pas en developpement sur un compte Centre "Seeing All", dont le
     * perimetre est vide : aucun predicat, donc aucun parametre a repeter.
     *
     * Le test execute donc la requete pour de vrai, en prepares natifs, et
     * exige le nombre exact de valeurs attendues -- un parametre surnumeraire
     * passe inapercu, un parametre manquant ne passe pas.
     */
    public function testChaqueOccurrenceDuScopeReoitSonPropreParametre(): void
    {
        $_SESSION = ['user_id' => 5, '_user_cache' => [
            'id' => 5, 'cabinet_id' => 4, 'collaborateur_id' => null, 'role_is_system' => 0,
        ]];

        $scope = list_scope('s');
        $this->assertNotEmpty($scope['params'], 'perimetre vide : le test ne prouve rien');

        // Le fil d'activite du tableau de bord, a l'identique.
        $requete = build_scoped_sql(
            "
                (SELECT 'societe' AS type, id, societe_raison_sociale AS libelle, id AS ref_id, created_at
                 FROM societes s WHERE 1=1 {{SCOPE}})
                UNION ALL
                (SELECT 'contrat', c.id, s.societe_raison_sociale, c.societe_id, c.created_at
                 FROM contrats c JOIN societes s ON s.id = c.societe_id WHERE 1=1 {{SCOPE}})
                UNION ALL
                (SELECT 'associe', a.id, s.societe_raison_sociale, a.societe_id, a.created_at
                 FROM associes a JOIN societes s ON s.id = a.societe_id WHERE 1=1 {{SCOPE}})
                ORDER BY created_at DESC LIMIT 3
            ",
            $scope
        );

        // Un seul nom pour les trois occurrences : c'est precisement ce que
        // PDO refuse en prepares natifs.
        $noms = [];
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $requete['sql'], $noms);
        $this->assertCount(
            3,
            $noms[1],
            'les trois branches du UNION doivent recevoir trois parametres distincts, pas un partage'
        );
        $this->assertCount(3, array_unique($noms[1]), 'deux branches portent le meme nom de parametre');
        $this->assertCount(3, $requete['params'], 'chaque parametre du scope doit etre fourni');
        $this->assertSame([4, 4, 4], array_values($requete['params']));

        // Verdict de MySQL, pas seulement du-phpunit : c'est lui qui leve HY093.
        $pdo = $this->connexionNative();
        if ($pdo === null) {
            $this->markTestSkipped('Base de developpement injoignable.');
        }

        $stmt = $pdo->prepare($requete['sql']);
        $stmt->execute($requete['params']);
        $this->assertIsArray($stmt->fetchAll());
    }

    /**
     * Un perimetre vide doit retirer le marqueur, pas laisser `{{SCOPE}}` dans
     * la requete : le SQL deviendrait invalide.
     */
    public function testPerimetreVideRetireLeMarqueur(): void
    {
        $requete = build_scoped_sql(
            'SELECT COUNT(*) FROM societes s WHERE 1=1 {{SCOPE}}',
            ['sql' => '', 'params' => []]
        );

        $this->assertStringNotContainsString('{{SCOPE}}', $requete['sql']);
        $this->assertSame('SELECT COUNT(*) FROM societes s WHERE 1=1 ', $requete['sql']);
        $this->assertSame([], $requete['params']);
    }

    /** Un `1 = 0` (employe interne sans fiche collaborateur) ne prend pas de parametre. */
    public function testRefusExpliciteSInjecteSansParametre(): void
    {
        $requete = build_scoped_sql(
            'SELECT COUNT(*) FROM societes s WHERE 1=1 {{SCOPE}}',
            ['sql' => '1 = 0', 'params' => []]
        );

        $this->assertStringContainsString(' AND 1 = 0', $requete['sql']);
        $this->assertSame([], $requete['params']);
    }

    /**
     * Les parametres propres a la requete sont preserves, et le perimetre
     * n'est pas surchargeable.
     *
     * Le second point est une frontiere de securite : `list_scope()` est
     * l'unique source du cloisonnement. Si une page pouvait passer
     * `scope_cabinet` dans ses propres parametres, le perimetre
     * parfaitement cloisonne deviendrait negociable depuis le formulaire.
     */
    public function testParametresDeLaRequeteSontPreservesEtPerimetreNonSurchargeable(): void
    {
        $requete = build_scoped_sql(
            'SELECT * FROM societes s WHERE s.id = :id AND 1=1 {{SCOPE}}',
            ['sql' => 's.cabinet_id = :scope_cabinet', 'params' => ['scope_cabinet' => 7]],
            ['id' => 42, 'scope_cabinet' => 99]
        );

        $this->assertStringContainsString(':id', $requete['sql']);
        $this->assertStringContainsString(':scope_cabinet_s0', $requete['sql']);
        $this->assertDoesNotMatchRegularExpression(
            '/:scope_cabinet(?!_s\d)/',
            $requete['sql'],
            'le nom d origine doit avoir ete renomme, sinon PDO refuse de le repetitionner'
        );
        $this->assertSame(42, $requete['params']['id'], 'un parametre propre a la requete doit survivre');
        $this->assertSame(7, $requete['params']['scope_cabinet_s0'], 'la valeur du perimetre vient de list_scope()');
    }

    /** Une requete sans marqueur est rendue telle quelle, parametres compris. */
    public function testRequeteSansMarqueurResteIntacte(): void
    {
        $requete = build_scoped_sql('SELECT 1', ['sql' => '1 = 0', 'params' => []], ['a' => 1]);

        $this->assertSame('SELECT 1', $requete['sql']);
        $this->assertSame(['a' => 1], $requete['params']);
    }

    private function connexionNative(): ?PDO
    {
        try {
            return new PDO(
                'mysql:host=127.0.0.1;dbname=center_domiciliation;charset=utf8mb4',
                'root',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
            );
        } catch (PDOException) {
            return null;
        }
    }
}
