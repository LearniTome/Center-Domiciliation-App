<?php

declare(strict_types=1);

$defaultsFile = __DIR__ . '/../config/defaults.json';

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
            redirect_to('defaults', ['tab' => $section]);
        }
    }

    if ($action === 'reset') {
        $section = $_POST['section'] ?? $tab;
        if (isset($defaultSections[$section])) {
            $defaults[$section] = [];
            file_put_contents($defaultsFile, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            set_flash('success', 'Section reinitialisee.');
            redirect_to('defaults', ['tab' => $section]);
        }
    }

    if ($action === 'reset-all') {
        file_put_contents($defaultsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        set_flash('success', 'Toutes les valeurs par defaut ont ete reinitialisees.');
        redirect_to('defaults');
    }
}

$currentSection = $tab;
$sectionDefaults = $defaults[$currentSection] ?? [];

$fieldLabels = [
    'societe' => [
        'societe_dossier' => 'N° dossier domiciliation',
        'societe_forme_juridique' => 'Forme juridique',
        'societe_capital' => 'Capital social',
        'societe_part_social' => 'Nombre de parts sociales',
        'societe_valeur_nominale' => 'Valeur nominale',
        'societe_adresse_siege' => 'Adresse siege social',
        'societe_tribunal' => 'Tribunal competent',
        'societe_type_generation' => 'Type de generation',
        'societe_procedure_creation' => 'Procedure de creation',
        'societe_mode_depot' => 'Mode de depot',
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
            <h2>Valeurs par defaut</h2>
            <p class="help-text">Personnaliser les valeurs pre-remplies dans les formulaires.</p>
        </div>
        <div style="display:flex;gap:6px">
            <form method="post" style="display:inline" onsubmit="return confirm('Reinitialiser toutes les sections ?')">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="reset-all">
                <button type="submit" class="btn btn-cancel"><span class="mdi mdi-restore"></span> Tout reinitialiser</button>
            </form>
            <a class="btn btn-back" href="<?= e(app_url('configuration')) ?>"><span class="mdi mdi-arrow-left"></span> Retour</a>
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
                <button type="submit" class="btn btn-next"><span class="mdi mdi-content-save"></span> Enregistrer</button>
                <button type="submit" class="btn btn-cancel" formaction="<?= e(app_url('defaults', ['tab' => $currentSection])) ?>" formmethod="post" name="action" value="reset" onclick="return confirm('Reinitialiser cette section ?')">
                    <span class="mdi mdi-restore"></span> Reinitialiser
                </button>
            </div>
        </form>
    <?php else: ?>
        <p class="table-empty">Aucune valeur par defaut pour cette section.</p>
    <?php endif; ?>
</section>

<style>
.defaults-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0.75rem;
}
.defaults-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.defaults-field label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.defaults-field input {
    padding: 6px 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    background: var(--bg);
    color: var(--text);
    transition: border-color var(--transition);
}
.defaults-field input:focus {
    outline: none;
    border-color: var(--primary);
}
</style>
