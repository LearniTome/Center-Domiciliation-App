<?php
/**
 * Usage in a list page:
 *   $quickCreateTitle = 'Nouvelle societe';
 *   $quickCreateTable = 'societes';
 *   $quickCreateFields = [
 *       ['name' => 'societe_raison_sociale', 'label' => 'Raison sociale', 'type' => 'text', 'required' => true],
 *       ['name' => 'societe_forme_juridique', 'label' => 'Forme juridique', 'type' => 'select', 'options' => $formesOptions, 'required' => true],
 *       ...
 *   ];
 *   require __DIR__ . '/../../includes/quick_create_modal.php';
 *   Wizard page (keyed, multi-modal):
 *   $quickCreateModalKey = 'formes-juridiques';
 *   $quickCreateTitle = 'Nouvelle forme juridique';
 *   $quickCreateTable = 'ref_formes_juridiques';
 *   $quickCreateFields = [...];
 *   require __DIR__ . '/../../includes/quick_create_modal.php';
 */
$modalKey = $quickCreateModalKey ?? 'quick-create';
?>
<div class="modal-overlay" data-modal="<?= e($modalKey) ?>">
    <div class="modal-panel">
        <div class="modal-header">
            <h3><?= e($quickCreateTitle ?? 'Nouvel enregistrement') ?></h3>
            <button class="btn-icon" data-modal-close type="button" title="Fermer"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form data-quick-create-form>
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="quick_create">
            <input type="hidden" name="table" value="<?= e($quickCreateTable) ?>">
            <div class="form-grid">
                <?php foreach ((array) ($quickCreateFields ?? []) as $field): ?>
                    <label class="field<?= !empty($field['full']) ? ' full' : '' ?>">
                        <span><?= e($field['label'] ?? '') ?></span>
                        <?php if (($field['type'] ?? 'text') === 'select' && isset($field['options'])): ?>
                            <select name="<?= e($field['name'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <option value=""><?= e($field['placeholder'] ?? 'Selectionner') ?></option>
                                <?php foreach ($field['options'] as $val => $label): ?>
                                    <?php $optVal = is_int($val) ? $label : $val; ?>
                                    <option value="<?= e((string) $optVal) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input
                                type="<?= e($field['type'] ?? 'text') ?>"
                                name="<?= e($field['name'] ?? '') ?>"
                                placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>
                            >
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-actions" style="margin-top:1rem;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-cancel" data-modal-close><span class="material-symbols-outlined">close</span> Annuler</button>
                <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">add</span> Creer</button>
            </div>
        </form>
    </div>
</div>
