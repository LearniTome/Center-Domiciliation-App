<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Page 403 « Accès refusé » : deux invariants, aucun des deux n'est evident.
 *
 * 1. Le repli ne doit jamais choir une page que le compte ne peut pas ouvrir.
 *    C'est la meme raison qui a fait passer `require_permission()` du redirect
 *    au rendu 403 : renvoyer le compte vers une page protegee produit une
 *    boucle. `first_allowed_page()` valide donc chaque candidat, mais une
 *    suppression de droit dans la liste de candidats rouvrirait la faille
 *    silencieusement — d'ou le test d'invariant plutot qu'un test de valeur.
 *
 * 2. Le lien de retour ne doit pas etre un `history.back()`. Le parcours
 *    d'echec est toujours identique : POST `connexion` → redirect `dashboard`
 *    → 403. Un retour arriere rejoue ce POST, qui re-redirige vers le
 *    dashboard, donc l'utilisateur retombe sur la page 403. Le bouton etait
 *    donc un piege ; il a ete remplace par un lien serveur, et ce test verrouille
 *    l'absence de script de navigation.
 */
final class AccessDeniedPageTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static int $userId = 0;
    private static int $cabinetId = 90004;
    private static int $roleId = 0;

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
        $pdo->exec("DELETE FROM users WHERE email LIKE 'perm-403-%@example.test'");
        $pdo->exec('DELETE FROM role_permissions WHERE role_id IN (SELECT id FROM roles WHERE nom = '
            . "'Role test 403')");
        $pdo->exec("DELETE FROM roles WHERE nom = 'Role test 403'");
        $pdo->exec('DELETE FROM cabinets WHERE id = ' . self::$cabinetId);

        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES
            (" . self::$cabinetId . ", 'Cabinet test 403', 'T403', 'actif')");

        $pdo->exec("INSERT INTO roles (nom, description, is_system) VALUES
            ('Role test 403', 'role limite, sans dashboard.view', 0)");
        self::$roleId = (int) $pdo->lastInsertId();

        $pdo->exec("INSERT INTO users
            (nom_complet, email, password_hash, cabinet_id, role_id, statut)
            VALUES ('Adherent 403 Test', 'perm-403-a@example.test', '!', "
            . self::$cabinetId . ', ' . self::$roleId . ", 'actif')");
        self::$userId = (int) $pdo->lastInsertId();

        $GLOBALS['pdo'] = $pdo;
        // `app_url()` (liens de sortie de la page) lit cette globale ; le
        // bootstrap de la suite ne charge pas `includes/config.php`.
        $GLOBALS['config'] = $GLOBALS['config'] ?? ['base_url' => 'index.php'];
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$pdo instanceof PDO) {
            return;
        }
        $pdo = self::$pdo;
        $pdo->exec('DELETE FROM role_permissions WHERE role_id = ' . self::$roleId);
        $pdo->exec('DELETE FROM roles WHERE id = ' . self::$roleId);
        $pdo->exec('DELETE FROM user_sessions WHERE user_id = ' . self::$userId);
        $pdo->exec('DELETE FROM user_permissions WHERE user_id = ' . self::$userId);
        $pdo->exec('DELETE FROM user_roles WHERE user_id = ' . self::$userId);
        $pdo->exec('DELETE FROM users WHERE id = ' . self::$userId);
        $pdo->exec('DELETE FROM cabinets WHERE id = ' . self::$cabinetId);
        unset($GLOBALS['pdo']);
        $_SESSION = [];
    }

    protected function setUp(): void
    {
        $pdo = self::$pdo;
        $pdo->exec('DELETE FROM role_permissions WHERE role_id = ' . self::$roleId);
        $pdo->exec('DELETE FROM user_roles WHERE user_id = ' . self::$userId);
        $pdo->exec('DELETE FROM user_permissions WHERE user_id = ' . self::$userId);
        $this->login();
    }

    private function login(): void
    {
        $_SESSION = ['user_id' => self::$userId];
        unset($_SESSION['_user_cache'], $_SESSION['_permissions_cache']);
    }

    /** @param list<string> $permissions */
    private function grant(string ...$permissions): void
    {
        $st = self::$pdo->prepare(
            'INSERT INTO role_permissions (role_id, permission_id)
             SELECT ?, id FROM permissions WHERE permission_key = ?'
        );
        foreach ($permissions as $permission) {
            $st->execute([self::$roleId, $permission]);
            if ($st->rowCount() === 0) {
                self::fail('Permission "' . $permission . '" introuvable : la matrice RBAC a change.');
            }
        }
        $this->login();
    }

    /**
     * Invariant central : la page de repli doit etre ouvrable par le compte.
     *
     * On rejoue le raisonnement de `require_page_access()` sur la page rendue :
     * si la page exige un droit que le compte n'a pas, le compte est renvoye
     * vers `dashboard`, qui est justement la page refusee → boucle.
     */
    public function testLeRepliEstUnePageQueLeComptePeutOuvrir(): void
    {
        $this->grant('contrats.view', 'associes.view');

        $page = first_allowed_page();
        $this->assertNotNull($page, 'Un compte avec des droits doit avoir une page de repli.');

        $permission = get_page_permission($page);
        $this->assertNotNull(
            $permission,
            'La page de repli "' . $page . '" ne porte aucun droit dans la map : elle a '
            . 'ete ajoutee aux candidats sans etre ajoutee a get_page_permission().'
        );
        $this->assertTrue(
            has_permission($permission),
            'La page de repli "' . $page . '" exige "' . $permission . '", droit que le '
            . 'compte ne possede pas : require_page_access() renverrait vers le dashboard, '
            . 'qui est la page refusee, donc boucle de redirection.'
        );
    }

    /** Le repli doit suivre les droits, pas une page fixe. */
    public function testLeRepliSuitLesDroitsDuCompte(): void
    {
        $this->grant('pv_ago.view');
        $this->assertSame('pv_ago', first_allowed_page());

        // `collaborateurs` passe devant dans l'ordre des candidats des lors qu'il est accessible.
        $this->grant('collaborateurs.view');
        $this->assertSame('collaborateurs', first_allowed_page());
    }

    /**
     * Un compte sans aucun droit n'a nulle part ou aller : le repli doit
     * renvoyer null, sinon la page 403 afficherait un lien vers une page refusee.
     */
    public function testAucunDroitAucunRepli(): void
    {
        $this->assertSame([], get_user_permissions());
        $this->assertNull(
            first_allowed_page(),
            'Un compte sans permission ne doit pas se voir proposer une page de repli.'
        );
    }

    /**
     * Le rendu doit dire quoi faire, pas seulement refuser.
     *
     * Un message generique force l'utilisateur a contacter un support qui ne
     * peut rien verifier ; la cle du droit manquant est ce que l'administrateur
     * utilise pour attribuer le role.
     */
    public function testLaPageIndiqueLeDroitManquantEtLeRole(): void
    {
        $this->grant('contrats.view');
        $html = $this->render();

        $this->assertStringContainsString('Accès refusé', $html);
        $this->assertStringContainsString('403', $html);
        $this->assertStringContainsString('dashboard.view', $html, 'La cle du droit manquant doit etre visible.');
        $this->assertStringContainsString('Role test 403', $html, 'Le role du compte doit etre visible.');
        $this->assertStringContainsString('Adherent 403 Test', $html);
        $this->assertStringContainsString('page=contrats', $html, 'Le repli doit pointer vers la page accessible.');
        $this->assertStringContainsString('page=deconnexion', $html, 'La deconnexion reste la sortie possible.');
    }

    /**
     * Rejoue le piege du bouton « Retour » par `history.back()` : le parcours
     * d'echec commence par un POST `connexion`, dont le retour arriere rejouerait
     * la soumission, qui renverrait de nouveau sur le dashboard refuse.
     */
    public function testAucunRetourParHistorique(): void
    {
        $this->grant('contrats.view');
        $html = $this->render();

        $this->assertStringNotContainsString('history.back', $html);
        $this->assertStringNotContainsString('data-denied-back', $html);
    }

    /** Un compte sans droit ne doit pas non plus avoir de lien de repli. */
    public function testAucunLienDeRepliSansDroit(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('Retour à l', $html);
        $this->assertStringContainsString('Aucun droit', $html);
        $this->assertStringContainsString('page=deconnexion', $html);
    }

    /** La page doit charger la charte de l'application, pas du HTML nu. */
    public function testLaPageChargeLaCharte(): void
    {
        $this->grant('contrats.view');
        $html = $this->render();

        $this->assertStringContainsString('assets/css/app.css', $html);
        $this->assertStringContainsString('Material+Symbols+Outlined', $html);
        $this->assertStringContainsString('material-symbols-outlined', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    /** La page est rendue sans layout : aucune dependance a la navigation. */
    public function testLaPageEstAutonome(): void
    {
        $this->grant('contrats.view');
        $html = $this->render();

        $this->assertStringNotContainsString('data-sidebar-toggle', $html);
        $this->assertStringNotContainsString('navigation.php', $html);
    }

    private function render(): string
    {
        $user = current_user();
        $deniedUserName = (string) ($user['nom_complet'] ?? '');
        $deniedRole = (string) ($user['role_nom'] ?? '');
        $deniedPermission = 'dashboard.view';
        $deniedFallback = first_allowed_page();
        $deniedHasAnyPermission = get_user_permissions() !== [];

        ob_start();
        require __DIR__ . '/../includes/acces_refuse.php';

        return (string) ob_get_clean();
    }
}
