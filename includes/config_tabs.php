<?php

declare(strict_types=1);

/**
 * Navigation de la section Configuration à 2 niveaux :
 *   niveau 1 : les 3 sous-pages (Accès & audit, Entreprise, Référentiels)
 *   niveau 2 : les onglets de la sous-page active
 *
 * Dépendances : e(), app_url(), has_permission(), current_user(), $_GET['page'].
 */

$configGroups = [
    'acces' => [
        'label' => "Accès & audit",
        'icon' => 'admin_panel_settings',
        'pages' => [
            'roles' => ['Gestion des rôles', 'admin_panel_settings', 'roles.manage', false],
            'activite' => ["Journal d'activité", 'history', 'roles.manage', false],
            'notifications-manage' => ['Gestion des notifications', 'notifications', 'roles.manage', true],
        ],
    ],
    'entreprise' => [
        'label' => 'Entreprise',
        'icon' => 'apartment',
        'pages' => [
            'centre' => ["Centre d'affaires", 'apartment', 'configuration.view', false],
            'pv-templates' => ['Modèles de résolutions PV', 'playlist_add_check', 'pv_resolutions.view', false],
        ],
    ],
    'referentiels' => [
        'label' => 'Référentiels',
        'icon' => 'database',
        'pages' => [
            'formes-juridiques' => ['Formes juridiques', 'description', 'configuration.view', false],
            'tribunaux' => ['Tribunaux', 'balance', 'configuration.view', false],
            'villes' => ['Villes', 'location_city', 'configuration.view', false],
            'nationalites' => ['Nationalités', 'flag', 'configuration.view', false],
            'lieux-naissance' => ['Lieux de naissance', 'location_on', 'configuration.view', false],
            'adresses' => ['Adresses', 'home', 'configuration.view', false],
            'qualites-associe' => ['Qualités associé', 'badge', 'configuration.view', false],
            'fonctions' => ['Fonctions', 'assignment', 'configuration.view', false],
            'activites' => ['Activités', 'work', 'configuration.view', false],
            'activites-ompic' => ['Activités OMPIC', 'verified', 'configuration.view', false],
        ],
    ],
];

/**
 * Renvoie la clé de groupe qui contient une page donnée (ou null).
 */
function config_group_for_page(string $page): ?string
{
    global $configGroups;
    foreach ($configGroups as $key => $group) {
        if (isset($group['pages'][$page])) {
            return $key;
        }
    }
    return null;
}

/**
 * Renvoie les onglets visibles d'un groupe pour l'utilisateur courant.
 */
function config_group_visible_pages(string $groupKey): array
{
    global $configGroups;
    if (!isset($configGroups[$groupKey])) {
        return [];
    }
    $user = current_user();
    $isRoot = $user && (int) ($user['role_id'] ?? 0) === 1;
    $out = [];
    foreach ($configGroups[$groupKey]['pages'] as $page => $meta) {
        [, , $perm, $adminOnly] = $meta;
        if ($adminOnly && !$isRoot) {
            continue;
        }
        if (function_exists('has_permission') && !has_permission($perm)) {
            continue;
        }
        $out[$page] = $meta;
    }
    return $out;
}

/**
 * Rend la barre d'onglets configuration à 2 niveaux.
 * $currentPage : page courante (onglet actif du niveau 2).
 * $groupOverride : groupe actif imposé (utilisé par le hub : page=configuration).
 */
function render_config_tabs(string $currentPage, ?string $groupOverride = null): void
{
    global $configGroups;

    $groups = [];
    foreach ($configGroups as $key => $group) {
        if (config_group_visible_pages($key) !== []) {
            $groups[$key] = $group;
        }
    }
    if ($groups === []) {
        return;
    }

    $activeGroup = $groupOverride;
    if ($activeGroup === null || !isset($groups[$activeGroup])) {
        $activeGroup = config_group_for_page($currentPage);
    }
    if ($activeGroup === null || !isset($groups[$activeGroup])) {
        $activeGroup = array_key_first($groups);
    }
    $onglets = config_group_visible_pages($activeGroup);

    // Onglets de la sous-page active uniquement (le niveau 1 "sous-pages"
    // est géré par la sidebar — pas de barre à 2 niveaux sur la page).
    if ($onglets === []) {
        return;
    }
    echo '<nav class="config-tabs" aria-label="Sections de configuration">';
    echo '<div class="config-tabs-level config-tabs-level-2">';
    foreach ($onglets as $page => $meta) {
        [$label, $icon] = $meta;
        $active = $page === $currentPage ? ' active' : '';
        echo '<a class="config-tab config-tab-onglet' . $active . '" href="' . e(app_url($page)) . '">';
        echo '<span class="material-symbols-outlined">' . e($icon) . '</span>';
        echo '<span>' . e($label) . '</span>';
        echo '</a>';
    }
    echo '</div>';
    echo '</nav>';
}