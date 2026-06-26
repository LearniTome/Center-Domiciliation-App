<?php
declare(strict_types=1);

if (is_post() && $step === 1) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 0]);
    }

    if ($wizard['mode'] === 'nouvelle') {
        $soc = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with($k, 'societe_')) {
                $soc[$k] = $v;
            }
        }
        $wizard['societe'] = $soc;
        $wizard['societe_id'] = 0;
        if (empty($soc['societe_raison_sociale'])) {
            set_flash('error', 'La raison sociale est obligatoire.');
            redirect_to('pv_ago', ['step' => 1]);
        }
    } elseif ($wizard['mode'] === 'existante') {
        $societeId = (int) ($_POST['societe_id'] ?? 0);
        if ($societeId > 0) {
            $wizard['societe_id'] = $societeId;
            $wizard['societe'] = [];
        } else {
            set_flash('error', 'Veuillez selectionner une societe.');
            redirect_to('pv_ago', ['step' => 1]);
        }
    }

    redirect_to('pv_ago', ['step' => 2]);
}

if ($step === 1):
    $isNew = $wizard['mode'] === 'nouvelle';
    $soc = $wizard['societe'] ?? [];
    if (!$isNew && $selectedSociete) {
        $soc = $selectedSociete;
    }
    $showSocieteSelector = !$isNew && !$selectedSociete;
?>
<div class="stack">
    <form method="post" class="form" id="wizard-step1">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="1">

        <?php if ($isNew): ?>
        <fieldset class="card card-box card-societe">
            <legend>Identifiants</legend>
            <div class="form-grid cols-2">
                <div class="field">
                    <span>Raison sociale</span>
                    <input name="societe_raison_sociale" required value="<?= e($soc['societe_raison_sociale'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Forme juridique</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="societe_forme_juridique" style="flex:1" required>
                            <option value="">Selectionner</option>
                            <?php foreach ($formesJuridiques as $opt): ?>
                                <option value="<?= e($opt['forme_juridique'] ?? '') ?>" <?= ((string) ($soc['societe_forme_juridique'] ?? '') === (string) ($opt['forme_juridique'] ?? '')) ? 'selected' : '' ?>><?= e($opt['forme_juridique'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-icon" data-quick-create-btn="formes-juridiques" title="Ajouter une forme juridique"><span class="material-symbols-outlined">add</span></button>
                    </div>
                </div>
                <div class="field">
                    <span>ICE</span>
                    <input name="societe_ice" value="<?= e($soc['societe_ice'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>RC</span>
                    <input name="societe_rc" value="<?= e($soc['societe_rc'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>I.F.</span>
                    <input name="societe_if" value="<?= e($soc['societe_if'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Ville</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="societe_ville" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($villes as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= ((string) ($soc['societe_ville'] ?? '') === (string) $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-icon" data-quick-create-btn="villes" title="Ajouter une ville"><span class="material-symbols-outlined">add</span></button>
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="card card-box" style="border-left-color: var(--success)">
            <legend>Capital</legend>
            <div class="form-grid cols-3">
                <div class="field">
                    <span>Capital (DH)</span>
                    <input type="text" name="societe_capital" value="<?= e($soc['societe_capital'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Nombre de parts</span>
                    <input type="number" name="societe_part_social" value="<?= e($soc['societe_part_social'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Valeur nominale (DH)</span>
                    <input type="text" name="societe_valeur_nominale" value="<?= e($soc['societe_valeur_nominale'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <fieldset class="card card-box" style="border-left-color: var(--warning)">
            <legend>Adresse</legend>
            <div class="form-grid cols-2">
                <div class="field full-width">
                    <span>Adresse du siege</span>
                    <textarea name="societe_adresse_siege" rows="2"><?= e($soc['societe_adresse_siege'] ?? '') ?></textarea>
                </div>
                <div class="field">
                    <span>Tribunal</span>
                    <select name="societe_tribunal">
                        <option value="">Selectionner</option>
                        <?php foreach ($tribunaux as $opt): ?>
                            <option value="<?= e($opt['tribunal'] ?? '') ?>" <?= ((string) ($soc['societe_tribunal'] ?? '') === (string) ($opt['tribunal'] ?? '')) ? 'selected' : '' ?>><?= e($opt['tribunal'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset class="card card-box" style="border-left-color: var(--info)">
            <legend>Contact</legend>
            <div class="form-grid cols-2">
                <div class="field">
                    <span>Email</span>
                    <input type="email" name="societe_email" value="<?= e($soc['societe_email'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Telephone</span>
                    <input name="societe_telephone" value="<?= e($soc['societe_telephone'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <?php elseif ($selectedSociete): ?>
        <div class="soc-info">
            <div>
                <strong><?= e($selectedSociete['societe_raison_sociale']) ?></strong>
                <span class="soc-meta"><?= e($selectedSociete['societe_forme_juridique']) ?></span>
            </div>
            <a class="btn" href="<?= e(app_url('pv_ago', ['step' => 0])) ?>"><span class="material-symbols-outlined">swap_horiz</span> Changer</a>
        </div>
        <?php endif; ?>

        <?php if ($showSocieteSelector): ?>
        <fieldset class="card card-box card-select">
            <legend>Selectionner une societe</legend>
            <div class="field">
                <span>Societe existante</span>
                <select name="societe_id" required>
                    <option value="">-- Choisir une societe --</option>
                    <?php foreach ($societesList as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['societe_raison_sociale']) ?> (<?= e($s['societe_forme_juridique']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <?php endif; ?>

        <div class="table-actions">
            <button type="submit" name="nav_action" value="back" class="btn btn-back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button type="submit" name="nav_action" value="next" class="btn btn-next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </form>
</div>

<?php
$quickCreateModalKey = 'formes-juridiques';
$quickCreateTitle = 'Nouvelle forme juridique';
$quickCreateTable = 'ref_formes_juridiques';
$quickCreateFields = [
    ['name' => 'forme_juridique', 'label' => 'Forme juridique', 'type' => 'text', 'required' => true],
    ['name' => 'template_folder', 'label' => 'Dossier template', 'type' => 'text', 'placeholder' => 'Optionnel'],
];
require __DIR__ . '/../../../../includes/quick_create_modal.php';

$quickCreateModalKey = 'villes';
$quickCreateTitle = 'Nouvelle ville';
$quickCreateTable = 'ref_ste_adresses';
$quickCreateFields = [
    ['name' => 'adresse', 'label' => 'Ville', 'type' => 'text', 'required' => true],
];
require __DIR__ . '/../../../../includes/quick_create_modal.php';
?>
<?php endif; ?>
