<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

// L'annulation quitte l'assistant : le message d'avertissement depend du type
// de dossier en cours (Creation d'une societe ou Domiciliation).
$wizardTypeGen = (string) ($societeData['societe_type_generation'] ?? 'domiciliation');
$wizardDossierLabel = $wizardTypeGen === 'creation' ? 'une société' : 'une domiciliation';
$wizardConfirmCreation = 'Vous avez quitté l\'assistant de création d\'une société. Les données saisies seront perdues.';
$wizardConfirmDomiciliation = 'Vous avez quitté l\'assistant de création d\'une domiciliation. Les données saisies seront perdues.';
$wizardConfirmMessage = $wizardTypeGen === 'creation' ? $wizardConfirmCreation : $wizardConfirmDomiciliation;
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Parcours guide: societe, associes, puis contrat, dans un seul flux.</p>
        </div>
        <div class="table-actions">
            <a class="btn btn-cancel" id="wizard-cancel-link"
               href="<?= e(app_url('creation', ['cancel' => '1'])) ?>"
               data-confirm="<?= e($wizardConfirmMessage) ?>"
               data-confirm-title="Quitter l'assistant ?"
               data-confirm-ok="Quitter"
               data-confirm-cancel="Rester dans l'assistant"
               data-confirm-creation="<?= e($wizardConfirmCreation) ?>"
               data-confirm-domiciliation="<?= e($wizardConfirmDomiciliation) ?>"><span class="material-symbols-outlined">cancel</span> Annuler</a>
            <a class="btn btn-back" href="<?= e(app_url('creation', ['reset' => '1'])) ?>" data-confirm="Reinitialiser cet assistant ? Les données saisies seront perdues." data-confirm-title="Reinitialiser l'assistant ?" data-confirm-ok="Reinitialiser"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
        </div>
    </div>

    <div class="wizard-steps" id="wizard-steps-top">
        <div class="wizard-step <?= $step > 1 ? 'done' : ($step === 1 ? 'active' : 'waiting') ?>">
            <strong>Etape 1</strong>
            <span>Societe</span>
        </div>
        <div class="wizard-step <?= $step > 2 ? 'done' : ($step === 2 ? 'active' : 'waiting') ?>">
            <strong>Etape 2</strong>
            <span>Associes</span>
        </div>
        <div class="wizard-step <?= $step > 3 ? 'done' : ($step === 3 ? 'active' : 'waiting') ?>">
            <strong>Etape 3</strong>
            <span>Contrat</span>
        </div>
        <div class="wizard-step <?= $step > 4 ? 'done' : ($step === 4 ? 'active' : 'waiting') ?>">
            <strong>Etape 4</strong>
            <span>Recapitulatif</span>
        </div>
        <div class="wizard-step <?= $step > 5 ? 'done' : ($step === 5 ? 'active' : 'waiting') ?>">
            <strong>Etape 5</strong>
            <span>Documents</span>
        </div>
        <div class="wizard-step <?= $step > 6 ? 'done' : ($step === 6 ? 'active' : 'waiting') ?>">
            <strong>Etape 6</strong>
            <span>Generation</span>
        </div>
    </div>

    <?php
$aiSuggestions = $_SESSION['creation_wizard']['ai_suggestions'] ?? null;
if ($aiSuggestions !== null) {
    unset($_SESSION['creation_wizard']['ai_suggestions']);
}
?>
    <?php
    require __DIR__ . '/step_01_Societe.php';
    require __DIR__ . '/step_02_Associes.php';
    require __DIR__ . '/step_03_Contrat.php';
    require __DIR__ . '/step_04_Recap.php';
    require __DIR__ . '/step_05_Upload.php';
    require __DIR__ . '/step_06_Generation.php';
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
    document.getElementById('btn-pdf-recap-disabled')?.addEventListener('click', async function () {
        var element = document.querySelector('.recap-a4');
        if (!element) return;

        var forme = this.getAttribute('data-forme') || '';
        var raison = this.getAttribute('data-raison') || 'Dossier';
        var raisonSlug = raison.replace(/[^a-zA-Z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '') || 'Dossier';
        var prefixMap = { 'SARL AU': 'SARL-AU', 'SARL': 'SARL', 'SA': 'SA', 'Personne Physique': 'PP' };
        var prefix = prefixMap[forme] || 'DOSSIER';
        var now = new Date();
        var yyyy = now.getFullYear();
        var mm = String(now.getMonth() + 1).padStart(2, '0');
        var filename = prefix + '_' + yyyy + '-' + mm + '_Recapitulatif-' + raisonSlug + '.pdf';

        this.disabled = true;
        this.innerHTML = '<span class="material-symbols-outlined spin">sync</span> Generation...';

        element.classList.add('recap-pdf-mode');
        try {
            await document.fonts.ready;
            var canvas = await window.html2canvas(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
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
            pdf.save(filename);
        } finally {
            element.classList.remove('recap-pdf-mode');
            document.getElementById('btn-pdf-recap').disabled = false;
            document.getElementById('btn-pdf-recap').innerHTML = '<span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF';
        }
    });
    </script>
    <script>
    document.querySelector('[data-type-gen]')?.addEventListener('change', function() {
        var show = this.value === 'creation';
        document.querySelectorAll('[data-depends-type-gen]').forEach(function(el) {
            el.style.display = show ? '' : 'none';
        });
        if (show) {
            var proc = document.querySelector('select[name="societe_procedure_creation"]');
            if (proc && !proc.value) proc.value = 'normal';
            var depot = document.querySelector('select[name="societe_mode_depot"]');
            if (depot && !depot.value) depot.value = 'depot_physique';
        }
        // Le message d'annulation suit le type de dossier choisi.
        var cancelLink = document.getElementById('wizard-cancel-link');
        if (cancelLink) {
            var msg = show
                ? cancelLink.getAttribute('data-confirm-creation')
                : cancelLink.getAttribute('data-confirm-domiciliation');
            if (msg) cancelLink.setAttribute('data-confirm', msg);
        }
    });
    </script>
</section>
