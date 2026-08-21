<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/analyseur_templates.php';

$templatesDir = __DIR__ . '/../../templates';
$outputDir = __DIR__ . '/../../dossiers_generer/dossiers_domiciliation';

$pageSubtitle = 'Variables trouvees dans les templates vs. variables disponibles dans le contexte de rendu.';

$templates = TemplateAnalyzer::scanTemplates($templatesDir);
$analysis = null;
$exported = false;

if ($templates) {
    $analysis = TemplateAnalyzer::analyzeCoverage($templates);

    if (is_post() && isset($_POST['rename'])) {
        verify_csrf();
        $oldName = trim($_POST['var_name'] ?? '');
        $newName = trim($_POST['new_name'] ?? '');
        if ($oldName !== '' && $newName !== '') {
            $result = TemplateAnalyzer::renameVariable($oldName, $newName, $templatesDir);
            $msg = "Variable {$oldName} renommee en {$newName} dans {$result['modified']} template(s).";
            if (!empty($result['errors'])) {
                $msg .= ' Erreurs: ' . implode('; ', $result['errors']);
            }
            set_flash('success', $msg);
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['delete_var'])) {
        verify_csrf();
        $varName = trim($_POST['var_name'] ?? '');
        if ($varName !== '') {
            $result = TemplateAnalyzer::deleteVariable($varName, $templatesDir);
            $msg = "Variable {$varName} supprimee de {$result['modified']} template(s).";
            if (!empty($result['errors'])) {
                $msg .= ' Erreurs: ' . implode('; ', $result['errors']);
            }
            set_flash('success', $msg);
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['bulk_rename'])) {
        verify_csrf();
        $oldNames = $_POST['old_names'] ?? [];
        $newNames = $_POST['new_names'] ?? [];
        if (is_array($oldNames) && is_array($newNames)) {
            $total = 0;
            $errors = [];
            foreach ($oldNames as $i => $old) {
                $new = $newNames[$i] ?? '';
                $old = trim($old);
                $new = trim($new);
                if ($old !== '' && $new !== '' && $old !== $new) {
                    $result = TemplateAnalyzer::renameVariable($old, $new, $templatesDir);
                    $total += $result['modified'];
                    if (!empty($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                    }
                }
            }
            $count = count($oldNames);
            $msg = "{$count} variable(s) renommee(s) dans {$total} template(s).";
            if (!empty($errors)) {
                $msg .= ' Erreurs: ' . implode('; ', $errors);
            }
            set_flash('success', $msg);
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['bulk_delete'])) {
        verify_csrf();
        $selected = $_POST['selected_vars'] ?? [];
        if (is_array($selected) && !empty($selected)) {
            $result = TemplateAnalyzer::deleteVariables($selected, $templatesDir);
            $count = count($selected);
            $msg = "{$count} variable(s) supprimee(s) de {$result['modified']} template(s).";
            if (!empty($result['errors'])) {
                $msg .= ' Erreurs: ' . implode('; ', $result['errors']);
            }
            set_flash('success', $msg);
            log_activity($pdo, 'rename', 'template_variable', null, "{$oldName} → {$newName}");
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['delete_var'])) {
        $varName = trim($_POST['var_name'] ?? '');
        if ($varName !== '') {
            $result = TemplateAnalyzer::deleteVariable($varName, $templatesDir);
            $msg = "Variable {$varName} supprimee de {$result['modified']} template(s).";
            if (!empty($result['errors'])) {
                $msg .= ' Erreurs: ' . implode('; ', $result['errors']);
            }
            set_flash('success', $msg);
            log_activity($pdo, 'delete', 'template_variable', null, $varName);
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['bulk_rename'])) {
        verify_csrf();
        $oldNames = $_POST['old_names'] ?? [];
        $newNames = $_POST['new_names'] ?? [];
        if (is_array($oldNames) && is_array($newNames)) {
            $total = 0;
            $errors = [];
            foreach ($oldNames as $i => $old) {
                $new = $newNames[$i] ?? '';
                $old = trim($old);
                $new = trim($new);
                if ($old !== '' && $new !== '' && $old !== $new) {
                    $result = TemplateAnalyzer::renameVariable($old, $new, $templatesDir);
                    $total += $result['modified'];
                    if (!empty($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                    }
                }
            }
            $count = count($oldNames);
            $msg = "{$count} variable(s) renommee(s) dans {$total} template(s).";
            if (!empty($errors)) {
                $msg .= ' Erreurs: ' . implode('; ', $errors);
            }
            set_flash('success', $msg);
            log_activity($pdo, 'bulk_rename', 'template_variable', null, $count . ' variable(s)');
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['bulk_delete'])) {
        verify_csrf();
        $selected = $_POST['selected_vars'] ?? [];
        if (is_array($selected) && !empty($selected)) {
            $result = TemplateAnalyzer::deleteVariables($selected, $templatesDir);
            $count = count($selected);
            $msg = "{$count} variable(s) supprimee(s) de {$result['modified']} template(s).";
            if (!empty($result['errors'])) {
                $msg .= ' Erreurs: ' . implode('; ', $result['errors']);
            }
            set_flash('success', $msg);
            log_activity($pdo, 'bulk_delete', 'template_variable', null, $count . ' variable(s)');
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['export_csv'])) {
        verify_csrf();
        $csvPath = $outputDir . DIRECTORY_SEPARATOR . 'analyse_templates_' . date('Y-m-d_His') . '.csv';
        TemplateAnalyzer::exportAnalysisCsv($analysis['variables'], $csvPath);
        set_flash('success', 'Analyse exportee dans output/');
        log_activity($pdo, 'export', 'analysis');
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['ai_suggest'])) {
        verify_csrf();
        if (ClaudeService::isAvailable()) {
            $result = ClaudeService::analyzeTemplates($analysis['variables']);
            $_SESSION['ai_analysis_suggestions'] = $result;
            if ($result !== null) {
                set_flash('success', 'Suggestions IA generees.');
                log_activity($pdo, 'ai_suggest', 'analysis');
            } else {
                set_flash('error', "Erreur lors de la generation des suggestions IA.");
            }
        } else {
            set_flash('error', "L'assistant IA n'est pas disponible. Configurez la cle API dans le fichier .env.");
        }
        redirect_to('analyse-couverture');
    }
}

$aiSuggestions = $_SESSION['ai_analysis_suggestions'] ?? null;
if ($aiSuggestions !== null) {
    unset($_SESSION['ai_analysis_suggestions']);
}
?>
<section class="card stack">
    <div class="section-header">
        <?php
    $activeFilter = $_GET['filter'] ?? 'all';
    if ($analysis):
        $covered = $analysis['summary']['covered_variables'];
        $uncovered = $analysis['summary']['uncovered_variables'];
        $total = $covered + $uncovered;
        $filterBtnClass = fn($f) => 'btn btn-sm ' . ($activeFilter === $f ? 'btn-next' : 'btn-secondary');
    ?>
        <div class="table-actions">
            <div class="analyse-filter-bar">
                <span class="analyse-filter-label">Filtrer :</span>
                <a class="<?= $filterBtnClass('all') ?>" href="?page=analyse-couverture" data-filter="all">Tous <span class="badge bg-secondary"><?= $total ?></span></a>
                <a class="<?= $filterBtnClass('couvert') ?>" href="?page=analyse-couverture&filter=couvert" data-filter="couvert">Couvertes <span class="badge bg-success"><?= $covered ?></span></a>
                <a class="<?= $filterBtnClass('non couvert') ?>" href="<?= e(app_url('analyse-couverture', ['filter' => 'non couvert'])) ?>" data-filter="non couvert">Non couvertes <span class="badge bg-danger"><?= $uncovered ?></span></a>
                <input type="text" id="var-search" class="var-search-input" placeholder="Rechercher une variable...">
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($aiSuggestions && isset($aiSuggestions['suggestions'])): ?>
        <div class="card ai-suggestions-card">
            <div class="section-header">
                <h4><span class="material-symbols-outlined text-info">smart_toy</span> Suggestions IA</h4>
            </div>
            <div class="ai-suggestions-list">
            <?php foreach ($aiSuggestions['suggestions'] as $suggestion): ?>
                <?php
                    $badgeClass = match($suggestion['action'] ?? '') {
                        'rename' => 'bg-warning',
                        'delete' => 'bg-danger',
                        default => 'bg-success',
                    };
                ?>
                <div class="ai-suggestion-row">
                    <code><?= e($suggestion['variable'] ?? '') ?></code>
                    <span class="ai-suggestion-arrow">→</span>
                    <span><?= e($suggestion['suggestion'] ?? '') ?></span>
                    <span class="ai-suggestion-badge <?= $badgeClass ?>"><?= e($suggestion['action'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$templates): ?>
        <p class="table-empty">Aucun template trouve. Ajoutez des fichiers .docx sur la page Templates.</p>
    <?php elseif (!$analysis): ?>
        <p class="table-empty">Impossible d analyser les templates.</p>
    <?php else: ?>
    <div class="table-scroll">
    <table data-sortable>
        <thead>
            <tr>
                <th class="var-th-checkbox"><input type="checkbox" id="select-all" title="Tout cocher"></th>
                <th data-col="Variable">Variable</th>
                <th data-col="Occurrences">Occurrences</th>
                <th data-col="Templates">Templates</th>
                <th data-col="Section">Section</th>
                <th data-col="Couverture">Couverture</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $contextKeys = TemplateAnalyzer::getExpectedContextKeys(); ?>
            <?php foreach ($analysis['variables'] as $v): ?>
                <tr data-coverage="<?= e($v['coverage']) ?>">
                    <td><input type="checkbox" class="var-checkbox" value="<?= e($v['variable']) ?>"></td>
                    <td><code><?= e($v['variable']) ?></code></td>
                    <td><?= e((string) $v['occurrences']) ?></td>
                    <td title="<?= e(implode(', ', $v['templates'])) ?>"><?= e((string) $v['templates_count']) ?> template(s)</td>
                    <td><span class="pill"><?= e($v['section']) ?></span></td>
                    <td>
                        <span class="statut-badge <?= $v['coverage'] === 'couvert' ? 'actif' : 'resilie' ?>">
                            <?= e($v['coverage']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="var-action-cell">
                            <form method="post" class="rename-var-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="var_name" value="<?= e($v['variable']) ?>">
                                <select name="new_name" required class="select-rename">
                                    <option value="">Renommer en...</option>
                                    <?php foreach ($contextKeys as $ck): ?>
                                    <option value="<?= e($ck) ?>"><?= e($ck) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="rename" value="1" class="btn-icon info" title="Renommer">
                                    <span class="material-symbols-outlined">drive_file_rename_outline</span>
                                </button>
                            </form>
                            <form method="post" class="inline-form delete-var-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="var_name" value="<?= e($v['variable']) ?>">
                                <button type="submit" name="delete_var" value="1" class="btn-icon danger" title="Supprimer">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</section>
<?php if ($analysis): ?>
<div class="table-actions bulk-actions-bar">
    <div class="bulk-actions-left">
        <button type="button" id="invert-select-btn" class="btn btn-secondary"><span class="material-symbols-outlined">deselect</span> Inverser la sélection</button>
    </div>
    <button type="button" id="bulk-rename-btn" class="btn btn-info"><span class="material-symbols-outlined">drive_file_rename_outline</span> Renommer la sélection</button>
    <button type="button" id="bulk-delete-btn" class="btn btn-danger"><span class="material-symbols-outlined">delete</span> Supprimer la sélection</button>
    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <button type="submit" name="export_csv" value="1" class="btn btn-info"><span class="material-symbols-outlined">download</span> Export CSV</button>
    </form>
    <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <button type="submit" name="ai_suggest" value="1" class="btn btn-info"><span class="material-symbols-outlined">smart_toy</span> Suggérer avec IA</button>
    </form>
</div>
<?php endif; ?>

<form id="bulk-delete-form" method="post" class="hidden-form">
    <?= csrf_input() ?>
</form>
<form id="bulk-rename-form" method="post" class="hidden-form">
    <?= csrf_input() ?>
</form>

<div id="loading-overlay">
    <div class="loader-card">
        <div class="spinner"></div>
        <p id="loading-text">Traitement en cours...</p>
    </div>
</div>
<script>
(function(){
    var overlay = document.getElementById('loading-overlay');
    var text = document.getElementById('loading-text');
    window.showOverlay = function(msg){
        text.textContent = msg;
        overlay.classList.add('show');
    };

    var searchInput = document.getElementById('var-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(){
            var q = this.value.toUpperCase();
            document.querySelectorAll('tr[data-coverage]').forEach(function(row){
                var code = row.querySelector('code');
                if (code) {
                    var match = code.textContent.toUpperCase().indexOf(q) !== -1;
                    row.style.display = match ? '' : 'none';
                }
            });
        });
    }

    document.getElementById('select-all').addEventListener('change', function(){
        document.querySelectorAll('.var-checkbox').forEach(function(cb){
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = this.checked;
            }
        }, this);
    });

    document.getElementById('invert-select-btn').addEventListener('click', function(){
        document.querySelectorAll('.var-checkbox').forEach(function(cb){
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = !cb.checked;
            }
        });
    });

    document.getElementById('bulk-rename-btn').addEventListener('click', function(){
        var checked = document.querySelectorAll('.var-checkbox:checked');
        if (checked.length === 0) {
            alert('Selectionnez au moins une variable.');
            return;
        }
        var pairs = [];
        checked.forEach(function(cb){
            var oldName = cb.value;
            var select = cb.closest('tr').querySelector('select[name="new_name"]');
            if (!select) return;
            var newName = select.value;
            if (newName !== '' && newName !== oldName) {
                pairs.push({old: oldName, new: newName});
            }
        });
        if (pairs.length === 0) {
            alert('Selectionnez au moins une variable avec un nouveau nom dans la liste deroulante.');
            return;
        }
        if (!confirm('Confirmer le renommage de ' + pairs.length + ' variable(s) ?')) return;
        var form = document.getElementById('bulk-rename-form');
        document.querySelectorAll('#bulk-rename-form .dynamic-input').forEach(function(e){ e.remove(); });
        pairs.forEach(function(p){
            var inpOld = document.createElement('input');
            inpOld.type = 'hidden';
            inpOld.name = 'old_names[]';
            inpOld.value = p.old;
            inpOld.className = 'dynamic-input';
            form.appendChild(inpOld);
            var inpNew = document.createElement('input');
            inpNew.type = 'hidden';
            inpNew.name = 'new_names[]';
            inpNew.value = p.new;
            inpNew.className = 'dynamic-input';
            form.appendChild(inpNew);
        });
        var btn = document.createElement('input');
        btn.type = 'hidden';
        btn.name = 'bulk_rename';
        btn.value = '1';
        btn.className = 'dynamic-input';
        form.appendChild(btn);
        window.showOverlay('Renommage en cours...');
        form.submit();
    });

    document.getElementById('bulk-delete-btn').addEventListener('click', function(){
        var checked = document.querySelectorAll('.var-checkbox:checked');
        if (checked.length === 0) {
            alert('Selectionnez au moins une variable.');
            return;
        }
        var names = [];
        checked.forEach(function(cb){ names.push(cb.value); });
        if (!confirm('Supprimer ' + names.length + ' variable(s) de tous les templates ?')) return;
        var form = document.getElementById('bulk-delete-form');
        document.querySelectorAll('#bulk-delete-form .dynamic-input').forEach(function(e){ e.remove(); });
        names.forEach(function(name){
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'selected_vars[]';
            inp.value = name;
            inp.className = 'dynamic-input';
            form.appendChild(inp);
        });
        var btn = document.createElement('input');
        btn.type = 'hidden';
        btn.name = 'bulk_delete';
        btn.value = '1';
        btn.className = 'dynamic-input';
        form.appendChild(btn);
        window.showOverlay('Suppression en cours...');
        form.submit();
    });

    document.querySelectorAll('.delete-var-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            var varName = form.querySelector('input[name="var_name"]').value;
            if (!confirm('Supprimer _' + varName + '_ de tous les templates ?')) {
                e.preventDefault();
                return;
            }
            window.showOverlay('Suppression en cours...');
        });
    });
    document.querySelectorAll('.rename-var-form').forEach(function(form){
        form.addEventListener('submit', function(){
            window.showOverlay('Renommage en cours...');
        });
    });

    var activeFilter = '<?= $activeFilter ?>';
    if (activeFilter !== 'all') {
        document.querySelectorAll('tr[data-coverage]').forEach(function(row){
            row.style.display = row.getAttribute('data-coverage') === activeFilter ? '' : 'none';
        });
    }
})();
</script>
