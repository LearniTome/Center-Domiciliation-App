<?php

declare(strict_types=1);

if (is_post() && $step === 5) {
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('creation', ['step' => 4]);
    }

    $uploadDir = __DIR__ . '/../../../uploads';
    $tmpDir = $uploadDir . '/tmp/' . session_id();
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0777, true);
    }

    $uploadedDocs = $wizard['uploaded_docs'] ?? [];

    if (!empty($_FILES['certificat_negatif']['name']) && $_FILES['certificat_negatif']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['certificat_negatif']['name'], PATHINFO_EXTENSION);
        $stored = 'certificat_negatif_' . date('Ymd_His') . '.' . $ext;
        $dest = $tmpDir . '/' . $stored;
        if (move_uploaded_file($_FILES['certificat_negatif']['tmp_name'], $dest)) {
            $uploadedDocs['certificat_negatif'] = [
                'original' => $_FILES['certificat_negatif']['name'],
                'stored' => $stored,
                'path' => $dest,
                'taille_ko' => round(filesize($dest) / 1024, 1),
            ];
        }
    }

    if (!empty($_FILES['cin_gerants']['name'][0]) && is_array($_FILES['cin_gerants']['name'])) {
        $files = $_FILES['cin_gerants'];
        $associeIndexes = $_POST['cin_associe_index'] ?? [];
        foreach ($files['name'] as $idx => $name) {
            if ($name === '' || $files['error'][$idx] !== UPLOAD_ERR_OK) continue;
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $stored = 'cin_gerant_' . $idx . '_' . date('Ymd_His') . '.' . $ext;
            $dest = $tmpDir . '/' . $stored;
            if (move_uploaded_file($files['tmp_name'][$idx], $dest)) {
                $associeIdx = $associeIndexes[$idx] ?? $idx;
                $uploadedDocs['cin_gerants'][$associeIdx] = [
                    'original' => $name,
                    'stored' => $stored,
                    'path' => $dest,
                    'taille_ko' => round(filesize($dest) / 1024, 1),
                ];
            }
        }
    }

    $wizard['uploaded_docs'] = $uploadedDocs;
    log_activity($pdo, 'upload', 'document', null, 'Uploads étape 5 — ' . count($uploadedDocs) . ' fichier(s)', json_encode(array_map(fn($d) => $d['type'], $uploadedDocs)));
    redirect_to('creation', ['step' => 6]);
}

if ($step === 5):
    $formeJuridique = $societeData['societe_forme_juridique'] ?? '';
    $gerants = array_filter($associesData, fn($a) => ((string) ($a['associe_est_gerant'] ?? '0') === '1'));
    $isSarlAu = str_starts_with($formeJuridique, 'SARL AU');
    $uploadedDocs = $wizard['uploaded_docs'] ?? [];
    $hasCn = isset($uploadedDocs['certificat_negatif']);
    $hasCin = isset($uploadedDocs['cin_gerants']);
?>
<div class="stack">
    <div class="section-header">
        <div>
            <h2>Etape 5 — Documents a uploader</h2>
            <p class="help-text">Fournissez les documents necessaires avant la generation.</p>
        </div>
    </div>

    <form method="post" class="stack" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="5">
        <input type="hidden" name="nav_action" value="next">

        <article class="card" style="border-color:<?= $hasCn ? 'var(--success)' : 'var(--danger)' ?>">
            <div class="section-header">
                <div>
                    <h3><span class="material-symbols-outlined">verified</span> Certificat Negatif</h3>
                    <p class="help-text">Document delivre par l'OMPIC (format PDF).</p>
                </div>
                <?php if ($hasCn): ?>
                    <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined">check_circle</span> Telecharge</span>
                <?php else: ?>
                    <span class="step-badge" style="color:var(--danger)"><span class="material-symbols-outlined">cancel</span> Manquant</span>
                <?php endif; ?>
            </div>
            <label class="field" style="margin-top:8px">
                <span>Fichier</span>
                <input type="file" name="certificat_negatif" accept=".pdf" <?= $hasCn ? '' : 'required' ?>>
                <?php if ($hasCn): ?>
                    <small style="color:var(--success)"><?= e($uploadedDocs['certificat_negatif']['original']) ?> deja uploade.</small>
                <?php endif; ?>
            </label>
        </article>

        <?php $cinBorder = count($gerants) === 0 ? '' : ($hasCin ? 'var(--success)' : 'var(--danger)'); ?>
        <article class="card"<?= $cinBorder !== '' ? ' style="border-color:' . $cinBorder . '"' : '' ?>>
            <div class="section-header">
                <div>
                    <h3><span class="material-symbols-outlined">badge</span> CIN des Gerants</h3>
                    <p class="help-text">
                        <?= $isSarlAu ? 'SARL AU : un seul CIN requis.' : 'SARL : CIN de tous les gerants.' ?>
                    </p>
                </div>
                <?php if (count($gerants) === 0): ?>
                    <span class="step-badge"><span class="material-symbols-outlined">info</span> Aucun gerant</span>
                <?php elseif ($hasCin): ?>
                    <span class="step-badge" style="color:var(--success)"><span class="material-symbols-outlined">check_circle</span> Telecharge(s)</span>
                <?php else: ?>
                    <span class="step-badge" style="color:var(--danger)"><span class="material-symbols-outlined">cancel</span> Manquant(s)</span>
                <?php endif; ?>
            </div>

            <?php if (count($gerants) === 0): ?>
                <p class="help-text" style="margin-top:8px;color:var(--warning)">
                    <span class="material-symbols-outlined">warning</span> Aucun gerant designe dans les associes. Veuillez revenir a l'etape 2.
                </p>
            <?php else: ?>
                <div class="stack" style="margin-top:8px;gap:12px">
                <?php foreach ($gerants as $idx => $gerant):
                    $nomGerant = $gerant['associe_nom_complet'] ?: ('Gerant ' . ($idx + 1));
                ?>
                    <label class="field">
                        <span><?= e('CIN de ' . $nomGerant) ?></span>
                        <input type="file" name="cin_gerants[]" accept=".pdf,.jpg,.jpeg,.png" <?= isset($uploadedDocs['cin_gerants'][$idx]) ? '' : 'required' ?>>
                        <input type="hidden" name="cin_associe_index[]" value="<?= $idx ?>">
                        <?php if (isset($uploadedDocs['cin_gerants'][$idx])): ?>
                            <small style="color:var(--success)"><?= e($uploadedDocs['cin_gerants'][$idx]['original']) ?> deja uploade.</small>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <div class="table-actions" style="margin-top:0.75rem">
            <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button class="btn btn-next" type="submit" name="nav_action" value="next" <?= count($gerants) === 0 ? 'disabled' : '' ?>><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('[type="file"]').forEach(function(input) {
    input.addEventListener('change', function() {
        var card = this.closest('.card');
        if (!card) return;
        var badge = card.querySelector('.step-badge');
        if (!badge) return;
        var hasFile = this.files && this.files.length > 0 && this.files[0].size > 0;
        if (hasFile) {
            badge.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Telecharge';
            badge.style.color = 'var(--success)';
            card.style.borderColor = 'var(--success)';
        } else {
            badge.innerHTML = '<span class="material-symbols-outlined">cancel</span> Manquant';
            badge.style.color = 'var(--danger)';
            card.style.borderColor = 'var(--danger)';
        }
    });
});
</script>
<?php endif; ?>
