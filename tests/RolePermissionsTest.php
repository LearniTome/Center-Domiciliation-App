<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Provisionnement des permissions : le verrouillage silencieux.
 *
 * `get_user_permissions()` ne lisait QUE la table pivot `user_roles`. Or
 * `users.role_id` est le role primaire denormalise, recopie dans le pivot par
 * la seule migration d'installation (20260927_100003). Tout compte cree
 * apres cette migration avec seulement `role_id` - ce que fera la Phase 5, un
 * import ou une saisie directe - n'a donc AUCUNE ligne de pivot et ZERO
 * permission.
 *
 * Le symptome n'est pas un message d'erreur mais une boucle de redirection.
 * `require_permission('dashboard.view')` refuse l'acces au dashboard et y
 * renvoie l'utilisateur, qui redemande `dashboard.view` : le navigateur
 * abandonne sur ERR_TOO_MANY_REDIRECTS et aucune page n'est plus accessible.
 *
 * Ce test ne pouvait pas exister avant : sans compte a ce profil, le
 * comportement correct (role lu depuis `role_id`) et le comportement
 * verrouille sont tous deux "le compte n'a pas de permission" du point de vue
 * du code, et rien ne distinguait les deux.
 *
 * Le role utilise ici est volontairement un role NON systeme : `has_permission()`
 * court-circuite sur `roles.is_system`, un Super Admin passerait le test meme
 * si la resolution etait completement cassee.
 */
final class RolePermissionsTest extends TestCase
{
    private static ?PDO $pdo = null;
    private static int $userId = 0;
    private static int $roleA = 0;
    private static int $roleB = 0;

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
        $pdo->exec("DELETE FROM users WHERE email LIKE 'perm-tst-%@example.test'");
        $pdo->exec("DELETE FROM cabinets WHERE id = 90003");

        $pdo->exec("INSERT INTO cabinets (id, nom, code, statut) VALUES
            (90003, 'Cabinet test perms', 'TSTP', 'actif')");

        self::$roleA = self::roleId('Collaborateur Cabinet');
        self::$roleB = self::roleId('Lecture seule');

        // Le cas du defaut : `role_id` renseigne, AUCUNE ligne de pivot.
        $pdo->exec("INSERT INTO users
            (nom_complet, email, password_hash, cabinet_id, collaborateur_id, role_id, statut)
            VALUES ('Adherent Perms Test', 'perm-tst-a@example.test', '!', 90003, NULL, "
            . self::$roleA . ", 'actif')");
        self::$userId = (int) $pdo->lastInsertId();

        $GLOBALS['pdo'] = $pdo;
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$pdo instanceof PDO) {
            return;
        }
        $pdo = self::$pdo;
        $pdo->exec("DELETE FROM user_permissions WHERE user_id = " . self::$userId);
        $pdo->exec("DELETE FROM user_roles WHERE user_id = " . self::$userId);
        $pdo->exec("DELETE FROM users WHERE id = " . self::$userId);
        $pdo->exec("DELETE FROM cabinets WHERE id = 90003");
        unset($GLOBALS['pdo']);
        $_SESSION = [];
    }

    protected function setUp(): void
    {
        // Aucune ligne de pivot : on recree a chaque test l'etat defective.
        $pdo = self::$pdo;
        $pdo->exec("DELETE FROM user_roles WHERE user_id = " . self::$userId);
        $pdo->exec("DELETE FROM user_permissions WHERE user_id = " . self::$userId);
        $this->login();
    }

    private function login(): void
    {
        $_SESSION = ['user_id' => self::$userId];
        unset($_SESSION['_user_cache'], $_SESSION['_permissions_cache']);
    }

    private static function roleId(string $nom): int
    {
        $id = (int) self::$pdo->query(
            "SELECT id FROM roles WHERE nom = " . self::$pdo->quote($nom)
        )->fetchColumn();

        if ($id === 0) {
            self::fail('Role "' . $nom . '" introuvable : la matrice RBAC a change.');
        }

        return $id;
    }

    /** @return list<string> */
    private static function rolePermissions(int $roleId): array
    {
        return self::$pdo->query(
            'SELECT p.permission_key FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ' . $roleId
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Le test central : un compte dont le `role_id` est renseigne n'est pas
     * verrouille. Sans ce correctif, le compte n'a zero permission et le
     * dashboard redirige vers lui-meme indefiniment.
     */
    public function testCompteAvecSeulRoleIdRecupereLesPermissionsDuRole(): void
    {
        $attendues = self::rolePermissions(self::$roleA);
        $this->assertNotSame([], $attendues, 'Le role de test ne porte aucune permission.');

        $obtenues = get_user_permissions();

        $manquantes = array_diff($attendues, $obtenues);
        $this->assertSame(
            [],
            array_values($manquantes),
            'Permission(s) du role primaire absente(s) alors que users.role_id est '
            . 'renseigne et que le pivot user_roles est vide. Le compte est alors '
            . 'verrouille : require_permission() refuse le dashboard et y renvoie, '
            . 'ce qui produit une boucle de redirection.'
        );

        $this->assertTrue(
            has_permission('dashboard.view'),
            'dashboard.view doit etre resolue depuis users.role_id, sinon le compte '
            . 'ne peut pas atteindre la page vers laquelle require_permission() le renvoie.'
        );
    }

    /** L'union ne doit pas ecraser le pivot : les deux sources s'additionnent. */
    public function testLePivotUserRolesCompleteLeRolePrimaire(): void
    {
        self::$pdo->exec("INSERT INTO user_roles (user_id, role_id, is_primary) VALUES ("
            . self::$userId . ', ' . self::$roleB . ', 0)');
        $this->login();

        $attendues = array_unique(array_merge(
            self::rolePermissions(self::$roleA),
            self::rolePermissions(self::$roleB)
        ));
        $obtenues = get_user_permissions();

        $manquantes = array_diff($attendues, $obtenues);
        $this->assertSame(
            [],
            array_values($manquantes),
            'La jonction user_roles ne doit pas disappear au profit du seul role_id.'
        );
    }

    /** Un role supplementaire ne doit rien retirer au role primaire. */
    public function testLeRoleSupplementaireNEnleveAucunePermissionExistante(): void
    {
        $avant = get_user_permissions();

        self::$pdo->exec("INSERT INTO user_roles (user_id, role_id, is_primary) VALUES ("
            . self::$userId . ', ' . self::$roleB . ', 0)');
        $this->login();

        $apres = get_user_permissions();
        $perdues = array_diff($avant, $apres);

        $this->assertSame(
            [],
            array_values($perdues),
            'Ajouter un role ne doit jamais retirer une permission deja acquise.'
        );
    }

    /** Le refuse individuel doit toujours l'emporter, y compris sur le role. */
    public function testLeRefuseIndividuelLEmporteSurLeRole(): void
    {
        $cle = self::rolePermissions(self::$roleA)[0];

        $permId = (int) self::$pdo->query(
            "SELECT id FROM permissions WHERE permission_key = " . self::$pdo->quote($cle)
        )->fetchColumn();
        self::$pdo->exec("INSERT INTO user_permissions (user_id, permission_id, granted) VALUES ("
            . self::$userId . ", " . $permId . ', 0)');
        $this->login();

        $this->assertNotContains(
            $cle,
            get_user_permissions(),
            'Un refuse individuel (granted = 0) doit retirer la permission, '
            . 'meme si le role primaire la porte.'
        );
        $this->assertFalse(has_permission($cle));
    }

    /**
     * `require_permission()` compare la page courante a sa destination de
     * repli pour eviter de se rediriger vers elle-meme. Cette lecture de la
     * globale est ce qui rend la garde anti-boucle effective.
     */
    public function testCurrentPageLitLaGlobaleDuFrontController(): void
    {
        $GLOBALS['page'] = 'dashboard';
        $this->assertSame('dashboard', current_page());

        $GLOBALS['page'] = 'societe_suivi';
        $this->assertSame('societe_suivi', current_page());
        unset($GLOBALS['page']);
    }

    /** Repli sur `$_GET` quand la globale n'est pas renseignee. */
    public function testCurrentPageRepliqueParGetQuandLaGlobaleEstAbsente(): void
    {
        unset($GLOBALS['page']);
        $_GET['page'] = 'contrats';
        $this->assertSame('contrats', current_page());
        unset($_GET['page']);
        $this->assertSame('', current_page());
    }
}
