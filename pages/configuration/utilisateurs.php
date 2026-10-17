<?php

declare(strict_types=1);

/**
 * Administration SaaS - Comptes de connexion (table `users`).
 *
 * `users` est la SEULE identite de connexion : `pages/auth/connexion.php` ne lit
 * que `users.email` + `users.password_hash`. Un mot de passe existe donc en
 * clair uniquement au moment ou il est pose, puis est perdu : cette page ne peut
 * pas l'afficher et ne propose que son renouvellement (c'est voulu, pas un
 * manque d'interface).
 *
 * Cloisonnement : `users` n'est pas dans `tenant_scoped_tables()`, la garde n'est
 * donc pas `assert_tenant_access()` mais le WHERE ci-dessous. Un compte de cabinet
 * ne voit QUE les comptes de son cabinet - le parametre `?cabinet=` est impose en
 * dur, jamais lu de l'URL, sinon `?cabinet=2` ouvrirait le tenant voisin.
 */

$pageSubtitle = "Comptes de connexion (Centre et cabinets clients). Un mot de passe "
    . "n'est jamais affichable : il est chiffré, seule sa réinitialisation est possible.";

$canCreate = has_permission('users.create');
$canDelete = has_permission('users.delete');

$db = ($pdo ?? null) instanceof PDO ? $pdo : null;
$monId = (int) ($_SESSION['user_id'] ?? 0);
$monCabinetId = current_cabinet_id();

/* ---------------------------------------------------------------------------
 * POST : suppression d'un compte.
 * ------------------------------------------------------------------------ */
if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $cibleId = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        if (!$canDelete) {
            set_flash('error', "Vous n'avez pas les droits nécessaires.");
            redirect_to('utilisateurs');
        }
        if ($cibleId <= 0) {
            set_flash('error', 'Compte introuvable.');
            redirect_to('utilisateurs');
        }
        if ($cibleId === $monId) {
            set_flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            redirect_to('utilisateurs');
        }

        try {
            $st = $db->prepare(
                'SELECT id, nom_complet, cabinet_id FROM users WHERE id = :id LIMIT 1'
            );
            $st->execute(['id' => $cibleId]);
            $cible = $st->fetch();
        } catch (PDOException) {
            $cible = false;
        }

        if ($cible === false) {
            set_flash('error', 'Compte introuvable.');
            redirect_to('utilisateurs');
        }

        // Garde anti-IDOR : un compte de cabinet ne touche que son cabinet.
        if ($monCabinetId !== null && (int) $cible['cabinet_id'] !== $monCabinetId) {
            set_flash('error', 'Compte appartenant à un autre cabinet.');
            redirect_to('utilisateurs');
        }

        try {
            // Les trois lignes filles sont detachees explicitement : `user_roles`
            // et `user_permissions` portent deja ON DELETE CASCADE, mais
            // `user_sessions` non, et une session orpheline laisserait un compte
            // disparu apparaître dans « utilisateurs en ligne ».
            $db->beginTransaction();
            $db->prepare('DELETE FROM user_sessions WHERE user_id = :id')
                ->execute(['id' => $cibleId]);
            $db->prepare('DELETE FROM user_permissions WHERE user_id = :id')
                ->execute(['id' => $cibleId]);
            $db->prepare('DELETE FROM user_roles WHERE user_id = :id')
                ->execute(['id' => $cibleId]);
            $db->prepare('DELETE FROM users WHERE id = :id')
                ->execute(['id' => $cibleId]);
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            set_flash('error', 'Suppression impossible : ' . $e->getMessage());
            redirect_to('utilisateurs');
        }

        log_activity($db, 'delete', 'user', $cibleId, (string) $cible['nom_complet']);
        set_flash('success', 'Compte « ' . (string) $cible['nom_complet'] . ' » supprimé.');
        redirect_to('utilisateurs');
    }
}

/* ---------------------------------------------------------------------------
 * Filtres.
 * ------------------------------------------------------------------------ */
$q = search_term();
$filtreCabinet = (int) ($_GET['cabinet'] ?? 0);
$filtreStatut = (string) ($_GET['statut'] ?? '');
$filtrePortee = (string) ($_GET['portee'] ?? '');

if (!in_array($filtreStatut, ['actif', 'suspendu'], true)) {
    $filtreStatut = '';
}
if (!in_array($filtrePortee, ['centre', 'cabinet'], true)) {
    $filtrePortee = '';
}

$where = [];
$params = [];

if ($monCabinetId !== null) {
    // Perimetre impose : aucun filtre URL n'est lu pour un compte de cabinet.
    $where[] = 'u.cabinet_id = :portee_cabinet';
    $params['portee_cabinet'] = $monCabinetId;
    $filtrePortee = 'cabinet';
} else {
    if ($filtreCabinet > 0) {
        $where[] = 'u.cabinet_id = :filtre_cabinet';
        $params['filtre_cabinet'] = $filtreCabinet;
    } elseif ($filtrePortee === 'centre') {
        $where[] = 'u.cabinet_id IS NULL';
    } elseif ($filtrePortee === 'cabinet') {
        $where[] = 'u.cabinet_id IS NOT NULL';
    }

    if ($filtreStatut !== '') {
        $where[] = 'u.statut = :filtre_statut';
        $params['filtre_statut'] = $filtreStatut;
    }
}

if ($q !== '') {
    $where[] = '(u.nom_complet LIKE :q OR u.email LIKE :q OR r.nom LIKE :q OR cb.nom LIKE :q)';
    $params['q'] = like_term($q);
}

$whereSql = $where === [] ? '1 = 1' : implode(' AND ', $where);

$sql = '
    SELECT u.id, u.nom_complet, u.email, u.statut, u.telephone, u.must_change_password,
           u.last_login, u.created_at, u.cabinet_id, u.role_id,
           r.nom AS role_nom, r.scope AS role_scope, r.is_system AS role_is_system,
           cb.code AS cabinet_code, cb.nom AS cabinet_nom
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    LEFT JOIN cabinets cb ON cb.id = u.cabinet_id
    WHERE ' . $whereSql . '
    ORDER BY u.nom_complet ASC, u.id ASC
'; // nosemgrep: tainted-sql-string -- $whereSql n'est compose que de fragments constants et de placeholders nommes

$comptes = [];
if ($db) {
    try {
        $st = $db->prepare($sql);
        $st->execute($params);
        $comptes = $st->fetchAll();
    } catch (PDOException $e) {
        set_flash('error', 'Lecture des comptes impossible : ' . $e->getMessage());
    }
}

/* ---------------------------------------------------------------------------
 * Export CSV : meme perimetre que l'affichage (les filtres sont rejoues).
 * ------------------------------------------------------------------------ */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!has_permission('users.view')) {
        set_flash('error', "Vous n'avez pas les droits nécessaires.");
        redirect_to('utilisateurs');
    }

    $rows = [];
    foreach ($comptes as $c) {
        $rows[] = [
            (string) ($c['nom_complet'] ?? ''),
            (string) ($c['email'] ?? ''),
            (string) ($c['role_nom'] ?? ''),
            ((int) $c['cabinet_id'] === 0 || $c['cabinet_id'] === null)
                ? 'Centre'
                : (string) ($c['cabinet_nom'] ?? '') . ' (' . (string) ($c['cabinet_code'] ?? '') . ')',
            $c['statut'] === 'actif' ? 'Actif' : 'Suspendu',
            (int) $c['must_change_password'] === 1 ? 'Provisoire' : 'Défini',
            $c['last_login'] !== null && $c['last_login'] !== ''
                ? date('d/m/Y H:i', strtotime((string) $c['last_login']))
                : 'Jamais',
        ];
    }

    export_csv('utilisateurs.csv', [
        'Nom complet', 'Login (email)', 'Role', 'Perimetre', 'Statut',
        'Mot de passe', 'Derniere connexion',
    ], $rows);
}

/* ---------------------------------------------------------------------------
 * Statuts d'affichage.
 * ------------------------------------------------------------------------ */
$nbTotal = count($comptes);
$nbActifs = count(array_filter($comptes, fn (array $c): bool => ($c['statut'] ?? '') === 'actif'));
$nbCentre = count(array_filter($comptes, fn (array $c): bool => $c['cabinet_id'] === null));
$nbCabinet = $nbTotal - $nbCentre;
$nbProvisoires = count(
    array_filter($comptes, fn (array $c): bool => (int) ($c['must_change_password'] ?? 0) === 1)
);

$etats = [
    'actif' => ['Actif', 'badge-success'],
    'suspendu' => ['Suspendu', 'badge-danger'],
];
?>
<?php require __DIR__ . '/../../includes/config_tabs.php'; render_config_tabs('utilisateurs'); ?>

<section class="stack">
    <div class="section-header">
        <p class="help-text">
            <?= $nbTotal ?> compte(s) — <?= $nbActifs ?> actif(s),
            <?= $nbCabinet ?> de cabinet, <?= $nbCentre ?> au Centre
            <?php if ($nbProvisoires > 0): ?>
                — <?= $nbProvisoires ?> mot de passe provisoire(s) à remplacer
            <?php endif; ?>
        </p>
    </div>

    <section class="stats small">
        <article class="stat primary">
            <span>Comptes</span>
            <strong><?= $nbTotal ?></strong>
        </article>
        <article class="stat success">
            <span>Actifs</span>
            <strong><?= $nbActifs ?></strong>
        </article>
        <article class="stat">
            <span>Cabinets clients</span>
            <strong><?= $nbCabinet ?></strong>
        </article>
        <article class="stat">
            <span>Centre</span>
            <strong><?= $nbCentre ?></strong>
        </article>
    </section>

    <article class="card">
        <div class="section-header">
            <span class="material-symbols-outlined" style="color:var(--text-secondary)">manage_accounts</span>
            <span>Comptes de connexion</span>

            <span class="table-actions" style="margin-left:auto">
                <form method="get" style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="page" value="utilisateurs">
                    <?php if ($monCabinetId === null && $filtreCabinet > 0): ?>
                        <input type="hidden" name="cabinet" value="<?= e((string) $filtreCabinet) ?>">
                    <?php endif; ?>
                    <?php if ($filtrePortee !== ''): ?>
                        <input type="hidden" name="portee" value="<?= e($filtrePortee) ?>">
                    <?php endif; ?>
                    <?php if ($filtreStatut !== ''): ?>
                        <input type="hidden" name="statut" value="<?= e($filtreStatut) ?>">
                    <?php endif; ?>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, login ou rôle…"
                           aria-label="Rechercher un compte" style="min-width:220px">
                    <button class="btn btn-secondary" type="submit">
                        <span class="material-symbols-outlined">search</span> Rechercher
                    </button>
                </form>

                <a class="btn btn-info" href="<?= e(app_url('utilisateurs', array_merge(
                    array_filter(['q' => $q, 'cabinet' => $filtreCabinet ?: null, 'portee' => $filtrePortee, 'statut' => $filtreStatut]),
                    ['export' => 'csv']
                ))) ?>">
                    <span class="material-symbols-outlined">download</span> Exporter CSV
                </a>
            </span>
        </div>

        <?php if ($monCabinetId === null): ?>
            <div class="table-actions" style="justify-content:flex-start;gap:6px;flex-wrap:wrap;padding:0 0 12px">
                <?php
                    $liens = [
                        '' => 'Tous',
                        'cabinet' => 'Cabinets clients',
                        'centre' => 'Centre',
                    ];
                    $base = array_filter(['q' => $q, 'statut' => $filtreStatut]);
                foreach ($liens as $cle => $libelle) {
                    $href = app_url('utilisateurs', $cle === '' ? $base : array_merge($base, ['portee' => $cle]));
                    $actif = $filtrePortee === $cle;
                    echo '<a class="btn btn-secondary" style="' . ($actif ? 'border-color:var(--primary);color:var(--primary);' : '') . '" href="'
                        . e($href) . '">' . e($libelle) . '</a>';
                }

                $statuts = ['' => 'Tous les statuts', 'actif' => 'Actifs', 'suspendu' => 'Suspendus'];
                foreach ($statuts as $cle => $libelle) {
                    $href = app_url('utilisateurs', $cle === '' ? $base : array_merge($base, ['statut' => $cle]));
                    $actif = $filtreStatut === $cle;
                    echo '<a class="btn btn-secondary" style="' . ($actif ? 'border-color:var(--primary);color:var(--primary);' : '') . '" href="'
                        . e($href) . '">' . e($libelle) . '</a>';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($comptes === []): ?>
            <p class="table-empty">Aucun compte ne correspond à cette recherche.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable>
                    <thead>
                        <tr>
                            <th data-col="utilisateur">Utilisateur</th>
                            <th data-col="perimetre">Périmètre</th>
                            <th data-col="role">Rôle</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="motdepasse">Mot de passe</th>
                            <th data-col="connexion">Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comptes as $c): ?>
                            <?php
                                $uid = (int) $c['id'];
                                $isCentre = $c['cabinet_id'] === null;
                                $initials = compte_initials((string) $c['nom_complet']);
                                $couleur = compte_avatar_color((string) $c['nom_complet']);
                                [$libelleEtat, $classeEtat] = $etats[$c['statut'] ?? ''] ?? [
                                    ucfirst((string) ($c['statut'] ?? '-')), '',
                                ];
                                $derniere = $c['last_login'] !== null && $c['last_login'] !== ''
                                    ? date('d/m/Y H:i', strtotime((string) $c['last_login']))
                                    : 'Jamais';
                            ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <span class="user-avatar <?= $couleur ?>"><?= e($initials) ?></span>
                                        <div class="user-cell-info">
                                            <span class="user-cell-name"><?= e((string) $c['nom_complet']) ?></span>
                                            <span class="user-cell-email"><?= e((string) $c['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isCentre): ?>
                                        <span class="badge badge-info">Centre</span>
                                    <?php else: ?>
                                        <span class="badge">
                                            <?= e((string) ($c['cabinet_code'] ?: $c['cabinet_nom'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['role_nom'] !== null && $c['role_nom'] !== ''): ?>
                                        <?= e((string) $c['role_nom']) ?>
                                        <?php if ((int) ($c['role_is_system'] ?? 0) === 1): ?>
                                            <span class="badge badge-warning">Système</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="help-text">Aucun rôle</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $classeEtat ?>"><?= e($libelleEtat) ?></span>
                                </td>
                                <td>
                                    <?php if ((int) ($c['must_change_password'] ?? 0) === 1): ?>
                                        <span class="badge badge-warning">Provisoire</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Défini</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.78rem;color:var(--text-secondary)"><?= e($derniere) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <?php if (has_permission('users.edit') || $uid === $monId): ?>
                                            <a class="btn-icon info" href="<?= e(app_url('utilisateur', ['id' => $uid])) ?>"
                                               title="Voir / modifier"><span class="material-symbols-outlined">edit</span></a>
                                        <?php endif; ?>
                                        <?php if ($canDelete && $uid !== $monId): ?>
                                            <form method="post" style="display:inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= e((string) $uid) ?>">
                                                <button class="btn-icon danger" type="submit" title="Supprimer"
                                                        data-confirm="Supprimer le compte &quot;<?= e((string) $c['nom_complet']) ?>&quot; ? Il perdra immédiatement l'accès à l'application.">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <p class="help-text">
        <span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:-2px">info</span>
        Un mot de passe ne peut pas être relu : il est conservé sous forme chiffré. Pour en
        attribuer un nouveau, ouvrez la fiche du compte puis utilisez « Réinitialiser le mot de passe ».
    </p>
</section>
