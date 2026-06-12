<?php

declare(strict_types=1);

$cessionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cession = null;
$societe = null;
$cessionParts = [];
$documents = [];

if ($cessionId > 0 && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('
        SELECT c.*, s.societe_raison_sociale, s.societe_dossier AS ste_dossier,
               s.societe_forme_juridique, s.societe_ville, s.societe_capital, s.societe_part_social
        FROM cessions c
        LEFT JOIN societes s ON s.id = c.societe_id
        WHERE c.id = :id
    ');
    $stmt->execute(['id' => $cessionId]);
    $cession = $stmt->fetch();

    if ($cession) {
        $stmt = $pdo->prepare('SELECT * FROM cession_parts WHERE cession_id = :id ORDER BY id');
        $stmt->execute(['id' => $cessionId]);
        $cessionParts = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT id, doc_type, fichier_docx, fichier_pdf, taille_ko, valide, created_at FROM documents_generes WHERE societe_id = :sid AND template_source = 'cession' ORDER BY created_at DESC");
        $stmt->execute(['sid' => $cession['societe_id']]);
        $documents = $stmt->fetchAll();
    }
}

if (!$cession) {
    http_response_code(404);
    ?>
    <section class="card stack">
        <h2>Cession introuvable</h2>
        <p>Le dossier de cession demande n'existe pas ou n'est plus disponible.</p>
        <a class="btn" href="<?= e(app_url('cessions')) ?>">Retour aux cessions</a>
    </section>
    <?php
    return;
}

$docTypeLabels = [
    'Acte-Cession-Parts' => "Acte de cession de parts",
    'PV-AGE-Cession' => "PV d'assemblee generale cession",
    'Declaration-Modificative-RC' => "Declaration modificative RC",
    'Annonce-Legale-Cession' => "Annonce legale cession",
];
?>
<div class="section-title-row">
    <h2><?= e($cession['cession_dossier']) ?> — <?= e($cession['societe_raison_sociale'] ?? '-') ?></h2>
    <div class="table-actions">
        <a class="btn btn-info" href="<?= e(app_url('cession', ['id' => $cessionId, 'edit' => '1'])) ?>"><span class="material-symbols-outlined">edit</span> Modifier</a>
        <a class="btn btn-back" href="<?= e(app_url('cessions')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
    </div>
</div>

<section class="stats small stats-bottom-margin">
    <article class="stat">
        <span>Societe</span>
        <strong><?= e($cession['societe_raison_sociale'] ?? '-') ?></strong>
    </article>
    <article class="stat">
        <span>Statut</span>
        <strong><?= ($cession['cession_status'] ?? 'brouillon') === 'finalise' ? 'Finalise' : 'Brouillon' ?></strong>
    </article>
    <article class="stat">
        <span>Date</span>
        <strong><?= format_date($cession['cession_date'] ?? null) ?></strong>
    </article>
    <article class="stat">
        <span>Lignes</span>
        <strong><?= count($cessionParts) ?></strong>
    </article>
    <article class="stat">
        <span>Total parts</span>
        <strong><?= array_sum(array_map(fn($p) => (int) ($p['parts_cedees'] ?? 0), $cessionParts)) ?></strong>
    </article>
</section>

<article class="card stack">
    <div class="form-grid">
        <h3 class="section-title">Informations generales</h3>
        <div class="info-grid">
            <div><span>Dossier</span><strong><?= e($cession['cession_dossier'] ?? '-') ?></strong></div>
            <div><span>Societe</span><strong><?= e($cession['societe_raison_sociale'] ?? '-') ?></strong></div>
            <div><span>Forme juridique</span><strong><?= e($cession['societe_forme_juridique'] ?? '-') ?></strong></div>
            <div><span>Ville</span><strong><?= e($cession['societe_ville'] ?? '-') ?></strong></div>
            <div><span>Capital</span><strong><?= format_money($cession['societe_capital'] !== null ? (float) $cession['societe_capital'] : null) ?></strong></div>
            <div><span>Parts avant cession</span><strong><?= $cession['parts_avant'] ?? '-' ?></strong></div>
            <div><span>Capital avant cession</span><strong><?= format_money($cession['capital_avant'] !== null ? (float) $cession['capital_avant'] : null) ?></strong></div>
            <div><span>Date de cession</span><strong><?= format_date($cession['cession_date'] ?? null) ?></strong></div>
            <div class="full"><span>Motif</span><strong><?= e($cession['cession_motif'] ?: '-') ?></strong></div>
        </div>
    </div>
</article>

<article class="card">
    <div class="section-header">
        <h3>Lignes de cession (<?= count($cessionParts) ?>)</h3>
    </div>
    <?php if (!$cessionParts): ?>
        <p class="table-empty">Aucune ligne de cession.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="cedant">Cedant</th>
                        <th data-col="cedant-cin">CIN</th>
                        <th data-col="cessionnaire">Cessionnaire</th>
                        <th data-col="cessionnaire-cin">CIN</th>
                        <th data-col="parts">Parts cedees</th>
                        <th data-col="prix-u">Prix unitaire</th>
                        <th data-col="prix-t">Prix total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cessionParts as $p): ?>
                    <tr>
                        <td><?= e($p['cedant_nom_complet']) ?></td>
                        <td><?= e($p['cedant_cin'] ?: '-') ?></td>
                        <td><?= e($p['cessionnaire_nom_complet']) ?></td>
                        <td><?= e($p['cessionnaire_cin'] ?: '-') ?></td>
                        <td><?= (int) ($p['parts_cedees'] ?? 0) ?></td>
                        <td><?= format_money((float) ($p['prix_unitaire'] ?? 0)) ?></td>
                        <td><?= format_money((float) ($p['prix_total'] ?? 0)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:600">
                        <td colspan="4">Total</td>
                        <td><?= array_sum(array_map(fn($p) => (int) ($p['parts_cedees'] ?? 0), $cessionParts)) ?></td>
                        <td></td>
                        <td><?= format_money(array_sum(array_map(fn($p) => (float) ($p['prix_total'] ?? 0), $cessionParts))) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</article>

<article class="card">
    <div class="section-header">
        <h3>Documents generes (<?= count($documents) ?>)</h3>
    </div>
    <?php if (!$documents): ?>
        <p class="table-empty">Aucun document genere pour cette cession.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table data-sortable>
                <thead>
                    <tr>
                        <th data-col="type">Type</th>
                        <th data-col="fichier">Fichier</th>
                        <th data-col="taille">Taille</th>
                        <th data-col="statut">Statut</th>
                        <th data-col="date">Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?= e($docTypeLabels[$doc['doc_type']] ?? $doc['doc_type']) ?></td>
                        <td><?= e(basename((string) ($doc['fichier_docx'] ?? '-'))) ?></td>
                        <td><?= $doc['taille_ko'] ? number_format((float) $doc['taille_ko'], 1) . ' Ko' : '-' ?></td>
                        <td>
                            <span class="statut-badge <?= $doc['valide'] ? 'valide' : 'brouillon' ?>">
                                <?= $doc['valide'] ? 'Valide' : 'Brouillon' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime((string) $doc['created_at'])) ?></td>
                        <td>
                            <div class="table-actions">
                                <?php if ($doc['fichier_docx'] && file_exists($doc['fichier_docx'])): ?>
                                <a class="btn-icon success" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_docx'])) ?>" download title="Telecharger DOCX">
                                    <span class="material-symbols-outlined">download</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($doc['fichier_pdf'] && file_exists($doc['fichier_pdf'])): ?>
                                <a class="btn-icon danger" href="<?= e(str_replace(__DIR__ . '/../', '', $doc['fichier_pdf'])) ?>" download title="Telecharger PDF">
                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</article>
