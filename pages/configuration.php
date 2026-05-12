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
$options = fetch_reference_options($pdo ?? null, $table, $column);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $postTab = $_POST['tab'] ?? $tab;

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $value = field_value($_POST, $column);
        if ($value !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} ({$column}) VALUES (:val)");
            $stmt->execute(['val' => $value]);
            set_flash('success', $label . ' ajoutee.');
        } else {
            set_flash('error', 'Le champ est obligatoire.');
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'update' && ($pdo ?? null) instanceof PDO) {
        $oldValue = field_value($_POST, 'old_value');
        $newValue = field_value($_POST, $column);
        if ($oldValue !== '' && $newValue !== '') {
            $stmt = $pdo->prepare("UPDATE {$table} SET {$column} = :new WHERE {$column} = :old");
            $stmt->execute(['new' => $newValue, 'old' => $oldValue]);
            set_flash('success', $label . ' modifiee.');
        }
        redirect_to('configuration', ['tab' => $postTab]);
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $value = field_value($_POST, $column);
        if ($value !== '') {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = :val");
            $stmt->execute(['val' => $value]);
            set_flash('success', $label . ' supprimee.');
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
        <a class="btn btn-secondary" href="<?= e(app_url('creation')) ?>">Retour</a>
    </div>

    <div class="tabs" style="margin-bottom:1rem">
        <?php foreach ($tabs as $key => [$t, $c, $l]): ?>
            <a class="tab <?= $key === $tab ? 'active' : '' ?>" href="<?= e(app_url('configuration', ['tab' => $key])) ?>"><?= e($l) ?></a>
        <?php endforeach; ?>
    </div>

    <form method="post" class="inline-form" style="margin-bottom:0.75rem">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div style="display:flex;gap:6px">
            <input name="<?= e($column) ?>" placeholder="Nouveau..." required style="flex:1;padding:4px 8px;font-size:0.8125rem">
            <button type="submit" class="btn-icon" title="Ajouter"><span class="mdi mdi-plus"></span></button>
        </div>
    </form>

    <?php if (count($options) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= e($label) ?></th>
                    <th style="width:70px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($options as $value): ?>
                    <tr>
                        <?php if ($editKey === $value): ?>
                            <td>
                                <form method="post" style="display:flex;gap:4px">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                    <input type="hidden" name="old_value" value="<?= e($value) ?>">
                                    <input name="<?= e($column) ?>" value="<?= e($value) ?>" required style="flex:1;padding:2px 6px;font-size:0.8125rem">
                                    <button type="submit" class="btn-icon" title="Enregistrer"><span class="mdi mdi-check"></span></button>
                                    <a class="btn-icon" href="<?= e(app_url('configuration', ['tab' => $tab])) ?>" title="Annuler"><span class="mdi mdi-close"></span></a>
                                </form>
                            </td>
                            <td></td>
                        <?php else: ?>
                            <td><?= e($value) ?></td>
                            <td>
                                <div style="display:flex;gap:2px">
                                    <a class="btn-icon" href="<?= e(app_url('configuration', ['tab' => $tab, 'edit' => $value])) ?>" title="Modifier"><span class="mdi mdi-pencil"></span></a>
                                    <form method="post" style="display:inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                        <input type="hidden" name="<?= e($column) ?>" value="<?= e($value) ?>">
                                        <button type="submit" class="btn-icon danger" data-confirm="Supprimer <?= e($value) ?> ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="table-empty">Aucun(e) <?= e(mb_strtolower($label)) ?>.</p>
    <?php endif; ?>
</section>
