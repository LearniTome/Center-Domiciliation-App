<?php

declare(strict_types=1);

$defaultsFile = __DIR__ . '/../../config/defaults.json';

$defaultSections = [
    'societe' => 'Societe',
    'associe' => 'Associe',
    'contrat' => 'Contrat',
    'collaborateur' => 'Collaborateur',
];

$tab = $_GET['tab'] ?? 'societe';
if (!isset($defaultSections[$tab])) {
    $tab = 'societe';
}

$defaults = load_defaults();

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $section = $_POST['section'] ?? $tab;
        $values = $_POST['values'] ?? [];

        if (isset($defaultSections[$section]) && is_array($values)) {
            foreach ($values as $key => $value) {
                $defaults[$section][$key] = trim((string) $value);
            }
            file_put_contents($defaultsFile, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            set_flash('success', 'Valeurs par defaut enregistrees.');
            log_activity($pdo, 'update', 'defaults', null, 'Section: ' . $section);
            redirect_to('defaults', ['tab' => $section]);
        }
    }

    if ($action === 'reset') {
        $section = $_POST['section'] ?? $tab;
        if (isset($defaultSections[$section])) {
            $defaults[$section] = [];
            file_put_contents($defaultsFile, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            set_flash('success', 'Section reinitialisee.');
            log_activity($pdo, 'reset', 'defaults', null, 'Section: ' . $section);
            redirect_to('defaults', ['tab' => $section]);
        }
    }

    if ($action === 'reset-all') {
        file_put_contents($defaultsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        set_flash('success', 'Toutes les valeurs par defaut ont ete reinitialisees.');
        log_activity($pdo, 'reset', 'defaults', null, 'Toutes les sections');
        redirect_to('defaults');
    }
}

$currentSection = $tab;
$sectionDefaults = $defaults[$currentSection] ?? [];

$fieldLabels = [
    'societe' => [
        'societe_dossier' => 'N° dossier domiciliation',
        'societe_dossier_creation' => 'N° dossier creation',
        'societe_forme_juridique' => 'Forme juridique',
        'societe_capital' => 'Capital social',
        'societe_part_social' => 'Nombre de parts sociales',
        'societe_valeur_nominale' => 'Valeur nominale',
        'societe_adresse_siege' => 'Adresse siege social',
        'societe_tribunal' => 'Tribunal competent',
        'societe_type_generation' => 'Type de generation',
        'societe_procedure_creation' => 'Procedure de creation',
        'societe_mode_depot' => 'Mode de depot',
        'societe_tribunal_type' => 'Type de tribunal',
    ],
    'associe' => [
        'associe_nationalite' => 'Nationalite',
        'associe_parts' => 'Nombre de parts',
        'associe_est_gerant' => 'Est gerant',
        'associe_qualite' => 'Qualite associe',
    ],
    'contrat' => [
        'contrat_type' => 'Type de contrat',
        'contrat_type_domiciliation' => 'Type domiciliation',
        'contrat_duree_mois' => 'Duree (mois)',
        'contrat_type_renouvellement' => 'Type de renouvellement',
        'contrat_tva_pourcent' => 'Taux TVA %',
        'contrat_loyer_ht' => 'Loyer mensuel HT',
        'contrat_renouv_tva_pourcent' => 'TVA renouvellement %',
        'contrat_renouv_loyer_ht' => 'Loyer renouvellement HT',
        'contrat_statut' => 'Statut',
    ],
    'collaborateur' => [
        'collaborateur_type' => 'Type de collaborateur',
        'collaborateur_code' => 'Code collaborateur',
        'statut' => 'Statut',
    ],
];

$currentLabels = $fieldLabels[$currentSection] ?? [];
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Personnaliser les valeurs pre-remplies dans les formulaires.</p>
        </div>
        <div style="display:flex;gap:6px">
            <form method="post" style="display:inline" onsubmit="return confirm('Reinitialiser toutes les sections ?')">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="reset-all">
                <button type="submit" class="btn btn-cancel"><span class="material-symbols-outlined">restore</span> Tout reinitialiser</button>
            </form>
            <a class="btn btn-back" href="<?= e(app_url('configuration')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        </div>
    </div>

    <div class="tabs" style="margin-bottom:1rem">
        <?php foreach ($defaultSections as $key => $label): ?>
            <a class="tab <?= $key === $currentSection ? 'active' : '' ?>" href="<?= e(app_url('defaults', ['tab' => $key])) ?>">
                <?= e($label) ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:var(--primary);color:#fff;font-size:0.65rem;margin-left:4px;line-height:1;vertical-align:middle">
                    <?= count($defaults[$key] ?? []) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($sectionDefaults): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="section" value="<?= e($currentSection) ?>">

            <div class="defaults-grid">
                <?php foreach ($sectionDefaults as $key => $value): ?>
                    <div class="defaults-field">
                        <label for="field-<?= e($key) ?>">
                            <?= e($currentLabels[$key] ?? $key) ?>
                        </label>
                        <input
                            id="field-<?= e($key) ?>"
                            name="values[<?= e($key) ?>]"
                            value="<?= e((string) $value) ?>"
                            placeholder="<?= e($currentLabels[$key] ?? $key) ?>"
                        >
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:6px;margin-top:0.5rem">
                <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">save</span> Enregistrer</button>
                <button type="submit" class="btn btn-cancel" formaction="<?= e(app_url('defaults', ['tab' => $currentSection])) ?>" formmethod="post" name="action" value="reset" onclick="return confirm('Reinitialiser cette section ?')">
                    <span class="material-symbols-outlined">restore</span> Reinitialiser
                </button>
            </div>
        </form>
    <?php else: ?>
        <p class="table-empty">Aucune valeur par defaut pour cette section.</p>
    <?php endif; ?>
</section>


