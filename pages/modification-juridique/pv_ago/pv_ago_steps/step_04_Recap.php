<?php
declare(strict_types=1);

if (is_post() && $step === 4) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 3]);
    }
    if ($navAction === 'save') {
        // Save resolutions from saved session or current
        if (empty($wizard['resolutions'])) {
            set_flash('error', 'Veuillez d\'abord enregistrer les resolutions a l\'etape 3.');
            redirect_to('pv_ago', ['step' => 4]);
        }
        redirect_to('pv_ago', ['step' => 5]);
    }
    redirect_to('pv_ago', ['step' => 5]);
}

if ($step === 4):
    $socForCalc = $selectedSociete ?: ($wizard['societe'] ?? []);
    $calcResult = pv_ago_calculs($wizard, $socForCalc);
    $calc = $calcResult['calculs'];
    $rsFmt = $calc['rsFmt'];
    $resolutions = $wizard['resolutions'] ?? $calcResult['resolutions'];
?>
<div class="stack">
    <div class="section-header">
        <h2>Recapitulatif du PV AGO</h2>
    </div>

    <div class="card recap-card">
        <h4>Informations de la societe</h4>
        <div class="info-grid">
            <div><span>Raison sociale</span><strong><?= e($socForCalc['societe_raison_sociale'] ?? '-') ?></strong></div>
            <div><span>Forme juridique</span><strong><?= e($socForCalc['societe_forme_juridique'] ?? '-') ?></strong></div>
            <div><span>Capital social</span><strong><?= $rsFmt($calc['capital']) ?> DH</strong></div>
            <div><span>Date AGO</span><strong><?php if (!empty($wizard['date_ago'])): $dt = date_create($wizard['date_ago']); echo $dt ? $dt->format('d/m/Y') : e($wizard['date_ago']); endif; ?></strong></div>
            <div><span>Exercice clos le</span><strong><?= e($wizard['exercice_clos'] ?? '-') ?></strong></div>
        </div>
    </div>

    <div class="card recap-card">
        <h4>Resultat et affectation</h4>
        <div class="info-grid">
            <div><span>Resultat net</span><strong class="<?= $calc['is_benefice'] ? '' : 'text-danger' ?>"><?= $rsFmt($calc['resultat_net']) ?> DH</strong></div>
            <div><span>Affectation</span><strong><?= $calc['affectation'] === 'profit_distribution' ? 'Distribution de dividendes' : ($calc['affectation'] === 'loss_carryforward' ? 'Report a nouveau' : 'Imputation sur reserves') ?></strong></div>
            <?php if ($calc['RL_dotation'] > 0): ?>
            <div><span>Dotation reserve legale</span><strong><?= $rsFmt($calc['RL_dotation']) ?> DH</strong></div>
            <?php endif; ?>
            <?php if ($calc['dividende_brut'] > 0): ?>
            <div><span>Dividende brut</span><strong><?= $rsFmt($calc['dividende_brut']) ?> DH</strong></div>
            <div><span>TPA (10%)</span><strong><?= $rsFmt($calc['tpa']) ?> DH</strong></div>
            <div><span>Dividende net</span><strong><?= $rsFmt($calc['dividende_net']) ?> DH</strong></div>
            <?php endif; ?>
            <div><span>Report a nouveau final</span><strong><?= $rsFmt(max(0, $calc['report_nouveau'])) ?> DH</strong></div>
        </div>
    </div>

    <div class="card recap-card">
        <h4>Resolutions (<?= count($resolutions) ?>)</h4>
        <?php foreach ($resolutions as $i => $r): ?>
        <div class="recap-resolution">
            <strong>Resolution <?= $i + 1 ?> : <?= e($r['title'] ?? '') ?></strong>
            <p><?= e(mb_substr($r['content'] ?? '', 0, 300)) ?><?= mb_strlen($r['content'] ?? '') > 300 ? '...' : '' ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <form method="post" class="form">
        <?= csrf_input() ?>
        <div class="table-actions">
            <button type="submit" name="nav_action" value="back" class="btn btn-back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button type="submit" name="nav_action" value="save" class="btn btn-next"><span class="material-symbols-outlined">check</span> Generer le PV AGO</button>
        </div>
    </form>
</div>
<?php endif; ?>
