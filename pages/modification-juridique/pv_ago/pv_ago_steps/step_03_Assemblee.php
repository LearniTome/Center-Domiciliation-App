<?php
declare(strict_types=1);

if (is_post() && $step === 3) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    $wizard['date_ago'] = $_POST['date_ago'] ?? date('Y-m-d');
    $wizard['heure_ago'] = $_POST['heure_ago'] ?? '10:00';
    $wizard['lieu_ago'] = $_POST['lieu_ago'] ?? 'au siege social';
    $wizard['president_nom'] = $_POST['president_nom'] ?? '';
    $wizard['president_qualite'] = $_POST['president_qualite'] ?? 'Gérant';
    $wizard['exercice_clos'] = $_POST['exercice_clos'] ?? '31/12/' . (date('Y') - 1);
    $wizard['total_parts'] = $_POST['total_parts'] ?? '';
    $wizard['parts_presentes'] = $_POST['parts_presentes'] ?? '';

    if ($navAction === 'back') {
        redirect_to('pv_ago_wizard', ['step' => 2]);
    }
    redirect_to('pv_ago_wizard', ['step' => 4]);
}

if ($step === 3):
?>
<div class="stack">
    <?php if ($selectedSociete): ?>
    <div class="soc-info">
        <div>
            <strong><?= e($selectedSociete['societe_raison_sociale']) ?></strong>
            <span class="soc-meta"><?= e($selectedSociete['societe_forme_juridique']) ?></span>
        </div>
        <a class="btn" href="<?= e(app_url('pv_ago_wizard', ['step' => 1])) ?>"><span class="material-symbols-outlined">edit</span> Modifier</a>
    </div>
    <?php endif; ?>

    <form method="post" class="form">
        <?= csrf_input() ?>

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
                    <input type="text" name="president_qualite" value="<?= e($wizard['president_qualite'] ?? 'Gérant') ?>">
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
