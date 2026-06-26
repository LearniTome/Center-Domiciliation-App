<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';
?>
<section>
    <style>
        .pv-ago-wizard input,
        .pv-ago-wizard select,
        .pv-ago-wizard textarea { padding: 6px 10px; font-size: 0.82rem; }
        .pv-ago-wizard .field { gap: 3px; }
        .pv-ago-wizard .field span { font-size: 0.7rem; }
        .pv-ago-wizard .form-grid { gap: 8px; }
        .resolutions-preview { white-space: pre-wrap; font-family: 'Georgia', serif; line-height: 1.6; padding: 20px; background: #fff; border: 1px solid var(--line); border-radius: 6px; margin-top: 12px; }
        .calc-line { padding: 2px 0; font-size: 0.85rem; }
        .calc-total { font-weight: 600; border-top: 1px solid var(--line); padding-top: 6px; margin-top: 6px; }
        .res-block { background: #fafafa; border: 1px solid var(--line); border-radius: 6px; padding: 14px; margin-bottom: 12px; }
        .res-block h4 { margin: 0 0 6px; font-size: 0.9rem; }
    </style>
    <article class="card stack pv-ago-wizard">

        <?php if ($step >= 1): ?>
        <div class="wizard-steps" id="wizard-steps-top">
            <?php for ($s = 1; $s <= 5; $s++): ?>
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
            <strong>Etape <?= $step ?></strong> — <?= e($stepLabels[$step - 1] ?? '') ?> de la societe : <?= e($raisonSociale) ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php
        require __DIR__ . '/step_00_Mode.php';
        require __DIR__ . '/step_01_Assemblee.php';
        require __DIR__ . '/step_02_Finances.php';
        require __DIR__ . '/step_03_Resolutions.php';
        require __DIR__ . '/step_04_Recap.php';
        require __DIR__ . '/step_05_Generation.php';
        ?>
    </article>
</section>
