<?php

declare(strict_types=1);

if (!function_exists('has_permission') || !has_permission('configuration.view')) {
    set_flash('error', 'Acces refuse.');
    redirect_to('dashboard');
}

$canEdit = has_permission('configuration.edit');
$centre = get_centre_affaires($pdo ?? null);
$logoPath = trim((string) ($centre['logo_path'] ?? ''));
$logoExists = $logoPath !== '' && is_file(__DIR__ . '/../../' . $logoPath);

if (is_post()) {
    verify_csrf();
    if (!$canEdit) {
        set_flash('error', 'Vous n\'avez pas la permission de modifier le centre d\'affaires.');
        redirect_to('centre');
    }
    if (!($pdo ?? null) instanceof PDO) {
        set_flash('error', 'Base de donnees indisponible.');
        redirect_to('centre');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $stmt = $pdo->prepare(
            'UPDATE centre_affaires SET denomination = :denomination, adresse = :adresse, numero_if = :numero_if, numero_ice = :numero_ice, numero_rc = :numero_rc, numero_tp = :numero_tp, numero_cnss = :numero_cnss, adresse_dgi = :adresse_dgi, adresse_cnss = :adresse_cnss, updated_at = NOW() WHERE id = 1'
        );
        $stmt->execute([
            'denomination' => field_value($_POST, 'denomination'),
            'adresse' => field_value($_POST, 'adresse'),
            'numero_if' => field_value($_POST, 'numero_if'),
            'numero_ice' => field_value($_POST, 'numero_ice'),
            'numero_rc' => field_value($_POST, 'numero_rc'),
            'numero_tp' => field_value($_POST, 'numero_tp'),
            'numero_cnss' => field_value($_POST, 'numero_cnss'),
            'adresse_dgi' => field_value($_POST, 'adresse_dgi'),
            'adresse_cnss' => field_value($_POST, 'adresse_cnss'),
        ]);
        set_flash('success', 'Centre d\'affaires mis a jour.');
        log_activity($pdo, 'update', 'centre_affaires', 1, 'Fiche centre d\'affaires');
        redirect_to('centre');
    }

    if ($action === 'logo-upload') {
        $file = $_FILES['logo'] ?? null;
        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Le fichier depasse la limite serveur (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'Le fichier depasse la limite du formulaire.',
                UPLOAD_ERR_PARTIAL    => 'Transfert partiel — reessayez.',
                UPLOAD_ERR_NO_FILE    => 'Aucun fichier recu.',
                UPLOAD_ERR_NO_TMP_DIR => 'Repertoire temporaire manquant sur le serveur.',
                UPLOAD_ERR_CANT_WRITE => 'Ecriture impossible sur le serveur.',
            ];
            $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            $msg = $uploadErrors[$errCode] ?? 'Erreur d\'upload (#' . $errCode . ').';
            set_flash('error', $msg);
            redirect_to('centre');
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            set_flash('error', 'Le logo ne doit pas depasser 2 Mo.');
            redirect_to('centre');
        }

        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        $mime = '';
        if (class_exists('finfo', false)) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($file['tmp_name']);
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']) ?: '';
        }
        if ($mime === '' && isset($file['name'])) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extMap = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif'];
            $mime = $extMap[$ext] ?? '';
        }

        if (!isset($allowed[$mime])) {
            set_flash('error', 'Format non supporte (PNG, JPG, WebP ou GIF uniquement). Type detecte : ' . ($mime ?: 'inconnu'));
            redirect_to('centre');
        }

        $dir = __DIR__ . '/../../uploads/centre';
        if (!is_dir($dir)) {
            $parents = dirname($dir);
            if (!is_dir($parents)) {
                @mkdir($parents, 0755, true);
            }
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                set_flash('error', 'Impossible de creer le repertoire uploads/centre/. Verifiez les permissions.');
                redirect_to('centre');
            }
        }
        if (!is_writable($dir)) {
            set_flash('error', 'Le repertoire uploads/centre/ n\'est pas accessible en ecriture.');
            redirect_to('centre');
        }

        foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $oldExt) {
            $oldFile = $dir . '/logo.' . $oldExt;
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        $ext = $allowed[$mime];
        $relativePath = 'uploads/centre/logo.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/logo.' . $ext)) {
            set_flash('error', 'Echec de l\'enregistrement du logo (move_uploaded_file).');
            redirect_to('centre');
        }

        $pdo->prepare('UPDATE centre_affaires SET logo_path = :lp, updated_at = NOW() WHERE id = 1')
            ->execute(['lp' => $relativePath]);
        set_flash('success', 'Logo mis a jour.');
        log_activity($pdo, 'upload', 'document', null, 'Logo du centre d\'affaires');
        redirect_to('centre');
    }

    if ($action === 'logo-delete') {
        if ($logoExists) {
            @unlink(__DIR__ . '/../../' . $logoPath);
        }
        $pdo->prepare("UPDATE centre_affaires SET logo_path = '', updated_at = NOW() WHERE id = 1")->execute();
        set_flash('success', 'Logo supprime.');
        log_activity($pdo, 'delete', 'document', null, 'Logo du centre d\'affaires');
        redirect_to('centre');
    }

    redirect_to('centre');
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Informations du centre d'affaires affichees dans l'application.</p>
        </div>
        <a class="btn btn-back" href="<?= e(app_url('configuration')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
    </div>

    <form method="post" class="stack">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="save">
        <div class="info-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px">
            <label class="field" style="grid-column:1/-1">
                <span>Dénomination *</span>
                <input name="denomination" value="<?= e((string) $centre['denomination']) ?>" required placeholder="Ex : Centre Domiciliation SARL">
            </label>
            <label class="field" style="grid-column:1/-1">
                <span>Adresse</span>
                <input name="adresse" value="<?= e((string) $centre['adresse']) ?>" placeholder="Adresse du siège du centre...">
            </label>
            <label class="field">
                <span>IF</span>
                <input name="numero_if" value="<?= e((string) $centre['numero_if']) ?>" placeholder="Numéro IF...">
            </label>
            <label class="field">
                <span>ICE</span>
                <input name="numero_ice" value="<?= e((string) $centre['numero_ice']) ?>" placeholder="Numéro ICE...">
            </label>
            <label class="field">
                <span>RC</span>
                <input name="numero_rc" value="<?= e((string) $centre['numero_rc']) ?>" placeholder="Registre de commerce...">
            </label>
            <label class="field">
                <span>Taxe professionnelle</span>
                <input name="numero_tp" value="<?= e((string) $centre['numero_tp']) ?>" placeholder="N° Taxe professionnelle...">
            </label>
            <label class="field">
                <span>CNSS</span>
                <input name="numero_cnss" value="<?= e((string) $centre['numero_cnss']) ?>" placeholder="N° CNSS...">
            </label>
            <label class="field" style="grid-column:1/-1">
                <span>Adresse DGI compétent</span>
                <input name="adresse_dgi" value="<?= e((string) $centre['adresse_dgi']) ?>" placeholder="Direction des Impôts... ">
            </label>
            <label class="field" style="grid-column:1/-1">
                <span>Adresse CNSS compétent</span>
                <input name="adresse_cnss" value="<?= e((string) $centre['adresse_cnss']) ?>" placeholder="Agence CNSS...">
            </label>
        </div>
        <?php if ($canEdit): ?>
            <div style="display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">check</span> Enregistrer</button>
            </div>
        <?php endif; ?>
    </form>

    <div class="section-title-row" style="margin-top:8px;border-top:1px solid var(--line);padding-top:14px">
        <h3><span class="material-symbols-outlined">image</span> Logo</h3>
        <p class="help-text" style="margin:0">PNG, JPG, WebP ou GIF — 2 Mo max. Affiché sur la barre supérieure et la page de connexion.</p>
    </div>

    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
        <div style="width:72px;height:72px;display:flex;align-items:center;justify-content:center;border:2px dashed var(--line);border-radius:var(--radius);overflow:hidden;background:var(--surface)">
            <?php if ($logoExists): ?>
                <img src="<?= e($logoPath) ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain">
            <?php else: ?>
                <span class="material-symbols-outlined" style="color:var(--text-muted)">location_city</span>
            <?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
            <form method="post" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="logo-upload">
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" required style="font-size:0.8125rem">
                <button type="submit" class="btn btn-info"><span class="material-symbols-outlined">upload</span> Téléverser</button>
            </form>
            <?php if ($logoExists): ?>
                <form method="post" style="display:inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="logo-delete">
                    <button type="submit" class="btn btn-danger" data-confirm="Supprimer le logo actuel ?"><span class="material-symbols-outlined">delete</span> Supprimer</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
