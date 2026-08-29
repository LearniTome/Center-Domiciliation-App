<?php

declare(strict_types=1);

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect_to('dashboard');
}

$error = '';
$redirect = $_GET['redirect'] ?? '';
$savedEmail = (string) ($_COOKIE['auth_email'] ?? '');
$rememberMe = false;

if (is_post()) {
    verify_csrf();

    $email = field_value($_POST, 'email');
    $password = field_value($_POST, 'password');
    $rememberMe = ($_POST['remember_me'] ?? '') === '1';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!($pdo ?? null) instanceof PDO) {
        $error = 'Erreur de connexion a la base de donnees.';
    } else {
        $stmt = $pdo->prepare('
            SELECT c.*, r.nom AS role_nom
            FROM collaborateurs c
            LEFT JOIN roles r ON r.id = c.role_id
            WHERE (c.email = :email1 OR c.collaborateur_email = :email2)
              AND c.can_login = 1
              AND c.statut = \'actif\'
            LIMIT 1
        ');
        $stmt->execute(['email1' => $email, 'email2' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $error = 'Email ou mot de passe incorrect.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            clear_user_cache();
            log_activity($pdo, 'connexion', 'auth', (int) $user['id'], $user['nom_complet']);

            // Update last_login
            $pdo->prepare('UPDATE collaborateurs SET last_login = NOW() WHERE id = :id')
                ->execute(['id' => (int) $user['id']]);

            // Remember me : conserver l'email dans un cookie (30 jours)
            if ($rememberMe) {
                setcookie('auth_email', $email, [
                    'expires' => time() + 30 * 24 * 3600,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie('auth_email', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            set_flash('success', 'Bienvenue, ' . $user['nom_complet'] . ' !');

            if ($redirect !== '' && !str_starts_with($redirect, 'http://') && !str_starts_with($redirect, 'https://')) {
                header('Location: ' . $redirect);
                exit;
            }
            redirect_to('dashboard');
        }
    }
}

$centre = get_centre_affaires($pdo ?? null);
$denomination = trim((string) ($centre['denomination'] ?? ''));
$adresse = trim((string) ($centre['adresse'] ?? ''));
$logo = get_centre_logo_path($pdo ?? null);

$_postEmail = field_value($_POST, 'email');
$emailValue = $_postEmail !== '' ? $_postEmail : $savedEmail;
$rememberChecked = ($rememberMe || $savedEmail !== '') ? ' checked' : '';
?>
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <?php if ($logo !== ''): ?>
                <img src="<?= e($logo) ?>" alt="Logo <?= e($denomination) ?>" class="auth-logo">
            <?php else: ?>
                <span class="auth-logo auth-logo-fallback material-symbols-outlined">location_city</span>
            <?php endif; ?>
            <h1 class="auth-title"><?= e($denomination !== '' ? $denomination : 'Centre Domiciliation') ?></h1>
            <p class="auth-subtitle">Espace collaborateur</p>
            <?php if ($adresse !== ''): ?>
                <p class="auth-address"><span class="material-symbols-outlined">place</span><?= e($adresse) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error auth-error" role="alert">
                <span class="material-symbols-outlined">error</span>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form" novalidate>
            <?= csrf_input() ?>
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" required autocomplete="email" placeholder="votre@email.com"
                       value="<?= e($emailValue) ?>" autofocus>
            </label>

            <label class="field">
                <span>Mot de passe</span>
                <span class="auth-password">
                    <input type="password" name="password" required autocomplete="current-password"
                           placeholder="Votre mot de passe" data-auth-password>
                    <button type="button" class="auth-password-toggle" data-auth-password-toggle
                            aria-label="Afficher le mot de passe" title="Afficher / masquer le mot de passe">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </span>
            </label>

            <label class="auth-remember">
                <input type="checkbox" name="remember_me" value="1"<?= $rememberChecked ?>>
                <span>Se souvenir de moi</span>
            </label>

            <button type="submit" class="auth-submit"><span class="material-symbols-outlined">login</span> Se connecter</button>
        </form>

        <p class="auth-footer"><span class="material-symbols-outlined">support_agent</span> Probleme de connexion ? Contactez l'administration.</p>
    </div>
</section>