<?php
/**
 * Modal d'import Excel en 2 étapes :
 *  1. Upload du fichier (modal)
 *  2. Prévisualisation + modification + confirmation (modal)
 *
 * Usage dans une page liste :
 *   <button class="btn btn-secondary" type="button" data-import-btn="societes">
 *       <span class="material-symbols-outlined">upload_file</span> Importer Excel
 *   </button>
 *   <?php require __DIR__ . '/../../includes/import_excel_modal.php'; ?>
 *
 * Le bouton peut être placé n'importe où dans la page.
 * L'attribut data-import-btn doit contenir le nom de la table (societes, associes, ...)
 */
?>
<!-- Modal 1 : Upload -->
<div class="modal-overlay" data-modal="import-upload">
    <div class="modal-panel" style="max-width:480px">
        <div class="modal-header">
            <h3><span class="material-symbols-outlined" style="vertical-align:middle;margin-right:6px">upload_file</span> Importer depuis Excel</h3>
            <button class="btn-icon" data-modal-close type="button" title="Fermer"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div data-import-upload-body>
            <form data-import-upload-form enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_preview">
                <input type="hidden" name="table" value="">
                <?= csrf_input() ?>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1rem">
                    Sélectionnez un fichier Excel (.xlsx ou .xls).<br>
                    La première ligne doit contenir les en-têtes de colonnes.
                </p>
                <label class="field full">
                    <span>Fichier Excel</span>
                    <input type="file" name="import_file" accept=".xlsx,.xls" required>
                </label>
                <div class="form-actions" style="margin-top:1rem;display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn btn-cancel" data-modal-close><span class="material-symbols-outlined">close</span> Annuler</button>
                    <button type="submit" class="btn btn-info"><span class="material-symbols-outlined">preview</span> Prévisualiser</button>
                </div>
            </form>
            <div data-import-upload-error style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;background:rgba(252,66,74,0.1);border-radius:var(--radius-sm);color:var(--danger);font-size:0.85rem"></div>
        </div>
    </div>
</div>

<!-- Modal 2 : Prévisualisation + Modification -->
<div class="modal-overlay" data-modal="import-preview">
    <div class="modal-panel" style="max-width:90%;width:1100px">
        <div class="modal-header">
            <h3><span class="material-symbols-outlined" style="vertical-align:middle;margin-right:6px">table_chart</span> Prévisualisation des données</h3>
            <button class="btn-icon" data-modal-close type="button" title="Fermer"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div data-import-preview-body>
            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.75rem">
                <span data-import-count>0</span> ligne(s) trouvée(s). Vous pouvez modifier les cellules directement avant de confirmer.
            </p>
            <div class="table-scroll" style="max-height:55vh;overflow:auto">
                <table data-import-preview-table>
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div data-import-preview-error style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;background:rgba(252,66,74,0.1);border-radius:var(--radius-sm);color:var(--danger);font-size:0.85rem"></div>
            <div class="form-actions" style="margin-top:1rem;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-cancel" data-modal-close><span class="material-symbols-outlined">close</span> Annuler</button>
                <button type="button" class="btn btn-next" data-import-confirm><span class="material-symbols-outlined">check</span> Confirmer l'import</button>
            </div>
        </div>
    </div>
</div>
