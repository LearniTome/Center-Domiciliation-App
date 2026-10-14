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

$fieldErrors = [];

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
        $submitted = [
            'denomination' => field_value($_POST, 'denomination'),
            'adresse' => field_value($_POST, 'adresse'),
            'numero_if' => field_value($_POST, 'numero_if'),
            'numero_ice' => field_value($_POST, 'numero_ice'),
            'numero_rc' => field_value($_POST, 'numero_rc'),
            'numero_tp' => field_value($_POST, 'numero_tp'),
            'numero_cnss' => field_value($_POST, 'numero_cnss'),
            'adresse_dgi' => field_value($_POST, 'adresse_dgi'),
            'adresse_cnss' => field_value($_POST, 'adresse_cnss'),
        ];
        // En cas d'erreur, le formulaire est re-rendu avec les valeurs saisies.
        $centre = array_merge($centre, $submitted);

        $len = static fn(string $v): int => function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);

        if ($submitted['denomination'] === '') {
            $fieldErrors['denomination'] = 'La denomination est obligatoire.';
        } elseif ($len($submitted['denomination']) > 190) {
            $fieldErrors['denomination'] = 'La denomination ne doit pas depasser 190 caracteres.';
        }

        if ($len($submitted['adresse']) > 255) {
            $fieldErrors['adresse'] = 'L\'adresse ne doit pas depasser 255 caracteres.';
        }

        $numericFields = [
            'numero_if' => 'L\'IF',
            'numero_rc' => 'Le RC',
            'numero_tp' => 'La taxe professionnelle',
            'numero_cnss' => 'Le numero CNSS',
        ];
        foreach ($numericFields as $name => $label) {
            $value = $submitted[$name];
            if ($value === '') {
                continue;
            }
            if ($len($value) > 50) {
                $fieldErrors[$name] = $label . ' ne doit pas depasser 50 caracteres.';
            } elseif (!preg_match('/^[0-9]+$/', $value)) {
                $fieldErrors[$name] = $label . ' doit contenir uniquement des chiffres.';
            }
        }

        if ($submitted['numero_ice'] !== ''
            && !preg_match('/^[0-9]{15}$/', $submitted['numero_ice'])) {
            $fieldErrors['numero_ice'] = 'L\'ICE doit contenir exactement 15 chiffres.';
        }

        if ($len($submitted['adresse_dgi']) > 255) {
            $fieldErrors['adresse_dgi'] = 'L\'adresse DGI ne doit pas depasser 255 caracteres.';
        }
        if ($len($submitted['adresse_cnss']) > 255) {
            $fieldErrors['adresse_cnss'] = 'L\'adresse CNSS ne doit pas depasser 255 caracteres.';
        }

        if ($fieldErrors === []) {
            $stmt = $pdo->prepare(
                'UPDATE centre_affaires SET denomination = :denomination, adresse = :adresse, numero_if = :numero_if, numero_ice = :numero_ice, numero_rc = :numero_rc, numero_tp = :numero_tp, numero_cnss = :numero_cnss, adresse_dgi = :adresse_dgi, adresse_cnss = :adresse_cnss, updated_at = NOW() WHERE id = 1'
            );
            $stmt->execute($submitted);
            set_flash('success', 'Centre d\'affaires mis a jour.');
            log_activity($pdo, 'update', 'centre_affaires', 1, 'Fiche centre d\'affaires');
            redirect_to('centre');
        }
    } elseif ($action === 'logo-upload') {
        $file = $_FILES['logo'] ?? null;
        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier depasse la limite serveur (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier depasse la limite du formulaire.',
                UPLOAD_ERR_PARTIAL => 'Transfert partiel — reessayez.',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier recu.',
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
        ];

        $mime = '';
        if (class_exists('finfo', false)) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $fi->file($file['tmp_name']);
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = (string) (mime_content_type($file['tmp_name']) ?: '');
        }
        if ($mime === '' && isset($file['name'])) {
            $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            $extMap = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
            $mime = $extMap[$ext] ?? '';
        }

        if (!isset($allowed[$mime])) {
            set_flash('error', 'Format non supporte (PNG, JPG ou WebP uniquement). Type detecte : ' . ($mime !== '' ? $mime : 'inconnu'));
            redirect_to('centre');
        }

        // Defense en profondeur : le fichier doit etre une image reellement lisible.
        $dimensions = @getimagesize($file['tmp_name']);
        if ($dimensions === false || empty($dimensions[0]) || empty($dimensions[1])) {
            set_flash('error', 'Le fichier n\'est pas une image valide.');
            redirect_to('centre');
        }

        $dir = __DIR__ . '/../../uploads/centre';
        if (!is_dir($dir)) {
            $parent = dirname($dir);
            if (!is_dir($parent)) {
                @mkdir($parent, 0755, true);
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
    } elseif ($action === 'logo-delete') {
        if ($logoExists) {
            @unlink(__DIR__ . '/../../' . $logoPath);
        }
        $pdo->prepare("UPDATE centre_affaires SET logo_path = '', updated_at = NOW() WHERE id = 1")->execute();
        set_flash('success', 'Logo supprime.');
        log_activity($pdo, 'delete', 'document', null, 'Logo du centre d\'affaires');
        redirect_to('centre');
    } else {
        redirect_to('centre');
    }
}

$errClass = static fn(string $field): string => isset($fieldErrors[$field]) ? ' field-error' : '';
$errInvalid = static fn(string $field): string => isset($fieldErrors[$field]) ? ' aria-invalid="true"' : '';
$errMsg = static function (string $field) use ($fieldErrors): string {
    return isset($fieldErrors[$field])
        ? '<small class="field-error">' . e($fieldErrors[$field]) . '</small>'
        : '';
};
?>
<?php require __DIR__ . '/../../includes/config_tabs.php'; render_config_tabs('centre'); ?>
<section class="card stack" data-centre-page>
    <div class="section-title-row">
        <h3><span class="material-symbols-outlined">apartment</span> Informations du centre</h3>
    </div>
    <p class="help-text centre-section-desc">Ces informations apparaissent dans l'application et les documents generes.</p>

    <form id="centre-form" method="post" class="stack">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="save">

        <div class="section-title-row">
            <h3><span class="material-symbols-outlined">badge</span> Informations générales</h3>
        </div>
        <div class="centre-grid">
            <label class="field full<?= $errClass('denomination') ?>">
                <span>Dénomination <em class="req-mark">*</em></span>
                <input name="denomination" value="<?= e((string) $centre['denomination']) ?>" required maxlength="190" placeholder="Ex : Centre Domiciliation SARL"<?= $errInvalid('denomination') ?>>
                <?= $errMsg('denomination') ?>
            </label>
            <label class="field full<?= $errClass('adresse') ?>">
                <span>Adresse</span>
                <input name="adresse" value="<?= e((string) $centre['adresse']) ?>" maxlength="255" placeholder="Adresse du siège du centre..."<?= $errInvalid('adresse') ?>>
                <?= $errMsg('adresse') ?>
            </label>
        </div>

        <div class="section-title-row">
            <h3><span class="material-symbols-outlined">receipt_long</span> Informations fiscales</h3>
        </div>
        <div class="centre-grid">
            <label class="field<?= $errClass('numero_if') ?>">
                <span>IF</span>
                <input name="numero_if" value="<?= e((string) $centre['numero_if']) ?>" inputmode="numeric" maxlength="50" placeholder="Numéro IF..."<?= $errInvalid('numero_if') ?>>
                <?= $errMsg('numero_if') ?>
            </label>
            <label class="field<?= $errClass('numero_ice') ?>">
                <span>ICE</span>
                <input name="numero_ice" value="<?= e((string) $centre['numero_ice']) ?>" inputmode="numeric" maxlength="15" placeholder="15 chiffres"<?= $errInvalid('numero_ice') ?>>
                <?= $errMsg('numero_ice') ?>
            </label>
            <label class="field<?= $errClass('numero_rc') ?>">
                <span>RC</span>
                <input name="numero_rc" value="<?= e((string) $centre['numero_rc']) ?>" inputmode="numeric" maxlength="50" placeholder="Registre de commerce..."<?= $errInvalid('numero_rc') ?>>
                <?= $errMsg('numero_rc') ?>
            </label>
            <label class="field<?= $errClass('numero_tp') ?>">
                <span>Taxe professionnelle</span>
                <input name="numero_tp" value="<?= e((string) $centre['numero_tp']) ?>" inputmode="numeric" maxlength="50" placeholder="N° Taxe professionnelle..."<?= $errInvalid('numero_tp') ?>>
                <?= $errMsg('numero_tp') ?>
            </label>
            <label class="field full<?= $errClass('adresse_dgi') ?>">
                <span>Adresse DGI compétente</span>
                <input name="adresse_dgi" value="<?= e((string) $centre['adresse_dgi']) ?>" maxlength="255" placeholder="Direction des Impôts..."<?= $errInvalid('adresse_dgi') ?>>
                <?= $errMsg('adresse_dgi') ?>
            </label>
        </div>

        <div class="section-title-row">
            <h3><span class="material-symbols-outlined">groups</span> Informations sociales</h3>
        </div>
        <div class="centre-grid">
            <label class="field centre-half<?= $errClass('numero_cnss') ?>">
                <span>CNSS</span>
                <input name="numero_cnss" value="<?= e((string) $centre['numero_cnss']) ?>" inputmode="numeric" maxlength="50" placeholder="N° CNSS..."<?= $errInvalid('numero_cnss') ?>>
                <?= $errMsg('numero_cnss') ?>
            </label>
            <label class="field centre-half<?= $errClass('adresse_cnss') ?>">
                <span>Adresse CNSS compétente</span>
                <input name="adresse_cnss" value="<?= e((string) $centre['adresse_cnss']) ?>" maxlength="255" placeholder="Agence CNSS..."<?= $errInvalid('adresse_cnss') ?>>
                <?= $errMsg('adresse_cnss') ?>
            </label>
        </div>

        <?php if (!$canEdit): ?>
            <p class="help-text" style="margin:0">Vous disposez d'un accès en lecture seule ; la modification requiert la permission « configuration.edit ».</p>
        <?php endif; ?>
    </form>

    <div class="section-title-row centre-section-sep">
        <h3><span class="material-symbols-outlined">image</span> Logo</h3>
    </div>

    <div class="centre-logo">
        <div class="centre-logo-media">
            <div class="centre-logo-preview" data-logo-preview>
                <?php if ($logoExists): ?>
                    <img src="<?= e($logoPath) ?>" alt="Logo du centre d'affaires">
                <?php else: ?>
                    <span class="material-symbols-outlined">location_city</span>
                <?php endif; ?>
            </div>
            <?php if ($canEdit && $logoExists): ?>
                <label for="centre-logo-input" class="centre-logo-edit" aria-label="Modifier le logo">
                    <span class="material-symbols-outlined">edit</span>
                </label>
            <?php endif; ?>
        </div>

        <div class="centre-logo-body">
            <?php if ($canEdit): ?>
                <form method="post" enctype="multipart/form-data" class="centre-logo-row">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="logo-upload">
                    <input type="file" id="centre-logo-input" name="logo" class="centre-logo-file" accept="image/png,image/jpeg,image/webp">
                    <label for="centre-logo-input" class="btn btn-info centre-logo-choose"><span class="material-symbols-outlined">folder_open</span> Choisir un fichier</label>
                    <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">upload</span> Téléverser</button>
                    <span class="centre-logo-filename help-text" data-logo-filename></span>
                </form>
                <?php if ($logoExists): ?>
                    <form method="post" class="centre-logo-danger">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="logo-delete">
                        <button type="submit" class="btn btn-danger" data-confirm="Supprimer le logo actuel ?"><span class="material-symbols-outlined">delete</span> Supprimer</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="help-text" style="margin:0">Aucune modification possible (lecture seule).</p>
            <?php endif; ?>
        </div>
    </div>
    <small class="help-text centre-logo-hint">PNG, JPG ou WebP — 2 Mo max.</small>
</section>

<script>
(function () {
    var page = document.querySelector('[data-centre-page]');
    if (!page) {
        return;
    }

    // Aperçu du logo avant téléversement.
    var logoInput = page.querySelector('#centre-logo-input');
    var preview = page.querySelector('[data-logo-preview]');
    var filename = page.querySelector('[data-logo-filename]');
    if (logoInput && preview) {
        logoInput.addEventListener('change', function () {
            var file = logoInput.files && logoInput.files[0];
            if (!file) {
                return;
            }
            if (filename) {
                filename.textContent = file.name;
            }
            var objectUrl = URL.createObjectURL(file);
            preview.innerHTML = '';
            var img = document.createElement('img');
            img.alt = 'Aperçu du logo';
            img.src = objectUrl;
            img.addEventListener('load', function () {
                URL.revokeObjectURL(objectUrl);
            });
            preview.appendChild(img);
        });
    }

    // Avertissement : modifications non enregistrees.
    var form = document.getElementById('centre-form');
    var dirty = false;
    var submitting = false;
    if (form) {
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('reset', function () { dirty = false; });
    }
    page.querySelectorAll('form').forEach(function (f) {
        f.addEventListener('submit', function () { submitting = true; });
    });
    if (logoInput) {
        logoInput.addEventListener('change', function () { dirty = true; });
    }
    window.addEventListener('beforeunload', function (event) {
        if (dirty && !submitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
</script>
