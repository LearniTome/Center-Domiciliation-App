<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Parcours guide: societe, associes, puis contrat, dans un seul flux.</p>
        </div>
        <div class="table-actions">
            <a class="btn btn-cancel" href="<?= e(app_url('creation', ['cancel' => '1'])) ?>" data-confirm="Annuler la creation ?"><span class="material-symbols-outlined">cancel</span> Annuler</a>
            <a class="btn btn-back" href="<?= e(app_url('creation', ['reset' => '1'])) ?>" data-confirm="Reinitialiser cet assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
    document.getElementById('btn-pdf-recap')?.addEventListener('click', function () {
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

        var opt = {
            margin:       10,
            filename:     filename,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save().then(function () {
            element.classList.remove('recap-pdf-mode');
            document.getElementById('btn-pdf-recap').disabled = false;
            document.getElementById('btn-pdf-recap').innerHTML = '<span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF';
        });
    });
    </script>
    <script>
    document.querySelector('[data-type-gen]')?.addEventListener('change', function() {
        var show = this.value === 'creation';
        document.querySelectorAll('[data-depends-type-gen]').forEach(function(el) {
            el.style.display = show ? '' : 'none';
        });
    });
    </script>
</section>
