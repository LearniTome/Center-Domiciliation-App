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
}
