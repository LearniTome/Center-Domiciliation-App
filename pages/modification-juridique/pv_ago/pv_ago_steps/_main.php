<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
?>
<section>
    <article class="card stack pv-ago-wizard">
        <?php if ($step >= 1): ?>
        <div class="wizard-steps" id="wizard-steps-top">
            <?php for ($s = 1; $s <= 7; $s++): ?>
                <div class="wizard-step <?= $step > $s ? 'done' : ($step === $s ? 'active' : 'waiting') ?>">
                    <strong>Etape <?= $s ?></strong>
                    <span><?= $stepLabels[$s - 1] ?></span>
                </div>
            <?php endfor; ?>
        </div>
        <?php
        $raisonSociale = $societeData['societe_raison_sociale'] ?? '';
        if ($raisonSociale !== ''):
        ?>
        <div class="wizard-info">
            <strong>Etape <?= $step ?></strong> — <?= e($stepLabels[$step - 1] ?? '') ?> de la societe : <?= e($raisonSociale) ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php
        require __DIR__ . '/step_00_Mode.php';
        require __DIR__ . '/step_01_Societe.php';
        require __DIR__ . '/step_02_Associes.php';
        require __DIR__ . '/step_03_Assemblee.php';
        require __DIR__ . '/step_04_Finances.php';
        require __DIR__ . '/step_05_Resolutions.php';
        require __DIR__ . '/step_06_Recap.php';
        require __DIR__ . '/step_07_Generation.php';
        ?>
    </article>
</section>
