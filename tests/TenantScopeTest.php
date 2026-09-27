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
}
