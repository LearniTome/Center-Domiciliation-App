<?php
/**
 * Usage in a list page:
 *   $bulkEditTitle = 'Modifier en masse';
 *   $bulkEditTable = 'societes';
 *   $bulkEditFields = [
 *       ['name' => 'societe_ville', 'label' => 'Ville', 'type' => 'select', 'options' => $villesOptions],
 *       ...
 *   ];
 *   require __DIR__ . '/../../includes/bulk_edit_modal.php';
 */
?>
<div class="modal-overlay" data-modal="bulk-edit">
    <div class="modal-panel">
        <div class="modal-header">
            <h3><?= e($bulkEditTitle ?? 'Modification en masse') ?></h3>
            <button class="btn-icon" data-modal-close type="button" title="Fermer"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form data-bulk-form>
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="bulk_update">
            <input type="hidden" name="table" value="<?= e($bulkEditTable) ?>">
            <input type="hidden" name="ids" value="">
            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1rem">
                Les champs renseignes seront appliques a tous les enregistrements selectionnes.
            </p>
            <div class="form-grid">
                <?php foreach ((array) ($bulkEditFields ?? []) as $field): ?>
                    <label class="field<?= !empty($field['full']) ? ' full' : '' ?>">
                        <span><?= e($field['label'] ?? '') ?></span>
                        <?php if (($field['type'] ?? 'text') === 'select' && isset($field['options'])): ?>
                            <select name="<?= e($field['name'] ?? '') ?>">
                                <option value="">— Ne pas modifier —</option>
                                <?php foreach ($field['options'] as $val => $label): ?>
                                    <?php $optVal = is_int($val) ? $label : $val; ?>
                                    <option value="<?= e((string) $optVal) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="<?= e($field['type'] ?? 'text') ?>" name="<?= e($field['name'] ?? '') ?>" placeholder="Laisser vide pour ignorer">
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-actions" style="margin-top:1rem;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-cancel" data-modal-close><span class="material-symbols-outlined">close</span> Annuler</button>
                <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">check</span> Appliquer</button>
            </div>
        </form>
    </div>
</div>
