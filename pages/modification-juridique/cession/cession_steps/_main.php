<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
?>
<section>
    <article class="card stack">

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
