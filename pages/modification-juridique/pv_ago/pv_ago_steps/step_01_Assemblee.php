<?php
declare(strict_types=1);

if (is_post() && $step === 1) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($wizard['mode'] === 'nouvelle') {
        $soc = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with($k, 'societe_')) {
                $soc[$k] = $v;
            }
        }
        $wizard['societe'] = $soc;
        if (empty($soc['societe_raison_sociale'])) {
            set_flash('error', 'La raison sociale est obligatoire.');
            redirect_to('pv_ago', ['step' => 1]);
        }
    } elseif ($wizard['mode'] === 'existante') {
        $societeId = (int) ($_POST['societe_id'] ?? 0);
        if ($societeId > 0) {
            $wizard['societe_id'] = $societeId;
        } elseif (!$selectedSociete) {
            set_flash('error', 'Veuillez selectionner une societe.');
            redirect_to('pv_ago', ['step' => 1]);
        }
    }

    $wizard['date_ago'] = $_POST['date_ago'] ?? date('Y-m-d');
    $wizard['heure_ago'] = $_POST['heure_ago'] ?? '10:00';
    $wizard['lieu_ago'] = $_POST['lieu_ago'] ?? 'au siege social';
    $wizard['president_nom'] = $_POST['president_nom'] ?? '';
    $wizard['president_qualite'] = $_POST['president_qualite'] ?? 'Gerant';
    $wizard['exercice_clos'] = $_POST['exercice_clos'] ?? '31/12/' . (date('Y') - 1);
    $wizard['total_parts'] = $_POST['total_parts'] ?? '';
    $wizard['parts_presentes'] = $_POST['parts_presentes'] ?? '';

    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 0]);
    }
    redirect_to('pv_ago', ['step' => 2]);
}

if ($step === 1):
    $isNew = $wizard['mode'] === 'nouvelle';
    $soc = $wizard['societe'] ?? [];
    if (!$isNew && $selectedSociete) {
        $soc = $selectedSociete;
        $wizard['total_parts'] = $wizard['total_parts'] ?: $soc['societe_part_social'] ?? '';
    }
    $showSocieteSelector = !$isNew && !$selectedSociete;
?>
<div class="stack">
    <form method="post" class="form">
        <?= csrf_input() ?>

        <?php if ($isNew): ?>
        <fieldset class="card card-box card-societe">
            <legend>Nouvelle societe</legend>
            <div class="form-grid cols-2">
                <?php foreach ([
                    'societe_raison_sociale' => ['Raison sociale', 'text', true],
                    'societe_forme_juridique' => ['Forme juridique', 'select', true],
                    'societe_ice' => ['ICE', 'text'],
                    'societe_rc' => ['RC', 'text'],
                    'societe_if' => ['I.F.', 'text'],
                    'societe_capital' => ['Capital (DH)', 'text'],
                    'societe_part_social' => ['Nombre de parts', 'number'],
                    'societe_valeur_nominale' => ['Valeur nominale (DH)', 'text'],
                    'societe_ville' => ['Ville', 'select'],
                    'societe_tribunal' => ['Tribunal', 'select'],
                    'societe_email' => ['Email', 'email'],
                    'societe_telephone' => ['Telephone', 'tel'],
                ] as $field => $def):
                    $label = $def[0] ?? '';
                    $type = $def[1] ?? 'text';
                    $required = $def[2] ?? null;
                ?>
                <div class="field">
                    <span><?= e($label) ?></span>
                    <?php if ($type === 'select'): ?>
                        <?php $isForme = $field === 'societe_forme_juridique'; ?>
                        <select name="<?= e($field) ?>" <?= !empty($required) ? 'required' : '' ?>>
                            <option value="">--</option>
                            <?php foreach ($isForme ? $formesJuridiques : ($field === 'societe_ville' ? $villes : $tribunaux) as $opt): ?>
                                <?php $optVal = $isForme ? ($opt['forme_juridique'] ?? '') : ($field === 'societe_ville' ? ($opt['id'] ?? $opt['ville'] ?? '') : ($opt['tribunal'] ?? '')); ?>
                                <option value="<?= e($optVal) ?>" <?= ((string) ($soc[$field] ?? '') === (string) $optVal) ? 'selected' : '' ?>><?= e($optVal) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="<?= e($type) ?>" name="<?= e($field) ?>" value="<?= e($soc[$field] ?? '') ?>" <?= !empty($required) ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="field" style="grid-column:1/-1">
                <span>Adresse du siege</span>
                <textarea name="societe_adresse_siege" rows="2"><?= e($soc['societe_adresse_siege'] ?? '') ?></textarea>
            </div>
        </fieldset>
        <?php elseif ($selectedSociete): ?>
        <div class="soc-info">
            <div>
                <strong><?= e($selectedSociete['societe_raison_sociale']) ?></strong>
                <span class="soc-meta"><?= e($selectedSociete['societe_forme_juridique']) ?></span>
            </div>
            <a class="btn btn-back" href="<?= e(app_url('pv_ago', ['step' => 0])) ?>"><span class="material-symbols-outlined">swap_horiz</span> Changer</a>
        </div>
        <?php endif; ?>

        <?php if ($showSocieteSelector): ?>
        <fieldset class="card card-box card-select">
            <legend>Selectionner une societe</legend>
            <div class="field">
                <span>Societe existante</span>
                <select name="societe_id" required onchange="this.form.querySelector('.societe-details')?.classList.toggle('hidden', !this.value)">
                    <option value="">-- Choisir une societe --</option>
                    <?php foreach ($societesList as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['societe_raison_sociale']) ?> (<?= e($s['societe_forme_juridique']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <?php endif; ?>

        <fieldset class="card card-box card-assemblee">
            <legend>Assemblee</legend>
            <div class="form-grid cols-2">
                <div class="field">
                    <span>Date de l'assemblee</span>
                    <input type="date" name="date_ago" value="<?= e($wizard['date_ago'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="field">
                    <span>Heure</span>
                    <input type="text" name="heure_ago" value="<?= e($wizard['heure_ago'] ?? '10:00') ?>" placeholder="10:00">
                </div>
                <div class="field full-width">
                    <span>Lieu</span>
                    <input type="text" name="lieu_ago" value="<?= e($wizard['lieu_ago'] ?? 'au siege social') ?>">
                </div>
                <div class="field">
                    <span>President de seance (nom)</span>
                    <input type="text" name="president_nom" value="<?= e($wizard['president_nom'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Qualite du president</span>
                    <input type="text" name="president_qualite" value="<?= e($wizard['president_qualite'] ?? 'Gerant') ?>">
                </div>
                <div class="field">
                    <span>Exercice clos le</span>
                    <input type="text" name="exercice_clos" value="<?= e($wizard['exercice_clos'] ?? ('31/12/' . (date('Y') - 1))) ?>" placeholder="31/12/2025">
                </div>
                <div class="field">
                    <span>Nombre total de parts sociales</span>
                    <input type="number" name="total_parts" value="<?= e($wizard['total_parts'] ?? '') ?>" min="1" required>
                </div>
                <div class="field">
                    <span>Parts presentes / representees</span>
                    <input type="number" name="parts_presentes" value="<?= e($wizard['parts_presentes'] ?? ($wizard['total_parts'] ?? '')) ?>" min="1" required>
                </div>
            </div>
        </fieldset>

        <div class="table-actions">
            <button type="submit" name="nav_action" value="back" class="btn btn-back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button type="submit" name="nav_action" value="next" class="btn btn-next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </form>
</div>
<?php endif; ?>
