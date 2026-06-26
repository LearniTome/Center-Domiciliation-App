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

    <div class="card" style="padding:16px;margin-bottom:16px">
        <h4>Informations de la societe</h4>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Raison sociale</span>
                <span class="info-value"><?= e($socForCalc['societe_raison_sociale'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Forme juridique</span>
                <span class="info-value"><?= e($socForCalc['societe_forme_juridique'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Capital social</span>
                <span class="info-value"><?= $rsFmt($calc['capital']) ?> DH</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date AGO</span>
                <span class="info-value"><?php if (!empty($wizard['date_ago'])): $dt = date_create($wizard['date_ago']); echo $dt ? $dt->format('d/m/Y') : e($wizard['date_ago']); endif; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Exercice clos le</span>
                <span class="info-value"><?= e($wizard['exercice_clos'] ?? '-') ?></span>
            </div>
        </div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:16px">
        <h4>Resultat et affectation</h4>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Resultat net</span>
                <span class="info-value <?= $calc['is_benefice'] ? '' : 'text-danger' ?>"><?= $rsFmt($calc['resultat_net']) ?> DH</span>
            </div>
            <div class="info-item">
                <span class="info-label">Affectation</span>
                <span class="info-value"><?= $calc['affectation'] === 'profit_distribution' ? 'Distribution de dividendes' : ($calc['affectation'] === 'loss_carryforward' ? 'Report a nouveau' : 'Imputation sur reserves') ?></span>
            </div>
            <?php if ($calc['RL_dotation'] > 0): ?>
            <div class="info-item">
                <span class="info-label">Dotation reserve legale</span>
                <span class="info-value"><?= $rsFmt($calc['RL_dotation']) ?> DH</span>
            </div>
            <?php endif; ?>
            <?php if ($calc['dividende_brut'] > 0): ?>
            <div class="info-item">
                <span class="info-label">Dividende brut</span>
                <span class="info-value"><?= $rsFmt($calc['dividende_brut']) ?> DH</span>
            </div>
            <div class="info-item">
                <span class="info-label">TPA (10%)</span>
                <span class="info-value"><?= $rsFmt($calc['tpa']) ?> DH</span>
            </div>
            <div class="info-item">
                <span class="info-label">Dividende net</span>
                <span class="info-value"><?= $rsFmt($calc['dividende_net']) ?> DH</span>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="info-label">Report a nouveau final</span>
                <span class="info-value"><?= $rsFmt(max(0, $calc['report_nouveau'])) ?> DH</span>
            </div>
        </div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:16px">
        <h4>Resolutions (<?= count($resolutions) ?>)</h4>
        <?php foreach ($resolutions as $i => $r): ?>
        <div style="margin-bottom:12px;padding:10px;background:var(--bg-secondary);border-radius:6px">
            <strong>Resolution <?= $i + 1 ?> : <?= e($r['title'] ?? '') ?></strong>
            <p style="margin:4px 0 0;white-space:pre-wrap;font-size:0.85rem;line-height:1.5"><?= e(mb_substr($r['content'] ?? '', 0, 300)) ?><?= mb_strlen($r['content'] ?? '') > 300 ? '...' : '' ?></p>
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
