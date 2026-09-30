<?php

declare(strict_types=1);

/**
 * Page « Accès refusé » — rendering autonome, sans layout applicatif.
 *
 * Contexte fourni par `require_permission()` avant le `require` :
 *   - $deniedPermission      : la clé de droit manquante (ex. `dashboard.view`)
 *   - $deniedRole            : le rôle du compte connecté, s'il existe
 *   - $deniedUserName        : le nom du compte connecté
 *   - $deniedFallback        : 1re page que le compte a le droit de voir, ou null
 *   - $deniedHasAnyPermission: le compte a-t-il au moins un droit ?
 *
 * Pourquoi une page et non un `redirect_to()` : l'utilisateur n'a pas
 * `dashboard.view`, donc aucune page applicative ne lui est atteignable. La
 * seule issue est la deconnexion — d'ou un rendu direct en 403, hors layout
 * (le menu de gauche lui-meme est protege).
 */

$cssPath = __DIR__ . '/../assets/css/app.css';
$cssVersion = is_file($cssPath) ? '?v=' . filemtime($cssPath) : '';
$permission = (string) ($deniedPermission ?? '');
$role = trim((string) ($deniedRole ?? ''));
$userName = trim((string) ($deniedUserName ?? ''));

// Traduction des clés de droits les plus courantes : la clé brute reste
// affichée en dessous, un administrateur a besoin de la valeur exacte.
$libelles = [
    'dashboard.view' => 'Consulter le tableau de bord',
    'societes.view' => 'Consulter les societes',
    'collaborateurs.view' => 'Consulter les collaborateurs',
    'contrats.view' => 'Consulter les contrats',
    'documents.view' => 'Consulter les documents generes',
    'roles.view' => 'Consulter les roles et permissions',
];

$libelle = $libelles[$permission] ?? null;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès refusé — Centre Domiciliation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <link rel="stylesheet" href="assets/css/app.css<?= e($cssVersion) ?>">
</head>
<body class="denied-body">
    <main class="denied-main">
        <article class="denied-card" role="alert">
            <span class="denied-icon material-symbols-outlined" aria-hidden="true">lock</span>

            <div class="denied-head">
                <h1 class="denied-title">Accès refusé</h1>
                <span class="denied-code">Erreur 403</span>
            </div>

            <p class="denied-lead">
                Votre rôle ne donne pas accès au tableau de bord.
            </p>

            <dl class="denied-details">
                <?php if ($userName !== ''): ?>
                    <div class="denied-detail">
                        <dt>Compte</dt>
                        <dd><?= e($userName) ?></dd>
                    </div>
                <?php endif; ?>
                <div class="denied-detail">
                    <dt>Rôle</dt>
                    <dd><?= e($role !== '' ? $role : 'Aucun rôle attribué') ?></dd>
                </div>
                <?php if ($permission !== ''): ?>
                    <div class="denied-detail">
                        <dt>Droit manquant</dt>
                        <dd>
                            <?php if ($libelle !== null): ?>
                                <span class="denied-detail-label"><?= e($libelle) ?></span>
                            <?php endif; ?>
                            <code class="denied-key"><?= e($permission) ?></code>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>

            <p class="denied-help">
                <?php if (empty($deniedHasAnyPermission)): ?>
                    <span class="denied-warn">Aucun droit n'est actuellement associé à votre compte.</span>
                    Contactez un administrateur pour qu'il vous attribue un rôle.
                <?php else: ?>
                    Contactez un administrateur pour qu'il vous attribue les droits correspondants
                    à votre rôle.
                <?php endif; ?>
            </p>

            <div class="denied-actions">
                <?php if (!empty($deniedFallback)): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url($deniedFallback)) ?>">
                        <span class="material-symbols-outlined">arrow_back</span> Retour à l'application
                    </a>
                <?php endif; ?>
                <a class="btn btn-danger" href="<?= e(app_url('deconnexion')) ?>">
                    <span class="material-symbols-outlined">logout</span> Se déconnecter
                </a>
            </div>
        </article>
    </main>
</body>
</html>
