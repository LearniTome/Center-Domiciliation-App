<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
?>
<section>
    <style>
        .cession-wizard input,
        .cession-wizard select,
        .cession-wizard textarea { padding: 6px 10px; font-size: 0.82rem; }
        .cession-wizard .field { gap: 3px; }
        .cession-wizard .field span { font-size: 0.7rem; }
        .cession-wizard .form-grid { gap: 8px; }
    </style>
    <article class="card stack cession-wizard">

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
        <div style="margin:12px 0 4px;font-size:0.9rem;color:var(--text-secondary)">
            <strong>Etape <?= $step ?></strong> — <?= e($stepLabels[$step - 1]) ?> de la société : <?= e($raisonSociale) ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php
        require __DIR__ . '/step_00_Mode.php';
        require __DIR__ . '/step_01_Societe.php';
        require __DIR__ . '/step_02_Associes.php';
        require __DIR__ . '/step_03_Parts.php';
        require __DIR__ . '/step_04_Recap.php';
        require __DIR__ . '/step_05_Pv.php';
        require __DIR__ . '/step_06_Upload.php';
        require __DIR__ . '/step_07_Generation.php';
        ?>
    </article>
</section>
