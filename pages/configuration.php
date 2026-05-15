<?php

declare(strict_types=1);

$tabs = [
    'formes-juridiques' => ['ref_formes_juridiques', 'forme_juridique', 'Formes juridiques'],
    'villes' => ['ref_villes', 'ville', 'Villes'],
    'tribunaux' => ['ref_tribunaux', 'tribunal', 'Tribunaux'],
    'nationalites' => ['ref_nationalites', 'nationalite', 'Nationalites'],
    'lieux-naissance' => ['ref_lieux_naissance', 'lieu_naissance', 'Lieux de naissance'],
    'adresses' => ['ref_ste_adresses', 'ste_adresse', 'Adresses'],
    'qualites-associe' => ['ref_qualites_associe', 'qualite_associe', 'Qualites associe'],
    'activites' => ['ref_activites', 'activite', 'Activites'],
    'certificat-negatif' => ['ref_nma2010', 'libelle', 'NMA2010'],
];

$tab = $_GET['tab'] ?? 'formes-juridiques';
if (!isset($tabs[$tab])) {
    $tab = 'formes-juridiques';
}

[$table, $column, $label] = $tabs[$tab];
$editKey = $_GET['edit'] ?? null;
$tabCounts = [];
$rows = [];
$isNmaTab = $tab === 'certificat-negatif';
$isTribunalTab = $tab === 'tribunaux';
if (($pdo ?? null) instanceof PDO) {
    foreach ($tabs as $key => [$t, $c, $l]) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$t}");
            $tabCounts[$key] = (int) $stmt->fetchColumn();
        } catch (PDOException) {
            $tabCounts[$key] = 0;
        }
    }
    try {
        $selectCols = $isNmaTab ? "id, code, {$column}, sort_order, created_at, updated_at" : ($isTribunalTab ? "id, tribunal_type, {$column}, sort_order, created_at, updated_at" : "id, {$column}, sort_order, created_at, updated_at");
        $orderBy = $isTribunalTab ? "FIELD(tribunal_type, 'Tribunal de commerce', 'Tribunal de Première Instance'), sort_order ASC, {$column} ASC" : "sort_order ASC, {$column} ASC";
        $stmt = $pdo->query("SELECT {$selectCols} FROM {$table} ORDER BY {$orderBy}");
        $rows = $stmt->fetchAll();
    } catch (PDOException) {
        $rows = [];
    }
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $postTab = $_POST['tab'] ?? $tab;

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $value = field_value($_POST, $column);
        if ($value !== '') {
            if ($tab === 'certificat-negatif') {
                $nmaCode = field_value($_POST, 'nma_code');
                if ($nmaCode === '') {
                    set_flash('error', 'Le code NMA2010 est obligatoire.');
                    redirect_to('configuration', ['tab' => $postTab]);
                }
                $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")->fetchColumn();
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} (code, {$column}, sort_order) VALUES (:code, :val, :so)");
                $stmt->execute(['code' => $nmaCode, 'val' => $value, 'so' => $max]);
            } elseif ($tab === 'tribunaux') {
                $tribunalType = field_value($_POST, 'tribunal_type');
                $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")->fetchColumn();
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} (tribunal, tribunal_type, sort_order) VALUES (:tribunal, :type, :so)");
                $stmt->execute(['tribunal' => $value, 'type' => $tribunalType, 'so' => $max]);
            } else {
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} ({$column}, sort_order) VALUES (:val, :so)");
                $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")->fetchColumn();
                $stmt->execute(['val' => $value, 'so' => $max]);
            }
            set_flash('success', $label . ' ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'update' && ($pdo ?? null) instanceof PDO) {
        $recordId = int_value($_POST, 'record_id');
        $newValue = field_value($_POST, $column);
        if ($recordId && $newValue !== '') {
            if ($tab === 'tribunaux') {
                $newType = field_value($_POST, 'tribunal_type');
                $stmt = $pdo->prepare("UPDATE {$table} SET {$column} = :new, tribunal_type = :type WHERE id = :id");
                $stmt->execute(['new' => $newValue, 'type' => $newType, 'id' => $recordId]);
            } else {
                $stmt = $pdo->prepare("UPDATE {$table} SET {$column} = :new WHERE id = :id");
                $stmt->execute(['new' => $newValue, 'id' => $recordId]);
            }
            set_flash('success', $label . ' modifiee.');
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $recordId = int_value($_POST, 'record_id');
        if ($recordId) {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
            $stmt->execute(['id' => $recordId]);
            set_flash('success', $label . ' supprimee.');
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'reorder' && ($pdo ?? null) instanceof PDO) {
        $recordId = int_value($_POST, 'record_id');
        $direction = $_POST['direction'] ?? '';
        if ($recordId && in_array($direction, ['up', 'down'], true)) {
            $current = $pdo->prepare("SELECT id, sort_order FROM {$table} WHERE id = :id");
            $current->execute(['id' => $recordId]);
            $cur = $current->fetch();
            if ($cur) {
                $curOrder = (int) $cur['sort_order'];
                $neighbour = $pdo->prepare(
                    $direction === 'up'
                        ? "SELECT id, sort_order FROM {$table} WHERE sort_order < :so ORDER BY sort_order DESC LIMIT 1"
                        : "SELECT id, sort_order FROM {$table} WHERE sort_order > :so ORDER BY sort_order ASC LIMIT 1"
                );
                $neighbour->execute(['so' => $curOrder]);
                $nb = $neighbour->fetch();
                if ($nb) {
                    $pdo->prepare("UPDATE {$table} SET sort_order = :so WHERE id = :id")->execute(['so' => $nb['sort_order'], 'id' => $recordId]);
                    $pdo->prepare("UPDATE {$table} SET sort_order = :so WHERE id = :id")->execute(['so' => $curOrder, 'id' => $nb['id']]);
                }
            }
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'reorder-batch' && ($pdo ?? null) instanceof PDO) {
        $ids = $_POST['ids'] ?? '';
        if ($ids !== '') {
            $idsArr = array_map('intval', explode(',', $ids));
            $stmt = $pdo->prepare("UPDATE {$table} SET sort_order = :so WHERE id = :id");
            foreach ($idsArr as $i => $id) {
                $stmt->execute(['so' => ($i + 1) * 10, 'id' => $id]);
            }
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'sort-az' && ($pdo ?? null) instanceof PDO) {
        $stmt = $pdo->query("SELECT id FROM {$table} ORDER BY {$column} ASC");
        $all = $stmt->fetchAll();
        $update = $pdo->prepare("UPDATE {$table} SET sort_order = :so WHERE id = :id");
        foreach ($all as $i => $row) {
            $update->execute(['so' => ($i + 1) * 10, 'id' => (int) $row['id']]);
        }
        set_flash('success', 'Ordre alphabetique applique.');
        redirect_to('configuration', ['tab' => $tab]);
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Configuration</h2>
            <p class="help-text">Gerer les listes de reference.</p>
        </div>
        <div style="display:flex;gap:6px">
            <form method="post" style="display:inline">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="sort-az">
                <button type="submit" class="btn btn-info"><span class="mdi mdi-sort-alphabetical-ascending"></span> Trier A-Z</button>
            </form>
            <a class="btn btn-back" href="<?= e(app_url('creation')) ?>"><span class="mdi mdi-arrow-left"></span> Retour</a>
        </div>
    </div>

    <div class="tabs" style="margin-bottom:1rem">
        <?php foreach ($tabs as $key => [$t, $c, $l]): ?>
            <a class="tab <?= $key === $tab ? 'active' : '' ?>" href="<?= e(app_url('configuration', ['tab' => $key])) ?>">
                <?= e($l) ?>
                <?php if (isset($tabCounts[$key])): ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:var(--primary);color:#fff;font-size:0.65rem;margin-left:4px;line-height:1;vertical-align:middle"><?= $tabCounts[$key] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="post" class="inline-form" style="margin-bottom:0.75rem">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if ($isTribunalTab): ?>
                <select name="tribunal_type" required style="padding:4px 8px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
                    <option value="">Type...</option>
                    <option value="Tribunal de commerce">Tribunal de commerce</option>
                    <option value="Tribunal de Première Instance">Tribunal de Première Instance</option>
                </select>
            <?php endif; ?>
            <?php if ($isNmaTab): ?>
                <input name="nma_code" placeholder="Code..." required style="width:100px;padding:4px 8px;font-size:0.8125rem">
            <?php endif; ?>
            <input name="<?= e($column) ?>" placeholder="Nouveau..." required style="flex:1;padding:4px 8px;font-size:0.8125rem;min-width:120px">
            <button type="submit" class="btn-icon" title="Ajouter" style="border:2px solid var(--primary);border-radius:var(--radius-sm);background:transparent;color:var(--primary);width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--transition)"><span class="mdi mdi-plus"></span></button>
        </div>
    </form>

    <?php if (count($rows) > 0): ?>
        <?php $firstId = (int) $rows[0]['id']; $lastId = (int) $rows[count($rows) - 1]['id']; ?>
        <div class="table-scroll">
        <table id="config-table" data-csrf="<?= e(csrf_token()) ?>">
            <thead>
                <tr>
                    <th style="width:32px"></th>
                    <?php if ($isTribunalTab): ?>
                        <th>Type</th>
                    <?php endif; ?>
                    <th><?= e($label) ?></th>
                    <th style="width:100px">Date creation</th>
                    <th style="width:100px">Modification</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $rid = (int) $row['id'];
                    $val = (string) $row[$column];
                    $typeVal = $isTribunalTab ? ((string) ($row['tribunal_type'] ?? '')) : '';
                ?>
                    <tr <?= $editKey === $val ? '' : 'draggable="true"' ?> data-record-id="<?= $rid ?>">
                        <?php if ($editKey === $val): ?>
                            <td style="text-align:center;color:var(--text-secondary)"><span class="mdi mdi-drag-vertical"></span></td>
                            <td <?= $isTribunalTab ? 'colspan="2"' : '' ?>>
                                <form method="post" style="display:flex;gap:4px">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                    <input type="hidden" name="record_id" value="<?= $rid ?>">
                                    <?php if ($isTribunalTab): ?>
                                        <select name="tribunal_type" required style="padding:2px 4px;font-size:0.8125rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)">
                                            <option value="Tribunal de commerce" <?= $typeVal === 'Tribunal de commerce' ? 'selected' : '' ?>>Tribunal de commerce</option>
                                            <option value="Tribunal de Première Instance" <?= $typeVal === 'Tribunal de Première Instance' ? 'selected' : '' ?>>Tribunal de Première Instance</option>
                                        </select>
                                    <?php endif; ?>
                                    <?php if ($isNmaTab): ?>
                                        <span style="padding:2px 6px;font-size:0.8125rem;color:var(--text-secondary)"><?= e((string) $row['code']) ?> -</span>
                                    <?php endif; ?>
                                    <input name="<?= e($column) ?>" value="<?= e($val) ?>" required style="flex:1;padding:2px 6px;font-size:0.8125rem">
                                    <button type="submit" class="btn-icon" title="Enregistrer"><span class="mdi mdi-check"></span></button>
                                    <a class="btn-icon" href="<?= e(app_url('configuration', ['tab' => $tab])) ?>" title="Annuler"><span class="mdi mdi-close"></span></a>
                                </form>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <?php if ($isTribunalTab): ?><td></td><?php endif; ?>
                        <?php else: ?>
                            <td style="text-align:center;color:var(--text-secondary);cursor:grab"><span class="mdi mdi-drag-vertical"></span></td>
                            <?php if ($isTribunalTab): ?>
                                <td><?= e($typeVal ?: '-') ?></td>
                            <?php endif; ?>
                            <td><?= $isNmaTab ? e((string) $row['code'] . ' - ' . $val) : e($val) ?></td>
                            <td style="font-size:0.75rem;color:var(--text-secondary)"><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                            <td style="font-size:0.75rem;color:var(--text-secondary)"><?= $row['updated_at'] ? date('d/m/Y H:i', strtotime($row['updated_at'])) : '-' ?></td>
                            <td>
                                <div style="display:flex;gap:2px;align-items:center">
                                    <form method="post" style="display:inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="reorder">
                                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                        <input type="hidden" name="record_id" value="<?= $rid ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn-icon" title="Monter" <?= $rid === $firstId ? 'disabled style="opacity:0.3"' : '' ?>><span class="mdi mdi-chevron-up"></span></button>
                                    </form>
                                    <form method="post" style="display:inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="reorder">
                                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                        <input type="hidden" name="record_id" value="<?= $rid ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn-icon" title="Descendre" <?= $rid === $lastId ? 'disabled style="opacity:0.3"' : '' ?>><span class="mdi mdi-chevron-down"></span></button>
                                    </form>
                                    <span style="width:6px;display:inline-block"></span>
                                    <a class="btn-icon" href="<?= e(app_url('configuration', ['tab' => $tab, 'edit' => $val])) ?>" title="Modifier"><span class="mdi mdi-pencil"></span></a>
                                    <form method="post" style="display:inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                        <input type="hidden" name="record_id" value="<?= $rid ?>">
                                        <button type="submit" class="btn-icon danger" data-confirm="Supprimer <?= e($val) ?> ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('config-table');
            if (!table) return;
            const tbody = table.querySelector('tbody');
            let dragRow = null;

            tbody.addEventListener('dragstart', function (e) {
                const tr = e.target.closest('tr');
                if (!tr || tr.querySelector('form[style*="flex"]')) return;
                dragRow = tr;
                tr.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            tbody.addEventListener('dragend', function (e) {
                const tr = e.target.closest('tr');
                if (tr) tr.classList.remove('dragging');
                tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over'));
                dragRow = null;
            });

            tbody.addEventListener('dragover', function (e) {
                e.preventDefault();
                const tr = e.target.closest('tr');
                if (!tr || tr === dragRow) return;
                tr.classList.add('drag-over');
            });

            tbody.addEventListener('dragleave', function (e) {
                const tr = e.target.closest('tr');
                if (tr) tr.classList.remove('drag-over');
            });

            tbody.addEventListener('drop', function (e) {
                e.preventDefault();
                const target = e.target.closest('tr');
                if (!target || !dragRow || target === dragRow) return;
                target.classList.remove('drag-over');

                if (target.parentNode !== tbody || dragRow.parentNode !== tbody) return;

                const rows = [...tbody.querySelectorAll('tr')].filter(r => !r.querySelector('form[style*="flex"]'));
                const idxA = rows.indexOf(dragRow);
                const idxB = rows.indexOf(target);
                if (idxA === -1 || idxB === -1) return;

                if (idxA < idxB) {
                    target.parentNode.insertBefore(dragRow, target.nextSibling);
                } else {
                    target.parentNode.insertBefore(dragRow, target);
                }

                const ids = [...tbody.querySelectorAll('tr')]
                    .filter(r => !r.querySelector('form[style*="flex"]'))
                    .map(r => r.getAttribute('data-record-id'))
                    .filter(Boolean);

                const csrfToken = table.getAttribute('data-csrf');
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';
                const c = document.createElement('input');
                c.type = 'hidden';
                c.name = 'csrf_token';
                c.value = csrfToken;
                form.appendChild(c);
                const a = document.createElement('input');
                a.type = 'hidden';
                a.name = 'action';
                a.value = 'reorder-batch';
                form.appendChild(a);
                const t = document.createElement('input');
                t.type = 'hidden';
                t.name = 'tab';
                t.value = '<?= e($tab) ?>';
                form.appendChild(t);
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'ids';
                i.value = ids.join(',');
                form.appendChild(i);
                document.body.appendChild(form);
                form.submit();
            });
        });
        </script>
        <style>
        #config-table tbody tr[draggable="true"] {
            transition: opacity 0.15s;
        }
        #config-table tbody tr.dragging {
            opacity: 0.4;
        }
        #config-table tbody tr.drag-over {
            border-bottom: 2px solid var(--primary);
        }
        </style>
    <?php else: ?>
        <p class="table-empty">Aucun(e) <?= e(mb_strtolower($label)) ?>.</p>
    <?php endif; ?>
</section>
