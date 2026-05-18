<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesDir = __DIR__ . '/../templates';
$outputDir = __DIR__ . '/../dossiers_dom';

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
        }
        redirect_to('analyse-couverture');
    }

    if (is_post() && isset($_POST['export_csv'])) {
        verify_csrf();
        $csvPath = $outputDir . DIRECTORY_SEPARATOR . 'analyse_templates_' . date('Y-m-d_His') . '.csv';
        TemplateAnalyzer::exportAnalysisCsv($analysis['variables'], $csvPath);
        set_flash('success', 'Analyse exportee dans output/');
        redirect_to('analyse-couverture');
    }
}
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Analyse de couverture des variables</h2>
            <p class="help-text">Variables trouvees dans les templates vs. variables disponibles dans le contexte de rendu.</p>
        </div>
        <?php if ($analysis): ?>
        <div class="table-actions">
            <button type="button" id="bulk-rename-btn" class="btn btn-info"><span class="mdi mdi-rename"></span> Renommer la sélection</button>
            <button type="button" id="bulk-delete-btn" class="btn btn-danger"><span class="mdi mdi-delete"></span> Supprimer la sélection</button>
            <form method="post" style="display:inline">
                <?= csrf_input() ?>
                <button type="submit" name="export_csv" value="1" class="btn btn-info"><span class="mdi mdi-download"></span> Export CSV</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$templates): ?>
        <p class="table-empty">Aucun template trouve. Ajoutez des fichiers .docx sur la page Templates.</p>
    <?php elseif (!$analysis): ?>
        <p class="table-empty">Impossible d analyser les templates.</p>
    <?php else: ?>
    <div class="stats compact">
        <article class="stat">
            <span>Templates</span>
            <strong><?= $analysis['summary']['total_templates'] ?></strong>
        </article>
        <article class="stat">
            <span>Variables distinctes</span>
            <strong><?= $analysis['summary']['total_variables'] ?></strong>
        </article>
        <article class="stat">
            <span>Couvertes</span>
            <strong style="color:var(--success)"><?= $analysis['summary']['covered_variables'] ?></strong>
        </article>
        <article class="stat">
            <span>Non couvertes</span>
            <strong style="color:var(--danger)"><?= $analysis['summary']['uncovered_variables'] ?></strong>
        </article>
    </div>

    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th style="width:32px"><input type="checkbox" id="select-all" title="Tout cocher"></th>
                <th>Variable</th>
                <th>Occurrences</th>
                <th>Templates</th>
                <th>Section</th>
                <th>Couverture</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $contextKeys = TemplateAnalyzer::getExpectedContextKeys(); ?>
            <?php foreach ($analysis['variables'] as $v): ?>
                <tr>
                    <td><input type="checkbox" class="var-checkbox" value="<?= e($v['variable']) ?>"></td>
                    <td><code><?= e($v['variable']) ?></code></td>
                    <td><?= e((string) $v['occurrences']) ?></td>
                    <td><?= e((string) $v['templates_count']) ?> template(s)</td>
                    <td><span class="pill"><?= e($v['section']) ?></span></td>
                    <td>
                        <span class="statut-badge <?= $v['coverage'] === 'couvert' ? 'actif' : 'resilie' ?>">
                            <?= e($v['coverage']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center">
                            <form method="post" style="display:flex;gap:4px;align-items:center" class="rename-var-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="var_name" value="<?= e($v['variable']) ?>">
                                <select name="new_name" required style="max-width:140px;font-size:0.75rem;padding:2px 4px">
                                    <option value="">Renommer en...</option>
                                    <?php foreach ($contextKeys as $ck): ?>
                                    <option value="<?= e($ck) ?>"><?= e($ck) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="rename" value="1" class="btn-icon" title="Renommer">
                                    <span class="mdi mdi-rename"></span>
                                </button>
                            </form>
                            <form method="post" style="display:inline" class="delete-var-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="var_name" value="<?= e($v['variable']) ?>">
                                <button type="submit" name="delete_var" value="1" class="btn-icon danger" title="Supprimer">
                                    <span class="mdi mdi-delete"></span>
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

<form id="bulk-delete-form" method="post" style="display:none">
    <?= csrf_input() ?>
</form>
<form id="bulk-rename-form" method="post" style="display:none">
    <?= csrf_input() ?>
</form>

<div id="loading-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center">
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
        overlay.style.display = 'flex';
    };

    document.getElementById('select-all').addEventListener('change', function(){
        document.querySelectorAll('.var-checkbox').forEach(function(cb){
            cb.checked = this.checked;
        }, this);
    });

    document.getElementById('bulk-rename-btn').addEventListener('click', function(){
        var checked = document.querySelectorAll('.var-checkbox:checked');
        if (checked.length === 0) {
            alert('Selectionnez au moins une variable.');
            return;
        }
        var pairs = [];
        var cancelled = false;
        checked.forEach(function(cb){
            if (cancelled) return;
            var oldName = cb.value;
            var newName = prompt('Renommer {{ ' + oldName + ' }} en :', '');
            if (newName === null) {
                cancelled = true;
                return;
            }
            newName = newName.trim().toUpperCase();
            if (newName !== '' && newName !== oldName) {
                pairs.push({old: oldName, new: newName});
            }
        });
        if (cancelled || pairs.length === 0) return;
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
            if (!confirm('Supprimer {{ ' + varName + ' }} de tous les templates ?')) {
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
})();
</script>
