<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 4/5 - Regles metier de la facturation SaaS.
 *
 * Trois familles :
 *
 * 1. Les statuts DERIVES. `expire` (abonnement) et `en_retard` (facture)
 *    n'existent pas en base : ils sont calcules a l'affichage. C'est
 *    volontaire, l'historique ne doit pas se reecrire tout seul quand le
 *    temps passe, mais cela impose que l'ecran et les exports passent par
 *    les memes fonctions.
 *
 * 2. Le choix de l'abonnement de reference. Un cabinet peut avoir un
 *    historique ; c'est celui dont la date de fin est la plus eloignee parmi
 *    essai/actif/suspendu qui fait foi pour le bandeau.
 *
 * 3. Regression : `current_abonnement_state(?PDO)` IGNORAIT son parametre.
 *    Le nom du parametre etant `pdo`, l'instruction `global $pdo` qui suit
 *    ecrasait la valeur recue par l'appelant et la fonction retombait sur la
 *    connexion globale. Sans argument (le seul cas reellement utilise par le
 *    bandeau) le defaut etait invisible ; des qu'un appelant passait une
 *    connexion - un test, un script de rapprochement, un futur job CLI - il
 *    lisait la mauvaise base, en silence.
 */
final class AbonnementTest extends TestCase
{
    private const CAB_A = 90021;
    private const CAB_B = 90022;
    private const USER_A = 90021;

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

        $pdo = self::$pdo;
        self::purge();

        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES
            (" . self::CAB_A . ", 'Cabinet A test', 'ABTA', 'actif'),
            (" . self::CAB_B . ", 'Cabinet B test', 'ABTB', 'actif')");

        $pdo->exec("INSERT INTO users (id, nom_complet, email, password_hash, cabinet_id, role_id, statut)
            VALUES (" . self::USER_A . ", 'Adherent A test', 'abo-tst-a@example.test', '!', "
            . self::CAB_A . ", NULL, 'actif')");

        $GLOBALS['pdo'] = $pdo;
    }

    public static function tearDownAfterClass(): void
    {
        self::purge();
        unset($GLOBALS['pdo']);
        $_SESSION = [];
    }

    private static function purge(): void
    {
        if (!self::$pdo instanceof PDO) {
            return;
        }
        $pdo = self::$pdo;
        $ids = self::CAB_A . ',' . self::CAB_B;
        $pdo->exec("DELETE FROM paiements WHERE cabinet_id IN ($ids)");
        $pdo->exec("DELETE FROM factures WHERE cabinet_id IN ($ids)");
        $pdo->exec("DELETE FROM abonnements WHERE cabinet_id IN ($ids)");
        $pdo->exec("DELETE FROM user_roles WHERE user_id = " . self::USER_A);
        $pdo->exec("DELETE FROM user_permissions WHERE user_id = " . self::USER_A);
        $pdo->exec("DELETE FROM users WHERE id = " . self::USER_A);
        $pdo->exec("DELETE FROM cabinets WHERE id IN ($ids)");
    }

    protected function setUp(): void
    {
        if (!self::$pdo instanceof PDO) {
            self::markTestSkipped('Base indisponible.');
        }
        $pdo = self::$pdo;
        $ids = self::CAB_A . ',' . self::CAB_B;
        $pdo->exec("DELETE FROM paiements WHERE cabinet_id IN ($ids)");
        $pdo->exec("DELETE FROM factures WHERE cabinet_id IN ($ids)");
        $pdo->exec("DELETE FROM abonnements WHERE cabinet_id IN ($ids)");

        $_SESSION = ['user_id' => self::USER_A];
        unset($_SESSION['_user_cache'], $_SESSION['_permissions_cache']);
    }

    private function abo(int $cabinetId, string $statut, string $dateFin, string $debut = '2026-01-01'): void
    {
        self::$pdo->exec("INSERT INTO abonnements
            (cabinet_id, date_debut, date_fin, statut, prix_annuel_negocie, devise, auto_renew)
            VALUES ($cabinetId, '$debut', '$dateFin', '$statut', 1000, 'MAD', 0)");
    }

    /* ================================================================
     * 1. Statuts derives
     * ================================================================ */

    public function testAbonnementExpireEstDeriveEtJamaisStocke(): void
    {
        $row = ['statut' => 'actif', 'date_fin' => '2020-01-01'];

        self::assertSame('expire', abonnement_display_statut($row));
        // La base reste intacte : c'est l'affichage qui expire.
        self::assertSame('actif', $row['statut']);
    }

    public function testEssaiEcheanceEstEgalementExpire(): void
    {
        self::assertSame('expire', abonnement_display_statut(['statut' => 'essai', 'date_fin' => '2020-01-01']));
    }

    public function testAbonnementNonEchuGardeSonStatut(): void
    {
        $futur = date('Y-m-d', strtotime('+30 days'));
        self::assertSame('actif', abonnement_display_statut(['statut' => 'actif', 'date_fin' => $futur]));
        self::assertSame('essai', abonnement_display_statut(['statut' => 'essai', 'date_fin' => $futur]));
    }

    public function testResilieEtSuspenduNeDeriventPas(): void
    {
        $passe = '2020-01-01';
        // Un abonnement resilie n'est pas "expire" : c'est une sortie, pas une
        // echeance. Le libelle ne doit pas mentir sur la sortie du client.
        self::assertSame('resilie', abonnement_display_statut(['statut' => 'resilie', 'date_fin' => $passe]));
        self::assertSame('suspendu', abonnement_display_statut(['statut' => 'suspendu', 'date_fin' => $passe]));
    }

    public function testLibelleExpireExiste(): void
    {
        self::assertSame('Expire', abonnement_statut_label('expire'));
    }

    public function testJoursRestantsNegatifQuandEcheancePassee(): void
    {
        self::assertSame(-3, abonnement_jours_restants(['date_fin' => date('Y-m-d', strtotime('-3 days'))]));
        self::assertSame(3, abonnement_jours_restants(['date_fin' => date('Y-m-d', strtotime('+3 days'))]));
        self::assertNull(abonnement_jours_restants(['date_fin' => null]));
    }

    public function testFactureEnRetardEstDerivee(): void
    {
        $passe = date('Y-m-d', strtotime('-5 days'));
        $futur = date('Y-m-d', strtotime('+5 days'));

        self::assertSame('en_retard', facture_display_statut([
            'statut' => 'emise', 'date_echeance' => $passe, 'montant_ttc' => '100.00',
        ]));
        self::assertSame('emise', facture_display_statut([
            'statut' => 'emise', 'date_echeance' => $futur, 'montant_ttc' => '100.00',
        ]));
    }

    public function testFacturePayeeOuAnnuleeNeDevientJamaisEnRetard(): void
    {
        $passe = date('Y-m-d', strtotime('-5 days'));
        self::assertSame('payee', facture_display_statut([
            'statut' => 'payee', 'date_echeance' => $passe, 'montant_ttc' => '100.00',
        ]));
        self::assertSame('annulee', facture_display_statut([
            'statut' => 'annulee', 'date_echeance' => $passe, 'montant_ttc' => '100.00',
        ]));
    }

    public function testFactureSansEcheanceNeTombeJamaisEnRetard(): void
    {
        // Pas d'echeance = pas d'etable de comparaison, la facture ne peut
        // pas etre en retard.
        self::assertSame('emise', facture_display_statut([
            'statut' => 'emise', 'date_echeance' => null, 'montant_ttc' => '100.00',
        ]));
    }

    /* ================================================================
     * 2. Abonnement de reference
     * ================================================================ */

    public function testAbonnementDeReferenceEstLePlusEloigne(): void
    {
        $this->abo(self::CAB_A, 'actif', date('Y-m-d', strtotime('+20 days')));
        $this->abo(self::CAB_A, 'essai', date('Y-m-d', strtotime('+5 days')));

        $state = current_abonnement_state(self::$pdo);
        self::assertSame('actif', $state['statut']);
        self::assertSame(20, $state['jours_restants']);
    }

    public function testResilieNEntrePasDansLeChoixDeReference(): void
    {
        $this->abo(self::CAB_A, 'resilie', date('Y-m-d', strtotime('+900 days')));
        $this->abo(self::CAB_A, 'actif', date('Y-m-d', strtotime('+10 days')));

        $state = current_abonnement_state(self::$pdo);
        self::assertSame('actif', $state['statut']);
    }

    public function testCabinetSansAbonnementEstAbsent(): void
    {
        $state = current_abonnement_state(self::$pdo);
        self::assertSame('absent', $state['statut']);
    }

    public function testLeCabinetDeLAdherentEstSeulConsidere(): void
    {
        // Le cabinet B a un abonnement confortable, le cabinet A rien du tout.
        // L'adherent A ne doit surtout pas heriter de l'etat de B.
        $this->abo(self::CAB_B, 'actif', date('Y-m-d', strtotime('+300 days')));

        self::assertSame('absent', current_abonnement_state(self::$pdo)['statut']);
    }

    public function testCompteCentreEstNeutre(): void
    {
        $_SESSION = ['user_id' => 99999999];
        unset($_SESSION['_user_cache']);
        $this->abo(self::CAB_B, 'actif', date('Y-m-d', strtotime('+300 days')));

        self::assertSame('centre', current_abonnement_state(self::$pdo)['statut']);
    }

    /* ================================================================
     * 3. Regression : le PDO passe en argument est respecte
     * ================================================================ */

    public function testLePdoPasseEnArgumentPrimeSurLaConnexionGlobale(): void
    {
        // On construit un schema miroir qui contient les MEMES tables mais un
        // abonnement marque. Si la fonction lisait la connexion globale au lieu
        // du parametre, elle renverrait le statut de l'abonnement du projet et
        // non celui du miroir.
        $miroir = 'test_abo_miroir';
        $avant = $GLOBALS['pdo'];
        $pdo = self::$pdo;

        try {
            $pdo->exec("DROP DATABASE IF EXISTS $miroir");
            $pdo->exec("CREATE DATABASE $miroir");
            $ombre = new PDO(
                "mysql:host=127.0.0.1;dbname=$miroir;charset=utf8mb4",
                'root',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $ombre->exec('CREATE TABLE plans (id INT PRIMARY KEY, nom VARCHAR(120) NULL, code VARCHAR(40) NULL)');
            $ombre->exec('CREATE TABLE abonnements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cabinet_id INT NOT NULL,
                plan_id INT NULL,
                date_fin DATE NOT NULL,
                statut VARCHAR(20) NOT NULL
            )');
            $ombre->exec("INSERT INTO abonnements (cabinet_id, plan_id, date_fin, statut)
                          VALUES (" . self::CAB_A . ", NULL, '" . date('Y-m-d', strtotime('+7 days')) . "', 'suspendu')");
        } catch (PDOException $e) {
            self::markTestSkipped('Impossible de creer le schema miroir : ' . $e->getMessage());
        }

        // Le compte de session appartient au cabinet A et l'identite est lue
        // sur la connexion globale (current_user() n'accepte pas de PDO).
        $GLOBALS['pdo'] = $pdo;
        $this->abo(self::CAB_A, 'actif', date('Y-m-d', strtotime('+300 days')));

        $state = current_abonnement_state($ombre);

        // Le miroir ne contient QUE le 'suspendu' du cabinet A : s'il est lu,
        // c'est que le parametre a bien ete utilise.
        self::assertSame('suspendu', $state['statut'], 'le PDO passe en argument a ete ignore');
        self::assertSame(7, $state['jours_restants']);

        $GLOBALS['pdo'] = $avant;
        $pdo->exec("DROP DATABASE IF EXISTS $miroir");
    }

    public function testConnexionGlobaleUtiliseeSiAucunPdoNestPasse(): void
    {
        $this->abo(self::CAB_A, 'actif', date('Y-m-d', strtotime('+60 days')));

        $state = current_abonnement_state();
        self::assertSame('actif', $state['statut']);
        self::assertSame(60, $state['jours_restants']);
    }

    /* ================================================================
     * 4. Bandeau : severites et non-bloquance
     * ================================================================ */

    public function testBandeauSilencieuxPourUnAbonnementSain(): void
    {
        $this->abo(self::CAB_A, 'actif', date('Y-m-d', strtotime('+120 days')));
        self::assertNull(abonnement_bandeau());
    }

    public function testBandeauAvertitAvantExpiration(): void
    {
        $this->abo(self::CAB_A, 'actif', date('Y-m-d', strtotime('+10 days')));
        $bandeau = abonnement_bandeau();
        self::assertNotNull($bandeau);
        self::assertSame('warning', $bandeau['tone']);
    }

    public function testBandeauAvertitEnFinDEssai(): void
    {
        $this->abo(self::CAB_A, 'essai', date('Y-m-d', strtotime('+5 days')));
        $bandeau = abonnement_bandeau();
        self::assertNotNull($bandeau);
        self::assertSame('warning', $bandeau['tone']);
        self::assertStringContainsString('essai', $bandeau['message']);
    }

    public function testBandeauErreurSiExpireSuspenduOuAbsent(): void
    {
        foreach ([['actif', '-2 days'], ['suspendu', '+30 days']] as [$statut, $ecart]) {
            $this->setUp();
            $this->abo(self::CAB_A, $statut, date('Y-m-d', strtotime($ecart)));
            $bandeau = abonnement_bandeau();
            self::assertNotNull($bandeau, $statut . ' ' . $ecart);
            self::assertSame('error', $bandeau['tone'], $statut . ' ' . $ecart);
        }

        // Aucun abonnement du tout.
        $this->setUp();
        $bandeau = abonnement_bandeau();
        self::assertNotNull($bandeau);
        self::assertSame('error', $bandeau['tone']);
    }

    public function testBandeauJamaisPourUnCompteCentre(): void
    {
        $_SESSION = ['user_id' => 99999999];
        unset($_SESSION['_user_cache']);
        self::assertNull(abonnement_bandeau());
    }

    /* ================================================================
     * 5. Numerotation et options
     * ================================================================ */

    public function testNumeroDeFactureSuitLeFormatAttendu(): void
    {
        $numero = next_facture_number(self::$pdo);
        self::assertMatchesRegularExpression('/^FAC-' . date('Y') . '-\d{3}$/', $numero);
    }

    public function testNumeroDeFactureRepartApresCollision(): void
    {
        // Une facture portant le numero attendu doit faire avancer la
        // sequence, sinon la contrainte UNIQUE uq_factures_numero rejette
        // l'insertion en production.
        $premier = next_facture_number(self::$pdo);
        $suffixe = substr($premier, -3);

        $pdo = self::$pdo;
        $pdo->exec("INSERT INTO factures (numero, cabinet_id, date_emission, montant_ht, tva_pct, montant_ttc, statut)
                    VALUES ('$premier', " . self::CAB_A . ", CURDATE(), 100, 20, 120, 'brouillon')");

        try {
            $suivant = next_facture_number(self::$pdo);
            self::assertNotSame($premier, $suivant);
            self::assertSame(sprintf('%03d', ((int) $suffixe + 1) % 1000), substr($suivant, -3));
        } finally {
            $pdo->exec("DELETE FROM factures WHERE numero = '$premier'");
        }
    }

    /* ================================================================
     * 9. Numerotation des codes cabinet (CAB-NNN)
     * ================================================================ */

    /**
     * Execute un scenario sur la table `cabinets` puis la restaure a l'identique.
     *
     * Le nettoyage compare la table avant et apres et ne supprime que les
     * lignes apparues entre les deux. Compter sur une liste de codes declarée
     * a la main ne suffit pas : une assertion qui verifie l'unicite du code
     * propose insere elle-meme une ligne, et un prefixe oublie dans cette liste
     * la laisserait behind, faussant la sequence des tests suivants. Aucun
     * DELETE par motif `CAB-%` / `TST-%` n'est employe, il effacerait des
     * cabinets reels sur une base de developpement.
     *
     * Le scenario recoit un second callable pour inserer ses cabinets. Ses
     * codes restent hors du prefixe `CAB-` (les fixtures du lot utilisent
     * ABTA / ABTB) afin de piloter une sequence isolee.
     */
    private function avecCabinets(callable $scenario): void
    {
        $pdo = self::$pdo;
        $lire = static fn (PDO $d): array => $d->query('SELECT code FROM cabinets')->fetchAll(PDO::FETCH_COLUMN);

        $avant = $lire($pdo);
        $ins = $pdo->prepare("INSERT INTO cabinets (code, nom, statut) VALUES (:c, 'Test', 'actif')");
        $creer = static function (string ...$codes) use ($ins): void {
            foreach ($codes as $code) {
                $ins->execute(['c' => $code]);
            }
        };

        try {
            $scenario($pdo, $creer);
        } finally {
            $nouveaux = array_values(array_diff($lire($pdo), $avant));
            if ($nouveaux !== []) {
                $in = "'" . implode("','", array_map(
                    static fn (string $c): string => str_replace("'", "''", $c),
                    $nouveaux
                )) . "'";
                $pdo->exec('DELETE FROM cabinets WHERE code IN (' . $in . ')');
            }
        }
    }

    public function testCodeCabinetSuitLeFormatAttendu(): void
    {
        $this->avecCabinets(function (PDO $pdo): void {
            // Les fixtures ABTA / ABTB ne portent pas le prefixe, la sequence
            // demarre donc a 001 sur une base sans cabinet CAB-.
            self::assertSame('CAB-001', next_cabinet_code($pdo));
            self::assertMatchesRegularExpression('/^CAB-\d{3,}$/', next_cabinet_code($pdo));
        });
    }

    public function testCodeCabinetProposeToujoursUneValeurInsérable(): void
    {
        // uq_cabinets_code est UNIQUE : si la valeur proposee etait deja
        // employee, la creation du cabinet echouerait a l'enregistrement.
        $this->avecCabinets(function (PDO $pdo, callable $creer): void {
            $creer('TST-001', 'TST-007');
            $code = next_cabinet_code($pdo, 'TST');
            self::assertSame('TST-008', $code, 'un trou dans la sequence ne doit pas etre propose');

            $pdo->prepare("INSERT INTO cabinets (code, nom, statut) VALUES (?, 'Collision', 'actif')")
                ->execute([$code]);
            self::assertTrue(true, 'la valeur proposee a ete acceptee par uq_cabinets_code');
        });
    }

    public function testCodeCabinetNeReproposePasUnCodeDejaEmploiParUnCodeManuel(): void
    {
        // `uq_cabinets_code` n'interdit que les doublons exacts : un code saisi a
        // la main peut donc decliner la suite. Ici TST-004 (id 1) precede
        // TST-003 (id 2), l'ordre des id ne suit plus l'ordre des numeros.
        // Une lecture « dernier insere » proposerait TST-004, deja employe, et
        // la creation du cabinet echouerait ; le plus grand suffixe propose
        // TST-005, inserable.
        $this->avecCabinets(function (PDO $pdo, callable $creer): void {
            $creer('TST-004', 'TST-003');
            $code = next_cabinet_code($pdo, 'TST');
            self::assertSame('TST-005', $code);

            self::assertFalse(
                $pdo->query('SELECT 1 FROM cabinets WHERE code = ' . $pdo->quote($code))->fetchColumn() !== false,
                'le code propose ne doit pas deja exister'
            );
            $pdo->prepare("INSERT INTO cabinets (code, nom, statut) VALUES (?, 'Manuel', 'actif')")
                ->execute([$code]);
            self::assertTrue(true, 'le code propose passe uq_cabinets_code');
        });
    }

    public function testCodeCabinetIgnoreLesCodesLibresHorsPrefixe(): void
    {
        // `TST-SUD` ou `TST-1A` ne sont pas des membres de la sequence : ils
        // ne doivent ni la faire boucler ni etre proposes.
        $this->avecCabinets(function (PDO $pdo, callable $creer): void {
            $creer('TST-001', 'TST-SUD', 'TST-1A');
            self::assertSame('TST-002', next_cabinet_code($pdo, 'TST'));
        });
    }

    public function testCodeCabinetVoitLesMinusculesCommeLaCollation(): void
    {
        // La colonne est en utf8mb4_unicode_ci : `uq_cabinets_code` refuse
        // `cab-001` face a `CAB-001`. La numerotation doit voir la meme chose,
        // sinon elle proposerait un code que MySQL rejette.
        $this->avecCabinets(function (PDO $pdo, callable $creer): void {
            $creer('cab-001', 'cab-002');
            self::assertSame('CAB-003', next_cabinet_code($pdo, 'CAB'));
        });
    }

    public function testCodeCabinetElargitLeSuffixeAuDelaDe999(): void
    {
        // Plutot que de reboucler sur CAB-000, la largeur du suffixe s'elargit.
        $this->avecCabinets(function (PDO $pdo, callable $creer): void {
            $creer('TST-001', 'TST-999');
            self::assertSame('TST-1000', next_cabinet_code($pdo, 'TST'));
        });
    }

    public function testCodeCabinetConserveLaLargeurDuSuffixeExistant(): void
    {
        $this->avecCabinets(function (PDO $pdo, callable $creer): void {
            $creer('TST-12');
            self::assertSame('TST-013', next_cabinet_code($pdo, 'TST'));
        });
    }

    public function testCodeCabinetSansBaseRenvoieUneValeurDeRepli(): void
    {
        // Base injoignable : on ne doit pas bloquer la saisie du formulaire.
        self::assertSame('CAB-001', next_cabinet_code(null));
    }

    public function testFetchPlansOptionsFiltreLesPlansInactifsParDefaut(): void
    {
        $pdo = self::$pdo;
        $pdo->exec("DELETE FROM plans WHERE code = 'TSTOPT'");
        $pdo->exec("INSERT INTO plans (code, nom, prix_annuel, devise, actif, trial_jours, sort_order)
                    VALUES ('TSTOPT', 'Plan inactif test', 100, 'MAD', 0, 0, 999)");
        $planId = (int) $pdo->lastInsertId();

        try {
            // Les options sont indexees par id de plan, pas par code.
            self::assertArrayNotHasKey($planId, fetch_plans_options($pdo), 'un plan inactif ne doit pas etre proposé a la creation');
            self::assertArrayHasKey($planId, fetch_plans_options($pdo, false), 'l\'edition doit pouvoir relire un plan devenu inactif');
        } finally {
            $pdo->exec("DELETE FROM plans WHERE id = " . $planId);
        }
    }

    public function testTypesDeCabinetCouvrentLesCinqNaturesExigees(): void
    {
        $expected = [
            'comptable_agree' => 'Cabinet comptable (Comptable agree)',
            'expertise_comptable' => "Cabinet d'expertise comptable",
            'comptable_independant' => 'Comptable independant',
            'juridique_avocat' => 'Cabinet juridique - Avocat',
            'juridique_notaire' => 'Cabinet juridique - Notaire',
        ];

        self::assertSame(array_keys($expected), cabinet_type_options());

        foreach ($expected as $slug => $label) {
            self::assertSame($label, cabinet_type_label($slug));
        }
    }

    public function testTypeDeCabinetInconnuRetombeSansInvokerUneErreur(): void
    {
        // Valeur absente : les cabinets anterieurs a la migration n'ont pas de
        // type. L'ecran doit rester renderisable, pas lever un match() Error.
        self::assertSame('Non renseigne', cabinet_type_label(null));
        self::assertSame('Non renseigne', cabinet_type_label(''));
        self::assertSame('badge-secondary', cabinet_type_tone(null), 'un cabinet sans type ne doit pas porter la couleur d un type metier');
        self::assertSame('Legal', cabinet_type_label('legal'), 'un slug inconnu est repris brut');
    }

    public function testChaqueTypeDeCabinetADistinctementSaTeinte(): void
    {
        // La couleur porte l'information de facon horizontale : deux types
        // partageant une teinte les rendrait indistinguables au balayage.
        $tones = array_map('cabinet_type_tone', cabinet_type_options());

        self::assertSame(
            count($tones),
            count(array_unique($tones)),
            'les cinq types doivent avoir cinq teintes distinctes'
        );
    }

    public function testColonnesCabinetAjouteesParLaMigrationSontPresentes(): void
    {
        // Parite avec `collaborateurs` : sans IF ni TP, un cabinet ne pouvait
        // pas etre rapproche de son interlocuteur sur les memes identifiants.
        $attendus = [
            'type_cabinet', 'telephone_fixe', 'telephone_mobile',
            'qualification', 'fonction', 'identifiant_fiscal', 'taxe_professionnelle',
        ];

        $presentes = self::$pdo->query('SHOW COLUMNS FROM cabinets')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($attendus as $colonne) {
            self::assertContains($colonne, $presentes, 'colonne manquante : ' . $colonne);
        }

        // Aucun champ historique ne doit avoir disparu.
        foreach (['code', 'nom', 'raison_sociale', 'email', 'telephone', 'adresse', 'ville', 'ice', 'rc', 'statut', 'notes'] as $colonne) {
            self::assertContains($colonne, $presentes, 'colonne historique perdue : ' . $colonne);
        }
    }

    public function testTypeDeCabinetResteNullablePourLesDossiersExistants(): void
    {
        // La migration n'invente pas de type pour les cabinets deja en base :
        // la colonne est donc NULLable, meme si le formulaire l'exige.
        $null = false;
        foreach (self::$pdo->query('SHOW COLUMNS FROM cabinets')->fetchAll(PDO::FETCH_ASSOC) as $col) {
            if ($col['Field'] === 'type_cabinet') {
                $null = $col['Null'] === 'YES';
            }
        }

        self::assertTrue($null, 'type_cabinet doit rester NULLable pour les cabinets anterieurs');
    }
}
