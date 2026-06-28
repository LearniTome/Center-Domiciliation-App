<?php

declare(strict_types=1);

$table = 'pv_resolutions_templates';

$rows = [];
if (($pdo ?? null) instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT id, title, content, category, sort_order, created_at, updated_at FROM {$table} ORDER BY sort_order ASC, title ASC");
        $rows = $stmt->fetchAll();
    } catch (PDOException) {
        $rows = [];
    }
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && ($pdo ?? null) instanceof PDO) {
        $title = field_value($_POST, 'title');
        $content = $_POST['content'] ?? '';
        $content = trim($content);
        $category = field_value($_POST, 'category', 'cession');
        if ($title !== '' && $content !== '') {
            $max = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM {$table}")->fetchColumn();
            $stmt = $pdo->prepare("INSERT IGNORE INTO {$table} (title, content, category, sort_order) VALUES (:t, :c, :cat, :so)");
            $stmt->execute(['t' => $title, 'c' => $content, 'cat' => $category, 'so' => $max]);
            set_flash('success', 'Modèle de résolution ajouté.');
            log_activity($pdo, 'create', 'config_pv_templates', null, $title);
        } else {
            set_flash('error', 'Le titre et le contenu sont obligatoires.');
        }
        redirect_to('pv-templates');
    }

    if ($action === 'update' && ($pdo ?? null) instanceof PDO) {
        $recordId = int_value($_POST, 'record_id');
        $title = field_value($_POST, 'title');
        $content = $_POST['content'] ?? '';
        $content = trim($content);
        $category = field_value($_POST, 'category', 'cession');
        if ($recordId && $title !== '') {
            $stmt = $pdo->prepare("UPDATE {$table} SET title = :t, content = :c, category = :cat WHERE id = :id");
            $stmt->execute(['t' => $title, 'c' => $content, 'cat' => $category, 'id' => $recordId]);
            set_flash('success', 'Modèle de résolution modifié.');
            log_activity($pdo, 'update', 'config_pv_templates', $recordId, $title);
        }
        redirect_to('pv-templates');
    }

    if ($action === 'delete' && ($pdo ?? null) instanceof PDO) {
        $recordId = int_value($_POST, 'record_id');
        if ($recordId) {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
            $stmt->execute(['id' => $recordId]);
            set_flash('success', 'Modèle de résolution supprimé.');
            log_activity($pdo, 'delete', 'config_pv_templates', $recordId, '');
        }
        redirect_to('pv-templates');
    }
}

$editId = int_value($_GET, 'edit');
$categories = ['cession' => 'Cession de parts', 'general' => 'Général'];
?>
<style>
.pv-tpl-textarea { font-family:monospace;font-size:0.8rem; }
.pv-tpl-content-preview { font-size:0.78rem;color:var(--text-secondary);max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
</style>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Gérer les modèles de résolutions pour les procès-verbaux de cession et assemblées générales.</p>
            <p class="help-text" style="font-size:0.75rem;margin-top:4px">Utilisez <code>**[Variable]**</code> pour les données dynamiques (affichées en gras dans le rendu).</p>
        </div>
        <div>
            <a class="btn btn-back" href="<?= e(app_url('configuration')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
        </div>
    </div>

    <form method="post" class="inline-form" style="margin-bottom:0.75rem">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:flex-start">
            <input name="title" placeholder="Titre du modèle..." required style="flex:1;min-width:180px;padding:4px 8px;font-size:0.8125rem">
            <select name="category" style="padding:4px 6px;font-size:0.8125rem">
                <?php foreach ($categories as $k => $v): ?>
                    <option value="<?= e($k) ?>"><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="content" placeholder="Contenu du modèle... (utilisez **[Variable]** pour le gras dynamique)" required style="flex:2;min-width:260px;padding:4px 8px;font-size:0.8rem;font-family:monospace;height:60px"><?php if (isset($_GET['edit'])): $eRow = current(array_filter($rows, fn($r) => (int)$r['id'] === $editId)); if ($eRow): ?><?= e($eRow['content']) ?><?php endif; endif; ?></textarea>
            <button type="submit" class="btn btn-next" style="padding:4px 10px;font-size:0.8125rem">Ajouter</button>
        </div>
    </form>

    <?php if (count($rows) > 0): ?>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th style="width:32px"></th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Aperçu</th>
                <th style="width:100px">Date création</th>
                <th style="width:120px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row):
                $rid = (int) $row['id'];
            ?>
                <tr>
                    <?php if ($editId === $rid): ?>
                        <td style="text-align:center;color:var(--text-secondary)"><span class="material-symbols-outlined">edit</span></td>
                        <td colspan="5">
                            <form method="post" style="display:flex;gap:4px;flex-wrap:wrap;align-items:flex-start">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="record_id" value="<?= $rid ?>">
                                <input name="title" value="<?= e($row['title']) ?>" required style="flex:1;min-width:160px;padding:2px 6px;font-size:0.8125rem">
                                <select name="category" style="padding:2px 4px;font-size:0.8125rem">
                                    <?php foreach ($categories as $k => $v): ?>
                                        <option value="<?= e($k) ?>" <?= $row['category'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea name="content" required style="flex:2;min-width:200px;padding:2px 6px;font-size:0.8rem;font-family:monospace;height:48px"><?= e($row['content']) ?></textarea>
                                <button type="submit" class="btn-icon" title="Enregistrer"><span class="material-symbols-outlined">check</span></button>
                                <a class="btn-icon" href="<?= e(app_url('pv-templates')) ?>" title="Annuler"><span class="material-symbols-outlined">close</span></a>
                            </form>
                        </td>
                    <?php else: ?>
                        <td style="text-align:center;color:var(--text-secondary)"><span class="material-symbols-outlined">drag_indicator</span></td>
                        <td><strong><?= e($row['title']) ?></strong></td>
                        <td><span class="step-badge" style="background:var(--info)"><?= e($categories[$row['category']] ?? $row['category']) ?></span></td>
                        <td><span class="pv-tpl-content-preview" title="<?= e($row['content']) ?>"><?= e(mb_substr($row['content'], 0, 80)) ?><?= mb_strlen($row['content']) > 80 ? '...' : '' ?></span></td>
                        <td style="font-size:0.75rem;color:var(--text-secondary)"><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                        <td>
                            <div style="display:flex;gap:2px;align-items:center">
                                <a class="btn-icon" href="<?= e(app_url('pv-templates', ['edit' => $rid])) ?>" title="Modifier"><span class="material-symbols-outlined">edit</span></a>
                                <form method="post" style="display:inline">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="record_id" value="<?= $rid ?>">
                                    <button type="submit" class="btn-icon danger" data-confirm="Supprimer <?= e($row['title']) ?> ?" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
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
    <div class="config-empty">
        <span class="material-symbols-outlined">playlist_add_check</span>
        <p>Aucun modèle de résolution pour le moment.</p>
    </div>
    <?php endif; ?>
</section>
