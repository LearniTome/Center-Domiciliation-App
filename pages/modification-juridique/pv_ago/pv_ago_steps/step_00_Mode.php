<?php
declare(strict_types=1);

if (is_post() && $step === 0) {
    verify_csrf();
    $mode = $_POST['mode'] ?? '';
    if ($mode === 'existante') {
        $societeId = (int) ($_POST['societe_id'] ?? 0);
        if ($societeId <= 0) {
            set_flash('error', 'Selectionnez une societe existante.');
            redirect_to('pv_ago', ['step' => 0]);
        }
        $wizard['mode'] = 'existante';
        $wizard['societe_id'] = $societeId;
        $wizard['societe'] = [];
        redirect_to('pv_ago', ['step' => 1]);
    }
    if ($mode === 'nouvelle') {
        $wizard['mode'] = 'nouvelle';
        $wizard['societe_id'] = 0;
        redirect_to('pv_ago', ['step' => 1]);
    }
    set_flash('error', 'Choisissez un mode.');
    redirect_to('pv_ago', ['step' => 0]);
}

if ($step === 0):
?>
<div class="stack">
    <div class="section-header">
        <h2>PV d'Assemblee Generale Ordinaire Annuelle</h2>
    </div>
    <p style="color:var(--text-secondary)">Selectionnez la societe concernee par le PV AGO.</p>

    <form method="post" class="form" style="max-width:500px">
        <?= csrf_input() ?>
        <div class="form-group">
            <div class="field">
                <span>Societe existante</span>
                <select name="societe_id" style="margin-bottom:8px">
                    <option value="">-- Choisir une societe --</option>
                    <?php foreach ($societesList as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['societe_raison_sociale']) ?> (<?= e($s['societe_forme_juridique']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="mode" value="existante" class="btn btn-next"><span class="material-symbols-outlined">check</span> Utiliser cette societe</button>
            </div>
        </div>
        <hr style="margin:16px 0;border:none;border-top:1px solid var(--line)">
        <div class="form-group">
            <button type="submit" name="mode" value="nouvelle" class="btn btn-info"><span class="material-symbols-outlined">add_circle</span> Nouvelle societe</button>
        </div>
        <a class="btn btn-back" href="<?= e(app_url('pvag')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour aux PV AGO</a>
    </form>
</div>
<?php endif; ?>
