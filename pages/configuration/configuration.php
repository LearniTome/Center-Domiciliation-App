<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/config_tabs.php';

$counts = [];
if (($pdo ?? null) instanceof PDO) {
    try {
        foreach ($configGroups as $group) {
            foreach ($group['pages'] as $page => [$label, $icon, $perm, $adminOnly]) {
                $table = match ($page) {
                    'roles' => 'roles',
                    'activite' => 'activity_logs',
                    'notifications-manage' => 'notifications',
                    'centre' => 'centre_affaires',
                    'pv-templates' => 'pv_resolutions_templates',
                    'formes-juridiques' => 'ref_formes_juridiques',
                    'tribunaux' => 'ref_tribunaux',
                    'villes' => 'ref_villes',
                    'nationalites' => 'ref_nationalites',
                    'lieux-naissance' => 'ref_lieux_naissance',
                    'adresses' => 'ref_ste_adresses',
                    'qualites-associe' => 'ref_qualites_associe',
                    'fonctions' => 'ref_fonctions',
                    'activites_statuts' => 'ref_activites_statuts',
                    'activites-ompic' => 'ref_activites_ompic',
                    default => null,
                };
                if ($table !== null) {
                    $counts[$page] = (int) ($pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() ?? 0);
                }
            }
        }
        $counts['collaborateurs'] = (int) ($pdo->query("SELECT COUNT(*) FROM collaborateurs WHERE statut != 'archive'")->fetchColumn() ?? 0);
    } catch (PDOException) {
        $counts = [];
    }
}

$_userHub = current_user();
$_isRoot = $_userHub && (int) ($_userHub['role_id'] ?? 0) === 1;

/** Renvoie les groupes dont au moins un onglet est visible. */
function config_hub_visible_groups(): array
{
    global $configGroups;
    $out = [];
    foreach ($configGroups as $key => $group) {
        if (config_group_visible_pages($key) !== []) {
            $out[$key] = $group;
        }
    }
    return $out;
}

/** Total d'entrées référentiels cumulées. */
$entrees = (int) array_sum(array_map(static fn ($p) => $counts[$p] ?? 0, array_keys($configGroups['referentiels']['pages'] ?? [])));

$g = $_GET['g'] ?? null;
if ($g === null || !isset(config_hub_visible_groups()[$g])) {
    $g = (string) array_key_first(config_hub_visible_groups());
}
$onglets = config_group_visible_pages($g);

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
        <?php if ($g === 'referentiels'): ?>
        <article class="stat primary">
            <span>Référentiels</span>
            <strong><?= count(config_hub_visible_groups()['referentiels']['pages'] ?? []) ?></strong>
        </article>
        <article class="stat">
            <span>Entrées de référentiels</span>
            <strong><?= $entrees ?></strong>
        </article>
        <article class="stat success">
            <span>Rôles</span>
            <strong><?= $counts['roles'] ?? 0 ?></strong>
        </article>
        <article class="stat info">
            <span>Collaborateurs</span>
            <strong><?= $counts['collaborateurs'] ?? 0 ?></strong>
        </article>
        <?php else: ?>
        <article class="stat primary">
            <span>Référentiels</span>
            <strong><?= count(config_hub_visible_groups()['referentiels']['pages'] ?? []) ?></strong>
        </article>
        <article class="stat">
            <span>Entrées de référentiels</span>
            <strong><?= $entrees ?></strong>
        </article>
        <article class="stat success">
            <span>Rôles</span>
            <strong><?= $counts['roles'] ?? 0 ?></strong>
        </article>
        <article class="stat info">
            <span>Collaborateurs</span>
            <strong><?= $counts['collaborateurs'] ?? 0 ?></strong>
        </article>
        <article class="stat warning">
            <span>Notifications</span>
            <strong><?= $counts['notifications-manage'] ?? 0 ?></strong>
        </article>
        <article class="stat">
            <span>Actions journalisées</span>
            <strong><?= $counts['activite'] ?? 0 ?></strong>
        </article>
        <?php endif; ?>
    </section>

    <?php if ($onglets !== []): ?>
    <div class="config-hub-grid">
        <?php foreach ($onglets as $page => $meta):
            [$label, $icon, $perm, $adminOnly] = $meta;
            $sub = match ($page) {
                'roles' => 'Rôles, permissions et accès',
                'activite' => 'Historique des actions',
                'notifications-manage' => 'Envoyer une notification',
                'centre' => "Identité et coordonnées de l'entreprise",
                'pv-templates' => 'Modèles de PV d\'assemblée générale',
                'formes-juridiques' => 'Lier chaque forme à un dossier de templates',
                'tribunaux' => 'Tribunaux de commerce et de première instance',
                'villes' => 'Liste des villes utilisées dans les dossiers',
                'nationalites' => 'Nationalités des associés',
                'lieux-naissance' => 'Lieux de naissance des associés',
                'adresses' => 'Sièges sociaux et adresses types',
                'qualites-associe' => 'Qualités (gérant, président...)',
                'fonctions' => 'Fonctions des collaborateurs',
                'activites_statuts' => "Activités Statuts (libres) — dossiers Création / Cession / PV AGO",
                'activites-ompic' => 'Activités normalisées OMPIC (NMA 2010) — domiciliation',
                default => '',
            }; ?>
            <?php config_hub_card($page, $label, $icon, $sub, $counts[$page] ?? 0); ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>