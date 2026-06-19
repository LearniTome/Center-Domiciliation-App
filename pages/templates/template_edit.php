<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/editeur_templates.php';

$templatesDir = __DIR__ . '/../../templates';
$templatePath = isset($_GET['path']) ? realpath((string) $_GET['path']) : '';

$folderLabels = require __DIR__ . '/../../config/templates.php';
$folderLabels = $folderLabels['folder_labels'];

$templateDirs = array_filter(glob($templatesDir . '/*', GLOB_ONLYDIR), fn($d) => basename($d)[0] !== '_');
$racineDir = $templatesDir . '/_Racine-Actifs';

if ($templatePath === '' || !str_starts_with($templatePath, realpath($templatesDir)) || !is_file($templatePath)) {
    ?>
    <section class="card stack">
        <div class="section-header">
            <div class="section-header-info">
                <span class="material-symbols-outlined icon-leading">edit_note</span>
                <div>
                    <h2>Selectionnez un template</h2>
                    <p class="help-text">Choisissez un dossier puis un fichier .docx a editer</p>
                </div>
            </div>
        </div>
        <?php
        $allFolders = [];
        $specialFolders = ['_Racine-Actifs', '_Cession'];
        foreach ($specialFolders as $sf) {
            $sfDir = $templatesDir . '/' . $sf;
            if (is_dir($sfDir)) {
                $sfFiles = glob($sfDir . '/*.docx');
                $allFolders[] = ['name' => $sf, 'label' => $folderLabels[$sf] ?? $sf, 'files' => $sfFiles];
            }
        }
        foreach ($templateDirs as $dir) {
            $folderName = basename($dir);
            $files = glob($dir . '/*.docx');
            $allFolders[] = ['name' => $folderName, 'label' => $folderLabels[$folderName] ?? $folderName, 'files' => $files];
        }
        usort($allFolders, fn($a, $b) => (empty($a['files']) <=> empty($b['files'])));
        ?>
        <div class="picker-tables">
            <?php foreach ($allFolders as $group): ?>
            <div class="picker-table-group">
                <h3 class="picker-table-title"><?= e($group['label']) ?></h3>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Fichier</th>
                                <th style="width:70px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($group['files'])): ?>
                            <tr>
                                <td colspan="2" class="table-empty-cell">Aucun template dans ce dossier</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($group['files'] as $f): ?>
                            <tr>
                                <td><?= e(basename($f)) ?></td>
                                <td>
                                    <a class="btn-icon info" href="<?= e(app_url('template_edit', ['path' => $f])) ?>" title="Editer">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="section-header picker-actions">
            <div></div>
            <div class="table-actions">
                <a class="btn btn-back" href="<?= e(app_url('templates')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
            </div>
        </div>
    </section>
    <?php
    return;
}

$filename = basename($templatePath);
$folder = basename(dirname($templatePath));
$templatesConfig = require __DIR__ . '/../../config/templates.php';
$folderLabels = $templatesConfig['folder_labels'];
$docTypes = $templatesConfig['document_types'];

$info = pathinfo($filename, PATHINFO_FILENAME);
$parts = explode('_', $info);
$docType = '';
if (count($parts) >= 3) {
    $rest = array_slice($parts, 2);
    $docType = implode('_', $rest);
    $docType = preg_replace('/_Template$/', '', $docType);
} elseif (count($parts) === 2) {
    $docType = str_replace('_Template', '', $parts[1]);
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $html = $_POST['content_html'] ?? '';
        try {
            TemplateEditor::saveHtml($templatePath, $html);
            set_flash('success', 'Template enregistre avec succes.');
            log_activity($pdo, 'update', 'template', null, $filename);
        } catch (Throwable $e) {
            set_flash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }
        redirect_to('template_edit', ['path' => $templatePath]);
    }

    if ($action === 'save_as') {
        $html = $_POST['content_html'] ?? '';
        $newName = field_value($_POST, 'new_name');
        if ($newName === '') {
            $newName = $filename;
        }
        if (!str_ends_with(strtolower($newName), '.docx')) {
            $newName .= '.docx';
        }
        $newPath = $templatesDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $newName;
        try {
            TemplateEditor::createNewHtml($html, $newPath);
            set_flash('success', 'Nouveau template cree avec succes.');
            log_activity($pdo, 'create', 'template', null, $newName);
            redirect_to('template_edit', ['path' => $newPath]);
        } catch (Throwable $e) {
            set_flash('error', 'Erreur lors de la creation : ' . $e->getMessage());
        }
        redirect_to('template_edit', ['path' => $templatePath]);
    }

    if ($action === 'create_blank') {
        $blankName = field_value($_POST, 'blank_name');
        if ($blankName === '') {
            $blankName = 'Nouveau_template';
        }
        if (!str_ends_with(strtolower($blankName), '.docx')) {
            $blankName .= '.docx';
        }
        $newPath = $templatesDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $blankName;
        try {
            TemplateEditor::createNewHtml('', $newPath);
            set_flash('success', 'Template vierge cree.');
            log_activity($pdo, 'create', 'template', null, $blankName);
            redirect_to('template_edit', ['path' => $newPath]);
        } catch (Throwable $e) {
            set_flash('error', 'Erreur : ' . $e->getMessage());
        }
        redirect_to('templates');
    }
}

$htmlContent = TemplateEditor::extractHtml($templatePath);
$variables = TemplateEditor::getAvailableVariables();

// Auto-filter: masquer categories cession pour templates non-cession
$isCession = $folder === '_Cession';
$visibleCategories = [];
foreach ($variables as $cat => $vars) {
    if (!$isCession && in_array($cat, ['Cession', 'Cedant', 'Cessionnaire'])) continue;
    $visibleCategories[$cat] = $vars;
}

// Exemples pour tooltips
$varExamples = [
    'SOCIETE_RAISON_SOCIALE' => 'Mon Centre SARL',
    'SOCIETE_FORME_JURIDIQUE' => 'SARL',
    'SOCIETE_ICE' => '123456789',
    'SOCIETE_RC' => '12345',
    'SOCIETE_IF' => '12345678',
    'SOCIETE_TP' => '12345678',
    'SOCIETE_CNSS' => '1234567',
    'SOCIETE_CAPITAL' => '100 000',
    'SOCIETE_PART_SOCIAL' => '1000',
    'SOCIETE_VALEUR_NOMINALE' => '100',
    'SOCIETE_VILLE' => 'Casablanca',
    'SOCIETE_TRIBUNAL' => 'Tribunal de Commerce de Casablanca',
    'SOCIETE_ADRESSE_SIEGE' => '123, Rue Mohammed V',
    'SOCIETE_EMAIL' => 'contact@exemple.ma',
    'SOCIETE_TELEPHONE' => '+212 6 00 00 00 00',
    'SOCIETE_DOSSIER' => 'DOS-2024-001',
    'SOCIETE_TYPE_GENERATION' => 'Creation',
    'SOCIETE_PROCEDURE_CREATION' => 'Normale',
    'SOCIETE_MODE_DEPOT' => 'Electronique',
    'SOCIETE_DATE_ICE' => '15/01/2024',
    'SOCIETE_DATE_EXP_CERT_NEG' => '15/07/2024',
    'ASSOCIE_NOM_COMPLET' => 'Ahmed Alaoui',
    'ASSOCIE_NOM' => 'Alaoui',
    'ASSOCIE_PRENOM' => 'Ahmed',
    'ASSOCIE_CIVILITE' => 'M.',
    'ASSOCIE_CIN' => 'AB123456',
    'ASSOCIE_DATE_VALIDITE_CIN' => '01/01/2030',
    'ASSOCIE_DATE_NAISSANCE' => '15/06/1985',
    'ASSOCIE_LIEU_NAISSANCE' => 'Casablanca',
    'ASSOCIE_NATIONALITE' => 'Marocaine',
    'ASSOCIE_ADRESSE' => '45, Rue Hassan II',
    'ASSOCIE_TELEPHONE' => '+212 6 11 22 33 44',
    'ASSOCIE_EMAIL' => 'ahmed@exemple.ma',
    'ASSOCIE_QUALITE' => 'Gerant',
    'ASSOCIE_PARTS' => '500',
    'ASSOCIE_CAPITAL_DETENU' => '50 000',
    'ASSOCIE_EST_GERANT' => 'Oui',
    'CONTRAT_TYPE' => 'Domiciliation',
    'CONTRAT_TYPE_DOMICILIATION' => 'Standard',
    'CONTRAT_DATE' => '01/01/2024',
    'CONTRAT_DATE_DEBUT' => '01/01/2024',
    'CONTRAT_DATE_FIN' => '31/12/2024',
    'CONTRAT_DUREE_MOIS' => '12',
    'CONTRAT_LOYER_TTC' => '15 000',
    'CONTRAT_LOYER_HT' => '12 500',
    'CONTRAT_TVA_POURCENT' => '20',
    'CONTRAT_TOTAL_HT' => '150 000',
    'CONTRAT_FRAIS_INTERMEDIAIRE' => '5 000',
    'CONTRAT_CAUTION' => '15 000',
    'CONTRAT_STATUT' => 'Actif',
    'CONTRAT_MODE_SIGNATURE' => 'Electronique',
    'CONTRAT_PACK_MONTANT_TTC' => '25 000',
    'CONTRAT_PACK_LOYER_TTC' => '15 000',
    'CONTRAT_TYPE_RENOUVELLEMENT' => 'Tacite',
    'CONTRAT_RENOUV_TVA_POURCENT' => '20',
    'CONTRAT_RENOUV_LOYER_HT' => '12 500',
    'CONTRAT_RENOUV_LOYER_TTC' => '15 000',
    'CONTRAT_RENOUV_ANNUEL_TTC' => '180 000',
    'ACTIVITES' => 'Activite 1, Activite 2',
    'ACTIVITES_INLINE' => 'Activite 1; Activite 2',
    'ACTIVITES_PLAIN' => 'Activite 1, Activite 2',
    'ACTIVITES_PUCES' => "- Activite 1\n- Activite 2",
    'ACTIVITES_SUITE_PUCES' => "- Activite 3\n- Activite 4",
    'NB_ACTIVITES' => '2',
    'CESSION_DATE' => '15/06/2024',
    'CESSION_DOSSIER' => 'CES-2024-001',
    'CESSION_STATUS' => 'En cours',
    'CESSION_MOTIF' => 'Cession totale',
    'PARTS_CEDEES' => '500',
    'PRIX_UNITAIRE' => '100',
    'PRIX_TOTAL' => '50 000',
    'CAPITAL_APRES' => '150 000',
    'PARTS_APRES' => '1500',
    'NB_CEDANTS' => '1',
    'NB_CESSIONNAIRES' => '1',
    'CEDANT_NOM_COMPLET' => 'Ahmed Alaoui',
    'CEDANT_CIN' => 'AB123456',
    'CEDANT_CIVILITE' => 'M.',
    'CEDANT_DATE_NAISSANCE' => '15/06/1985',
    'CEDANT_LIEU_NAISSANCE' => 'Casablanca',
    'CEDANT_NATIONALITE' => 'Marocaine',
    'CEDANT_ADRESSE' => '45, Rue Hassan II',
    'CESSIONNAIRE_NOM_COMPLET' => 'Fatima Benali',
    'CESSIONNAIRE_CIN' => 'CD789012',
    'CESSIONNAIRE_CIVILITE' => 'Mme',
    'CESSIONNAIRE_DATE_NAISSANCE' => '20/03/1990',
    'CESSIONNAIRE_LIEU_NAISSANCE' => 'Rabat',
    'CESSIONNAIRE_NATIONALITE' => 'Marocaine',
    'CESSIONNAIRE_ADRESSE' => '12, Avenue Mohammed VI',
    'CESSIONNAIRE_TELEPHONE' => '+212 6 22 33 44 55',
    'CESSIONNAIRE_EMAIL' => 'fatima@exemple.ma',
    'CESSIONNAIRE_QUALITE' => 'Gerant',
    'CESSIONNAIRE_PARTS' => '500',
    'CESSIONNAIRE_CAPITAL_DETENU' => '50 000',
    'CESSIONNAIRE_EST_GERANT' => 'Oui',
    'DATE' => '19/06/2026',
    'DATE_LONG' => '19 juin 2026',
    'ANNEE' => '2026',
    'MOIS' => 'juin',
    'JOUR' => '19',
];
?>

<style>
.template-editor-layout {
    min-height: calc(100vh - 3rem);
    align-items: stretch;
    grid-template-columns: 1fr;
}
.template-editor-layout .editor-main {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 100%;
}
.template-editor-layout .editor-main > form#editor-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}
.template-editor-layout .editor-toolbar {
    flex-shrink: 0;
    position: sticky; top: 0; z-index: 5;
    background: var(--bg); padding-bottom: 2px;
    border-bottom: 1px solid var(--line);
}
.template-editor-layout .editor-wrapper {
    flex: 1;
    min-height: 0;
    max-height: none;
}
.template-editor-layout .editor-content {
    min-height: 100%;
}
.variables-panel {
    margin-top: 1rem;
}
.variables-panel .section-header {
    cursor: pointer;
    user-select: none;
}
.variables-panel.collapsed .sidebar-body {
    display: none;
}
.var-btn[draggable="true"] {
    cursor: grab;
}
.var-btn[draggable="true"]:active {
    cursor: grabbing;
}
.var-btn.drag-over {
    border-color: var(--primary); background: rgba(74,108,247,0.08);
}
#recent-vars {
    display: flex; flex-direction: row; flex-wrap: wrap; gap: 4px; align-items: center;
    margin-bottom: 0.5rem; padding: 0.25rem 0;
}
#recent-vars .var-recent-header {
    display: flex; align-items: center; gap: 3px; font-size: 0.72rem;
    color: var(--text-secondary); white-space: nowrap;
}
#recent-vars .var-recent-header .material-symbols-outlined { font-size: 14px; }
#recent-vars .var-count { font-size: 0.65rem; }
#recent-vars .var-btn {
    display: inline-flex; gap: 2px; padding: 0.15rem 0.4rem;
    font-size: 0.7rem; background: transparent; border: 1px solid var(--line);
    border-radius: var(--radius-sm); cursor: pointer; transition: all 0.1s;
}
#recent-vars .var-btn:hover { background: var(--panel-strong); }
#recent-vars .var-btn code { font-size: 0.7rem; color: var(--primary); }
.var-table-wrap {
    flex: 1; overflow-y: auto; min-height: 0;
}
.var-table {
    width: 100%; border-collapse: collapse; font-size: 0.75rem;
}
.var-table thead th {
    position: sticky; top: 0; background: var(--bg); z-index: 1;
    padding: 0.3rem 0.4rem; text-align: left; font-weight: 600;
    border-bottom: 2px solid var(--line); color: var(--text-secondary);
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;
}
.var-table tbody tr {
    transition: background 0.1s;
}
.var-table tbody tr:hover {
    background: var(--panel-strong);
}
.var-table tbody tr.var-used {
    background: rgba(0,184,148,0.06);
}
.var-table tbody tr.var-used .var-cell code {
    color: var(--success);
}
.var-table td {
    padding: 0.2rem 0.4rem; vertical-align: middle; border-bottom: 1px solid var(--line);
}
.var-table .var-type {
    color: var(--text-secondary); font-size: 0.68rem; white-space: nowrap;
    width: 80px;
}
.var-table .var-cell { padding: 0; }
.var-table .var-btn {
    display: block; width: 100%; text-align: left; padding: 0.2rem 0.4rem;
    background: transparent; border: none; cursor: pointer; font-size: 0.75rem;
    border-radius: 0; transition: none;
}
.var-table .var-btn:hover { background: transparent; }
.var-table .var-btn code { font-family: 'Courier New', monospace; color: var(--primary); }
.var-table .var-label {
    color: var(--text-secondary); font-size: 0.72rem;
}
.var-table .var-use-cell { text-align: center; width: 50px; }
.var-table .var-checkbox { margin: 0; cursor: pointer; }
.var-table .var-count-cell { text-align: center; width: 60px; }
.var-table .var-usage {
    font-size: 0.62rem; color: var(--text-secondary);
    background: var(--panel-strong); padding: 0 0.35rem; border-radius: 6px;
    line-height: 1.6; display: inline-block; min-width: 14px; text-align: center;
}
.cat-cession { display: none; }

.preview-page {
    width: 794px; min-height: 1123px; padding: 2cm 2.5cm;
    background: white; color: #1a1a1a;
    font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 11pt;
    line-height: 1.5; box-shadow: 0 2px 10px rgba(0,0,0,0.25);
    box-sizing: border-box; position: relative; margin-bottom: 24px;
    overflow: hidden;
}
.preview-page-footer {
    position: absolute; bottom: 1cm; left: 2.5cm; right: 2.5cm;
    text-align: center; font-size: 9pt; color: #999;
    border-top: 1px solid #ddd; padding-top: 4pt;
}
</style>
<section class="template-editor-layout">
    <div class="editor-main card stack">
        <div class="section-header">
            <div>
                <h2><?= e($docTypes[$docType] ?? $docType ?: 'Editeur de template') ?></h2>
                <p class="help-text"><?= e($folderLabels[$folder] ?? $folder) ?> &mdash; <?= e($filename) ?></p>
            </div>
            <div class="table-actions">
                <a class="btn btn-back" href="<?= e(app_url('template', ['path' => $templatePath])) ?>" title="Retour aux infos template">
                    <span class="material-symbols-outlined">arrow_back</span> Retour
                </a>
                <a class="btn-icon" href="<?= e(app_url('template_edit')) ?>" title="Changer de template">
                    <span class="material-symbols-outlined">edit_note</span>
                </a>
            </div>
        </div>

        <form method="post" id="editor-form">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="content_html" id="content-html">

            <div class="editor-toolbar">
                <select id="para-style" onmousedown="saveSelection()" onchange="applyParagraphStyle(this.value)" title="Style de paragraphe">
                    <option value="p">Paragraphe</option>
                    <option value="h1">Titre 1</option>
                    <option value="h2">Titre 2</option>
                    <option value="h3">Titre 3</option>
                    <option value="h4">Titre 4</option>
                    <option value="pre">Code</option>
                    <option value="blockquote">Citation</option>
                </select>
                <select id="font-family" onmousedown="saveSelection()" onchange="applyFont(this.value)" title="Police">
                    <option value="Calibri, sans-serif">Calibri</option>
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="Times New Roman, serif">Times New Roman</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="Courier New, monospace">Courier New</option>
                    <option value="Tahoma, sans-serif">Tahoma</option>
                    <option value="Verdana, sans-serif">Verdana</option>
                    <option value="Trebuchet MS, sans-serif">Trebuchet MS</option>
                    <option value="Garamond, serif">Garamond</option>
                </select>
                <select id="font-size" onmousedown="saveSelection()" onchange="applyFontSize(this.value)" title="Taille de police">
                    <option value="8pt">8</option>
                    <option value="9pt">9</option>
                    <option value="10pt">10</option>
                    <option value="11pt" selected>11</option>
                    <option value="12pt">12</option>
                    <option value="14pt">14</option>
                    <option value="16pt">16</option>
                    <option value="18pt">18</option>
                    <option value="20pt">20</option>
                    <option value="22pt">22</option>
                    <option value="24pt">24</option>
                    <option value="26pt">26</option>
                    <option value="28pt">28</option>
                    <option value="36pt">36</option>
                    <option value="48pt">48</option>
                </select>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('bold')" title="Gras (Ctrl+B)">
                    <span class="material-symbols-outlined">format_bold</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('italic')" title="Italique (Ctrl+I)">
                    <span class="material-symbols-outlined">format_italic</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('underline')" title="Souligne (Ctrl+U)">
                    <span class="toolbar-letter-icon" style="font-weight:700;text-decoration:underline;font-size:1rem;line-height:1;font-family:inherit">S</span>
                </button>
                <span class="toolbar-sep"></span>
                <span class="color-btn">
                    <input type="color" id="text-color" value="#000000" onmousedown="saveSelection()" onchange="applyColor(this.value)" title="Couleur du texte">
                    <span class="color-preview" style="background:#000000"></span>
                </span>
                <span class="color-btn">
                    <input type="color" id="bg-color" value="#ffff00" onmousedown="saveSelection()" onchange="applyBgColor(this.value)" title="Surbrillance">
                    <span class="color-preview" style="background:#ffff00"></span>
                </span>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifyleft')" title="Aligner a gauche">
                    <span class="material-symbols-outlined">format_align_left</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifycenter')" title="Centrer">
                    <span class="material-symbols-outlined">format_align_center</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifyright')" title="Aligner a droite">
                    <span class="material-symbols-outlined">format_align_right</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifyfull')" title="Justifier">
                    <span class="material-symbols-outlined">format_align_justify</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('insertorderedlist')" title="Liste numerotee">
                    <span class="material-symbols-outlined">format_list_numbered</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('insertunorderedlist')" title="Liste a puces">
                    <span class="material-symbols-outlined">format_list_bulleted</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="showTableDialog()" title="Insérer un tableau">
                    <span class="material-symbols-outlined">table</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('inserthorizontalrule')" title="Ligne horizontale">
                    <span class="material-symbols-outlined">remove</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('undo')" title="Annuler">
                    <span class="material-symbols-outlined">undo</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('redo')" title="Retablir">
                    <span class="material-symbols-outlined">redo</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleSource()" title="Code source">
                    <span class="material-symbols-outlined">code</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="togglePreview()" title="Aperçu">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearFormatting()" title="Effacer la mise en forme">
                    <span class="material-symbols-outlined">format_clear</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="printEditor()" title="Imprimer (Ctrl+P)">
                    <span class="material-symbols-outlined">print</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exportPDF()" title="Exporter en PDF">
                    <span class="material-symbols-outlined">picture_as_pdf</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="insertPageBreak()" title="Nouvelle page">
                    <span class="material-symbols-outlined">note_add</span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-next btn-sm" onclick="beforeSave();document.getElementById('editor-form').submit()" title="Enregistrer (Ctrl+S)">
                    <span class="material-symbols-outlined">save</span>
                </button>
            </div>

            <div class="editor-wrapper">
                <div id="editor-content" class="editor-content" contenteditable="true" data-placeholder="Commencez a rediger...">
                    <div class="a4-page"><?= $htmlContent ?></div>
                </div>
            </div>

            <textarea id="editor-source" class="editor-source hidden" spellcheck="false"><?= e($htmlContent) ?></textarea>

            <div class="editor-wrapper" id="preview-wrapper" style="display:none">
                <div class="preview-toolbar">
                    <span class="preview-zoom-label">Aperçu</span>
                    <div class="preview-zoom-controls">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="zoomOut()" title="Zoom arrière">
                            <span class="material-symbols-outlined">zoom_out</span>
                        </button>
                        <span id="zoom-level" class="preview-zoom-level">100%</span>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="zoomIn()" title="Zoom avant">
                            <span class="material-symbols-outlined">zoom_in</span>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="zoomReset()" title="Rétablir le zoom">
                            <span class="material-symbols-outlined">fit_screen</span>
                        </button>
                    </div>
                </div>
                <div id="editor-preview" class="editor-preview hidden"></div>
            </div>

            <div class="editor-actions">
                <button type="submit" class="btn btn-next" onclick="return beforeSave()">
                    <span class="material-symbols-outlined">save</span> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="showSaveAs()">
                    <span class="material-symbols-outlined">save</span> Enregistrer sous
                </button>
                <button type="button" class="btn btn-secondary" onclick="if(confirm('Creer un nouveau template vierge ?'))document.getElementById('blank-form').submit();">
                    <span class="material-symbols-outlined">note_add</span> Nouveau vierge
                </button>
            </div>
        </form>

        <div id="table-dialog" class="table-dialog hidden">
            <div class="card stack">
                <h4>Insérer un tableau</h4>
                <div class="inline-form">
                    <label>Colonnes: <input type="number" id="table-cols" value="3" min="1" max="10" class="num-input-sm"></label>
                    <label>Lignes: <input type="number" id="table-rows" value="3" min="1" max="20" class="num-input-sm"></label>
                    <button type="button" class="btn btn-sm" onclick="insertTable()">Insérer</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeTableDialog()">Annuler</button>
                </div>
            </div>
        </div>

        <form method="post" id="save-as-form" class="hidden save-as-form">
            <div class="card stack">
                <h4>Enregistrer sous</h4>
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="save_as">
                <input type="hidden" name="content_html" id="content-html-saveas">
                <div class="inline-form">
                    <input type="text" name="new_name" placeholder="Nom du fichier (ex: Mon_Template.docx)" required class="input-flex">
                    <button type="submit" class="btn btn-next" onclick="document.getElementById('content-html-saveas').value=document.getElementById('editor-content').innerHTML">
                        <span class="material-symbols-outlined">save</span> Creer
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('save-as-form').classList.add('hidden')">
                        <span class="material-symbols-outlined">close</span> Annuler
                    </button>
                </div>
            </div>
        </form>

        <form method="post" id="blank-form" class="hidden">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_blank">
            <input type="hidden" name="blank_name" value="">
        </form>
    </div>
</section>

<div class="variables-panel card stack" id="editor-sidebar">
    <div class="section-header" onclick="toggleSidebar()">
        <h3>Variables</h3>
        <p class="help-text">Cliquez ou glissez pour insérer</p>
        <button type="button" class="btn-icon" id="sidebar-toggle-btn">
            <span class="material-symbols-outlined">expand_less</span>
        </button>
    </div>
    <div class="sidebar-body">
        <div class="variable-search">
            <input type="text" id="var-search" placeholder="Rechercher..." class="input-full" autocomplete="off">
        </div>
        <div id="recent-vars" style="display:none">
            <div class="var-recent-header">
                <span class="material-symbols-outlined">schedule</span>
                Recentes
                <span class="var-count" id="recent-count">0</span>
            </div>
            <div id="recent-list"></div>
        </div>
        <div class="var-table-wrap" id="var-categories">
            <table class="var-table" id="var-table">
                <thead>
                    <tr>
                        <th data-col="type">Type Variable</th>
                        <th data-col="name">Variable</th>
                        <th data-col="label">Libellé Variable</th>
                        <th data-col="use">Utiliser</th>
                        <th data-col="count">Nombre de fois</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variables as $category => $vars):
                        $hide = !$isCession && in_array($category, ['Cession', 'Cedant', 'Cessionnaire']);
                        foreach ($vars as $varName => $varLabel):
                            $example = $varExamples[$varName] ?? '';
                    ?>
                        <tr class="var-row<?= $hide ? ' cat-cession' : '' ?>">
                            <td class="var-type"><?= e($category) ?></td>
                            <td class="var-cell">
                                <button type="button" class="var-btn" draggable="true"
                                    data-var="{{ <?= e($varName) ?> }}"
                                    title="<?= e($varLabel) ?><?= $example ? '  ex: ' . e($example) : '' ?>">
                                    <code>{{ <?= e($varName) ?> }}</code>
                                </button>
                            </td>
                            <td class="var-label"><?= e($varLabel) ?></td>
                            <td class="var-use-cell"><input type="checkbox" class="var-checkbox"></td>
                            <td class="var-count-cell"><span class="var-usage" style="display:none">0</span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let savedRange = null;


function saveSelection() {
    const editor = document.getElementById('editor-content');
    const sel = window.getSelection();
    if (sel.rangeCount > 0 && editor.contains(sel.anchorNode)) {
        savedRange = sel.getRangeAt(0).cloneRange();
    }
}

function restoreSelection() {
    if (!savedRange) return;
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
}

function exec(cmd, val) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    document.execCommand(cmd, false, val || null);
    editor.focus();
}

function insertVar(text) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    if (window.getSelection) {
        const sel = window.getSelection();
        if (sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            const varNode = document.createElement('var');
            varNode.textContent = text;
            range.deleteContents();
            range.insertNode(varNode);
            range.setStartAfter(varNode);
            range.setEndAfter(varNode);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }
    trackVarUsage(text);
}

function showSaveAs() {
    document.getElementById('save-as-form').classList.remove('hidden');
}

function showTableDialog() {
    document.getElementById('table-dialog').classList.remove('hidden');
}

function closeTableDialog() {
    document.getElementById('table-dialog').classList.add('hidden');
}

function insertTable() {
    const cols = parseInt(document.getElementById('table-cols').value) || 3;
    const rows = parseInt(document.getElementById('table-rows').value) || 3;
    let html = '<table style="width:100%;border-collapse:collapse">';
    for (let r = 0; r < rows; r++) {
        html += '<tr>';
        for (let c = 0; c < cols; c++) {
            html += '<td style="border:1px solid #000;padding:4px">&nbsp;</td>';
        }
        html += '</tr>';
    }
    html += '</table><p><br></p>';
    exec('insertHTML', false, html);
    closeTableDialog();
}

function getEditorWrappers() {
    return Array.from(document.querySelectorAll('.editor-wrapper')).filter(function(w) {
        return w.id !== 'preview-wrapper';
    });
}

function toggleSource() {
    const editor = document.getElementById('editor-content');
    const wrappers = getEditorWrappers();
    const source = document.getElementById('editor-source');
    const preview = document.getElementById('editor-preview');
    const pvWrapper = preview.closest('.editor-wrapper');
    preview.classList.add('hidden');
    pvWrapper.style.display = 'none';
    if (source.classList.contains('hidden')) {
        source.value = editor.innerHTML;
        source.classList.remove('hidden');
        editor.style.display = 'none';
        wrappers.forEach(function(w) { w.style.display = 'none'; });
    } else {
        editor.innerHTML = source.value;
        source.classList.add('hidden');
        editor.style.display = '';
        wrappers.forEach(function(w) { w.style.display = ''; });
    }
}

function buildPaginatedPreview(html) {
    var styled = html
        .replace(/<var>/g, '<var style="color:#0090e7;font-style:normal;font-family:\'Courier New\',monospace;background:#e8f4fd;padding:0 2px;border-radius:2px">')
        .replace(/<table/g, '<table style="width:100%;border-collapse:collapse;margin:0.5em 0"')
        .replace(/<td/g, '<td style="border:1px solid #999;padding:6px"')
        .replace(/<th/g, '<th style="border:1px solid #999;padding:6px;background:#f0f0f0"');

    var temp = document.createElement('div');
    temp.innerHTML = styled;

    var blocks = [];
    var children = Array.from(temp.children);
    if (children.length === 1 && children[0].classList.contains('a4-page')) {
        blocks = Array.from(children[0].children);
    } else {
        for (var i = 0; i < children.length; i++) {
            var c = children[i];
            if (c.classList && c.classList.contains('a4-page')) {
                blocks = blocks.concat(Array.from(c.children));
            } else {
                blocks.push(c);
            }
        }
    }

    if (blocks.length === 0) return '<p style="color:#999;padding:2cm">(vide)</p>';

    var measurer = document.createElement('div');
    measurer.style.cssText = 'position:fixed;left:-9999px;top:0;width:794px;height:1123px;padding:2cm 2.5cm;font-family:Calibri,sans-serif;font-size:11pt;line-height:1.5;overflow:hidden;';
    document.body.appendChild(measurer);

    var pageChunks = [];
    measurer.innerHTML = '';

    for (var i = 0; i < blocks.length; i++) {
        var clone = blocks[i].cloneNode(true);
        measurer.appendChild(clone);
        if (measurer.scrollHeight > measurer.clientHeight + 2) {
            measurer.removeChild(clone);
            if (measurer.children.length > 0) {
                pageChunks.push(measurer.innerHTML);
                measurer.innerHTML = '';
            }
            measurer.appendChild(clone);
        }
    }

    if (measurer.innerHTML.trim()) {
        pageChunks.push(measurer.innerHTML);
    }

    document.body.removeChild(measurer);

    var total = pageChunks.length;
    return pageChunks.map(function(chunk, idx) {
        return '<div class="preview-page">'
            + chunk
            + '<div class="preview-page-footer">Page ' + (idx + 1) + ' / ' + total + '</div>'
            + '</div>';
    }).join('<hr class="page-break">');
}

function togglePreview() {
    const editor = document.getElementById('editor-content');
    const wrappers = getEditorWrappers();
    const source = document.getElementById('editor-source');
    const preview = document.getElementById('editor-preview');
    const pvWrapper = preview.closest('.editor-wrapper');
    let html;
    if (!source.classList.contains('hidden')) {
        html = source.value;
    } else {
        html = editor.innerHTML;
    }
    if (preview.classList.contains('hidden')) {
        preview.innerHTML = buildPaginatedPreview(html);
        preview.classList.remove('hidden');
        pvWrapper.style.display = '';
        currentZoom = 1;
        applyZoom(preview);
        if (!source.classList.contains('hidden')) {
            source.style.display = 'none';
        } else {
            wrappers.forEach(function(w) { w.style.display = 'none'; });
            editor.style.display = '';
        }
    } else {
        preview.classList.add('hidden');
        pvWrapper.style.display = 'none';
        if (!source.classList.contains('hidden')) {
            source.style.display = '';
        } else {
            editor.style.display = '';
            wrappers.forEach(function(w) { w.style.display = ''; });
        }
    }
}

let currentZoom = 1;
const ZOOM_MIN = 0.3;
const ZOOM_MAX = 3;
const ZOOM_STEP = 0.1;

function zoomIn() {
    const preview = document.getElementById('editor-preview');
    if (preview.classList.contains('hidden')) return;
    currentZoom = Math.min(ZOOM_MAX, +(currentZoom + ZOOM_STEP).toFixed(1));
    applyZoom(preview);
}

function zoomOut() {
    const preview = document.getElementById('editor-preview');
    if (preview.classList.contains('hidden')) return;
    currentZoom = Math.max(ZOOM_MIN, +(currentZoom - ZOOM_STEP).toFixed(1));
    applyZoom(preview);
}

function zoomReset() {
    const preview = document.getElementById('editor-preview');
    if (preview.classList.contains('hidden')) return;
    currentZoom = 1;
    applyZoom(preview);
}

function applyZoom(preview) {
    preview.style.transform = 'scale(' + currentZoom + ')';
    preview.style.transformOrigin = 'top center';
    document.getElementById('zoom-level').textContent = Math.round(currentZoom * 100) + '%';
}

let editorZoom = 1;
const EDITOR_ZOOM_MIN = 0.3;
const EDITOR_ZOOM_MAX = 2;
const EDITOR_ZOOM_STEP = 0.1;

function editorZoomIn() {
    editorZoom = Math.min(EDITOR_ZOOM_MAX, +(editorZoom + EDITOR_ZOOM_STEP).toFixed(1));
    applyEditorZoom();
}

function editorZoomOut() {
    editorZoom = Math.max(EDITOR_ZOOM_MIN, +(editorZoom - EDITOR_ZOOM_STEP).toFixed(1));
    applyEditorZoom();
}

function editorZoomReset() {
    editorZoom = 1;
    applyEditorZoom();
}

function applyEditorZoom() {
    const editor = document.getElementById('editor-content');
    editor.style.zoom = editorZoom;
    document.getElementById('editor-zoom-level').textContent = Math.round(editorZoom * 100) + '%';
}

function toggleSidebar() {
    const panel = document.getElementById('editor-sidebar');
    panel.classList.toggle('collapsed');
    const icon = document.querySelector('#sidebar-toggle-btn .material-symbols-outlined');
    icon.textContent = panel.classList.contains('collapsed') ? 'expand_more' : 'expand_less';
}

// Drag & drop
function initDragDrop() {
    const editor = document.getElementById('editor-content');
    document.querySelectorAll('.var-btn[draggable]').forEach(function(btn) {
        btn.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', btn.getAttribute('data-var'));
            e.dataTransfer.effectAllowed = 'copy';
        });
    });
    editor.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });
    editor.addEventListener('drop', function(e) {
        e.preventDefault();
        const text = e.dataTransfer.getData('text/plain');
        if (!text) return;
        let range;
        if (document.caretRangeFromPoint) {
            range = document.caretRangeFromPoint(e.clientX, e.clientY);
        } else if (e.rangeParent) {
            range = document.createRange();
            range.setStart(e.rangeParent, e.rangeOffset);
        }
        if (range) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            savedRange = range.cloneRange();
        }
        insertVar(text);
    });
}

// Recent / Favorites
function getRecent() {
    try { return JSON.parse(localStorage.getItem('template_var_recent') || '[]'); } catch(e) { return []; }
}

function saveRecent(recent) {
    try { localStorage.setItem('template_var_recent', JSON.stringify(recent.slice(0, 20))); } catch(e) {}
}

function trackVarUsage(varText) {
    const name = varText.trim();
    let recent = getRecent();
    recent = recent.filter(function(n) { return n !== name; });
    recent.unshift(name);
    saveRecent(recent);
    renderRecent();
}

function renderRecent() {
    const recent = getRecent();
    const container = document.getElementById('recent-vars');
    const list = document.getElementById('recent-list');
    const count = document.getElementById('recent-count');
    if (recent.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = '';
    count.textContent = recent.length;
    list.innerHTML = recent.map(function(name) {
        const escaped = name.replace(/'/g, "\\'");
        return '<button type="button" class="var-btn" onclick="insertVar(\'' + escaped + '\')" title="Cliquer pour ins\u00e9rer"><code>' + name.replace(/</g, '&lt;') + '</code></button>';
    }).join('');
}

// Usage counter
function countUsage() {
    const editor = document.getElementById('editor-content');
    const source = document.getElementById('editor-source');
    const content = source.classList.contains('hidden') ? editor.innerHTML : source.value;
    document.querySelectorAll('.var-row').forEach(function(row) {
        const btn = row.querySelector('.var-btn');
        const varName = btn ? btn.getAttribute('data-var') : '';
        if (!varName) return;
        let count = 0;
        let idx = 0;
        const searchStr = varName.trim();
        while ((idx = content.indexOf(searchStr, idx)) !== -1) {
            count++;
            idx += searchStr.length;
        }
        const badge = row.querySelector('.var-usage');
        if (badge) {
            badge.style.display = count > 0 ? 'inline' : 'none';
            badge.textContent = count;
        }
        row.classList.toggle('var-used', count > 0);
    });
}

function beforeSave() {
    const editor = document.getElementById('editor-content');
    const source = document.getElementById('editor-source');
    const hidden = document.getElementById('content-html');
    if (!source.classList.contains('hidden')) {
        hidden.value = source.value;
    } else {
        const pages = editor.querySelectorAll('.a4-page');
        let html = '';
        pages.forEach(p => { html += p.innerHTML; });
        hidden.value = html;
    }
    return true;
}

function applyParagraphStyle(style) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    document.execCommand('formatBlock', false, '<' + style + '>');
    editor.focus();
}

function applyFont(font) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    document.execCommand('fontName', false, font);
    editor.focus();
}

function applyFontSize(size) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    document.execCommand('fontSize', false, '7');
    setTimeout(function() {
        editor.querySelectorAll('font[size="7"]').forEach(function(f) {
            f.style.fontSize = size;
            f.removeAttribute('size');
        });
        editor.querySelectorAll('[style*="font-size"]').forEach(function(el) {
            if (el.style.fontSize && el.tagName !== 'FONT') {
                el.style.fontSize = size;
            }
        });
    }, 0);
    editor.focus();
}

function applyColor(color) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    document.execCommand('foreColor', false, color);
    document.querySelector('#text-color + .color-preview').style.background = color;
    editor.focus();
}

function applyBgColor(color) {
    restoreSelection();
    const editor = document.getElementById('editor-content');
    editor.focus();
    document.execCommand('hiliteColor', false, color);
    document.querySelector('#bg-color + .color-preview').style.background = color;
    editor.focus();
}

function insertPageBreak() {
    const editor = document.getElementById('editor-content');
    const newPage = document.createElement('div');
    newPage.className = 'a4-page';
    const sel = window.getSelection();
    if (sel.rangeCount) {
        const range = sel.getRangeAt(0);
        range.insertNode(newPage);
        range.setStart(newPage, 0);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
    } else {
        editor.appendChild(newPage);
    }
    editor.dispatchEvent(new Event('input'));
    document.getElementById('editor-content').focus();
}

function checkPaginate() {
    const editor = document.getElementById('editor-content');
    if (!editor) return;
    const pages = editor.querySelectorAll('.a4-page');
    const last = pages[pages.length - 1];
    if (!last || last.scrollHeight <= last.clientHeight + 3) return;
    const newPage = document.createElement('div');
    newPage.className = 'a4-page';
    const nodes = Array.from(last.childNodes);
    for (let i = nodes.length - 1; i >= 0; i--) {
        const el = nodes[i];
        last.removeChild(el);
        newPage.insertBefore(el, newPage.firstChild);
        if (last.scrollHeight <= last.clientHeight + 3) break;
    }
    editor.insertBefore(newPage, last.nextSibling);
}

function repageAll() {
    const editor = document.getElementById('editor-content');
    if (!editor) return;
    const existing = editor.querySelectorAll('.a4-page');
    const content = document.createElement('div');
    existing.forEach(function(p) {
        while (p.firstChild) content.appendChild(p.firstChild);
        p.remove();
    });
    const firstPage = document.createElement('div');
    firstPage.className = 'a4-page';
    while (content.firstChild) firstPage.appendChild(content.firstChild);
    editor.appendChild(firstPage);
    let safety = 0;
    while (safety < 100) {
        const pages = editor.querySelectorAll('.a4-page');
        const last = pages[pages.length - 1];
        if (!last || last.scrollHeight <= last.clientHeight + 3) break;
        checkPaginate();
        safety++;
    }
}

document.getElementById('editor-content')?.addEventListener('input', function() {
    clearTimeout(this._pageTimer);
    this._pageTimer = setTimeout(function() {
        checkPaginate();
        var prev = document.getElementById('editor-preview');
        if (prev && !prev.classList.contains('hidden')) {
            var source = document.getElementById('editor-source');
            var editor = document.getElementById('editor-content');
            var html = source.classList.contains('hidden') ? editor.innerHTML : source.value;
            prev.innerHTML = buildPaginatedPreview(html);
        }
    }, 300);
});

document.querySelector('.editor-toolbar')?.addEventListener('mousedown', function(e) {
    if (e.target.closest('button, .color-btn input')) saveSelection();
});

setTimeout(repageAll, 100);

function clearFormatting() {
    const sel = window.getSelection();
    if (!sel.rangeCount) return;
    if (sel.toString().length > 0) {
        document.execCommand('removeFormat', false, null);
    }
    document.getElementById('editor-content').focus();
}

function printEditor() {
    beforeSave();
    const pages = document.querySelectorAll('#editor-content .a4-page');
    let content = '';
    pages.forEach(p => { content += '<div class="a4-print-page">' + p.innerHTML + '</div>'; });
    const printWin = window.open('', '_blank', 'width=800,height=600');
    if (!printWin) {
        window.print();
        return;
    }
    printWin.document.write('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">');
    printWin.document.write('<title>Editeur de template</title>');
    printWin.document.write('<style>');
    printWin.document.write('body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.5; margin: 0; color: #000; }');
    printWin.document.write('h1 { font-size: 18pt; font-weight: 700; margin: 12pt 0 6pt; }');
    printWin.document.write('h2 { font-size: 16pt; font-weight: 700; margin: 10pt 0 4pt; }');
    printWin.document.write('h3 { font-size: 14pt; font-weight: 600; margin: 8pt 0 4pt; }');
    printWin.document.write('h4 { font-size: 12pt; font-weight: 600; margin: 6pt 0 3pt; }');
    printWin.document.write('p { margin: 0 0 6pt; }');
    printWin.document.write('table { width: 100%; border-collapse: collapse; margin: 6pt 0; }');
    printWin.document.write('td, th { border: 1px solid #999; padding: 4pt; }');
    printWin.document.write('var { color: #0090e7; font-style: normal; font-family: "Courier New", monospace; }');
    printWin.document.write('.a4-print-page { page-break-after: always; break-after: page; padding: 2cm; }');
    printWin.document.write('.a4-print-page:last-child { page-break-after: auto; }');
    printWin.document.write('@page { margin: 0; size: A4; }');
    printWin.document.write('@media print { body { margin: 0; padding: 0; } }');
    printWin.document.write('</style></head><body>');
    printWin.document.write(content);
    printWin.document.write('</body></html>');
    printWin.document.close();
    printWin.focus();
    setTimeout(function() { printWin.print(); }, 500);
}

function exportPDF() {
    beforeSave();
    const editor = document.getElementById('editor-content');
    const source = document.getElementById('editor-source');
    const html = source.classList.contains('hidden') ? editor.innerHTML : source.value;
    const paginated = buildPaginatedPreview(html);
    const pageHtmls = paginated.split('<hr class="page-break">');
    const content = pageHtmls.map(function(chunk, idx) {
        var inner = chunk.replace(/<div class="preview-page">/, '').replace(/<div class="preview-page-footer">.*?<\/div><\/div>$/s, '');
        return '<div class="a4-page-visuel">' + inner + '<div class="page-footer">Page ' + (idx + 1) + ' / ' + pageHtmls.length + '</div></div>';
    }).join('');
    const htmlOut = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
        + '<title>Exporter PDF</title><style>'
        + 'body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.5; margin: 0; color: #000; background:#f0f0f0; display:flex; flex-direction:column; align-items:center; padding:24px 0; }'
        + 'h1 { font-size: 18pt; font-weight: 700; margin: 12pt 0 6pt; }'
        + 'h2 { font-size: 16pt; font-weight: 700; margin: 10pt 0 4pt; }'
        + 'h3 { font-size: 14pt; font-weight: 600; margin: 8pt 0 4pt; }'
        + 'h4 { font-size: 12pt; font-weight: 600; margin: 6pt 0 3pt; }'
        + 'p { margin: 0 0 6pt; }'
        + 'table { width: 100%; border-collapse: collapse; margin: 6pt 0; }'
        + 'td, th { border: 1px solid #999; padding: 4pt; }'
        + 'var { color: #0090e7; font-style: normal; font-family: "Courier New", monospace; background:#e8f4fd; padding:0 2px; border-radius:2px; }'
        + '.a4-page-visuel { width:21cm; min-height:29.7cm; padding:2cm 2.5cm; background:white; box-shadow:0 2px 10px rgba(0,0,0,0.25); box-sizing:border-box; margin-bottom:24px; position:relative; }'
        + '.page-footer { position:absolute; bottom:1cm; left:2.5cm; right:2.5cm; text-align:center; font-size:9pt; color:#999; border-top:1px solid #ddd; padding-top:4pt; }'
        + '@media print { body { background:white; padding:0; } .a4-page-visuel { width:auto; min-height:auto; padding:2cm; box-shadow:none; margin:0; page-break-after:always; } .a4-page-visuel:last-child { page-break-after:auto; } .page-footer { display:none; } }'
        + '</style></head><body>' + content + '</body></html>';
    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    const name = document.querySelector('.section-header h2')?.textContent?.trim() || 'template';
    a.download = name + '.html';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

document.getElementById('var-search')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.var-row').forEach(function(row) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
});

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        beforeSave();
        document.getElementById('editor-form').submit();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printEditor();
    }
    if ((e.ctrlKey || e.metaKey) && e.code === 'Space') {
        e.preventDefault();
        const input = document.getElementById('var-search');
        input.focus();
        input.select();
    }
});

// Init enhancements
document.addEventListener('DOMContentLoaded', function() {
    initDragDrop();
    renderRecent();
    countUsage();
    document.getElementById('editor-content').addEventListener('input', countUsage);
    document.getElementById('editor-source').addEventListener('input', countUsage);
});
</script>
