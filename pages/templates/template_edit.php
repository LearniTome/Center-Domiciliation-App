<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/editeur_templates.php';

$templatesDir = __DIR__ . '/../../templates';
$templatePath = isset($_GET['path']) ? realpath((string) $_GET['path']) : '';

$folderLabels = require __DIR__ . '/../../config/templates.php';
$folderLabels = $folderLabels['folder_labels'];

$templateDirs = array_filter(glob($templatesDir . '/*', GLOB_ONLYDIR), fn($d) => basename($d)[0] !== '_');
$racineDir = $templatesDir . '/_Racine-Actifs';

if ($templatePath === '' || !str_starts_with($templatePath, realpath($templatesDir)) || !file_exists($templatePath)) {
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
?>

<section class="template-editor-layout">
    <div class="editor-sidebar card stack">
        <div class="section-header">
            <h3>Variables</h3>
            <p class="help-text">Cliquez pour insérer</p>
        </div>
        <div class="variable-search">
            <input type="text" id="var-search" placeholder="Rechercher..." class="input-full">
        </div>
            <div class="variable-categories">
                <?php foreach ($variables as $category => $vars): ?>
                    <div class="var-category">
                        <h4 class="var-category-title" onclick="toggleCategory(this)">
                            <span class="material-symbols-outlined">expand_more</span>
                            <?= e($category) ?>
                            <span class="var-count"><?= count($vars) ?></span>
                        </h4>
                        <div class="var-list">
                            <?php foreach ($vars as $varName => $varLabel): ?>
                                <button type="button" class="var-btn" onclick="insertVar('{{ <?= e($varName) ?> }}')" title="<?= e($varLabel) ?>">
                                    <code>{{ <?= e($varName) ?> }}</code>
                                    <small><?= e($varLabel) ?></small>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sidebar-zoom-controls">
                <span class="sidebar-zoom-label">Zoom éditeur</span>
                <div class="sidebar-zoom-buttons">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="editorZoomOut()" title="Zoom arrière">
                        <span class="material-symbols-outlined">zoom_out</span>
                    </button>
                    <span id="editor-zoom-level" class="preview-zoom-level">100%</span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="editorZoomIn()" title="Zoom avant">
                        <span class="material-symbols-outlined">zoom_in</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="editorZoomReset()" title="Rétablir le zoom">
                        <span class="material-symbols-outlined">fit_screen</span>
                    </button>
                </div>
            </div>
        </div>

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
                    <span class="material-symbols-outlined">format_underline</span>
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
                    <span class="material-symbols-outlined">visibility</span> Aperçu
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearFormatting()" title="Effacer la mise en forme">
                    <span class="material-symbols-outlined">format_clear</span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="printEditor()" title="Imprimer / PDF (Ctrl+P)">
                    <span class="material-symbols-outlined">print</span> PDF
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="insertPageBreak()" title="Nouvelle page">
                    <span class="material-symbols-outlined">note_add</span>
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
}

function toggleCategory(titleEl) {
    const list = titleEl.nextElementSibling;
    const icon = titleEl.querySelector('.material-symbols-outlined');
    if (list.style.display === 'none') {
        list.style.display = 'flex';
        icon.textContent = 'expand_more';
    } else {
        list.style.display = 'none';
        icon.textContent = 'chevron_right';
    }
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
        let display = html
            .replace(/<var>/g, '<var style="color:#0090e7;font-style:normal;font-family:\'Courier New\',monospace;background:#e8f4fd;padding:0 2px;border-radius:2px">')
            .replace(/<table/g, '<table style="width:100%;border-collapse:collapse;margin:0.5em 0"')
            .replace(/<td/g, '<td style="border:1px solid #999;padding:6px"')
            .replace(/<th/g, '<th style="border:1px solid #999;padding:6px;background:#f0f0f0"');
        preview.innerHTML = display || '<p style="color:#999">(vide)</p>';
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
    const pages = editor.querySelectorAll('.a4-page');
    const last = pages[pages.length - 1];
    if (!last) return;
    if (last.scrollHeight > last.clientHeight + 3) {
        const newPage = document.createElement('div');
        newPage.className = 'a4-page';
        const nodes = Array.from(last.childNodes);
        let moved = false;
        for (let i = nodes.length - 1; i >= 0; i--) {
            const el = nodes[i];
            const h = el.offsetHeight || 0;
            last.removeChild(el);
            newPage.insertBefore(el, newPage.firstChild);
            if (last.scrollHeight <= last.clientHeight + 3) {
                moved = true;
                break;
            }
        }
        if (last.nextSibling) {
            editor.insertBefore(newPage, last.nextSibling);
        } else {
            editor.appendChild(newPage);
        }
    }
}

document.getElementById('editor-content')?.addEventListener('input', function() {
    clearTimeout(this._pageTimer);
    this._pageTimer = setTimeout(checkPaginate, 300);
});

document.querySelector('.editor-toolbar')?.addEventListener('mousedown', function(e) {
    if (e.target.closest('button, .color-btn input')) saveSelection();
});

document.querySelector('.var-grid')?.addEventListener('mousedown', function(e) {
    if (e.target.closest('.var-btn')) saveSelection();
});

setTimeout(checkPaginate, 100);

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

document.getElementById('var-search')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.var-btn').forEach(function(btn) {
        btn.style.display = btn.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
    document.querySelectorAll('.var-category').forEach(function(cat) {
        const btns = cat.querySelectorAll('.var-btn');
        const visible = Array.from(btns).some(b => b.style.display !== 'none');
        cat.style.display = visible ? '' : 'none';
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
});
</script>
