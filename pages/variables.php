<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesDir = __DIR__ . '/../templates';
$templates = TemplateAnalyzer::scanTemplates($templatesDir);
$contextKeys = TemplateAnalyzer::getExpectedContextKeys();
$contextKeySet = array_flip(array_map('strtoupper', $contextKeys));

$allVariables = [];
$variableTemplates = [];
$variableOccurrences = [];

foreach ($templates as $tpl) {
    $vars = $tpl['variables'] ?? [];
    foreach ($vars as $var) {
        $upper = strtoupper($var);
        $allVariables[$upper] = $var;
        $variableOccurrences[$upper] = ($variableOccurrences[$upper] ?? 0) + 1;
        if (!isset($variableTemplates[$upper])) {
            $variableTemplates[$upper] = [];
        }
        $variableTemplates[$upper][] = $tpl['path'];
    }
}

ksort($allVariables);

$mappedCount = 0;
$unmappedCount = 0;
foreach ($allVariables as $upper => $original) {
    if (isset($contextKeySet[$upper])) {
        $mappedCount++;
    } else {
        $unmappedCount++;
    }
}
$totalCount = count($allVariables);

if (is_post() && isset($_POST['apply_mapping'])) {
    verify_csrf();
    $selected = $_POST['selected_vars'] ?? [];
    $targets = $_POST['target_names'] ?? [];
    $totalRenamed = 0;
    $errors = [];

    foreach ($selected as $oldUpper) {
        $oldUpper = strtoupper(trim($oldUpper));
        $newName = trim($targets[$oldUpper] ?? '');
        if ($oldUpper === '' || $newName === '' || $oldUpper === strtoupper($newName)) {
            continue;
        }
        $result = TemplateAnalyzer::renameVariable($oldUpper, $newName, $templatesDir);
        $totalRenamed += $result['modified'];
        if (!empty($result['errors'])) {
            $errors = array_merge($errors, $result['errors']);
        }
    }

    $count = count($selected);
    $msg = "{$count} variable(s) traitee(s) dans {$totalRenamed} template(s).";
    if (!empty($errors)) {
        $msg .= ' Erreurs: ' . implode('; ', array_unique($errors));
    }
    set_flash('success', $msg);
    redirect_to('variables');
}

$filter = $_GET['filter'] ?? 'all';
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <p class="help-text">Mapper les variables des templates vers les variables de l'application</p>
        </div>
        <div class="table-actions">
            <button type="button" id="apply-btn" class="btn btn-next" disabled><span class="mdi mdi-check-all"></span> Appliquer la selection</button>
        </div>
    </div>

    <div class="stats compact">
        <article class="stat">
            <span>Variables trouvees</span>
            <strong><?= $totalCount ?></strong>
        </article>
        <article class="stat">
            <span>Mappees</span>
            <strong style="color:var(--success)"><?= $mappedCount ?></strong>
        </article>
        <article class="stat">
            <span>Non mappees</span>
            <strong style="color:var(--danger)"><?= $unmappedCount ?></strong>
        </article>
        <article class="stat">
            <span>Templates analyses</span>
            <strong><?= count($templates) ?></strong>
        </article>
    </div>

    <div class="filter-bar" style="display:flex;gap:8px;align-items:center;padding:8px 0;flex-wrap:wrap">
        <a class="btn <?= $filter === 'all' ? 'btn-next' : '' ?>" href="?page=variables&filter=all">Tous</a>
        <a class="btn <?= $filter === 'unmapped' ? 'btn-next' : '' ?>" href="?page=variables&filter=unmapped">Non mappes <span class="badge" style="background:var(--danger);color:#fff;padding:1px 8px;border-radius:10px;font-size:0.7rem"><?= $unmappedCount ?></span></a>
        <a class="btn <?= $filter === 'mapped' ? 'btn-next' : '' ?>" href="?page=variables&filter=mapped">Mappes <span class="badge" style="background:var(--success);color:#fff;padding:1px 8px;border-radius:10px;font-size:0.7rem"><?= $mappedCount ?></span></a>
        <input type="text" id="var-search" placeholder="Rechercher une variable..." style="margin-left:auto;padding:6px 10px;background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-sm);color:var(--text);font-size:0.8rem;min-width:200px">
    </div>

    <?php if (!$totalCount): ?>
        <p class="table-empty">Aucune variable trouvee. Ajoutez des templates sur la page <a href="<?= e(app_url('templates')) ?>">Templates</a>.</p>
    <?php else: ?>
    <form method="post" id="mapping-form">
        <?= csrf_input() ?>
        <input type="hidden" name="apply_mapping" value="1">
        <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width:32px"><input type="checkbox" id="select-all" title="Tout cocher"></th>
                    <th>Variable dans les templates</th>
                    <th>Occurrences</th>
                    <th>Templates</th>
                    <th>Mapper vers</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allVariables as $upper => $original): ?>
                    <?php
                    $isMapped = isset($contextKeySet[$upper]);
                    if ($filter === 'mapped' && !$isMapped) continue;
                    if ($filter === 'unmapped' && $isMapped) continue;
                    $tplPaths = $variableTemplates[$upper] ?? [];
                    $tplCount = count($tplPaths);
                    $tplNames = array_map('basename', $tplPaths);
                    $firstTpl = $tplPaths[0] ?? null;
                    ?>
                    <tr class="<?= $isMapped ? 'row-mapped' : 'row-unmapped' ?>">
                        <td><input type="checkbox" class="var-checkbox" value="<?= e($upper) ?>" <?= $isMapped ? 'disabled' : '' ?>></td>
                        <td><code style="color:var(--primary)">{{ <?= e($original) ?> }}</code></td>
                        <td><?= $variableOccurrences[$upper] ?></td>
                        <td title="<?= e(implode(', ', $tplNames)) ?>" style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap;max-width:200px;overflow:hidden;text-overflow:ellipsis"><?= $tplCount ?> template(s)</td>
                        <td>
                            <select name="target_names[<?= e($upper) ?>]" style="max-width:180px;font-size:0.78rem;padding:2px 4px" <?= $isMapped ? 'disabled' : '' ?>>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($contextKeys as $ck): ?>
                                <option value="<?= e($ck) ?>" <?= $isMapped && $upper === strtoupper($ck) ? 'selected' : '' ?>><?= e($ck) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <?php if ($isMapped): ?>
                                <span class="statut-badge actif">Mappee</span>
                            <?php else: ?>
                                <span class="statut-badge resilie">Non mappee</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($firstTpl): ?>
                            <div class="action-links">
                                <a class="btn-icon" href="<?= e(app_url('template', ['path' => $firstTpl])) ?>" title="Voir le template"><span class="mdi mdi-eye"></span></a>
                                <?php if ($tplCount > 1): ?>
                                <div class="action-more">
                                    <button type="button" class="btn-icon toggle-dropdown" title="Tous les templates">
                                        <span class="mdi mdi-dots-horizontal"></span>
                                    </button>
                                    <div class="action-dropdown hidden">
                                        <?php foreach ($tplPaths as $tplPath): ?>
                                        <a href="<?= e(app_url('template', ['path' => $tplPath])) ?>" class="action-link"><?= e(basename($tplPath)) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </form>
    <?php endif; ?>
</section>

<script>
(function(){
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.toggle-dropdown');
        document.querySelectorAll('.action-dropdown').forEach(function(d){
            if (!btn || !btn.closest('.action-more').contains(d)) {
                d.classList.add('hidden');
            }
        });
        if (btn) {
            var dd = btn.closest('.action-more').querySelector('.action-dropdown');
            if (dd) dd.classList.toggle('hidden');
        }
    });

    var searchInput = document.getElementById('var-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(){
            var q = this.value.toLowerCase();
            document.querySelectorAll('#mapping-form tbody tr').forEach(function(row){
                var code = row.querySelector('code');
                if (!code) return;
                row.style.display = code.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    var selectAll = document.getElementById('select-all');
    var checkboxes = document.querySelectorAll('.var-checkbox:not([disabled])');
    var applyBtn = document.getElementById('apply-btn');

    function updateApplyBtn() {
        var checked = document.querySelectorAll('.var-checkbox:not([disabled]):checked');
        applyBtn.disabled = checked.length === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function(){
            checkboxes.forEach(function(cb){
                cb.checked = selectAll.checked;
            });
            updateApplyBtn();
        });
    }

    checkboxes.forEach(function(cb){
        cb.addEventListener('change', updateApplyBtn);
    });

    applyBtn.addEventListener('click', function(){
        var checked = document.querySelectorAll('.var-checkbox:not([disabled]):checked');
        if (checked.length === 0) {
            alert('Selectionnez au moins une variable non mappee.');
            return;
        }
        var hasEmpty = false;
        checked.forEach(function(cb){
            var select = cb.closest('tr').querySelector('select[name^="target_names"]');
            if (select && select.value === '') {
                hasEmpty = true;
            }
        });
        if (hasEmpty) {
            if (!confirm('Certaines variables n\'ont pas de destination choisie. Les variables sans destination seront ignorees. Continuer ?')) {
                return;
            }
        }
        document.getElementById('mapping-form').submit();
    });
})();
</script>


