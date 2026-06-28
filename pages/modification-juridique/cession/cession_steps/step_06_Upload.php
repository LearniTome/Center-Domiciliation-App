<?php
declare(strict_types=1);

// POST handler
if (is_post() && $step === 6) {
    verify_csrf();

    // Step 6: Upload / Validation
    if ($step === 6) {
        $navAction = $_POST['nav_action'] ?? 'next';
        if ($navAction === 'back') {
            redirect_to('cession', ['step' => 5]);
        }

        $uploadDir = __DIR__ . '/../../../../uploads';
        $tmpDir = $uploadDir . '/tmp/' . session_id();
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

        $uploadedDocs = $wizard['uploaded_docs'] ?? [];

        $docFields = [
            'ancien_statuts' => 'cession_as',
            'cin_cedant' => 'cession_cinc',
            'cin_cessionnaire' => 'cession_cincs',
            'attestation_non_preemption' => 'cession_anp',
        ];

        foreach ($docFields as $field => $prefix) {
            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $stored = $prefix . '_' . date('Ymd_His') . '.' . $ext;
                $dest = $tmpDir . '/' . $stored;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
                    $uploadedDocs[$field] = [
                        'original' => $_FILES[$field]['name'],
                        'stored' => $stored,
                        'path' => $dest,
                        'taille_ko' => round(filesize($dest) / 1024, 1),
                    ];
                }
            }
        }

        $wizard['uploaded_docs'] = $uploadedDocs;
        redirect_to('cession', ['step' => 7]);
    }
}

// HTML view
if ($step === 6):
    $uploadedDocs = $wizard['uploaded_docs'] ?? [];
?>
        <div class="stack">
            <form method="post" class="stack" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="nav_action" value="next">

                <?php
                $docItems = [
                    'ancien_statuts' => [
                        'label' => 'Anciens statuts',
                        'icon' => 'description',
                        'accept' => '.pdf',
                    ],
                    'cin_cedant' => [
                        'label' => 'CIN Cédant',
                        'icon' => 'badge',
                        'accept' => '.pdf,.jpg,.jpeg,.png',
                    ],
                    'cin_cessionnaire' => [
                        'label' => 'CIN Cessionnaire',
                        'icon' => 'badge',
                        'accept' => '.pdf,.jpg,.jpeg,.png',
                    ],
                    'attestation_non_preemption' => [
                        'label' => 'Attestation non prépondérance immobilière',
                        'icon' => 'gavel',
                        'accept' => '.pdf',
                    ],
                ];
                ?>

                <article class="card">
                    <div class="section-header">
                        <h3>Documents à fournir</h3>
                    </div>
                    <div class="grid two" style="gap:16px;margin-top:8px">
                    <?php foreach ($docItems as $field => $info): ?>
                    <?php $hasDoc = isset($uploadedDocs[$field]); ?>
                        <label class="field" style="border:1px solid <?= $hasDoc ? 'var(--success)' : 'var(--danger)' ?>;border-radius:6px;padding:10px 12px;cursor:pointer">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <span class="material-symbols-outlined" style="font-size:18px;color:<?= $hasDoc ? 'var(--success)' : 'var(--danger)' ?>"><?= $info['icon'] ?></span>
                                <strong style="flex:1;font-size:0.9rem"><?= $info['label'] ?></strong>
                                <?php if ($hasDoc): ?>
                                    <span style="color:var(--success);font-size:0.8rem"><span class="material-symbols-outlined" style="font-size:16px">check_circle</span></span>
                                <?php else: ?>
                                    <span style="color:var(--danger);font-size:0.8rem"><span class="material-symbols-outlined" style="font-size:16px">cancel</span></span>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="<?= $field ?>" accept="<?= $info['accept'] ?>" style="font-size:0.85rem">
                            <?php if ($hasDoc): ?>
                                <div style="font-size:0.75rem;color:var(--success);margin-top:2px"><?= e($uploadedDocs[$field]['original']) ?></div>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </article>

                <div class="footer-actions" style="margin-top:12px">
                    <div style="display:flex;gap:8px;margin-left:auto">
                        <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                        <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
                    </div>
                </div>
            </form>
        </div>
<?php endif; ?>
