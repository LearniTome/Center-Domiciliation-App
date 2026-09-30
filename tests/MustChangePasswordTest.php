<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Mot de passe provisoire (`users.must_change_password`).
 *
 * Un compte adherent est cree par le Centre avec un mot de passe genere : tant
 * que l'utilisateur ne l'a pas change, ce mot de passe reste connu d'un tiers.
 * La colonne existe depuis la migration 20260927_100002 mais n'etait lue nulle
 * part, le drapeau ne declenchait donc rien.
 *
 * La porte vit dans index.php, hors de portee de PHPUnit : on fige ici les
 * invariants dont depend cette porte, dont le plus important est l'absence de
 * boucle de redirection (si `mot_de_passe` etait lui-meme redirige, le compte
 * resterait bloque sans issue).
 */
final class MustChangePasswordTest extends TestCase
{
    private function code(string $file): string
    {
        $src = file_get_contents(dirname(__DIR__) . '/' . $file);
        $this->assertIsString($src, $file . ' est illisible.');

        $code = '';
        foreach (token_get_all($src) as $token) {
            if (!is_array($token)) {
                $code .= $token;
                continue;
            }
            $code .= in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)
                ? str_repeat("\n", substr_count($token[1], "\n"))
                : $token[1];
        }

        return $code;
    }

    public function testLeDrapeauDeclencheLaPorte(): void
    {
        $this->assertTrue(must_change_password(['must_change_password' => 1]));
        $this->assertTrue(must_change_password(['must_change_password' => '1']));
    }

    public function testUnCompteNormalNEstPasBloque(): void
    {
        $this->assertFalse(must_change_password(['must_change_password' => 0]));
        $this->assertFalse(must_change_password(['must_change_password' => null]));
        $this->assertFalse(must_change_password([]), 'colonne absente = compte deja en regle');
    }

    public function testLesPagesDeSortieNeSontPasBloquees(): void
    {
        $exempt = password_change_exempt_pages();

        // Sans ces deux pages, impossible de se liberer du mot de passe provisoire.
        $this->assertContains('mot_de_passe', $exempt, 'la page de changement se redirigerait elle-meme.');
        $this->assertContains('deconnexion', $exempt, 'un compte bloque ne pourrait plus se deconnecter.');

        // Et aucune page metier ne doit etre exoneree.
        foreach (['dashboard', 'societes', 'cabinets', 'mon_abonnement'] as $page) {
            $this->assertNotContains($page, $exempt, $page . ' ne doit pas etre accessible avant le changement.');
        }
    }

    public function testLIndexAppliqueLaPorteSurToutesLesPages(): void
    {
        $code = self::code('index.php');

        $this->assertStringContainsString(
            'must_change_password()',
            $code,
            "L'index n'applique plus la porte du mot de passe provisoire."
        );
        $this->assertStringContainsString(
            'password_change_exempt_pages()',
            $code,
            "La porte de l'index ne se repose pas sur la liste d'exemption."
        );
    }

    public function testLaConnexionRedirigeVersLeChangementDeMotDePasse(): void
    {
        $code = self::code('pages/auth/connexion.php');

        $this->assertStringContainsString(
            'must_change_password',
            $code,
            'Apres connexion, un compte provisoire doit etre redirige sans passer par le tableau de bord.'
        );
    }

    public function testLeChangementDeMotDePasseLeveLeDrapeau(): void
    {
        $code = self::code('pages/auth/mot_de_passe.php');

        $this->assertStringContainsString(
            'must_change_password = 0',
            $code,
            "Le drapeau n'est pas leve : le compte resterait bloque en boucle."
        );
        $this->assertStringContainsString(
            'password_hash = :hash',
            $code,
            'Le nouveau mot de passe doit etre enregistre.'
        );
        // Le mot de passe provisoire ne doit pas survivre au changement, ni en
        // session (l'utilisateur resterait "en ligne") ni chez lui.
        $this->assertStringContainsString(
            'purge_user_session(',
            $code,
            'La session doit etre purgee : les autres sessions ont ete ouvertes avec le mot de passe provisoire.'
        );
        $this->assertStringContainsString(
            'verify_csrf()',
            $code,
            'Le formulaire de changement de mot de passe doit etre protege par CSRF.'
        );
        $this->assertStringContainsString(
            'password_verify(',
            $code,
            "L'ancien mot de passe doit etre verifie avant d'en accepter un nouveau."
        );
    }

    public function testLaPageEstDeclareeSansBarreLaterale(): void
    {
        $index = self::code('index.php');
        $this->assertStringContainsString("'mot_de_passe' => 'auth'", $index, 'Page non routée.');
        $this->assertStringContainsString(
            "\$noLayoutPages = ['connexion', 'deconnexion', 'mot_de_passe']",
            $index,
            'La page doit rendre sans barre laterale : un compte provisoire n\'a pas de menu.'
        );
    }
}
