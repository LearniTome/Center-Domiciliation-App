<?php

declare(strict_types=1);

/**
 * Administration SaaS - Cabinets clients.
 *
 * Ecran prealable a la facturation : un abonnement se rattache a un cabinet.
 * Seuls les comptes Centre (cabinet_id IS NULL) possessing cabinets.* accedent
 * a cette page, la garde est faite par get_page_permission() / require_page_access().
 */

$query = search_term();
$canCreate = has_permission('cabinets.create');
$canEdit = has_permission('cabinets.edit');
$canDelete = has_permission('cabinets.delete');
$db = ($pdo ?? null) instanceof PDO ? $pdo : null;

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$formOpen = isset($_GET['action']) && $_GET['action'] === 'new';

$formData = [
    'code' => '', 'type_cabinet' => '', 'nom' => '', 'email' => '',
    'telephone' => '', 'telephone_fixe' => '', 'telephone_mobile' => '',
    'adresse' => '', 'ville' => '', 'ice' => '', 'rc' => '',
    'identifiant_fiscal' => '', 'taxe_professionnelle' => '',
    'statut' => 'actif',
];
$fieldErrors = [];

if ($db && $editId > 0) {
    $stmt = $db->prepare('SELECT * FROM cabinets WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $formOpen = true;
        $formData = array_merge($formData, $existing);
    } else {
        set_flash('error', 'Cabinet introuvable.');
        redirect_to('cabinets');
    }
}

// Creation : on propose le prochain code de sequence, que l'utilisateur peut
// remplacer. Place AVANT le handler POST pour qu'un code deja saisi, ou une
// erreur de validation, ne soit jamais ecrase par une nouvelle proposition
// (`$formData` est fusionne avec $_POST juste apres, en cas d'erreur).
if ($formOpen && $editId === 0 && (string) $formData['code'] === '') {
    $formData['code'] = next_cabinet_code($db);
}

if (is_post() && $db) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        if (!$canDelete) {
            require_permission('cabinets.delete');
        }

        $targetId = (int) ($_POST['id'] ?? 0);

        // MySQL refuserait la suppression via les cles etrangeres
        // fk_users_cabinet / fk_abonnements_cabinet / fk_factures_cabinet /
        // fk_paiements_cabinet. On verifie en amont pour donner un message
        // exploitable au lieu d'une exception SQL.
        $deps = [];
        foreach (['users' => 'utilisateurs', 'abonnements' => 'abonnements', 'factures' => 'factures', 'paiements' => 'paiements'] as $table => $label) {
            $check = $db->prepare("SELECT COUNT(*) FROM `$table` WHERE cabinet_id = :cid");
            $check->execute(['cid' => $targetId]);
            $count = (int) $check->fetchColumn();
            if ($count > 0) {
                $deps[] = $count . ' ' . $label;
            }
        }

        if ($deps !== []) {
            set_flash('error', 'Suppression impossible : ce cabinet est encore rattache a ' . implode(', ', $deps) . '. Supprimez ou reassignez ces elements d\'abord.');
        } else {
            $stmt = $db->prepare('DELETE FROM cabinets WHERE id = :id');
            $stmt->execute(['id' => $targetId]);
            log_activity($db, 'delete', 'cabinet', $targetId);
            set_flash('success', 'Cabinet supprime.');
        }

        redirect_to('cabinets');
    }

    if ($action === 'save') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId > 0 && !$canEdit) {
            require_permission('cabinets.edit');
        }
        if ($targetId === 0 && !$canCreate) {
            require_permission('cabinets.create');
        }

        $code = strtoupper(field_value($_POST, 'code'));
        $typeCabinet = field_value($_POST, 'type_cabinet');
        $nom = field_value($_POST, 'nom');
        $email = field_value($_POST, 'email');
        $statut = field_value($_POST, 'statut', 'actif');

        if (!in_array($statut, cabinet_statut_options(), true)) {
            $statut = 'actif';
        }

        // Les erreurs sont indexees par champ : le formulaire les rend sous la
        // saisie concernee. Une seule message par champ, le premier compte
        // (`??=`), pour qu'une regle de longueur ne soit pas recouverte par une
        // regle de format plus tard dans le meme POST.
        $marquer = static function (string $champ, string $message) use (&$fieldErrors): void {
            $fieldErrors[$champ] ??= $message;
        };

        // La colonne reste nullable pour les cabinets deja enregistres, dont on
        // ignore le type, mais tout enregistrement passe par cette porte.
        if (!in_array($typeCabinet, cabinet_type_options(), true)) {
            $marquer('type_cabinet', $typeCabinet === ''
                ? 'Le type de cabinet est obligatoire.'
                : 'Ce type de cabinet est inconnu.');
        }

        // `maxlength` n'est respectable que par le navigateur : un POST direct
        // tronquerait silencieusement en base. On refuse plutot que d'ecrire
        // une valeur amputee. Le libelle n'est pas repris dans le message, il
        // est deja porte par l'intitule du champ, au-dessus de la saisie.
        $lengths = [
            'code' => 40,
            'nom' => 150,
            'email' => 190,
            'telephone' => 40,
            'telephone_fixe' => 60,
            'telephone_mobile' => 60,
            'adresse' => 255,
            'ville' => 120,
            'ice' => 40,
            'rc' => 60,
            'identifiant_fiscal' => 100,
            'taxe_professionnelle' => 100,
        ];
        foreach ($lengths as $field => $max) {
            if (mb_strlen(field_value($_POST, $field)) > $max) {
                $marquer($field, 'Maximum ' . $max . ' caractères.');
            }
        }

        if ($code === '') {
            $marquer('code', 'Le code du cabinet est obligatoire.');
        }
        if ($nom === '') {
            $marquer('nom', 'Le nom du cabinet est obligatoire.');
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $marquer('email', 'Adresse email invalide.');
        }

        if ($code !== '' && !isset($fieldErrors['code'])) {
            $dup = $db->prepare('SELECT id FROM cabinets WHERE code = :code AND id <> :id');
            $dup->execute(['code' => $code, 'id' => $targetId]);
            if ($dup->fetch()) {
                $marquer('code', 'Ce code de cabinet est déjà utilisé.');
            }
        }

        if ($fieldErrors !== []) {
            $formOpen = true;
            $formData = array_merge($formData, $_POST);
        } else {
            // NULL plutot que '' pour les colonnes optionnelles : les listes et
            // les exports affichent alors '-' via `?? '-'` au lieu d'un trou.
            $opt = static fn (string $value): ?string => $value === '' ? null : $value;

            // `raison_sociale` et `notes` sont volontairement absents du
            // payload : le formulaire ne les propose plus. Les laisser hors
            // ecriture les preserve telles quelles en mise a jour (elles
            // sortent de l'ecran, elles ne sont pas effacees) et les laissent a
            // NULL sur creation, ou le nom unique porte la denomination.
            $payload = [
                'code' => $code,
                'type_cabinet' => $typeCabinet,
                'nom' => $nom,
                'email' => $opt($email),
                'telephone' => $opt(field_value($_POST, 'telephone')),
                'telephone_fixe' => $opt(field_value($_POST, 'telephone_fixe')),
                'telephone_mobile' => $opt(field_value($_POST, 'telephone_mobile')),
                'adresse' => $opt(field_value($_POST, 'adresse')),
                'ville' => $opt(field_value($_POST, 'ville')),
                'ice' => $opt(field_value($_POST, 'ice')),
                'rc' => $opt(field_value($_POST, 'rc')),
                'identifiant_fiscal' => $opt(field_value($_POST, 'identifiant_fiscal')),
                'taxe_professionnelle' => $opt(field_value($_POST, 'taxe_professionnelle')),
                'statut' => $statut,
            ];

            if ($targetId > 0) {
                $sets = [];
                $params = ['id' => $targetId];
                foreach ($payload as $col => $val) {
                    $sets[] = "$col = :$col";
                    $params[$col] = $val;
                }
                $stmt = $db->prepare('UPDATE cabinets SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $stmt->execute($params);
                log_activity($db, 'update', 'cabinet', $targetId, $nom);
                set_flash('success', 'Cabinet « ' . $nom . ' » mis a jour.');
            } else {
                $cols = array_keys($payload);
                $stmt = $db->prepare(
                    'INSERT INTO cabinets (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
                );
                $stmt->execute($payload);
                $newId = (int) $db->lastInsertId();
                log_activity($db, 'create', 'cabinet', $newId, $nom);
                set_flash('success', 'Cabinet « ' . $nom . ' » cree.');
            }

            redirect_to('cabinets');
        }
    }
}

// Filtre par type, valide contre la liste figee : une valeur bricolee dans
// l'URL ne doit pas non plus devenir une colonne du SQL.
$typeFilter = (string) ($_GET['type'] ?? '');
if (!in_array($typeFilter, cabinet_type_options(), true)) {
    $typeFilter = '';
}

// La recherche couvre le `telephone` historique comme les deux lignes dediees :
// la colonne Contact les affiche les trois, une recherche qui ignorerait le
// numero principal serait incoherent avec l'ecran.
//
// `raison_sociale` n'y figure plus : la migration a reporte sa valeur dans
// `nom`, qui est donc cherche a sa place. `qualification` et `fonction` ont ete
// supprimees, et les lister ici ferait echouer la requete sur une colonne
// inconnue.
$searchedColumns = [
    'code', 'nom', 'email', 'ville', 'ice', 'rc',
    'telephone', 'telephone_fixe', 'telephone_mobile',
    'identifiant_fiscal', 'taxe_professionnelle',
];

$cabinets = [];
if ($db) {
    // Les conditions sont assemblees puis IMPLIQUEES entre elles. Un AND ecrit
    // a la suite d'une serie de OR ne porterait que sur le dernier OR :
    // AND lie plus fort que OR, le filtre type disparaitrait de fait.
    $where = [];
    $params = [];

    if ($query !== '') {
        $like = like_term($query);
        $ors = [];
        foreach ($searchedColumns as $i => $column) {
            $param = 'like' . $i;
            $ors[] = "c.$column LIKE :$param";
            $params[$param] = $like;
        }
        $where[] = '(' . implode(' OR ', $ors) . ')';
    }

    if ($typeFilter !== '') {
        $where[] = 'c.type_cabinet = :filtre_type';
        $params['filtre_type'] = $typeFilter;
    }

    $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

    $stmt = $db->prepare('
        SELECT c.*,
               (SELECT COUNT(*) FROM abonnements a WHERE a.cabinet_id = c.id) AS nb_abonnements,
               (SELECT COUNT(*) FROM users u WHERE u.cabinet_id = c.id) AS nb_utilisateurs
        FROM cabinets c
        ' . $whereSql . '
        ORDER BY c.nom ASC
    ');
    $stmt->execute($params);
    $cabinets = $stmt->fetchAll();

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static fn (array $r): array => [
            $r['code'],
            cabinet_type_label((string) ($r['type_cabinet'] ?? '')),
            $r['nom'],
            $r['email'] ?? '-',
            $r['telephone'] ?? '-',
            $r['telephone_fixe'] ?? '-',
            $r['telephone_mobile'] ?? '-',
            $r['adresse'] ?? '-',
            $r['ville'] ?? '-',
            $r['ice'] ?? '-',
            $r['rc'] ?? '-',
            $r['identifiant_fiscal'] ?? '-',
            $r['taxe_professionnelle'] ?? '-',
            cabinet_statut_label((string) $r['statut']),
            $r['nb_utilisateurs'] ?? 0,
            $r['nb_abonnements'] ?? 0,
        ], $cabinets);

        $headers = [
            'Code', 'Type de cabinet', 'Nom du cabinet', 'Email',
            'Téléphone', 'Téléphone fixe', 'Téléphone mobile', 'Adresse', 'Ville',
            'ICE', 'RC', 'Identifiant fiscal (IF)', 'Taxe professionnelle (TP)',
            'Statut', 'Utilisateurs', 'Abonnements',
        ];

        if ($exportType === 'csv') {
            export_csv('cabinets.csv', $headers, $rows);
        } else {
            export_excel('cabinets.xlsx', $headers, $rows);
        }
    }
}

$statutOptions = [];
foreach (cabinet_statut_options() as $s) {
    $statutOptions[$s] = cabinet_statut_label($s);
}

$typeOptions = [];
foreach (cabinet_type_options() as $t) {
    $typeOptions[$t] = cabinet_type_label($t);
}

// Rendus d'erreur par champ, reutilises sur les seize saisies : sans eux le
// gabarit du formulaire triple la condition d'erreur a chaque fois, et le
// message finit systematiquement par ne plus etre pose sur la bonne ligne.
$err = static function (string $champ) use ($fieldErrors): string {
    return isset($fieldErrors[$champ]) ? ' class="input-error" aria-invalid="true"' : '';
};

$msg = static function (string $champ) use ($fieldErrors): string {
    $texte = $fieldErrors[$champ] ?? '';
    return $texte === '' ? '' : '<small class="field-error">' . e($texte) . '</small>';
};
?>
<section class="stack">
    <?php if ($formOpen && ($canCreate || $canEdit)): ?>
        <div class="section-header" style="margin-bottom:4px;">
            <h2><?= $editId > 0 ? 'Modifier le cabinet' : 'Nouveau cabinet' ?></h2>
            <div class="table-actions">
                <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Fermer</a>
            </div>
        </div>

        <form method="post" class="form-compact">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($editId > 0): ?>
                <input type="hidden" name="id" value="<?= e((string) $editId) ?>">
            <?php endif; ?>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">business</span>
                    <h2>Identité du cabinet</h2>
                </div>
                <div class="form-grid">
                    <label class="field<?= isset($fieldErrors['code']) ? ' field-error' : '' ?>">
                        <span>Code <em class="req-mark">*</em></span>
                        <input type="text" name="code" required maxlength="40" value="<?= e((string) ($formData['code'] ?? '')) ?>" placeholder="CAB-001"<?= $err('code') ?>>
                        <?= $msg('code') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['type_cabinet']) ? ' field-error' : '' ?>">
                        <span>Type <em class="req-mark">*</em></span>
                        <select name="type_cabinet" required<?= $err('type_cabinet') ?>>
                            <option value="" disabled<?= (string) ($formData['type_cabinet'] ?? '') === '' ? ' selected' : '' ?>>Choisir un type</option>
                            <?php foreach ($typeOptions as $val => $lbl): ?>
                                <option value="<?= e($val) ?>"<?= (string) ($formData['type_cabinet'] ?? '') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $msg('type_cabinet') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['nom']) ? ' field-error' : '' ?>">
                        <span>Nom du cabinet / Raison sociale <em class="req-mark">*</em></span>
                        <input type="text" name="nom" required maxlength="150" value="<?= e((string) ($formData['nom'] ?? '')) ?>" placeholder="Cabinet Exemple"<?= $err('nom') ?>>
                        <?= $msg('nom') ?>
                    </label>
                    <label class="field">
                        <span>Statut</span>
                        <select name="statut">
                            <?php foreach ($statutOptions as $val => $lbl): ?>
                                <option value="<?= e($val) ?>"<?= (string) ($formData['statut'] ?? 'actif') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">contact_mail</span>
                    <h2>Contact</h2>
                </div>
                <div class="form-grid">
                    <label class="field<?= isset($fieldErrors['email']) ? ' field-error' : '' ?>">
                        <span>Email</span>
                        <input type="email" name="email" maxlength="190" value="<?= e((string) ($formData['email'] ?? '')) ?>" placeholder="contact@cabinet.ma"<?= $err('email') ?>>
                        <?= $msg('email') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['telephone']) ? ' field-error' : '' ?>">
                        <span>Téléphone</span>
                        <input type="text" name="telephone" maxlength="40" value="<?= e((string) ($formData['telephone'] ?? '')) ?>" placeholder="0522 00 00 00"<?= $err('telephone') ?>>
                        <?= $msg('telephone') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['telephone_fixe']) ? ' field-error' : '' ?>">
                        <span>Téléphone fixe</span>
                        <input type="text" name="telephone_fixe" maxlength="60" value="<?= e((string) ($formData['telephone_fixe'] ?? '')) ?>" placeholder="0522 00 00 00"<?= $err('telephone_fixe') ?>>
                        <?= $msg('telephone_fixe') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['telephone_mobile']) ? ' field-error' : '' ?>">
                        <span>Téléphone mobile</span>
                        <input type="text" name="telephone_mobile" maxlength="60" value="<?= e((string) ($formData['telephone_mobile'] ?? '')) ?>" placeholder="0661 00 00 00"<?= $err('telephone_mobile') ?>>
                        <?= $msg('telephone_mobile') ?>
                    </label>
                    <label class="field full<?= isset($fieldErrors['adresse']) ? ' field-error' : '' ?>">
                        <span>Adresse</span>
                        <input type="text" name="adresse" maxlength="255" value="<?= e((string) ($formData['adresse'] ?? '')) ?>" placeholder="Rue, numéro, quartier"<?= $err('adresse') ?>>
                        <?= $msg('adresse') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['ville']) ? ' field-error' : '' ?>">
                        <span>Ville</span>
                        <input type="text" name="ville" maxlength="120" value="<?= e((string) ($formData['ville'] ?? '')) ?>" placeholder="Casablanca"<?= $err('ville') ?>>
                        <?= $msg('ville') ?>
                    </label>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">verified</span>
                    <h2>Identifiants légaux</h2>
                </div>
                <div class="form-grid">
                    <label class="field<?= isset($fieldErrors['ice']) ? ' field-error' : '' ?>">
                        <span>ICE</span>
                        <input type="text" name="ice" maxlength="40" value="<?= e((string) ($formData['ice'] ?? '')) ?>" placeholder="001234567890123"<?= $err('ice') ?>>
                        <?= $msg('ice') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['rc']) ? ' field-error' : '' ?>">
                        <span>RC</span>
                        <input type="text" name="rc" maxlength="60" value="<?= e((string) ($formData['rc'] ?? '')) ?>" placeholder="123456"<?= $err('rc') ?>>
                        <?= $msg('rc') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['identifiant_fiscal']) ? ' field-error' : '' ?>">
                        <span>IF</span>
                        <input type="text" name="identifiant_fiscal" maxlength="100" value="<?= e((string) ($formData['identifiant_fiscal'] ?? '')) ?>" placeholder="40312785"<?= $err('identifiant_fiscal') ?>>
                        <?= $msg('identifiant_fiscal') ?>
                    </label>
                    <label class="field<?= isset($fieldErrors['taxe_professionnelle']) ? ' field-error' : '' ?>">
                        <span>TP</span>
                        <input type="text" name="taxe_professionnelle" maxlength="100" value="<?= e((string) ($formData['taxe_professionnelle'] ?? '')) ?>" placeholder="27185403"<?= $err('taxe_professionnelle') ?>>
                        <?= $msg('taxe_professionnelle') ?>
                    </label>
                </div>
            </article>

            <article class="card">
                <div class="section-header">
                    <span class="material-symbols-outlined">visibility</span>
                    <h2>Aperçu généré</h2>
                </div>
                <div class="form-grid">
                    <label class="field">
                        <span>Nom complet généré</span>
                        <input type="text" value="<?= e((string) ($formData['nom'] ?? '')) ?>" placeholder="Remplissez le nom du cabinet" readonly data-apercu="nom">
                    </label>
                    <label class="field">
                        <span>Code généré</span>
                        <input type="text" value="<?= e((string) ($formData['code'] ?? '')) ?>" placeholder="CAB-001" readonly data-apercu="code">
                    </label>
                </div>
            </article>

            <div class="form-actions">
                <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Annuler</a>
                <?php if ($editId > 0): ?>
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">save</span> Mettre à jour</button>
                <?php else: ?>
                    <button class="btn btn-next" type="submit"><span class="material-symbols-outlined">add</span> Créer le cabinet</button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>

    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($cabinets) ?> cabinet(s)</span>
            <div class="table-actions">
                <a class="btn btn-info" href="<?= e(app_url('cabinets', ['export' => 'csv', 'q' => $query, 'type' => $typeFilter])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-info" href="<?= e(app_url('cabinets', ['export' => 'xlsx', 'q' => $query, 'type' => $typeFilter])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
            </div>
        </div>

        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="cabinets">
            <div class="inline-form">
                <input type="search" name="q" placeholder="Rechercher par code, nom, ville, email, ICE, RC, IF ou TP" value="<?= e($query) ?>">
                <select name="type" aria-label="Filtrer par type de cabinet">
                    <option value="">Tous les types</option>
                    <?php foreach ($typeOptions as $val => $lbl): ?>
                        <option value="<?= e($val) ?>"<?= $typeFilter === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== '' || $typeFilter !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url('cabinets')) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$cabinets): ?>
            <p class="table-empty">
                <?php if ($query !== '' || $typeFilter !== ''): ?>
                    Aucun cabinet ne correspond à cette recherche.
                <?php else: ?>
                    Aucun cabinet enregistré. Les abonnements se rattachent à un cabinet : créez d'abord votre premier client.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <div class="table-scroll">
                <table data-sortable data-table="cabinets">
                    <thead>
                        <tr>
                            <th data-col="code">Code</th>
                            <th data-col="type">Type</th>
                            <th data-col="nom">Nom</th>
                            <th data-col="identifiants">Identifiants</th>
                            <th data-col="contact">Contact</th>
                            <th data-col="ville">Ville</th>
                            <th data-col="statut">Statut</th>
                            <th data-col="users">Utilisateurs</th>
                            <th data-col="abos">Abonnements</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cabinets as $cab): ?>
                        <?php
                        // Les identifiants sont empiles en petits caracteres : sur
                        // une ligne horizontale, quatre colonnes distinctes
                        // repoussaient les compteurs hors de l'ecran.
                        $identifiants = array_filter([
                            'ICE' => $cab['ice'] ?? '',
                            'RC' => $cab['rc'] ?? '',
                            'IF' => $cab['identifiant_fiscal'] ?? '',
                            'TP' => $cab['taxe_professionnelle'] ?? '',
                        ], static fn ($v): bool => $v !== null && $v !== '');
                        ?>
                        <tr>
                            <td><strong><?= e((string) $cab['code']) ?></strong></td>
                            <td><span class="badge <?= e(cabinet_type_tone((string) ($cab['type_cabinet'] ?? ''))) ?>"><?= e(cabinet_type_label((string) ($cab['type_cabinet'] ?? ''))) ?></span></td>
                            <td><?= e((string) $cab['nom']) ?></td>
                            <td>
                                <?php if ($identifiants === []): ?>
                                    <?= '-' ?>
                                <?php else: ?>
                                    <?php foreach ($identifiants as $sigle => $value): ?>
                                        <small><?= e($sigle) ?> : <?= e((string) $value) ?></small><br>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e((string) ($cab['email'] ?? '-')) ?>
                                <?php foreach (['telephone', 'telephone_fixe', 'telephone_mobile'] as $phoneField): ?>
                                    <?php if (!empty($cab[$phoneField])): ?>
                                        <br><small><?= e((string) $cab[$phoneField]) ?></small>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td><?= e((string) ($cab['ville'] ?? '-')) ?></td>
                            <td><span class="badge <?= e(cabinet_statut_tone((string) $cab['statut'])) ?>"><?= e(cabinet_statut_label((string) $cab['statut'])) ?></span></td>
                            <td><?= (int) ($cab['nb_utilisateurs'] ?? 0) ?></td>
                            <td><?= (int) ($cab['nb_abonnements'] ?? 0) ?></td>
                            <td class="table-actions">
                                <?php if ($canEdit): ?>
                                    <a class="btn-icon info" href="<?= e(app_url('cabinets', ['edit' => (int) $cab['id']])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $cab['id']) ?>">
                                        <button class="btn-icon danger" type="submit" data-confirm="Supprimer le cabinet « <?= e((string) $cab['nom']) ?> » ?" data-confirm-title="Supprimer le cabinet" data-confirm-ok="Supprimer" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<script>
// Apercu genere : les deux lectures seules recopient en direct les saisies
// dont elles sont derivees. Elles n'ont pas de `name`, donc l'apercu ne peut
// pas etre poste ni ecrit en base : seul le script les alimente.
(function () {
    var form = document.querySelector('form.form-compact');
    if (!form) {
        return;
    }

    var sources = {
        nom: form.querySelector('[name="nom"]'),
        code: form.querySelector('[name="code"]')
    };

    form.addEventListener('input', function () {
        form.querySelectorAll('[data-apercu]').forEach(function (apercu) {
            var source = sources[apercu.getAttribute('data-apercu')];
            if (source) {
                apercu.value = source.value;
            }
        });
    });
})();
</script>
