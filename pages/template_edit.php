<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateEditor.php';

$templatesDir = __DIR__ . '/../templates';
$templatePath = isset($_GET['path']) ? realpath((string) $_GET['path']) : '';

if ($templatePath === '' || !str_starts_with($templatePath, realpath($templatesDir)) || !file_exists($templatePath)) {
    ?>
    <section class="card stack">
        <h2>Template introuvable</h2>
        <p>Le fichier demande n'existe pas ou n'est pas accessible.</p>
        <a class="btn" href="<?= e(app_url('templates')) ?>">Retour aux templates</a>
    </section>
    <?php
    return;
}

$filename = basename($templatePath);
$folder = basename(dirname($templatePath));
$templatesConfig = require __DIR__ . '/../config/templates.php';
$legalForms = $templatesConfig['legal_forms'];
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
                        <span class="mdi mdi-chevron-down"></span>
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
    </div>

    <div class="editor-main card stack">
        <div class="section-header">
            <div>
                <h2><?= e($docTypes[$docType] ?? $docType ?: 'Editeur de template') ?></h2>
                <p class="help-text"><?= e($legalForms[$folder] ?? $folder) ?> &mdash; <?= e($filename) ?></p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('template', ['path' => $templatePath])) ?>">Fermer</a>
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
                    <span class="mdi mdi-format-bold"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('italic')" title="Italique (Ctrl+I)">
                    <span class="mdi mdi-format-italic"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('underline')" title="Souligne (Ctrl+U)">
                    <span class="mdi mdi-format-underline"></span>
                </button>
                <span class="toolbar-sep"></span>
                <span class="color-btn">
                    <input type="color" id="text-color" value="#000000" onchange="applyColor(this.value)" title="Couleur du texte">
                    <span class="color-preview" style="background:#000000"></span>
                </span>
                <span class="color-btn">
                    <input type="color" id="bg-color" value="#ffff00" onchange="applyBgColor(this.value)" title="Surbrillance">
                    <span class="color-preview" style="background:#ffff00"></span>
                </span>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifyleft')" title="Aligner a gauche">
                    <span class="mdi mdi-format-align-left"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifycenter')" title="Centrer">
                    <span class="mdi mdi-format-align-center"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifyright')" title="Aligner a droite">
                    <span class="mdi mdi-format-align-right"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('justifyfull')" title="Justifier">
                    <span class="mdi mdi-format-align-justify"></span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('insertorderedlist')" title="Liste numerotee">
                    <span class="mdi mdi-format-list-numbered"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('insertunorderedlist')" title="Liste a puces">
                    <span class="mdi mdi-format-list-bulleted"></span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="showTableDialog()" title="Insérer un tableau">
                    <span class="mdi mdi-table"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('inserthorizontalrule')" title="Ligne horizontale">
                    <span class="mdi mdi-minus"></span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('undo')" title="Annuler">
                    <span class="mdi mdi-undo"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="exec('redo')" title="Retablir">
                    <span class="mdi mdi-redo"></span>
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleSource()" title="Code source">
                    <span class="mdi mdi-code-tags"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="togglePreview()" title="Aperçu">
                    <span class="mdi mdi-eye"></span> Aperçu
                </button>
                <span class="toolbar-sep"></span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearFormatting()" title="Effacer la mise en forme">
                    <span class="mdi mdi-format-clear"></span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="printEditor()" title="Imprimer / PDF (Ctrl+P)">
                    <span class="mdi mdi-printer"></span> PDF
                </button>
            </div>

            <div class="editor-wrapper">
                <div id="editor-content" class="editor-content" contenteditable="true" data-placeholder="Commencez a rediger..."><?= $htmlContent ?></div>
            </div>

            <textarea id="editor-source" class="editor-source hidden" spellcheck="false"><?= e($htmlContent) ?></textarea>

            <div class="editor-wrapper" id="preview-wrapper" style="display:none">
                <div id="editor-preview" class="editor-preview hidden"></div>
            </div>

            <div class="editor-actions">
                <div>
                    <button type="submit" class="btn" onclick="return beforeSave()">
                        <span class="mdi mdi-content-save"></span> Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="showSaveAs()">
                        <span class="mdi mdi-content-save-outline"></span> Enregistrer sous...
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="if(confirm('Creer un nouveau template vierge ?'))document.getElementById('blank-form').submit();">
                        <span class="mdi mdi-file-plus"></span> Nouveau vierge
                    </button>
                </div>
            </div>
        </form>

        <div id="table-dialog" class="table-dialog hidden">
            <div class="card stack">
                <h4>Insérer un tableau</h4>
                <div class="inline-form">
                    <label>Colonnes: <input type="number" id="table-cols" value="3" min="1" max="10" style="width:60px"></label>
                    <label>Lignes: <input type="number" id="table-rows" value="3" min="1" max="20" style="width:60px"></label>
                    <button type="button" class="btn btn-sm" onclick="insertTable()">Insérer</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeTableDialog()">Annuler</button>
                </div>
            </div>
        </div>

        <form method="post" id="save-as-form" class="hidden inline-form" style="margin-top:0.5rem">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="save_as">
            <input type="hidden" name="content_html" id="content-html-saveas">
            <input type="text" name="new_name" placeholder="Nom du fichier (ex: Mon_Template.docx)" required class="input-full">
            <button type="submit" class="btn" onclick="document.getElementById('content-html-saveas').value=document.getElementById('editor-content').innerHTML">Creer</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('save-as-form').classList.add('hidden')">Annuler</button>
        </form>

        <form method="post" id="blank-form" class="hidden">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_blank">
            <input type="hidden" name="blank_name" value="">
        </form>
    </div>
</section>

<style>
.template-editor-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1rem;
    align-items: start;
}
.editor-sidebar {
    position: sticky;
    top: 1rem;
    max-height: calc(100vh - 140px);
    overflow-y: auto;
}
.editor-sidebar::-webkit-scrollbar { width: 4px; }
.editor-sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
.variable-search { margin-bottom: 0.75rem; }
.input-full {
    width: 100%; padding: 0.5rem; background: var(--bg); border: 1px solid var(--border);
    border-radius: 4px; color: var(--text); font-family: inherit; font-size: 0.85rem; box-sizing: border-box;
}
.input-full:focus { outline: none; border-color: var(--primary); }
.var-category { margin-bottom: 0.25rem; }
.var-category-title {
    font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;
    color: var(--text-secondary); cursor: pointer; padding: 0.35rem 0;
    display: flex; align-items: center; gap: 0.35rem; user-select: none;
}
.var-category-title .mdi { font-size: 1rem; }
.var-category-title .var-count {
    margin-left: auto; font-size: 0.7rem; color: var(--text-muted);
    background: var(--panel); padding: 0 0.4rem; border-radius: 8px;
}
.var-list { display: flex; flex-direction: column; gap: 2px; margin-bottom: 0.5rem; }
.var-btn {
    display: flex; flex-direction: column; align-items: flex-start;
    padding: 0.3rem 0.5rem; background: transparent; border: 1px solid transparent;
    border-radius: 4px; cursor: pointer; text-align: left; transition: all 0.15s;
}
.var-btn:hover { background: var(--panel-hover); border-color: var(--border); }
.var-btn code { font-size: 0.78rem; color: var(--primary); font-family: 'Courier New', monospace; }
.var-btn small { font-size: 0.68rem; color: var(--text-muted); }
.editor-main { min-height: 400px; }
.editor-toolbar {
    display: flex; align-items: center; gap: 0.25rem;
    padding: 0.5rem; background: var(--bg); border: 1px solid var(--border);
    border-bottom: none; border-radius: 4px 4px 0 0; flex-wrap: wrap;
}
.toolbar-sep { width: 1px; height: 22px; background: var(--border); margin: 0 0.25rem; }
.btn-sm { padding: 0.3rem 0.5rem; font-size: 0.8rem; }
.editor-content {
    width: 100%; min-height: 400px; padding: 2cm 2.5cm;
    background: white; color: #1a1a1a;
    font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt;
    line-height: 1.5; border: 1px solid var(--border);
    outline: none; overflow-y: auto; box-sizing: border-box;
    max-width: 21cm; margin: 0 auto; box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}
.editor-content:empty:before {
    content: attr(data-placeholder);
    color: #bbb;
    pointer-events: none;
}
.editor-content:focus { border-color: var(--primary); }
.editor-content h1 { font-size: 18pt; font-weight: 700; margin: 12pt 0 6pt; }
.editor-content h2 { font-size: 16pt; font-weight: 700; margin: 10pt 0 4pt; }
.editor-content h3 { font-size: 14pt; font-weight: 600; margin: 8pt 0 4pt; }
.editor-content h4 { font-size: 12pt; font-weight: 600; margin: 6pt 0 3pt; }
.editor-content p { margin: 0 0 6pt; }
.editor-content table { width: 100%; border-collapse: collapse; margin: 6pt 0; }
.editor-content td, .editor-content th { border: 1px solid #999; padding: 4pt; }
.editor-content:focus { border-color: var(--primary); }
.editor-content var {
    color: #0090e7; font-style: normal; font-family: 'Courier New', monospace;
    background: #e8f4fd; padding: 0 3px; border-radius: 2px;
}
.editor-source {
    width: 100%; min-height: 400px; padding: 1rem;
    background: #1a1a2e; color: #e0e0e0;
    font-family: 'Courier New', monospace; font-size: 0.8rem;
    line-height: 1.5; border: 1px solid var(--border);
    resize: vertical; box-sizing: border-box; tab-size: 2;
}
.editor-preview {
    width: 100%; min-height: 400px; padding: 2cm 2.5cm;
    background: white; border: 1px solid var(--border);
    border-radius: 0 0 4px 4px; color: #1a1a1a;
    font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt;
    line-height: 1.5; overflow-y: auto; box-sizing: border-box;
    max-width: 21cm; margin: 0 auto; box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}
.editor-preview var {
    color: #0090e7; font-style: normal; font-family: 'Courier New', monospace;
    background: #e8f4fd; padding: 0 2px; border-radius: 2px;
}
.editor-wrapper {
    background: #666; padding: 1.5rem 1rem;
    border-radius: 0 0 4px 4px; overflow-y: auto;
    max-height: calc(100vh - 280px); min-height: 400px;
}
.editor-toolbar select {
    padding: 0.25rem 0.4rem; background: var(--bg); border: 1px solid var(--border);
    border-radius: 4px; color: var(--text); font-size: 0.78rem; cursor: pointer;
    font-family: inherit; max-width: 140px;
}
.editor-toolbar select:focus { outline: none; border-color: var(--primary); }
.editor-toolbar input[type="color"] {
    width: 28px; height: 28px; padding: 2px; border: 1px solid var(--border);
    border-radius: 4px; background: transparent; cursor: pointer; vertical-align: middle;
}
.editor-toolbar input[type="color"]:hover { border-color: var(--text-secondary); }
.editor-toolbar .color-btn {
    position: relative; display: inline-flex; align-items: center; gap: 2px;
}
.editor-toolbar .color-btn .color-preview {
    width: 12px; height: 12px; border-radius: 2px; border: 1px solid var(--border);
    display: inline-block;
}
@media print {
    body { background: white !important; margin: 0 !important; padding: 0 !important; }
    .sidebar, .page-header, .flash, .editor-sidebar, .editor-toolbar,
    .editor-actions, .editor-source, #table-dialog, #save-as-form, #blank-form,
    .template-editor-layout .section-header .table-actions { display: none !important; }
    .template-editor-layout { display: block !important; }
    .editor-main { border: none !important; padding: 0 !important; box-shadow: none !important; }
    .editor-wrapper { background: none !important; padding: 0 !important; max-height: none !important; box-shadow: none !important; }
    .editor-content { max-width: none !important; box-shadow: none !important; padding: 0 !important; border: none !important; }
    .main { padding: 0 !important; }
    .shell { display: block !important; }
    @page { margin: 2cm; size: A4; }
}
.editor-actions {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 0.75rem; gap: 0.5rem; flex-wrap: wrap;
}
.editor-actions > div { display: flex; gap: 0.5rem; align-items: center; }
.table-dialog {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
    z-index: 1000;
}
.table-dialog .card { padding: 1.5rem; min-width: 350px; }
.table-dialog .inline-form { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; margin-top: 0.75rem; }
.table-dialog label { display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.85rem; color: var(--text-secondary); }
.table-dialog input { padding: 0.4rem; background: var(--bg); border: 1px solid var(--border); border-radius: 4px; color: var(--text); }
.hidden { display: none !important; }
@media (max-width: 900px) {
    .template-editor-layout { grid-template-columns: 1fr; }
    .editor-sidebar { position: static; max-height: none; }
}
</style>

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
    document.execCommand(cmd, false, val || null);
    document.getElementById('editor-content').focus();
}

function insertVar(text) {
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
    const icon = titleEl.querySelector('.mdi');
    if (list.style.display === 'none') {
        list.style.display = 'flex';
        icon.classList.replace('mdi-chevron-right', 'mdi-chevron-down');
    } else {
        list.style.display = 'none';
        icon.classList.replace('mdi-chevron-down', 'mdi-chevron-right');
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

function toggleSource() {
    const editor = document.getElementById('editor-content');
    const wrappers = document.querySelectorAll('.editor-wrapper');
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
    const wrappers = document.querySelectorAll('.editor-wrapper');
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

function beforeSave() {
    const editor = document.getElementById('editor-content');
    const source = document.getElementById('editor-source');
    const hidden = document.getElementById('content-html');
    if (!source.classList.contains('hidden')) {
        hidden.value = source.value;
    } else {
        hidden.value = editor.innerHTML;
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
    document.execCommand('foreColor', false, color);
    document.querySelector('#text-color + .color-preview').style.background = color;
    document.getElementById('editor-content').focus();
}

function applyBgColor(color) {
    document.execCommand('hiliteColor', false, color);
    document.querySelector('#bg-color + .color-preview').style.background = color;
    document.getElementById('editor-content').focus();
}

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
    const content = document.getElementById('editor-content').innerHTML;
    const printWin = window.open('', '_blank', 'width=800,height=600');
    if (!printWin) {
        window.print();
        return;
    }
    printWin.document.write('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">');
    printWin.document.write('<title>Editeur de template</title>');
    printWin.document.write('<style>');
    printWin.document.write('body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.5; margin: 2cm; color: #000; }');
    printWin.document.write('h1 { font-size: 18pt; font-weight: 700; margin: 12pt 0 6pt; }');
    printWin.document.write('h2 { font-size: 16pt; font-weight: 700; margin: 10pt 0 4pt; }');
    printWin.document.write('h3 { font-size: 14pt; font-weight: 600; margin: 8pt 0 4pt; }');
    printWin.document.write('h4 { font-size: 12pt; font-weight: 600; margin: 6pt 0 3pt; }');
    printWin.document.write('p { margin: 0 0 6pt; }');
    printWin.document.write('table { width: 100%; border-collapse: collapse; margin: 6pt 0; }');
    printWin.document.write('td, th { border: 1px solid #999; padding: 4pt; }');
    printWin.document.write('var { color: #0090e7; font-style: normal; font-family: "Courier New", monospace; }');
    printWin.document.write('@page { margin: 2cm; size: A4; }');
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
