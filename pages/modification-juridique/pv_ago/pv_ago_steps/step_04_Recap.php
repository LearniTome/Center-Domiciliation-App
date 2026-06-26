<?php
declare(strict_types=1);

if (is_post() && $step === 4) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 3]);
    }
    redirect_to('pv_ago', ['step' => 5]);
}

if ($step === 4):
    $socForCalc = $selectedSociete ?: ($wizard['societe'] ?? []);
    $calcResult = pv_ago_calculs($wizard, $socForCalc);
    $calc = $calcResult['calculs'];
    $rsFmt = $calc['rsFmt'];
    $resolutions = !empty($wizard['resolutions']) ? $wizard['resolutions'] : $calcResult['resolutions'];
    $socName = e($socForCalc['societe_raison_sociale'] ?? '-');
    $dateAgo = '';
    if (!empty($wizard['date_ago'])) { $dt = date_create($wizard['date_ago']); $dateAgo = $dt ? $dt->format('d/m/Y') : e($wizard['date_ago']); }
    $capital = $rsFmt($calc['capital']) . ' DH';
    $resultat = ($calc['is_benefice'] ? '' : '-') . $rsFmt(abs($calc['resultat_net'])) . ' DH';
    $affectLabel = $calc['affectation'] === 'profit_distribution' ? 'Distribution de dividendes' : ($calc['affectation'] === 'loss_carryforward' ? 'Report a nouveau' : 'Imputation sur reserves');
    $reportFinal = ($calc['report_nouveau'] >= 0 ? '' : '-') . $rsFmt(abs($calc['report_nouveau'])) . ' DH';
?>
<div class="stack">
    <div class="section-header">
        <h2>Recapitulatif du PV AGO</h2>
    </div>

    <div class="step-4-controls table-actions" style="margin-bottom:12px">
        <button type="button" class="btn btn-info" onclick="window.print()">
            <span class="material-symbols-outlined">print</span> Imprimer
        </button>
        <button type="button" class="btn btn-info" id="btn-pdf-recap">
            <span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF
        </button>
        <a class="btn btn-back" href="<?= e(app_url('pv_ago', ['step' => 3])) ?>">
            <span class="material-symbols-outlined">edit</span> Modifier les resolutions
        </a>
    </div>

    <div class="recap-a4" id="recap-a4">
        <div class="recap-header">
            <h2>Proces-Verbal d'Assemblee Generale Ordinaire</h2>
            <p>Societe : <?= $socName ?> — Date AGO : <?= $dateAgo ?> — Exercice clos le : <?= e($wizard['exercice_clos'] ?? '-') ?></p>
        </div>

        <div class="recap-section">
            <h3>Informations de la societe</h3>
            <div class="recap-grid">
                <div class="item"><span class="label">Raison sociale</span><span class="value"><?= $socName ?></span></div>
                <div class="item"><span class="label">Forme juridique</span><span class="value"><?= e($socForCalc['societe_forme_juridique'] ?? '-') ?></span></div>
                <div class="item"><span class="label">Capital social</span><span class="value"><?= $capital ?></span></div>
                <div class="item"><span class="label">Nombre de parts</span><span class="value"><?= $calc['total_parts'] ?></span></div>
                <div class="item"><span class="label">Parts presentes / representees</span><span class="value"><?= $calc['parts_presentes'] ?> (<?= $calc['pct_presence'] ?>%)</span></div>
                <div class="item"><span class="label">President de seance</span><span class="value"><?= e($wizard['president_nom'] ?? '-') ?> (<?= e($wizard['president_qualite'] ?? '-') ?>)</span></div>
            </div>
        </div>

        <div class="recap-section">
            <h3>Resultat et affectation</h3>
            <div class="recap-grid">
                <div class="item">
                    <span class="label">Resultat net</span>
                    <span class="value <?= $calc['is_benefice'] ? 'badge-success' : 'badge-danger' ?>"><?= $resultat ?></span>
                </div>
                <div class="item"><span class="label">Affectation</span><span class="value"><?= $affectLabel ?></span></div>
                <?php if ($calc['report_debiteur'] > 0): ?>
                <div class="item">
                    <span class="label">Report a nouveau debiteur anterieur</span>
                    <span class="value badge-danger">-<?= $rsFmt($calc['report_debiteur']) ?> DH</span>
                </div>
                <?php endif; ?>
                <?php if ($calc['RL_dotation'] > 0): ?>
                <div class="item"><span class="label">Dotation reserve legale (5%)</span><span class="value"><?= $rsFmt($calc['RL_dotation']) ?> DH</span></div>
                <?php endif; ?>
                <?php if ($calc['reserve_statutaire'] > 0): ?>
                <div class="item"><span class="label">Dotation reserve statutaire</span><span class="value"><?= $rsFmt($calc['reserve_statutaire']) ?> DH</span></div>
                <?php endif; ?>
                <?php if ($calc['reserve_facultative'] > 0): ?>
                <div class="item"><span class="label">Dotation reserve facultative</span><span class="value"><?= $rsFmt($calc['reserve_facultative']) ?> DH</span></div>
                <?php endif; ?>
                <?php if ($calc['dividende_brut'] > 0): ?>
                <div class="item"><span class="label">Dividende brut</span><span class="value"><?= $rsFmt($calc['dividende_brut']) ?> DH</span></div>
                <div class="item"><span class="label">TPA (10%)</span><span class="value"><?= $rsFmt($calc['tpa']) ?> DH</span></div>
                <div class="item"><span class="label">Dividende net</span><span class="value"><?= $rsFmt($calc['dividende_net']) ?> DH</span></div>
                <?php endif; ?>
                <?php if ($calc['perte_prelevement'] > 0): ?>
                <div class="item"><span class="label">Prelevement sur reserves</span><span class="value"><?= $rsFmt($calc['perte_prelevement']) ?> DH</span></div>
                <?php endif; ?>
                <div class="item">
                    <span class="label">Report a nouveau final</span>
                    <span class="value <?= $calc['report_nouveau'] >= 0 ? 'badge-success' : 'badge-danger' ?>"><?= $reportFinal ?></span>
                </div>
            </div>
        </div>

        <div class="recap-section">
            <h3>Resolutions (<?= count($resolutions) ?>)</h3>
            <?php foreach ($resolutions as $i => $r): ?>
            <div class="recap-associe">
                <div class="associe-num">Resolution <?= $i + 1 ?> : <?= e($r['title'] ?? '') ?></div>
                <div style="font-size:0.75rem;line-height:1.5;white-space:pre-wrap;margin-top:4px;color:var(--text)"><?= e($r['content'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="post" class="footer-actions">
        <?= csrf_input() ?>
        <button type="submit" name="nav_action" value="back" class="btn btn-back">
            <span class="material-symbols-outlined">arrow_back</span> Retour
        </button>
        <button type="submit" name="nav_action" value="save" class="btn btn-next">
            <span class="material-symbols-outlined">check</span> Generer le PV AGO
        </button>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function(){
    'use strict';
    var btnPdf = document.getElementById('btn-pdf-recap');
    if (!btnPdf) return;
    btnPdf.addEventListener('click', function(){
        var el = document.getElementById('recap-a4');
        el.classList.add('recap-pdf-mode');
        var opt = {
            margin:        [10, 10, 10, 10],
            filename:      'PV-AGO_<?= e(preg_replace('/[^a-zA-Z0-9]/', '_', strip_tags($socName))) ?>_<?= e(date('Y-m-d')) ?>.pdf',
            image:         { type: 'jpeg', quality: 0.98 },
            html2canvas:   { scale: 2, letterRendering: true },
            jsPDF:         { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(el).save().then(function(){
            el.classList.remove('recap-pdf-mode');
        });
    });
})();
</script>
<?php endif; ?>
