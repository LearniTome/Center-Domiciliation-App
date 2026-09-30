<?php

declare(strict_types=1);

$pageTitle = 'Mot de passe';
$pageSubtitle = must_change_password()
    ? 'Votre mot de passe est provisoire. Choisissez-en un nouveau pour continuer.'
    : 'Remplacez le mot de passe de votre compte.';

$user = current_user();
$force = must_change_password($user);
$error = '';

if (is_post()) {
    verify_csrf();

    $current = field_value($_POST, 'current_password');
    $next = field_value($_POST, 'new_password');
    $confirm = field_value($_POST, 'confirm_password');

    if ($current === '' || $next === '' || $confirm === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!$pdo instanceof PDO || !$user) {
        $error = 'Erreur de connexion a la base de donnees.';
    } elseif (!password_verify($current, (string) ($user['password_hash'] ?? ''))) {
        $error = 'Mot de passe actuel incorrect.';
    } elseif (mb_strlen($next) < 8) {
        $error = 'Le nouveau mot de passe doit contenir au moins 8 caracteres.';
    } elseif ($next === $current) {
        $error = 'Le nouveau mot de passe doit etre different de l\'actuel.';
    } elseif ($next !== $confirm) {
        $error = 'La confirmation ne correspond pas au nouveau mot de passe.';
    } else {
        $stmt = $pdo->prepare('
            UPDATE users
            SET password_hash = :hash, must_change_password = 0
            WHERE id = :id
        ');
        $stmt->execute([
            'hash' => password_hash($next, PASSWORD_DEFAULT),
            'id'   => (int) $user['id'],
        ]);

        clear_user_cache();
        purge_user_session($pdo);
        log_activity($pdo, 'update', 'user', (int) $user['id'], $user['nom_complet']);

        set_flash('success', 'Mot de passe mis a jour.');
        redirect_to('dashboard');
    }
}
?>
<section class="auth-split auth-split-solo">
    <div class="auth-side auth-side-form">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="auth-logo auth-logo-fallback material-symbols-outlined">lock_reset</span>
                <h1 class="auth-title">Mot de passe</h1>
                <?php if ($force): ?>
                    <span class="auth-badge"><span class="material-symbols-outlined">verified_user</span> Acces limite</span>
                <?php endif; ?>
                <p class="auth-welcome"><?= e($pageSubtitle) ?></p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error auth-error" role="alert">
                    <span class="material-symbols-outlined">error</span>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form" novalidate>
                <?= csrf_input() ?>

                <div class="auth-field">
                    <label for="pwd-current">Mot de passe actuel</label>
                    <div class="auth-control">
                        <span class="material-symbols-outlined auth-control-icon" aria-hidden="true">lock</span>
                        <input type="password" id="pwd-current" name="current_password" required autocomplete="current-password"
                               autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="pwd-new">Nouveau mot de passe</label>
                    <div class="auth-control">
                        <span class="material-symbols-outlined auth-control-icon" aria-hidden="true">lock_reset</span>
                        <input type="password" id="pwd-new" name="new_password" required minlength="8"
                               autocomplete="new-password" placeholder="8 caracteres minimum">
                    </div>
                </div>

                <div class="auth-field">
                    <label for="pwd-confirm">Confirmation</label>
                    <div class="auth-control">
                        <span class="material-symbols-outlined auth-control-icon" aria-hidden="true">check_circle</span>
                        <input type="password" id="pwd-confirm" name="confirm_password" required minlength="8"
                               autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    <span class="material-symbols-outlined">save</span> Enregistrer
                </button>
            </form>

            <?php if (!$force): ?>
                <p class="auth-footer">
                    <a href="<?= e(app_url('dashboard')) ?>">Annuler</a>
                </p>
            <?php else: ?>
                <p class="auth-footer">
                    <span class="material-symbols-outlined">logout</span>
                    <a href="<?= e(app_url('deconnexion')) ?>">Se deconnecter</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
