<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Quotas de plan (`plans.max_utilisateurs` / `max_societes` / `max_dossiers`).
 *
 * Ces colonnes sont affichees dans `mon_abonnement` mais n'etaient lues par
 * aucune decision : un cabinet adherent pouvait creer des dossiers sans fin en
 * restant sous la formule qu'il paye. Le test verrouille deux invariants qui
 * expliquent ces colonnes :
 *
 * 1. une formule "illimitee" (colonne NULL) ne bloque rien ;
 * 2. le comptage est cloisonne par `cabinet_id` - un quota qui additionne les
 *    dossiers d'un autre cabinet ne bloque jamais, donc n'a aucun effet.
 *
 * Le second point est le piege : le defaut fail-open et le comportement correct
 * se ressemblent quand tous les cabinets sont sous le plafond. On verifie donc
 * qu'un cabinet **au plafond** est bloque, et que le compteur d'un cabinet ne
 * bouge pas de la consommation de l'autre.
 */
final class QuotaTest extends TestCase
{
    private const CAB_A = 90041;
    private const CAB_B = 90042;
    private const PLAN_1 = 90041;
    private const PLAN_ILLIMITE = 90042;

    private static ?PDO $pdo = null;

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

        self::purge();
        $pdo = self::$pdo;

        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES
            (" . self::CAB_A . ", 'Cabinet quotas A', 'QTA', 'actif'),
            (" . self::CAB_B . ", 'Cabinet quotas B', 'QTB', 'actif')");

        // Plan a 2 dossiers, et formule sans plafond.
        $pdo->exec("INSERT INTO plans (id, code, nom, prix_annuel, max_utilisateurs, max_societes, max_dossiers, actif)
            VALUES (" . self::PLAN_1 . ", 'QT1', 'Quota test', 0.00, 1, 2, 2, 1),
                   (" . self::PLAN_ILLIMITE . ", 'QTI', 'Illimite test', 0.00, NULL, NULL, NULL, 1)");

        $pdo->exec("INSERT INTO abonnements (cabinet_id, plan_id, statut, date_debut, date_fin)
            VALUES (" . self::CAB_A . ", " . self::PLAN_1 . ", 'actif', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
                   (" . self::CAB_B . ", " . self::PLAN_ILLIMITE . ", 'actif', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))");

        // Cabinet A : 2 dossiers, exactement son plafond. Cabinet B : 1.
        $pdo->exec("INSERT INTO societes (societe_raison_sociale, societe_ice, cabinet_id) VALUES
            ('QUOTA A1', 'QTA-1', " . self::CAB_A . "),
            ('QUOTA A2', 'QTA-2', " . self::CAB_A . "),
            ('QUOTA B1', 'QTB-1', " . self::CAB_B . ")");

        $pdo->exec("INSERT INTO users (nom_complet, email, password_hash, cabinet_id, statut) VALUES
            ('Quota user A', 'quota-a@example.test', '!', " . self::CAB_A . ", 'actif')");
    }

    public static function tearDownAfterClass(): void
    {
        self::purge();
    }

    private static function purge(): void
    {
        if (!self::$pdo) {
            return;
        }
        $pdo = self::$pdo;
        $pdo->exec("DELETE FROM societes WHERE societe_ice LIKE 'QT%-%'");
        $pdo->exec("DELETE FROM users WHERE email = 'quota-a@example.test'");
        $pdo->exec("DELETE FROM abonnements WHERE cabinet_id IN (" . self::CAB_A . ", " . self::CAB_B . ")");
        $pdo->exec("DELETE FROM plans WHERE id IN (" . self::PLAN_1 . ", " . self::PLAN_ILLIMITE . ")");
        $pdo->exec("DELETE FROM cabinets WHERE id IN (" . self::CAB_A . ", " . self::CAB_B . ")");
    }

    public function testLeCompteurNeVoitQueLesDossiersDuCabinet(): void
    {
        $state = quota_state('dossiers', self::$pdo, self::CAB_A);

        $this->assertSame(2, $state['utilise'], 'Le cabinet A possede 2 dossiers, pas 3.');
        $this->assertSame(2, $state['limite']);
        $this->assertTrue($state['atteint']);
    }

    public function testLePlafondEstAtteintAuDernierDossier(): void
    {
        // 2 dossiers pour un plafond de 2 : le troisieme est refuse. C'est le
        // cas que le defaut fail-open laisse passer.
        $this->assertTrue(quota_state('dossiers', self::$pdo, self::CAB_A)['atteint']);
    }

    public function testUneFormuleSansPlafondNeBloqueJamais(): void
    {
        $state = quota_state('dossiers', self::$pdo, self::CAB_B);

        $this->assertNull($state['limite'], 'NULL = illimite.');
        $this->assertFalse($state['atteint']);
        $this->assertSame(1, $state['utilise']);
    }

    public function testLeQuotaUtilisateursCompteLesComptesDuCabinet(): void
    {
        $state = quota_state('utilisateurs', self::$pdo, self::CAB_A);

        $this->assertSame(1, $state['utilise']);
        $this->assertSame(1, $state['limite']);
        $this->assertTrue($state['atteint'], 'Plafond de 1 utilisateur pour 1 compte.');
    }

    public function testLePlafondLePlusStrictDesColonnesPrime(): void
    {
        // max_societes et max_dossiers visent le meme compteur (une ligne
        // `societes` = un dossier). Si un plan les fixe differemment, c'est le
        // plus bas qui s'applique : sinon `max_dossiers` pourrait relever un
        // `max_societes` plus strict.
        $pdo = self::$pdo;
        $planId = 90043;
        $pdo->exec("INSERT INTO plans (id, code, nom, prix_annuel, max_utilisateurs, max_societes, max_dossiers, actif)
            VALUES ($planId, 'QT2', 'Quotas divergents', 0.00, NULL, 1, 99, 1)");
        $pdo->exec("INSERT INTO abonnements (cabinet_id, plan_id, statut, date_debut, date_fin)
            VALUES (" . self::CAB_B . ", $planId, 'actif', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 YEAR))");

        try {
            $state = quota_state('dossiers', self::$pdo, self::CAB_B);
            $this->assertSame(1, $state['limite'], 'min(max_societes=1, max_dossiers=99)');
            $this->assertTrue($state['atteint']);
        } finally {
            $pdo->exec("DELETE FROM abonnements WHERE plan_id = $planId");
            $pdo->exec("DELETE FROM plans WHERE id = $planId");
        }
    }

    public function testUnQuotaInconnuNeBloquePas(): void
    {
        $state = quota_state('inexistant', self::$pdo, self::CAB_A);

        $this->assertFalse($state['atteint']);
        $this->assertSame(0, $state['utilise']);
    }

    public function testUneBaseInjoignableNeBloquePersonne(): void
    {
        // Un nettoyage d'affichage ne doit pas priver l'utilisateur de son
        // application : base tombee => comptage impossible => aucun blocage.
        $state = quota_state('dossiers', null, self::CAB_A);
        $this->assertFalse($state['atteint']);
    }

    public function testLesCompteursVisentLesBonnesTables(): void
    {
        $counters = quota_counters();

        $this->assertSame('users', $counters['utilisateurs']['table']);
        $this->assertSame('societes', $counters['dossiers']['table']);
        // Les deux colonnes de dossiers visent le meme compteur.
        $this->assertSame(['max_societes', 'max_dossiers'], $counters['dossiers']['columns']);
    }
}
