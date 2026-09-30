<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ouverture d'un acces cabinet apres souscription d'un abonnement.
 *
 * La feature tient en deux gestes qui doivent rester solidaires :
 *
 *  1. "Creer un acces", disponible sur un abonnement vivant, ouvre le compte
 *     administrateur du cabinet. Le cabinet n'est jamais choisi dans le
 *     formulaire : il DECOULE de l'abonnement. Un `abonnement_id` forge ne
 *     doit donc jamais pouvoir ouvrir un compte chez un tenant qui n'a pas
 *     souscrit, et un role 'centre' ne doit jamais pouvoir etre attribue.
 *
 *  2. La connexion refuse d'ouvrir une session a un compte dont l'abonnement
 *     n'autorise plus l'acces. La regle est unique
 *     (abonnement_autorise_acces) : si l'ecran et la porte divergent, un
 *     adherent peut soit creer un compte mort-nais, soit etre bloque par un
 *     etat que l'ecran disait valide.
 *
 * Les tests se placent sur la connexion d'abord : c'est elle qui materialise
 * la regle, et une divergence ici a des consequences reelles (verrouillage
 * d'un adherent, acces ouvert dans un cabinet non souscripteur).
 */
final class AccesCabinetTest extends TestCase
{
    /** Cabinet temoin du refus : aucun abonnement, donc aucun acces. */
    private const CAB_SANS_ABO = 90101;

    private static ?PDO $pdo = null;
    private static ?int $roleAdminCabinet = null;
    private static ?int $roleSuperAdmin = null;

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

        // Les roles sont lus, pas crees : ils sont installes par la migration
        // 20260927_100003. Un role de chaque scope suffit, et la suite ne doit
        // pas dependre du contenu exact du catalogue livre.
        $stmt = $pdo->query("SELECT id FROM roles WHERE scope = 'cabinet' AND nom = 'Administrateur Cabinet'");
        self::$roleAdminCabinet = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT id FROM roles WHERE scope = 'centre' LIMIT 1");
        self::$roleSuperAdmin = $stmt->fetchColumn();

        if (self::$roleAdminCabinet === false || self::$roleSuperAdmin === false) {
            self::markTestSkipped('Roles SaaS absents : la migration 20260927_100003 n\'a pas ete appliquee.');
        }

        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES ("
            . self::CAB_SANS_ABO . ", 'Cabinet sans abonnement test', 'ABSA', 'actif')");
    }

    public static function tearDownAfterClass(): void
    {
        self::purge();
        $_SESSION = [];
    }

    private static function purge(): void
    {
        if (!self::$pdo instanceof PDO) {
            return;
        }
        $pdo = self::$pdo;
        $pdo->exec('DELETE FROM abonnements WHERE cabinet_id = ' . self::CAB_SANS_ABO);
        $pdo->exec('DELETE FROM user_roles WHERE user_id IN (SELECT id FROM users WHERE cabinet_id = ' . self::CAB_SANS_ABO . ')');
        $pdo->exec('DELETE FROM users WHERE cabinet_id = ' . self::CAB_SANS_ABO);
        $pdo->exec('DELETE FROM cabinets WHERE id = ' . self::CAB_SANS_ABO);
    }

    protected function setUp(): void
    {
        if (!self::$pdo instanceof PDO) {
            self::markTestSkipped('Base indisponible.');
        }
        $pdo = self::$pdo;
        $pdo->exec('DELETE FROM abonnements WHERE cabinet_id = ' . self::CAB_SANS_ABO);
        $pdo->exec('DELETE FROM user_roles WHERE user_id IN (SELECT id FROM users WHERE cabinet_id = ' . self::CAB_SANS_ABO . ')');
        $pdo->exec('DELETE FROM users WHERE cabinet_id = ' . self::CAB_SANS_ABO);
        $pdo->exec('DELETE FROM cabinets WHERE id = ' . self::CAB_SANS_ABO);
        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES ("
            . self::CAB_SANS_ABO . ", 'Cabinet sans abonnement test', 'ABSA', 'actif')");
    }

    private function abo(string $statut, string $dateFin): int
    {
        $stmt = self::$pdo->prepare('
            INSERT INTO abonnements (cabinet_id, date_debut, date_fin, statut, prix_annuel_negocie, devise, auto_renew)
            VALUES (:cid, :debut, :fin, :statut, 1000, \'MAD\', 0)
        ');
        $stmt->execute([
            'cid' => self::CAB_SANS_ABO,
            'debut' => date('Y-m-d', strtotime('-1 month')),
            'fin' => $dateFin,
            'statut' => $statut,
        ]);

        return (int) self::$pdo->lastInsertId();
    }

    private function lireComptes(): array
    {
        $stmt = self::$pdo->prepare('SELECT * FROM users WHERE cabinet_id = :cid ORDER BY id');
        $stmt->execute(['cid' => self::CAB_SANS_ABO]);

        return $stmt->fetchAll();
    }

    /* ================================================================
     * 1. La regle unique d'acces
     * ================================================================ */

    public function testCompteInterneDuCentreJamaisBloque(): void
    {
        // Un employe du Centre n'a pas d'abonnement et n'en a pas besoin : le
        // bloquer casserait l'administration de la plateforme elle-meme.
        self::assertTrue(abonnement_autorise_acces([
            'statut' => 'centre', 'jours_restants' => null, 'libelle' => 'Compte interne',
        ]));
    }

    public function testEssaiEnCoursAutoriseLEAcces(): void
    {
        self::assertTrue(abonnement_autorise_acces([
            'statut' => 'essai', 'jours_restants' => 12, 'libelle' => 'Essai',
        ]));
    }

    public function testActifNonEchuAutoriseLEAcces(): void
    {
        self::assertTrue(abonnement_autorise_acces([
            'statut' => 'actif', 'jours_restants' => 1, 'libelle' => 'Actif',
        ]));
        self::assertTrue(abonnement_autorise_acces([
            'statut' => 'actif', 'jours_restants' => 0, 'libelle' => 'Actif',
        ]), 'le jour meme de l\'echeance, l\'acces reste ouvert');
    }

    public function testActifEchuBloqueLEAcces(): void
    {
        // 'expire' n'est jamais stocke : un abonnement actif echu se revele par
        // ses jours restants negatifs. Sans ce test, le blocage disparaitrait
        // en silence le jour de l'echeance.
        self::assertFalse(abonnement_autorise_acces([
            'statut' => 'actif', 'jours_restants' => -1, 'libelle' => 'Abonnement expire',
        ]));
    }

    public function testSuspenduAbsentEtResilieBloquentLEAcces(): void
    {
        foreach (['suspendu', 'absent', 'resilie'] as $statut) {
            self::assertFalse(
                abonnement_autorise_acces(['statut' => $statut, 'jours_restants' => 300, 'libelle' => $statut]),
                $statut . ' ne doit pas autoriser l\'acces, meme avec une echeance lointaine'
            );
        }
    }

    public function testChaqueRefusExposeUnMessageRedige(): void
    {
        // Un message vide devant un formulaire de connexion donne un mur
        // blanc : l'adherent ne sait ni pourquoi il est refuse, ni a qui
        // s'adresser.
        foreach ([
            ['absent', 'Aucun abonnement actif'],
            ['suspendu', 'Abonnement suspendu'],
            ['actif', 'Abonnement expire'],
        ] as [$statut, $libelle]) {
            $message = abonnement_refus_message([
                'statut' => $statut, 'jours_restants' => -1, 'libelle' => $libelle,
            ]);

            self::assertNotSame('', trim($message), $statut);
            self::assertStringContainsString('Centre', $message, $statut . ' : le message doit indiquer un interlocuteur');
        }
    }

    public function testEtatDuCabinetSeLitSansSessionConnectee(): void
    {
        // A la connexion, le compte est authentifie mais pas encore en session.
        // Lire l'etat par current_cabinet_id() serait circulaire : il faut donc
        // pouvoir le lire a partir d'un cabinet nomme.
        $this->abo('actif', date('Y-m-d', strtotime('+45 days')));
        $this->abo('suspendu', date('Y-m-d', strtotime('+400 days')));

        $state = abonnement_state_for_cabinet(self::$pdo, self::CAB_SANS_ABO);

        // L'abonnement de reference reste celui dont la date de fin est la
        // plus eloignee, et c'est lui qui fait foi.
        self::assertSame('suspendu', $state['statut']);
        self::assertFalse(abonnement_autorise_acces($state));
    }

    public function testCabinetSansAbonnementNAutoriseAucunAcces(): void
    {
        $state = abonnement_state_for_cabinet(self::$pdo, self::CAB_SANS_ABO);

        self::assertSame('absent', $state['statut']);
        self::assertFalse(abonnement_autorise_acces($state));
    }

    /* ================================================================
     * 2. Mot de passe provisoire
     * ================================================================ */

    public function testMotDePasseGenereEstLongEtSansCaractereAmbigu(): void
    {
        $mdp = generer_mot_de_passe_provisoire();

        self::assertSame(14, strlen($mdp));
        // 0/O et 1/l/I se confondent a la recopie : le secret transite par un
        // canal humain, un "0" lu "O" fait echouer la premiere connexion.
        self::assertDoesNotMatchRegularExpression('/[O0lI1]/', $mdp);
        // Les trois classes sont presentes : un secret 100 % majuscules est
        // refuse par plusieurs validateurs de mot de passe.
        self::assertMatchesRegularExpression('/[a-z]/', $mdp);
        self::assertMatchesRegularExpression('/[A-Z]/', $mdp);
        self::assertMatchesRegularExpression('/[0-9]/', $mdp);
    }

    public function testMotsDePasseGenresSontDistincts(): void
    {
        // Deux cabinets qui s'abonnent le meme jour ne peuvent pas recevoir le
        // meme secret : le mot de passe est la seule donnee de l'ecran de
        // creation, il doit differer.
        $vus = [];
        for ($i = 0; $i < 25; $i++) {
            $vus[generer_mot_de_passe_provisoire()] = true;
        }

        self::assertCount(25, $vus);
    }

    /* ================================================================
     * 3. Creation de l'acces
     * ================================================================ */

    public function testAccesCreeEstActifRattacheAuCabinetEtForceLeChangementDeMotDePasse(): void
    {
        $cree = creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Amrani Salma',
            'salma.amrani@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertIsArray($cree);

        $comptes = self::lireComptes();
        self::assertCount(1, $comptes);

        $user = $comptes[0];
        self::assertSame('actif', (string) $user['statut'], 'un acces cree doit pouvoir se connecter immediatement');
        self::assertSame(self::CAB_SANS_ABO, (int) $user['cabinet_id'], 'le cabinet decoule de l\'abonnement, il n\'est jamais choisi');
        self::assertSame((int) self::$roleAdminCabinet, (int) $user['role_id']);
        self::assertSame(1, (int) $user['must_change_password'], 'sans cela, le mot de passe provisoire deviendrait definitif');
        self::assertNotSame('', (string) $user['password_hash']);
        self::assertNotSame(
            $cree['mot_de_passe'],
            (string) $user['password_hash'],
            'le mot de passe ne doit jamais etre stocke en clair'
        );
        self::assertTrue(
            password_verify($cree['mot_de_passe'], (string) $user['password_hash']),
            'le mot de passe renvoye a l\'ecran doit etre celui qui ouvre la session'
        );
    }

    public function testAccesCreeAlimenteLePivotDeRoles(): void
    {
        $cree = creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Test Pivot',
            'pivot@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertIsArray($cree);

        // `get_user_permissions()` fait l'union du pivot et de `users.role_id`.
        // Ne pas ecrire le pivot n'est pas neutre : le role ajoute ensuite par
        // un grant individuel n'aurait aucun effet.
        $stmt = self::$pdo->prepare('SELECT role_id, is_primary FROM user_roles WHERE user_id = :uid');
        $stmt->execute(['uid' => $cree['id']]);
        $lignes = $stmt->fetchAll();

        self::assertCount(1, $lignes);
        self::assertSame((int) self::$roleAdminCabinet, (int) $lignes[0]['role_id']);
        self::assertSame(1, (int) $lignes[0]['is_primary']);
    }

    public function testRoleDuCentreEstRefuse(): void
    {
        // Le select de l'ecran ne propose que des roles 'cabinet', mais un
        // POST forge peut envoyer n'importe quel id. Attribuer 'Super Admin' a
        // un cabinet lui donnerait un acces transverse a tous les tenants :
        // l'inverse exact du cloisonnement.
        $cree = creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Intrus',
            'intrusion@cabinet.test',
            (int) self::$roleSuperAdmin
        );

        self::assertNull($cree);
        self::assertSame([], self::lireComptes(), 'aucun compte ne doit etre cree');
    }

    public function testEmailDejaPrisEstRefuseMemeChezUnAutreCabinet(): void
    {
        // uq_users_email porte sur TOUTE la table : un email deja utilise chez
        // un autre tenant est un conflit, pas un doublon interne.
        creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Premier',
            'partage@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        $stmt = self::$pdo->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->execute(['e' => 'partage@cabinet.test']);
        $userId = (int) $stmt->fetchColumn();

        // Un deuxieme cabinet, pour que le refus ne vienne pas de la regle
        // "un acces par cabinet" mais bien de l'unicite de l'email.
        $pdo = self::$pdo;
        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES (90102, 'Autre cabinet test', 'ABSB', 'actif')");
        try {
            $cree = creer_acces_cabinet(
                $pdo,
                90102,
                'Second',
                'partage@cabinet.test',
                (int) self::$roleAdminCabinet
            );

            self::assertNull($cree);
            self::assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM users WHERE cabinet_id = 90102')->fetchColumn());
        } finally {
            $pdo->exec('DELETE FROM user_roles WHERE user_id IN (SELECT id FROM users WHERE cabinet_id = 90102)');
            $pdo->exec('DELETE FROM users WHERE cabinet_id = 90102');
            $pdo->exec('DELETE FROM cabinets WHERE id = 90102');
        }
    }

    public function testLExclusionLaisserUnCompteConserverSonPropreEmail(): void
    {
        // `$excludeUserId` sert a l'edition d'un compte : sans lui, un compte
        // verrait son propre email annonce comme deja pris et la sauvegarde
        // serait refusee a chaque fois.
        $cree = creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Existant',
            'existant@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertIsArray($cree);
        self::assertTrue(user_email_existe(self::$pdo, 'existant@cabinet.test'));
        self::assertFalse(user_email_existe(self::$pdo, 'existant@cabinet.test', $cree['id']));
        self::assertTrue(user_email_existe(self::$pdo, 'existant@cabinet.test', $cree['id'] + 1));
    }

    public function testEmailDejaPrisEstRefuseDansLeMemeCabinet(): void
    {
        creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Premier',
            'meme@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        $cree = creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Second',
            'meme@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertNull($cree);
        self::assertCount(1, self::lireComptes());
    }

    public function testIdentificationEstInsensibleALaCasseCommeLaCollation(): void
    {
        // `users.email` est en utf8mb4_unicode_ci : MySQL refuse 'SALMA@X' face a
        // 'salma@x'. Notre pre-verification doit voir la meme chose, sinon
        // l'INSERT echoue sur une erreur SQL illisible.
        creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Origine',
            'casse@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertTrue(user_email_existe(self::$pdo, 'CASSE@CABINET.TEST'));
        self::assertNull(creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Variante',
            'Casse@Cabinet.TEST',
            (int) self::$roleAdminCabinet
        ));
    }

    public function testChampsObligatoiresManquantRenvoientUnRefus(): void
    {
        self::assertNull(creer_acces_cabinet(self::$pdo, self::CAB_SANS_ABO, '', 'a@b.test', (int) self::$roleAdminCabinet));
        self::assertNull(creer_acces_cabinet(self::$pdo, self::CAB_SANS_ABO, 'Nom', '', (int) self::$roleAdminCabinet));
        self::assertNull(creer_acces_cabinet(self::$pdo, self::CAB_SANS_ABO, 'Nom', 'c@d.test', 0));
        self::assertNull(creer_acces_cabinet(self::$pdo, 0, 'Nom', 'e@f.test', (int) self::$roleAdminCabinet));
        self::assertSame([], self::lireComptes());
    }

    public function testMotDePasseSaisiEstRespecte(): void
    {
        // L'ecran laisse le champ vide pour un mot de passe genere, mais
        // l'admin peut imposer un secret : il doit alors etre honore tel quel.
        $cree = creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Choix',
            'choix@cabinet.test',
            (int) self::$roleAdminCabinet,
            'MonMotDePasse2026'
        );

        self::assertIsArray($cree);
        self::assertSame('MonMotDePasse2026', $cree['mot_de_passe']);

        $comptes = self::lireComptes();
        self::assertTrue(password_verify('MonMotDePasse2026', (string) $comptes[0]['password_hash']));
    }

    public function testBaseInjoignableNeCreeAucunCompte(): void
    {
        self::assertNull(creer_acces_cabinet(null, self::CAB_SANS_ABO, 'Nom', 'f@g.test', (int) self::$roleAdminCabinet));
    }

    public function testEchecDuPivotNeLaisseAucunCompteOrphelin(): void
    {
        // Les deux INSERT doivent etre solidaires. Un trigger qui refuse toute
        // ligne simule l'echec du pivot (FK, contrainte, ou coupure reseau apres
        // l'INSERT `users`) : sans transaction, la ligne `users` resterait
        // commitee, le cabinet passerait pour equipe, et l'ecran refuserait
        // ensuite toute nouvelle tentative -- donnee irreparableable depuis
        // l'interface.
        self::$pdo->exec('DROP TRIGGER IF EXISTS trg_test_pivot_ko');
        self::$pdo->exec('
            CREATE TRIGGER trg_test_pivot_ko BEFORE INSERT ON user_roles
            FOR EACH ROW
            SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'echec simule du pivot\'
        ');

        try {
            $cree = creer_acces_cabinet(
                self::$pdo,
                self::CAB_SANS_ABO,
                'Apres Echec',
                'apres-echec@cabinet.test',
                (int) self::$roleAdminCabinet
            );

            self::assertNull($cree);
            self::assertSame([], self::lireComptes(), 'le rollback doit avoir supprime la ligne users');
            self::assertFalse(
                cabinet_a_un_compte(self::$pdo, self::CAB_SANS_ABO),
                'le cabinet ne doit pas paraitre equipe apres un echec de creation'
            );
        } finally {
            self::$pdo->exec('DROP TRIGGER IF EXISTS trg_test_pivot_ko');
        }

        // Une fois le trigger retire, la creation aboutit : l'echec precedent
        // n'a rien laisse derriere lui.
        self::assertIsArray(creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Apres Echec',
            'apres-echec@cabinet.test',
            (int) self::$roleAdminCabinet
        ));
    }

    public function testEchecDuPivotDansUneTransactionExterneRespecteLExtant(): void
    {
        // Appelant deja en transaction (un import, un lot) : le helper ne doit
        // ni ouvrir un Begin imbrique (qui echouerait), ni committer le travail
        // d'autrui.
        $pdo = self::$pdo;
        $pdo->beginTransaction();
        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES (90103, 'Cabinet appelant', 'ABAP', 'actif')");

        try {
            creer_acces_cabinet($pdo, 90103, 'Externe', 'externe@cabinet.test', (int) self::$roleAdminCabinet);
            self::assertTrue($pdo->inTransaction(), 'le helper ne doit pas committer la transaction de l\'appelant');
        } finally {
            $pdo->rollBack();
        }

        self::assertNull(fetch_record($pdo, 'cabinets', 90103), 'le rollback de l\'appelant doit avoir joue');
    }

    /* ================================================================
     * 4. Detection du cabinet deja equipe
     * ================================================================ */

    public function testCabinetEquipeEstDetecte(): void
    {
        self::assertFalse(cabinet_a_un_compte(self::$pdo, self::CAB_SANS_ABO));

        creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Equipe',
            'equipe@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertTrue(cabinet_a_un_compte(self::$pdo, self::CAB_SANS_ABO));
    }

    public function testComptesDuCabinetSontRelusPourAffichage(): void
    {
        creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Affiche',
            'affiche@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        $comptes = fetch_comptes_cabinet(self::$pdo, self::CAB_SANS_ABO);

        self::assertCount(1, $comptes);
        self::assertSame('Affiche', (string) $comptes[0]['nom_complet']);
        self::assertSame('affiche@cabinet.test', (string) $comptes[0]['email']);
        // Le libelle du role est resolu dans la meme requete : l'ecran affiche
        // « Administrateur Cabinet » et non un identifiant nu.
        self::assertSame('Administrateur Cabinet', (string) $comptes[0]['role_nom']);
    }

    public function testComptesDUnAutreCabinetNeSontJamaisRelus(): void
    {
        creer_acces_cabinet(
            self::$pdo,
            self::CAB_SANS_ABO,
            'Le Mien',
            'mien@cabinet.test',
            (int) self::$roleAdminCabinet
        );

        self::assertSame([], fetch_comptes_cabinet(self::$pdo, 99999999));
    }

    /* ================================================================
     * 5. Roles proposables
     * ================================================================ */

    public function testRolesProposesExcluentLesRolesDuCentre(): void
    {
        $options = fetch_roles_cabinet_options(self::$pdo);

        self::assertNotEmpty($options, 'aucun role de cabinet : la creation d\'acces serait impossible');
        self::assertArrayHasKey((int) self::$roleAdminCabinet, $options);
        self::assertArrayNotHasKey((int) self::$roleSuperAdmin, $options, 'un role du Centre ne doit jamais etre proposé à un cabinet');

        foreach ($options as $id => $nom) {
            self::assertIsInt($id);
            self::assertIsString($nom);
        }
    }

    public function testRolesProposesRestentSilencieuxSansBase(): void
    {
        self::assertSame([], fetch_roles_cabinet_options(null));
    }
}
