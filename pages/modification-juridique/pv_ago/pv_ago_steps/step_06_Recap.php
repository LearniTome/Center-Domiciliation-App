<?php
declare(strict_types=1);

if (is_post() && $step === 6) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('pv_ago_wizard', ['step' => 5]);
    }
    redirect_to('pv_ago_wizard', ['step' => 7]);
}

if ($step === 6):
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
    <div class="step-4-controls table-actions" style="margin-bottom:12px">
        <button type="button" class="btn btn-info" onclick="window.print()">
            <span class="material-symbols-outlined">print</span> Imprimer
        </button>
        <button type="button" class="btn btn-info" id="btn-pdf-recap">
            <span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF
        </button>
        <a class="btn btn-back" href="<?= e(app_url('pv_ago_wizard', ['step' => 5])) ?>">
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
            <table class="recap-grid">
                <tr>
                    <td class="item"><span class="label">Raison sociale</span><span class="value"><?= $socName ?></span></td>
                    <td class="item"><span class="label">Forme juridique</span><span class="value"><?= e($socForCalc['societe_forme_juridique'] ?? '-') ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Capital social</span><span class="value"><?= $capital ?></span></td>
                    <td class="item"><span class="label">Nombre de parts</span><span class="value"><?= $calc['total_parts'] ?></span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Parts presentes / representees</span><span class="value"><?= $calc['parts_presentes'] ?> (<?= $calc['pct_presence'] ?>%)</span></td>
                    <td class="item"><span class="label">President de seance</span><span class="value"><?= e($wizard['president_nom'] ?? '-') ?> (<?= e($wizard['president_qualite'] ?? '-') ?>)</span></td>
                </tr>
            </table>
        </div>

        <div class="recap-section">
            <h3>Resultat et affectation</h3>
            <table class="recap-grid">
                <tr>
                    <td class="item">
                        <span class="label">Resultat net</span>
                        <span class="value <?= $calc['is_benefice'] ? 'badge-success' : 'badge-danger' ?>"><?= $resultat ?></span>
                    </td>
                    <td class="item"><span class="label">Affectation</span><span class="value"><?= $affectLabel ?></span></td>
                </tr>
                <?php if ($calc['report_debiteur'] > 0): ?>
                <tr>
                    <td class="item" colspan="2">
                        <span class="label">Report a nouveau debiteur anterieur</span>
                        <span class="value badge-danger">-<?= $rsFmt($calc['report_debiteur']) ?> DH</span>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($calc['RL_dotation'] > 0): ?>
                <tr>
                    <td class="item<?= $calc['reserve_statutaire'] > 0 ? '' : ' full' ?>"<?= $calc['reserve_statutaire'] > 0 ? '' : ' colspan="2"' ?>><span class="label">Dotation reserve legale (5%)</span><span class="value"><?= $rsFmt($calc['RL_dotation']) ?> DH</span></td>
                <?php if ($calc['reserve_statutaire'] > 0): ?>
                    <td class="item"><span class="label">Dotation reserve statutaire</span><span class="value"><?= $rsFmt($calc['reserve_statutaire']) ?> DH</span></td>
                <?php endif; ?>
                </tr>
                <?php endif; ?>
                <?php if ($calc['reserve_facultative'] > 0): ?>
                <tr>
                    <td class="item"><span class="label">Dotation reserve facultative</span><span class="value"><?= $rsFmt($calc['reserve_facultative']) ?> DH</span></td>
                </tr>
                <?php endif; ?>
                <?php if ($calc['dividende_brut'] > 0): ?>
                <tr>
                    <td class="item"><span class="label">Dividende brut</span><span class="value"><?= $rsFmt($calc['dividende_brut']) ?> DH</span></td>
                    <td class="item"><span class="label">TPA (10%)</span><span class="value"><?= $rsFmt($calc['tpa']) ?> DH</span></td>
                </tr>
                <tr>
                    <td class="item"><span class="label">Dividende net</span><span class="value"><?= $rsFmt($calc['dividende_net']) ?> DH</span></td>
                </tr>
                <?php endif; ?>
                <?php if ($calc['perte_prelevement'] > 0): ?>
                <tr>
                    <td class="item"><span class="label">Prelevement sur reserves</span><span class="value"><?= $rsFmt($calc['perte_prelevement']) ?> DH</span></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="item">
                        <span class="label">Report a nouveau final</span>
                        <span class="value <?= $calc['report_nouveau'] >= 0 ? 'badge-success' : 'badge-danger' ?>"><?= $reportFinal ?></span>
                    </td>
                </tr>
            </table>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function(){
    'use strict';
    var btnPdf = document.getElementById('btn-pdf-recap');
    if (!btnPdf) return;
    btnPdf.addEventListener('click', async function(){
        var el = document.getElementById('recap-a4');
        el.classList.add('recap-pdf-mode');
        try {
            await document.fonts.ready;
            var canvas = await window.html2canvas(el, { scale: 2, letterRendering: true, backgroundColor: '#ffffff' });
            var jsPDF = window.jspdf.jsPDF;
            var pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
            var margin = 10;
            var pageW = 210 - margin * 2;
            var pageH = 297 - margin * 2;
            var scaleMm = canvas.width / pageW;
            var fullH = canvas.height / scaleMm;
            var imgW = canvas.width;
            var srcY = 0;
            var y = margin;
            var first = true;
            while (true) {
                var chunkH = Math.min(fullH - (srcY / scaleMm), pageH);
                var slice = document.createElement('canvas');
                slice.width = imgW;
                slice.height = Math.round(chunkH * scaleMm);
                slice.getContext('2d').drawImage(canvas, 0, srcY, imgW, slice.height, 0, 0, imgW, slice.height);
                if (!first) { pdf.addPage(); y = margin; }
                pdf.addImage(slice.toDataURL('image/jpeg', 0.98), 'JPEG', margin, y, pageW, chunkH);
                srcY += slice.height;
                if (srcY >= canvas.height) break;
                first = false;
            }
            pdf.save('PV-AGO_<?= e(preg_replace('/[^a-zA-Z0-9]/', '_', strip_tags($socName))) ?>_<?= e(date('Y-m-d')) ?>.pdf');
        } finally {
            el.classList.remove('recap-pdf-mode');
        }
    });
})();
</script>
<?php endif; ?>
