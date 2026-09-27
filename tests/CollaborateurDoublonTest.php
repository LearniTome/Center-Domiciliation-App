<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Non-regression sur les doublons de collaborateurs.
 *
 * Trois entrees ecrivent collaborateurs.nom_complet sans aucun controle de
 * unicite : la quick-create (api.php), le formulaire de la fiche
 * (collaborateur_details.php) et l'import Excel (api.php). Trois imports de
 * coup ont produit trois fiches "ZZTEST Nominal" (ids 57 a 59).
 *
 * collaborateur_code ne peut pas servir de garde-fou : il est genere, donc
 * toujours different. Le nom complet est le seul identifiant saisi partout.
 *
 * L'insensibilite a la casse et aux accents n'est pas codee en PHP : elle
 * vient de la collation utf8mb4_unicode_ci de la colonne. Le test
 * testLaComparaisonSAppuieSurLaCollation le verifie en reproduisant une
 * colonne insensible a la casse.
 */
final class CollaborateurDoublonTest extends TestCase
{
    private function pdo(string $collation = ''): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE collaborateurs (
            id INTEGER PRIMARY KEY,
            nom_complet TEXT ' . $collation . ' NOT NULL
        )');
        $pdo->exec("INSERT INTO collaborateurs (id, nom_complet) VALUES
            (1, 'Amrani Salma'),
            (2, 'Tazi Karim')");

        return $pdo;
    }

    public function testUnNomDejaPresentEstDetecte(): void
    {
        $this->assertTrue(collaborateur_nom_existe($this->pdo(), 'Amrani Salma'));
    }

    public function testUnNomInconnuNEstPasDetecte(): void
    {
        $this->assertFalse(collaborateur_nom_existe($this->pdo(), 'Berrada Youssef'));
    }

    public function testUnEspaceEnDebordNEstPasUnNomValide(): void
    {
        $pdo = $this->pdo();

        // Un nom vide ne peut pas "concurrencer" la ligne vide eventuelle :
        // sans cette garde, deux collaborateurs sans nom seraient refuses.
        $this->assertFalse(collaborateur_nom_existe($pdo, ''));
        $this->assertFalse(collaborateur_nom_existe($pdo, '   '));
    }

    public function testUneEditionPeutConserverSonPropreNom(): void
    {
        $pdo = $this->pdo();

        // La fiche 1 porte le nom : en l'editing, elle doit pouvoir le garder.
        $this->assertFalse(collaborateur_nom_existe($pdo, 'Amrani Salma', 1));
        // La fiche 2 ne le porte pas : renommer vers ce nom la rendrait
        // doublon de la fiche 1, la saisie doit etre refusee.
        $this->assertTrue(collaborateur_nom_existe($pdo, 'Amrani Salma', 2));
        $this->assertFalse(collaborateur_nom_existe($pdo, 'Tazi Karim', 2));
    }

    public function testLaComparaisonSAppuieSurLaCollation(): void
    {
        // utf8mb4_unicode_ci en MySQL ; NOCASE est l'equivalent le plus proche
        // en SQLite. Aucune normalisation n'est appliquee en PHP : c'est la
        // colonne qui tranche, donc le helper suit la collation du schema.
        $pdo = $this->pdo('COLLATE NOCASE');

        $this->assertTrue(collaborateur_nom_existe($pdo, 'amrani salma'));
        $this->assertTrue(collaborateur_nom_existe($pdo, 'AMRANI SALMA'));
    }

    public function testSansConnexionLeControleLaisseraitPasser(): void
    {
        // Connexion indisponible : on ne bloque pas la saisie pour une base
        // injoignable, l'index unique reste le filet de securite.
        $this->assertFalse(collaborateur_nom_existe(null, 'Amrani Salma'));
    }
}
