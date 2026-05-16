<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesDir = __DIR__ . '/../templates';
$outputDir = __DIR__ . '/../output';

$templates = TemplateAnalyzer::scanTemplates($templatesDir);
$analysis = null;
$exported = false;

if ($templates) {
    $analysis = TemplateAnalyzer::analyzeCoverage($templates);

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
                <th>Variable</th>
                <th>Occurrences</th>
                <th>Templates</th>
                <th>Section</th>
                <th>Couverture</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($analysis['variables'] as $v): ?>
                <tr>
                    <td><code><?= e($v['variable']) ?></code></td>
                    <td><?= e((string) $v['occurrences']) ?></td>
                    <td><?= e((string) $v['templates_count']) ?> template(s)</td>
                    <td><span class="pill"><?= e($v['section']) ?></span></td>
                    <td>
                        <span class="statut-badge <?= $v['coverage'] === 'couvert' ? 'actif' : 'resilie' ?>">
                            <?= e($v['coverage']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</section>
