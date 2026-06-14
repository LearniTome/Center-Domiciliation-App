<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
?>
<section>
    <article class="card stack">
        <div class="section-header">
            <h2 style="display:flex;align-items:center;gap:8px;margin:0">
                <span class="material-symbols-outlined" style="color:var(--primary)">transfer_within_a_station</span>
                Cession de parts sociales
            </h2>
            <div style="display:flex;gap:8px">
                <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Reinitialiser l assistant ?"><span class="material-symbols-outlined">restart_alt</span> Reinitialiser</a>
            </div>
        </div>

        <?php if ($step >= 1): ?>
        <div class="wizard-steps" id="wizard-steps-top">
            <?php for ($s = 1; $s <= 6; $s++): ?>
                <div class="wizard-step <?= $step > $s ? 'done' : ($step === $s ? 'active' : 'waiting') ?>">
                    <strong>Etape <?= $s ?></strong>
                    <span><?= $stepLabels[$s - 1] ?></span>
                </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php
        require __DIR__ . '/step_00_Mode.php';
        require __DIR__ . '/step_01_Societe.php';
        require __DIR__ . '/step_02_Associes.php';
        require __DIR__ . '/step_03_Parts.php';
        require __DIR__ . '/step_04_Recap.php';
        require __DIR__ . '/step_05_Upload.php';
        require __DIR__ . '/step_06_Generation.php';
        ?>
    </article>
</section>
