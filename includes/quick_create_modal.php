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
 *
 *   Wizard page sans tableau de listing, alimentant un <select> de la page :
 *   $quickCreateModalKey      = 'collaborateurs';
 *   $quickCreateTargetSelect  = 'societe_collaborateur_id';
 *   $quickCreateTargetLabel   = ['nom_complet', 'collaborateur_code'];
 *   -> apres creation, l'option est ajoutee au select cible et selectionnee.
 *
 *   Champs calcules (readonly) affiches dans le pied collant plutot que dans
 *   la grille — ils se remplissent seuls et ne sont pas saisissables :
 *   $quickCreatePreview      = ['nom_complet', 'collaborateur_code'];
 *
 *   Message de confirmation apres creation (page liste rechargee) :
 *   $quickCreateLabelField   = 'nom_complet';  // "Collaborateur « X » cree."
 */
$modalKey = $quickCreateModalKey ?? '';
$modalAttr = $modalKey !== '' ? 'quick-create-' . $modalKey : 'quick-create';
$targetSelect = (string) ($quickCreateTargetSelect ?? '');
$targetLabel = array_values(array_filter((array) ($quickCreateTargetLabel ?? []), static fn($k) => $k !== ''));
// Champs sortis de la grille vers l'apercu du pied (opt-in par nom de champ).
$previewNames = array_values(array_filter((array) ($quickCreatePreview ?? []), static fn($k) => $k !== ''));
// Libelle du bouton de validation, surchargeable par l'appelant. On le
// consomme puis on le retire : une page peut includer plusieurs modales et la
// valeur ne doit pas fuiter sur la suivante.
$qcSubmitLabel = (string) ($quickCreateSubmitLabel ?? 'Créer');
unset($quickCreateSubmitLabel);
// Entite et champ-identifiant pour le message de confirmation cote JS.
// strtolower (et non mb_strtolower) volontairement : le projet n'utilise nulle
// part mbstring, et l'hebergeur mutualise n'est pas garanti de l'avoir. En
// locale C, strtolower ne touche que A-Z : les octets UTF-8 restent intacts.
$qcEntity = (string) ($quickCreateEntity ?? '');
if ($qcEntity === '') {
    $qcEntity = strtolower(preg_replace('/^(nouveau|nouvelle)\s+/iu', '', (string) ($quickCreateTitle ?? '')));
    $qcEntity = trim($qcEntity) !== '' ? trim($qcEntity) : 'enregistrement';
}
$qcLabelField = (string) ($quickCreateLabelField ?? '');
// Modale large : occupe la largeur de la fenetre (beaucoup de champs) tout en
// restant centree verticalement. Opt-in via $quickCreateWide.
$qcPanelClass = 'modal-panel' . (!empty($quickCreateWide) ? ' qc-wide' : '');
$previewFields = [];
?>
<div class="modal-overlay" data-modal="<?= e($modalAttr) ?>">
    <div class="<?= e($qcPanelClass) ?>" role="dialog" aria-modal="true" aria-labelledby="qc-title-<?= e($modalAttr) ?>">
        <div class="modal-header">
            <div class="qc-head">
                <span class="qc-head-icon"><span class="material-symbols-outlined">add</span></span>
                <h3 id="qc-title-<?= e($modalAttr) ?>"><?= e($quickCreateTitle ?? 'Nouvel enregistrement') ?></h3>
            </div>
            <button class="btn-icon" data-modal-close type="button" title="Fermer"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form data-quick-create-form data-quick-create-entity="<?= e($qcEntity) ?>"<?= $qcLabelField !== '' ? ' data-quick-create-label-field="' . e($qcLabelField) . '"' : '' ?><?= $targetSelect !== '' ? ' data-quick-create-target="' . e($targetSelect) . '" data-quick-create-label="' . e(implode(',', $targetLabel)) . '"' : '' ?>>
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="quick_create">
            <input type="hidden" name="table" value="<?= e($quickCreateTable) ?>">
            <div class="qc-alert" data-qc-error role="alert" hidden>
                <span class="material-symbols-outlined">error</span>
                <span data-qc-error-text></span>
                <button type="button" class="qc-alert-close" data-qc-error-close title="Masquer le message">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="form-grid">
                <?php foreach ((array) ($quickCreateFields ?? []) as $field): ?>
                    <?php
                    // Champ calcule : il sort de la grille et sera rendu dans
                    // l'apercu du pied collant (meme attributs, meme JS).
                    if ($previewNames !== [] && in_array((string) ($field['name'] ?? ''), $previewNames, true)) {
                        $previewFields[] = $field;
                        continue;
                    }
                    ?>
                    <?php if (($field['type'] ?? '') === 'title'): ?>
                        <h3 class="section-title"><?= e($field['label'] ?? '') ?></h3>
                    <?php elseif (($field['type'] ?? '') === 'title-secondary'): ?>
                        <h4 style="grid-column:1/-1;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-secondary);margin:8px 0 2px;padding:0"><?= e($field['label'] ?? '') ?></h4>
                    <?php elseif (($field['type'] ?? '') === 'hidden'): ?>
                        <input type="hidden" name="<?= e($field['name'] ?? '') ?>" value="<?= e((string) ($field['value'] ?? ($quickCreateDefaults[$field['name']] ?? ''))) ?>">
                    <?php elseif (($field['type'] ?? '') === 'dynamic-select' && isset($field['options'])): ?>
                        <h3 class="section-title"><?= e($field['label'] ?? '') ?></h3>
                        <div data-dynamic-select="<?= e($field['name'] ?? '') ?>" style="grid-column:1/-1">
                            <div data-dynamic-item style="display:flex;gap:6px;margin-bottom:4px">
                                <select style="flex:1" data-dynamic-option>
                                    <option value="">Sélectionner</option>
                                    <?php $optList = array_is_list($field['options']); ?>
                                    <?php foreach ($field['options'] as $val => $label): ?>
                                        <?php $optVal = $optList ? $label : $val; ?>
                                        <option value="<?= e((string) $optVal) ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-icon danger" data-dynamic-remove style="flex-shrink:0" tabindex="-1" title="Retirer"><span class="material-symbols-outlined">close</span></button>
                            </div>
                        </div>
                        <div style="grid-column:1/-1;display:flex;gap:6px">
                            <button type="button" class="btn" data-dynamic-add="<?= e($field['name'] ?? '') ?>" style="padding:3px 8px;font-size:0.7rem"><span class="material-symbols-outlined" style="font-size:14px">add</span> Ajouter une activite</button>
                        </div>
                        <template data-dynamic-template>
                            <div data-dynamic-item style="display:flex;gap:6px;margin-bottom:4px">
                                <select style="flex:1" data-dynamic-option>
                                    <option value="">Sélectionner</option>
                                    <?php $optList = array_is_list($field['options']); ?>
                                    <?php foreach ($field['options'] as $val => $label): ?>
                                        <?php $optVal = $optList ? $label : $val; ?>
                                        <option value="<?= e((string) $optVal) ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-icon danger" data-dynamic-remove style="flex-shrink:0" tabindex="-1" title="Retirer"><span class="material-symbols-outlined">close</span></button>
                            </div>
                        </template>
                    <?php else: ?>
                    <label class="field<?= !empty($field['full']) ? ' full' : '' ?>">
                        <span><?= e($field['label'] ?? '') ?><?= !empty($field['required']) ? '<span class="qc-required" aria-hidden="true">*</span>' : '' ?></span>
                        <?php if (($field['type'] ?? 'text') === 'select' && isset($field['options'])): ?>
                            <?php $fd = ($quickCreateDefaults ?? [])[$field['name']] ?? ''; ?>
                            <select name="<?= e($field['name'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <option value=""><?= e($field['placeholder'] ?? 'Sélectionner') ?></option>
                                <?php $optList = array_is_list($field['options']); ?>
                                <?php foreach ($field['options'] as $val => $label): ?>
                                    <?php $optVal = $optList ? $label : $val; ?>
                                    <option value="<?= e((string) $optVal) ?>"<?= ((string) $optVal) === $fd ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (($field['type'] ?? 'text') === 'textarea'): ?>
                            <textarea name="<?= e($field['name'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= !empty($field['rows']) ? 'rows="' . (int) $field['rows'] . '"' : '' ?>></textarea>
                        <?php else: ?>
                            <?php
                            $fdAttrs = '';
                            if (!empty($field['data-derived'])) {
                                $fdAttrs .= ' data-derived="' . e((string) $field['data-derived']) . '"';
                            }
                            if (!empty($field['data-code-part'])) {
                                $fdAttrs .= ' data-code-part="' . e((string) $field['data-code-part']) . '"';
                            }
                            if (!empty($field['readonly'])) {
                                $fdAttrs .= ' readonly tabindex="-1"';
                            }
                            ?>
                            <input
                                type="<?= e($field['type'] ?? 'text') ?>"
                                name="<?= e($field['name'] ?? '') ?>"
                                placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                value="<?= e(($quickCreateDefaults ?? [])[$field['name']] ?? '') ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>
                                <?= $fdAttrs ?>
                            >
                        <?php endif; ?>
                    </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="form-actions" style="margin-top:1rem;display:flex;gap:8px;justify-content:flex-end">
                <?php if ($previewFields !== []): ?>
                <div class="qc-preview">
                    <span class="qc-preview-label section-title"><span class="material-symbols-outlined">visibility</span> Aperçu généré</span>
                    <div class="qc-preview-fields">
                        <?php foreach ($previewFields as $pfield): ?>
                            <label class="field">
                                <span><?= e($pfield['label'] ?? '') ?></span>
                                <input
                                    type="text"
                                    name="<?= e($pfield['name'] ?? '') ?>"
                                    value="<?= e(($quickCreateDefaults ?? [])[$pfield['name']] ?? '') ?>"
                                    <?php if (!empty($pfield['data-derived'])): ?> data-derived="<?= e((string) $pfield['data-derived']) ?>"<?php endif; ?>
                                    <?php if (!empty($pfield['data-code-part'])): ?> data-code-part="<?= e((string) $pfield['data-code-part']) ?>"<?php endif; ?>
                                    readonly tabindex="-1"
                                >
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <button type="button" class="btn btn-cancel" data-modal-close><span class="material-symbols-outlined">close</span> Annuler</button>
                <button type="submit" class="btn btn-next"><span class="material-symbols-outlined">add</span> <?= e($qcSubmitLabel) ?></button>
            </div>
        </form>
    </div>
</div>
