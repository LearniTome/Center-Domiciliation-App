<?php

declare(strict_types=1);

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect_to('dashboard');
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if (is_post()) {
    $email = field_value($_POST, 'email');
    $password = field_value($_POST, 'password');

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

            set_flash('success', 'Bienvenue, ' . $user['nom_complet'] . ' !');

            if ($redirect !== '' && !str_starts_with($redirect, 'http://') && !str_starts_with($redirect, 'https://')) {
                header('Location: ' . $redirect);
                exit;
            }
            redirect_to('dashboard');
        }
    }
}
?>
<section class="grid two">
    <article class="card stack" style="max-width:400px;margin:80px auto;">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <?php $_authLogo = get_centre_logo_path($pdo ?? null); if ($_authLogo !== ''): ?>
                <img src="<?= e($_authLogo) ?>" alt="Logo" style="max-width:120px;max-height:80px;object-fit:contain;margin-bottom:6px">
            <?php else: ?>
                <span class="material-symbols-outlined" style="font-size:3rem;color:var(--primary);">location_city</span>
            <?php endif; ?>
            <p class="help-text">Centre de Domiciliation — Espace collaborateur</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="stack">
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" required autocomplete="email" placeholder="votre@email.com" value="<?= e(field_value($_POST, 'email')) ?>">
            </label>

            <label class="field">
                <span>Mot de passe</span>
                <input type="password" name="password" required autocomplete="current-password" placeholder="Votre mot de passe">
            </label>

            <button type="submit"><span class="material-symbols-outlined">login</span> Se connecter</button>
        </form>
    </article>
</section>