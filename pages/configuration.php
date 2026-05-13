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
];

$tab = $_GET['tab'] ?? 'formes-juridiques';
if (!isset($tabs[$tab])) {
    $tab = 'formes-juridiques';
}

[$table, $column, $label] = $tabs[$tab];
$editKey = $_GET['edit'] ?? null;
$tabCounts = [];
$rows = [];
if (($pdo ?? null) instanceof PDO) {
    foreach ($tabs as $key => [$t, $c, $l]) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$t}");
        $tabCounts[$key] = (int) $stmt->fetchColumn();
    }
    $stmt = $pdo->query("SELECT id, {$column}, sort_order, created_at, updated_at FROM {$table} ORDER BY sort_order ASC, {$column} ASC");
    $rows = $stmt->fetchAll();
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $postTab = $_POST['tab'] ?? $tab;

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $value = field_value($_POST, $column);
        if ($value !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} ({$column}, sort_order) VALUES (:val, :so)");
            $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}")->fetchColumn();
            $stmt->execute(['val' => $value, 'so' => $max]);
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
            $stmt = $pdo->prepare("UPDATE {$table} SET {$column} = :new WHERE id = :id");
            $stmt->execute(['new' => $newValue, 'id' => $recordId]);
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
                    set_flash('success', 'Ordre mis a jour.');
                }
            }
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Configuration</h2>
            <p class="help-text">Gerer les listes de reference.</p>
        </div>
        <a class="btn btn-back" href="<?= e(app_url('creation')) ?>"><span class="mdi mdi-arrow-left"></span> Retour</a>
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
        <div style="display:flex;gap:6px">
            <input name="<?= e($column) ?>" placeholder="Nouveau..." required style="flex:1;padding:4px 8px;font-size:0.8125rem">
            <button type="submit" class="btn-icon" title="Ajouter" style="border:2px solid var(--primary);border-radius:var(--radius-sm);background:transparent;color:var(--primary);width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--transition)"><span class="mdi mdi-plus"></span></button>
        </div>
    </form>

    <?php if (count($rows) > 0): ?>
        <?php $firstId = (int) $rows[0]['id']; $lastId = (int) $rows[count($rows) - 1]['id']; ?>
        <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width:50px">#</th>
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
                ?>
                    <tr>
                        <?php if ($editKey === $val): ?>
                            <td style="color:var(--text-secondary);font-size:0.8rem"><?= $rid ?></td>
                            <td>
                                <form method="post" style="display:flex;gap:4px">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                    <input type="hidden" name="record_id" value="<?= $rid ?>">
                                    <input name="<?= e($column) ?>" value="<?= e($val) ?>" required style="flex:1;padding:2px 6px;font-size:0.8125rem">
                                    <button type="submit" class="btn-icon" title="Enregistrer"><span class="mdi mdi-check"></span></button>
                                    <a class="btn-icon" href="<?= e(app_url('configuration', ['tab' => $tab])) ?>" title="Annuler"><span class="mdi mdi-close"></span></a>
                                </form>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                        <?php else: ?>
                            <td style="color:var(--text-secondary);font-size:0.8rem"><?= $rid ?></td>
                            <td><?= e($val) ?></td>
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
    <?php else: ?>
        <p class="table-empty">Aucun(e) <?= e(mb_strtolower($label)) ?>.</p>
    <?php endif; ?>
</section>
