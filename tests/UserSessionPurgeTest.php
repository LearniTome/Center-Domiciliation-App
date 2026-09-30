<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 2 - Retrait de la session PHP de `user_sessions` a la deconnexion.
 *
 * `update_user_session()` upsert la ligne a chaque page vue, et un purge
 * automatique efface au bout d'une heure ce qui n'a pas bouge. Sans purge
 * explicite, un utilisateur qui se deconnecte reste doncliste "en ligne"
 * pendant pres d'une heure : `get_online_users()` et le widget du tableau de
 * bord annoncent des comptes connects qui ne le sont plus.
 */
final class UserSessionPurgeTest extends TestCase
{
    private const PREFIX = 'tst-purge-';

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
        $like = self::PREFIX . '%';
        $pdo->prepare('DELETE FROM user_sessions WHERE session_id LIKE :like')->execute(['like' => $like]);
    }

    private static function insert(string $sessionId): int
    {
        $pdo = self::$pdo;
        $pdo->prepare("INSERT INTO user_sessions
            (user_id, cabinet_id, last_active, current_page, ip_address, user_agent, session_id)
            VALUES (90031, NULL, NOW(), 'accueil', '127.0.0.1', 'phpunit', :sid)")
            ->execute(['sid' => $sessionId]);
        return (int) $pdo->lastInsertId();
    }

    private static function stillThere(string $sessionId): bool
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE session_id = :sid');
        $stmt->execute(['sid' => $sessionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function testLaSessionCouranteEstRetireeDeLaTable(): void
    {
        $sid = self::PREFIX . 'a-' . uniqid();
        self::insert($sid);
        $this->assertTrue(self::stillThere($sid), 'preparation : la session doit exister');

        $deleted = purge_user_session(self::$pdo, $sid);

        $this->assertSame(1, $deleted);
        $this->assertFalse(self::stillThere($sid));
    }

    public function testUneSessionInconnueNeSupprimeRien(): void
    {
        $this->assertSame(0, purge_user_session(self::$pdo, self::PREFIX . 'jamais-vue'));
    }

    public function testLaPurgeNeToucheQueLaSessionDemandee(): void
    {
        $conservee = self::PREFIX . 'b-' . uniqid();
        $supprimee = self::PREFIX . 'c-' . uniqid();
        self::insert($conservee);
        self::insert($supprimee);

        purge_user_session(self::$pdo, $supprimee);

        $this->assertTrue(self::stillThere($conservee), 'une autre session ne doit pas etre purgee');
        $this->assertFalse(self::stillThere($supprimee));

        purge_user_session(self::$pdo, $conservee);
    }

    public function testUneBaseInjoignableNeProvoquePasDErreur(): void
    {
        // La deconnexion doit aboutir meme si la base est tombee : la purge est
        // un nettoyage, jamais une condition pour pouvoir se deconnecter.
        $this->assertSame(0, purge_user_session(null, self::PREFIX . 'x'));
    }
}
