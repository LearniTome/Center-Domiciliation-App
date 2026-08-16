<?php

declare(strict_types=1);

// Page paramétrable : les wrappers creations_liste / domiciliations_liste définissent
// $listeType (null = toutes, 'creation', 'domiciliation') et $listePage (clé de page pour les URLs).
$listeType = $listeType ?? null;
$listePage = $listePage ?? ($page ?? 'societes');
$showCreationCol = ($listeType === null || $listeType === 'creation');

$query = search_term();
$user = current_user();
$isAdmin = $user && in_array((int) $user['role_id'], [1, 2], true);
$canEdit = has_permission('societes.edit');

if (isset($_GET['import_msg']) && $_GET['import_msg'] !== '') {
    set_flash('success', htmlspecialchars($_GET['import_msg']));
}

// Reference data for quick create / bulk edit
$formesOptions = [];
$villesOptions = [];
$tribunauxOptions = [];
$adressesOptions = [];
$tribunalTypes = [];
$activitesOptions = [];
if (($pdo ?? null) instanceof PDO) {
    $formesOptions = fetch_reference_options($pdo, 'ref_formes_juridiques', 'forme_juridique');
    $villesOptions = fetch_reference_options($pdo, 'ref_villes', 'ville');
    $tribunauxOptions = fetch_reference_options($pdo, 'ref_tribunaux', 'tribunal');
    $adressesOptions = fetch_reference_options($pdo, 'ref_ste_adresses', 'adresse');
    $tribunalTypes = fetch_tribunaux_types($pdo);
    $tribunauxAll = fetch_tribunaux_all($pdo);
    $adressesAll = fetch_adresses_all($pdo);
    $activitesOptions = fetch_reference_options($pdo, 'ref_activites', 'activite');
}

$societeDefaults = load_defaults('societe');

// JSON-encoded option arrays for inline editable selects
$formesJson = e(json_encode(array_values($formesOptions)));
$villesJson = e(json_encode(array_values($villesOptions)));
$tribunauxJson = e(json_encode(array_values($tribunauxOptions)));
$sourceOptions = ['creation', 'domiciliation', 'cession'];
$sourceJson = e(json_encode($sourceOptions));
$tribunalTypesJson = e(json_encode($tribunalTypes));

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM societes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        log_activity($pdo, 'delete', 'societe', (int) $_POST['id']);
        set_flash('success', 'Societe supprimee avec succes.');
        redirect_to($listePage);
    }
}

if (($pdo ?? null) instanceof PDO) {
    $userFilter = '';
    $userParams = [];
    if (!$isAdmin && $user) {
        $userFilter = ' AND created_by = :user_id';
        $userParams['user_id'] = (int) $user['id'];
    }
    $typeFilter = '';
    $typeParams = [];
    if ($listeType === 'creation') {
        $typeFilter = ' AND societe_type_generation = :type_gen';
        $typeParams['type_gen'] = 'creation';
    } elseif ($listeType === 'domiciliation') {
        $typeFilter = ' AND societe_type_generation = :type_gen';
        $typeParams['type_gen'] = 'domiciliation';
    }
    if ($query !== '') {
        $likeTerm = like_term($query);
        $stmt = $pdo->prepare('
            SELECT *
            FROM societes
            WHERE (societe_dossier_domiciliation_number LIKE :term1 OR societe_dossier_creation_number LIKE :term2 OR societe_raison_sociale LIKE :term3 OR societe_forme_juridique LIKE :term4 OR societe_ice LIKE :term5 OR societe_ville LIKE :term6)
            ' . $userFilter . $typeFilter . '
            ORDER BY id DESC
        ');
        $params = [
            'term1' => $likeTerm,
            'term2' => $likeTerm,
            'term3' => $likeTerm,
            'term4' => $likeTerm,
            'term5' => $likeTerm,
            'term6' => $likeTerm,
        ] + $userParams + $typeParams;
        $stmt->execute($params);
        $societes = $stmt->fetchAll();
    } else {
        $sql = 'SELECT * FROM societes';
        $where = [];
        if ($userFilter) {
            $where[] = 'created_by = :user_id';
        }
        if ($typeFilter) {
            $where[] = substr($typeFilter, 4); // enlève le ' AND '
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($userParams + $typeParams);
        $societes = $stmt->fetchAll();
    }

    $exportType = $_GET['export'] ?? '';
    if ($exportType === 'csv' || $exportType === 'xlsx') {
        $rows = array_map(static function (array $societe): array {
            return [
                $societe['id'],
                $societe['societe_raison_sociale'],
                $societe['societe_dossier_domiciliation_number'],
                $societe['societe_dossier_creation_number'] ?? '',
                $societe['societe_forme_juridique'],
                $societe['societe_source'] ?? 'creation',
                $societe['societe_ice'],
                format_date($societe['societe_date_ice'] ?? null),
                $societe['societe_rc'],
                $societe['societe_if'],
                $societe['societe_activites_statuts'] ?? '',
                $societe['societe_activites_ompic'] ? fetch_activites_ompic_display($pdo ?? null, (string) $societe['societe_activites_ompic']) : '',
                $societe['societe_tribunal'],
                $societe['societe_ville'],
                $societe['societe_email'],
                $societe['societe_telephone'],
                $societe['societe_capital'],
            ];
        }, $societes);

        $headers = [
            'ID', 'Raison sociale', 'Dossier domiciliation', 'Dossier creation', 'Forme juridique',
            'Origine', 'ICE', 'Date cert. negatif', 'RC', 'IF',
            'Activites (Statuts)', 'Activites (OMPIC)', 'Tribunal', 'Ville',
            'Email', 'Telephone', 'Capital',
        ];

        if ($exportType === 'csv') {
            export_csv($listePage . '.csv', $headers, $rows);
        } else {
            export_excel($listePage . '.xlsx', $headers, $rows);
        }
    }
} else {
    $societes = [];
}

?>
<section>
    <article class="card">
        <div class="section-header">
            <span class="page-count"><?= count($societes) ?> enregistrement(s)</span>
            <div class="table-actions">
                <?php if (has_permission('societes.create')): ?>
                <button class="btn btn-next" type="button" data-quick-create-btn><span class="material-symbols-outlined">add</span> Nouvelle societe</button>
                <?php endif; ?>
                <button class="btn btn-secondary" type="button" data-col-toggle-btn><span class="material-symbols-outlined">view_column</span> Colonnes <span class="col-toggle-count" data-col-count>0/0</span></button>
                <a class="btn btn-info" href="<?= e(app_url($listePage, ['export' => 'csv', 'q' => $query])) ?>"><span class="material-symbols-outlined">download</span> CSV</a>
                <a class="btn btn-next" href="<?= e(app_url($listePage, ['export' => 'xlsx', 'q' => $query])) ?>"><span class="material-symbols-outlined">table_chart</span> Excel</a>
                <?php if (has_permission('societes.import')): ?>
                <button class="btn btn-secondary" type="button" data-import-btn="societes"><span class="material-symbols-outlined">upload_file</span> Importer Excel</button>
                <?php endif; ?>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="<?= e($listePage) ?>">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par dossier, societe, ICE, forme ou ville"
                    value="<?= e($query) ?>"
                >
                <button type="submit"><span class="material-symbols-outlined">search</span> Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-cancel" href="<?= e(app_url($listePage)) ?>"><span class="material-symbols-outlined">close</span> Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$societes): ?>
            <p class="table-empty">Aucune societe pour le moment.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table data-col-toggle data-sortable data-table="societes" data-bulk>
                <thead>
                <tr>
                    <th data-bulk-col><input type="checkbox" data-bulk-select-all title="Tout selectionner"></th>
                    <th data-col="dossier">N° Dossier Domiciliation</th>
                    <?php if ($showCreationCol): ?>
                    <th data-col="dossier-creation">N° Dossier Creation</th>
                    <?php endif; ?>
                    <th data-col="source">Origine</th>
                    <th data-col="raison-sociale">Raison sociale</th>
                    <th data-col="forme">Forme</th>
                    <th data-col="ice">ICE</th>
                    <th data-col="date-ompic">Date cert. OMPIC</th>
                    <th data-col="rc">RC</th>
                    <th data-col="if">IF</th>
                    <th data-col="capital">Capital</th>
                    <th data-col="ville">Ville</th>
                    <th data-col="tribunal">Tribunal</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="email">Email</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($societes as $societe): ?>
                    <tr data-id="<?= (int) $societe['id'] ?>">
                        <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                        <td<?= $canEdit ? ' data-editable="societe_dossier_domiciliation_number"' : '' ?>><?= e($societe['societe_dossier_domiciliation_number'] ?? '-') ?></td>
                        <?php if ($showCreationCol): ?>
                        <td<?= $canEdit ? ' data-editable="societe_dossier_creation_number"' : '' ?>><?= e($societe['societe_dossier_creation_number'] ?? '-') ?></td>
                        <?php endif; ?>
                        <td<?= $canEdit ? ' data-editable="societe_source" data-editable-options="' . $sourceJson . '"' : '' ?>>
                            <?php $src = $societe['societe_source'] ?? 'creation'; ?>
                            <?php if ($src === 'cession'): ?>
                                <span class="badge badge-info" style="font-size:0.65rem">Cession</span>
                            <?php elseif ($src !== 'creation'): ?>
                                <span class="badge badge-info" style="font-size:0.65rem"><?= e(ucfirst($src)) ?></span>
                            <?php else: ?>
                                <span class="badge badge-success" style="font-size:0.65rem">Creation</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500"><?= e($societe['societe_raison_sociale']) ?></a></td>
                        <td<?= $canEdit ? ' data-editable="societe_forme_juridique" data-editable-options="' . $formesJson . '"' : '' ?>><?= e($societe['societe_forme_juridique']) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_ice"' : '' ?>><?= e($societe['societe_ice'] ?? '-') ?></td>
                        <td><?= e(format_date($societe['societe_date_ice'] ?? null)) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_rc"' : '' ?>><?= e($societe['societe_rc'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_if"' : '' ?>><?= e($societe['societe_if'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_capital"' : '' ?>><?= $societe['societe_capital'] !== null ? e(number_format((float) $societe['societe_capital'], 2, ',', ' ') . ' DH') : '-' ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_ville" data-editable-options="' . $villesJson . '"' : '' ?>><?= e($societe['societe_ville']) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_tribunal" data-editable-options="' . $tribunauxJson . '"' : '' ?>><?= e($societe['societe_tribunal'] ?? '-') ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_telephone"' : '' ?>><?= e($societe['societe_telephone']) ?></td>
                        <td<?= $canEdit ? ' data-editable="societe_email"' : '' ?>><?= e($societe['societe_email'] ?? '-') ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $societe['created_at']))) ?></td>
                        <td><?= e(date('d/m/Y', strtotime((string) $societe['updated_at']))) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon primary" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                            <a class="btn-icon info" href="<?= e(app_url('societe', ['id' => (int) $societe['id'], 'edit' => '1'])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $societe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette societe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <template data-row-template>
                <tr data-id="">
                    <td data-bulk-cell><input type="checkbox" data-bulk-checkbox title="Selectionner"></td>
                    <td data-cell="societe_dossier_domiciliation_number"></td>
                    <?php if ($showCreationCol): ?>
                    <td data-cell="societe_dossier_creation_number"></td>
                    <?php endif; ?>
                    <td><span class="badge <?= $listeType === 'domiciliation' ? 'badge-info' : 'badge-success' ?>" style="font-size:0.65rem"><?= $listeType === 'domiciliation' ? 'Domiciliation' : 'Creation' ?></span></td>
                    <td data-cell-link="societe" data-cell-value="id" data-cell-label="societe_raison_sociale"></td>
                    <td data-cell="societe_forme_juridique"></td>
                    <td data-cell="societe_ice"></td>
                    <td data-cell="societe_date_ice"></td>
                    <td data-cell="societe_rc"></td>
                    <td data-cell="societe_if"></td>
                    <td data-cell="societe_capital"></td>
                    <td data-cell="societe_ville"></td>
                    <td data-cell="societe_tribunal"></td>
                    <td data-cell="societe_telephone"></td>
                    <td data-cell="societe_email"></td>
                    <td data-cell="created_at"></td>
                    <td data-cell="updated_at"></td>
                    <td data-cell-actions>
                        <a class="btn-icon primary" href="" title="Voir"><span class="material-symbols-outlined">visibility</span></a>
                        <a class="btn-icon info" href="" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                        <form method="post" action="index.php?page=<?= e($listePage) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="_csrf_token" value="">
                            <button class="btn-icon danger" type="submit" data-confirm="Supprimer cette societe ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                        </form>
                    </td>
                </tr>
            </template>
            </div>
        <?php endif; ?>
    </article>

    <?php
    $quickCreateTitle = 'Nouvelle societe';
    $quickCreateTable = 'societes';
    $quickCreateDefaults = [];
    if ($listeType === 'creation' || $listeType === 'domiciliation') {
        $quickCreateDefaults['societe_type_generation'] = $listeType;
    }
    $adressesSimple = [];
    if (!empty($adressesAll)) {
        foreach ($adressesAll as $r) {
            $adressesSimple[] = $r['ste_adresse'];
        }
    }
    $quickCreateFields = [
        ['type' => 'hidden', 'name' => 'societe_type_generation', 'value' => $listeType ?? ''],
        ['type' => 'title', 'label' => 'Identifiants'],
        ['name' => 'societe_raison_sociale', 'label' => 'Raison sociale', 'type' => 'text', 'required' => true],
        ['name' => 'societe_forme_juridique', 'label' => 'Forme juridique', 'type' => 'select', 'options' => $formesOptions, 'required' => true],
        ['name' => 'societe_ice', 'label' => 'ICE', 'type' => 'text'],
        ['name' => 'societe_date_ice', 'label' => 'Date cert. negatif', 'type' => 'date'],
        ['name' => 'societe_date_exp_cert_neg', 'label' => 'Date exp. cert. negatif', 'type' => 'date'],
        ['name' => 'societe_rc', 'label' => 'RC', 'type' => 'text'],
        ['name' => 'societe_if', 'label' => 'IF', 'type' => 'text'],
        ['name' => 'societe_tp', 'label' => 'TP', 'type' => 'text'],
        ['name' => 'societe_cnss', 'label' => 'CNSS', 'type' => 'text'],
        ['name' => 'societe_activites_statuts', 'label' => 'Activites (Statuts)', 'type' => 'dynamic-select', 'options' => $activitesOptions],
        ['type' => 'title', 'label' => 'Capital'],
        ['name' => 'societe_capital', 'label' => 'Capital', 'type' => 'number'],
        ['name' => 'societe_part_social', 'label' => 'Part social', 'type' => 'number'],
        ['name' => 'societe_valeur_nominale', 'label' => 'Valeur nominale', 'type' => 'number'],
        ['type' => 'title', 'label' => 'Adresse'],
        ['name' => 'societe_adresse_siege', 'label' => 'Adresse de reference', 'type' => 'select', 'options' => $adressesSimple],
        ['name' => 'societe_ville', 'label' => 'Ville', 'type' => 'select', 'options' => $villesOptions],
        ['name' => 'societe_tribunal_type', 'label' => 'Type tribunal', 'type' => 'select', 'options' => $tribunalTypes],
        ['name' => 'societe_tribunal', 'label' => 'Tribunal', 'type' => 'select', 'options' => $tribunauxOptions],
        ['type' => 'title', 'label' => 'Contact'],
        ['name' => 'societe_email', 'label' => 'Email', 'type' => 'email'],
        ['name' => 'societe_telephone', 'label' => 'Telephone', 'type' => 'text'],
    ];
    require __DIR__ . '/../../includes/quick_create_modal.php'; ?>
    <script>
    (function() {
        var form = document.querySelector('[data-modal="quick-create"] [data-quick-create-form]');
        if (!form) return;

        form.addEventListener('click', function(e) {
            var addBtn = e.target.closest('[data-dynamic-add]');
            if (addBtn) {
                var container = form.querySelector('[data-dynamic-select="' + addBtn.getAttribute('data-dynamic-add') + '"]');
                var tmpl = form.querySelector('[data-dynamic-template]');
                if (container && tmpl) {
                    var clone = tmpl.content.cloneNode(true);
                    container.appendChild(clone);
                }
                return;
            }
            var rmBtn = e.target.closest('[data-dynamic-remove]');
            if (rmBtn) {
                var item = rmBtn.closest('[data-dynamic-item]');
                var parent = item ? item.parentNode : null;
                if (parent && parent.querySelectorAll('[data-dynamic-item]').length > 1) {
                    parent.removeChild(item);
                }
            }
        });

        form.addEventListener('submit', function() {
            var dynSelects = form.querySelectorAll('[data-dynamic-select]');
            dynSelects.forEach(function(container) {
                var items = container.querySelectorAll('[data-dynamic-item]');
                var vals = [];
                items.forEach(function(item) {
                    var sel = item.querySelector('[data-dynamic-option]');
                    if (sel && sel.value) vals.push(sel.value);
                });
                var name = container.getAttribute('data-dynamic-select');
                var h = document.createElement('input');
                h.type = 'hidden';
                h.name = name;
                h.value = vals.join(', ');
                form.appendChild(h);
            });
        });
    })();
    </script>
    <?php
    $bulkEditTitle = 'Modifier les societes selectionnees';
    $bulkEditTable = 'societes';
    $bulkEditFields = [
        ['name' => 'societe_forme_juridique', 'label' => 'Forme juridique', 'type' => 'select', 'options' => $formesOptions],
        ['name' => 'societe_ville', 'label' => 'Ville', 'type' => 'select', 'options' => $villesOptions],
        ['name' => 'societe_tribunal', 'label' => 'Tribunal', 'type' => 'select', 'options' => $tribunauxOptions],
    ];
    require __DIR__ . '/../../includes/bulk_edit_modal.php';
    ?>
</section>

<div class="bulk-toolbar" data-bulk-toolbar>
    <span class="bulk-info"><span class="bulk-count" data-bulk-count>0</span> enregistrement(s) selectionne(s)</span>
    <button class="btn btn-info" type="button" data-bulk-edit-btn><span class="material-symbols-outlined">edit_note</span> Modifier en masse</button>
</div>

<?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>
