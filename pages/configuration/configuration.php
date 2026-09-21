<?php

declare(strict_types=1);

$referentials = [
    'formes-juridiques' => ['ref_formes_juridiques', 'Formes juridiques', 'description', 'Lier chaque forme à un dossier de templates'],
    'tribunaux' => ['ref_tribunaux', 'Tribunaux', 'balance', 'Tribunaux de commerce et de première instance'],
    'villes' => ['ref_villes', 'Villes', 'location_city', 'Liste des villes utilisées dans les dossiers'],
    'nationalites' => ['ref_nationalites', 'Nationalités', 'flag', 'Nationalités des associés'],
    'lieux-naissance' => ['ref_lieux_naissance', 'Lieux de naissance', 'location_on', 'Lieux de naissance des associés'],
    'adresses' => ['ref_ste_adresses', 'Adresses', 'home', 'Sièges sociaux et adresses types'],
    'qualites-associe' => ['ref_qualites_associe', 'Qualités associé', 'badge', 'Qualités (gérant, président...)'],
    'fonctions' => ['ref_fonctions', 'Fonctions', 'assignment', 'Fonctions des collaborateurs'],
    'activites' => ['ref_activites', 'Activités', 'work', "Activités économiques (objet social)"],
    'activites-ompic' => ['ref_activites_ompic', 'Activités OMPIC', 'verified', 'Activités normalisées OMPIC (code + libellé)'],
];

$gestionCards = [
    [
        'page' => 'roles',
        'perm' => 'roles.manage',
        'label' => 'Gestion des rôles',
        'icon' => 'admin_panel_settings',
        'sub' => 'Rôles, permissions et accès',
        'count_key' => 'roles',
    ],
    [
        'page' => 'activite',
        'perm' => 'roles.manage',
        'label' => "Journal d'activité",
        'icon' => 'history',
        'sub' => 'Historique des actions',
        'count_key' => 'journal',
    ],
    [
        'page' => 'notifications-manage',
        'perm' => 'roles.manage',
        'label' => 'Gestion des notifications',
        'icon' => 'notifications',
        'sub' => 'Envoyer une notification',
        'admin_only' => true,
        'count_key' => 'notifications',
    ],
];

$entrepriseCards = [
    [
        'page' => 'centre',
        'perm' => 'configuration.view',
        'label' => "Centre d'affaires",
        'icon' => 'apartment',
        'sub' => "Identité et coordonnées de l'entreprise",
        'count_key' => 'centre',
    ],
    [
        'page' => 'pv-templates',
        'perm' => 'pv_resolutions.view',
        'label' => 'Modèles de résolutions PV',
        'icon' => 'playlist_add_check',
        'sub' => 'Modèles de PV d\'assemblée générale',
        'count_key' => 'pv-templates',
    ],
];

$counts = [];
if (($pdo ?? null) instanceof PDO) {
    try {
        foreach ($referentials as $page => [$table]) {
            $counts[$page] = (int) ($pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() ?? 0);
        }
        $counts['roles'] = (int) ($pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn() ?? 0);
        $counts['collaborateurs'] = (int) ($pdo->query('SELECT COUNT(*) FROM collaborateurs WHERE statut != \'archive\'')->fetchColumn() ?? 0);
        $counts['notifications'] = (int) ($pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() ?? 0);
        $counts['journal'] = (int) ($pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn() ?? 0);
        $counts['pv-templates'] = (int) ($pdo->query('SELECT COUNT(*) FROM pv_resolutions_templates')->fetchColumn() ?? 0);
        $counts['centre'] = (int) ($pdo->query('SELECT COUNT(*) FROM centre_affaires')->fetchColumn() ?? 0);
    } catch (PDOException) {
        $counts = [];
    }
}

$entrees = (int) array_sum(array_map(fn ($p) => $counts[$p] ?? 0, array_keys($referentials)));

$_userHub = current_user();
$_isRoot = $_userHub && (int) ($_userHub['role_id'] ?? 0) === 1;

function config_hub_card(string $page, string $label, string $icon, string $sub, int $count, bool $withCount = true): void
{
    ?>
    <a class="config-hub-card" href="<?= e(app_url($page)) ?>">
        <span class="config-hub-card-icon"><span class="material-symbols-outlined"><?= e($icon) ?></span></span>
        <span class="config-hub-card-body">
            <span class="config-hub-card-label"><?= e($label) ?></span>
            <span class="config-hub-card-sub"><?= e($sub) ?></span>
        </span>
        <?php if ($withCount): ?>
        <span class="config-hub-card-count"><?= e((string) $count) ?></span>
        <?php endif; ?>
    </a>
    <?php
}
?>
<section class="stack">
    <section class="stats small">
        <article class="stat primary">
            <span>Référentiels</span>
            <strong><?= count($referentials) ?></strong>
        </article>
        <article class="stat">
            <span>Entrées de référentiels</span>
            <strong><?= $entrees ?></strong>
        </article>
        <article class="stat warning">
            <span>Rôles</span>
            <strong><?= $counts['roles'] ?? 0 ?></strong>
        </article>
        <article class="stat success">
            <span>Collaborateurs</span>
            <strong><?= $counts['collaborateurs'] ?? 0 ?></strong>
        </article>
        <article class="stat info">
            <span>Notifications</span>
            <strong><?= $counts['notifications'] ?? 0 ?></strong>
        </article>
        <article class="stat">
            <span>Actions journalisées</span>
            <strong><?= $counts['journal'] ?? 0 ?></strong>
        </article>
    </section>

    <div class="section-title-row">
        <h2>Accès &amp; audit</h2>
    </div>
    <div class="config-hub-grid">
        <?php foreach ($gestionCards as $card):
            if (!has_permission($card['perm'])) continue;
            if (!empty($card['admin_only']) && !$_isRoot) continue; ?>
            <?php config_hub_card($card['page'], $card['label'], $card['icon'], $card['sub'], $counts[$card['count_key']] ?? 0); ?>
        <?php endforeach; ?>
    </div>

    <div class="section-title-row">
        <h2>Entreprise</h2>
    </div>
    <div class="config-hub-grid">
        <?php foreach ($entrepriseCards as $card):
            if (!has_permission($card['perm'])) continue; ?>
            <?php config_hub_card($card['page'], $card['label'], $card['icon'], $card['sub'], $counts[$card['count_key']] ?? 0); ?>
        <?php endforeach; ?>
    </div>

    <div class="section-title-row">
        <h2>Référentiels</h2>
    </div>
    <div class="config-hub-grid">
        <?php foreach ($referentials as $page => [$table, $label, $icon, $sub]): ?>
            <?php config_hub_card($page, $label, $icon, $sub, $counts[$page] ?? 0); ?>
        <?php endforeach; ?>
    </div>
</section>