<?php

declare(strict_types=1);

// ─── Labels selon le type de generation ───────────────────────────────────
$stepLabelsCreation = [
    'certificat_negatif' => 'Certificat negatif',
    'redaction_statuts'  => 'Redaction des statuts',
    'signature'          => 'Signature',
    'enregistrement'     => 'Enregistrement',
    'depot_greffe'       => 'Depot au greffe',
    'publication_jal_bo' => 'Publication JAL/BO',
    'rc'                 => 'Immatriculation RC',
    'remise'             => 'Remise de documents',
];

$stepLabelsDomiciliation = [
    'contrat_domiciliation' => 'Contrat de domiciliation',
    'redaction'             => 'Redaction des documents',
    'signature'             => 'Signature',
    'enregistrement'        => 'Enregistrement',
    'depot_greffe'          => 'Depot au greffe',
    'publication_jal'       => 'Publication JAL',
    'rc_modificatif'        => 'RC modificatif',
    'remise'                => 'Remise de documents',
];

$stepIcons = [
    'certificat_negatif' => 'verified',
    'contrat_domiciliation' => 'description',
    'redaction_statuts'  => 'edit_note',
    'redaction'          => 'edit_note',
    'signature'          => 'draw',
    'enregistrement'     => 'receipt_long',
    'legalisation'       => 'verified',
    'depot_greffe'       => 'folder',
    'publication_jal'    => 'newspaper',
    'publication_jal_bo' => 'newspaper',
    'publication_bo'     => 'campaign',
    'rc'                 => 'assignment',
    'rc_modificatif'     => 'assignment',
    'reglement'          => 'payments',
    'remise'             => 'move_up',
];

$documentSuggestions = [
    'certificat_negatif' => ['Certificat negatif OMPIC'],
    'contrat_domiciliation' => ['Contrat de domiciliation signe'],
    'redaction_statuts'  => ['Projet de statuts'],
    'redaction'          => ['Projet de documents'],
    'signature'          => ['Acte signe par les parties'],
    'enregistrement'     => ['Acte enregistre', 'Quittance de paiement'],
    'depot_greffe'       => ['Recipisse de depot', 'Certificat de depot'],
    'publication_jal'    => ['Attestation de parution'],
    'publication_jal_bo' => ['Attestation de parution JAL/BO'],
    'rc'                 => ['Nouvel extrait RC'],
    'rc_modificatif'     => ['Nouvel extrait RC'],
    'remise'             => ['Bordereau de remise', 'PV de remise'],
];

$statutBadges = [
    'en_attente' => 'brouillon',
    'en_cours'   => 'warning',
    'termine'    => 'valide',
];

$statutLabels = [
    'en_attente' => 'En attente',
    'en_cours'   => 'En cours',
    'termine'    => 'Termine',
];

// ─── Societe courante ─────────────────────────────────────────────────────
$societeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$societe = $societeId > 0 ? fetch_record($pdo ?? null, 'societes', $societeId) : null;
$etapes = [];
$documents = [];
$docsByEtape = [];

$isCreation = $societe && (string) ($societe['societe_type_generation'] ?? '') === 'creation';
$stepLabels = $isCreation ? $stepLabelsCreation : $stepLabelsDomiciliation;

if ($societe && ($pdo ?? null) instanceof PDO) {
    $stmt = $pdo->prepare('SELECT * FROM societe_suivi_etapes WHERE societe_id = :id ORDER BY ordre');
    $stmt->execute(['id' => $societeId]);
    $etapes = $stmt->fetchAll();

    $stmt = $pdo->prepare('
        SELECT d.*, e.etape
        FROM societe_suivi_documents d
        JOIN societe_suivi_etapes e ON e.id = d.etape_id
        WHERE e.societe_id = :id
        ORDER BY d.uploaded_at DESC
    ');
    $stmt->execute(['id' => $societeId]);
    $documents = $stmt->fetchAll();
    foreach ($documents as $d) {
        $docsByEtape[$d['etape_id']][] = $d;
    }
}

if (!$societe) {
    if ($societeId !== 0) {
        http_response_code(404);
        ?><section class="card stack">
            <h2>Societe introuvable</h2>
            <p>Le dossier demande n'existe pas.</p>
            <a class="btn" href="<?= e(app_url('creations')) ?>">Retour aux societes</a>
        </section><?php
        return;
    }

    // ─── Liste de suivi de toutes les societes ────────────────────────────
    $q = trim($_GET['q'] ?? '');
    $filterType = $_GET['type'] ?? '';
    $sql = 'SELECT s.*,
                   (SELECT COUNT(*) FROM societe_suivi_etapes WHERE societe_id = s.id) AS total_etapes,
                   (SELECT COUNT(*) FROM societe_suivi_etapes WHERE societe_id = s.id AND statut = \'termine\') AS termine_count,
                   (SELECT COUNT(*) FROM societe_suivi_etapes WHERE societe_id = s.id AND statut = \'en_cours\') AS en_cours_count
            FROM societes s';
    $conditions = [];
    $params = [];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $conditions[] = '(s.societe_raison_sociale LIKE :q1 OR s.societe_dossier_domiciliation_number LIKE :q2 OR s.societe_dossier_creation_number LIKE :q3 OR s.societe_ice LIKE :q4)';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
    }
    if (in_array($filterType, ['creation', 'domiciliation'], true)) {
        $conditions[] = 's.societe_type_generation = :type';
        $params['type'] = $filterType;
    }
    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY s.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allSocietes = $stmt->fetchAll();
    ?>

    <div class="section-title-row">
        <h2>Suivi administratif</h2>
        <div class="table-actions">
            <form method="get" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                <input type="hidden" name="page" value="societe_suivi">
                <select name="type" style="padding:5px 8px;border:1px solid var(--line);border-radius:4px;font-size:.85rem">
                    <option value="">Tous</option>
                    <option value="creation" <?= $filterType === 'creation' ? 'selected' : '' ?>>Creation</option>
                    <option value="domiciliation" <?= $filterType === 'domiciliation' ? 'selected' : '' ?>>Domiciliation</option>
                </select>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Rechercher..." style="padding:5px 10px;border:1px solid var(--line);border-radius:4px;font-size:.85rem">
                <button type="submit" class="btn" style="font-size:.8rem;padding:4px 10px"><span class="material-symbols-outlined">search</span></button>
            </form>
        </div>
    </div>

    <?php if (empty($allSocietes)): ?>
        <div class="empty-state">
            <span class="material-symbols-outlined">checklist</span>
            <p class="table-empty">Aucune societe trouvee.</p>
        </div>
    <?php else: ?>
    <div class="table-scroll">
        <table data-sortable>
            <thead>
            <tr>
                <th data-col="dossier">Dossier</th>
                <th data-col="societe">Societe</th>
                <th data-col="forme">Forme</th>
                <th data-col="type">Type</th>
                <th data-col="progression">Progression</th>
                <th data-col="statut">Statut</th>
                <th class="col-actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($allSocietes as $s):
                $total = (int) $s['total_etapes'];
                $termine = (int) $s['termine_count'];
                $enCours = (int) $s['en_cours_count'];
                $pct = $total > 0 ? round($termine / $total * 100) : 0;
                if ($termine === $total && $total > 0) {
                    $globalStatut = 'termine';
                } elseif ($enCours > 0) {
                    $globalStatut = 'en_cours';
                } else {
                    $globalStatut = 'en_attente';
                }
                $dossierNum = $s['societe_type_generation'] === 'creation'
                    ? ($s['societe_dossier_creation_number'] ?? '-')
                    : ($s['societe_dossier_domiciliation_number'] ?? '-');
                $typeLabel = $s['societe_type_generation'] === 'creation' ? 'Creation' : 'Domiciliation';
            ?>
            <tr>
                <td><?= e($dossierNum) ?></td>
                <td><a href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>" style="color:var(--primary);text-decoration:none;font-weight:500"><?= e($s['societe_raison_sociale'] ?? '-') ?></a></td>
                <td><?= e($s['societe_forme_juridique'] ?? '-') ?></td>
                <td><span class="statut-badge <?= $s['societe_type_generation'] === 'creation' ? 'valide' : 'warning' ?>"><?= $typeLabel ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        <div style="flex:1;height:6px;background:var(--line);border-radius:3px;overflow:hidden;min-width:60px">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct === 100 ? 'var(--success)' : ($pct > 0 ? 'var(--info)' : 'var(--line)') ?>;border-radius:3px"></div>
                        </div>
                        <small style="color:var(--text-muted);white-space:nowrap"><?= $termine ?>/<?= $total ?></small>
                    </div>
                </td>
                <td><span class="statut-badge <?= $statutBadges[$globalStatut] ?? 'brouillon' ?>"><?= $statutLabels[$globalStatut] ?? e($globalStatut) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a class="btn-icon primary" href="<?= e(app_url('societe_suivi', ['id' => (int) $s['id']])) ?>" title="Voir le suivi">
                            <span class="material-symbols-outlined">visibility</span>
                        </a>
                        <a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>" title="Fiche societe">
                            <span class="material-symbols-outlined">folder_open</span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php
    return;
}

// ─── POST handlers ────────────────────────────────────────────────────────
if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();

    // Update statut
    if (isset($_POST['update_statut'])) {
        $etapeId = (int) ($_POST['etape_id'] ?? 0);
        $newStatut = $_POST['statut'] ?? '';
        if ($etapeId > 0 && in_array($newStatut, ['en_attente', 'en_cours', 'termine'], true)) {
            $updates = ['statut = :statut'];
            $params = ['statut' => $newStatut, 'id' => $etapeId];
            if ($newStatut === 'en_cours') {
                $updates[] = 'date_debut = COALESCE(date_debut, CURDATE())';
            }
            if ($newStatut === 'termine') {
                $updates[] = 'date_fin = COALESCE(date_fin, CURDATE())';
            }
            $sql = 'UPDATE societe_suivi_etapes SET ' . implode(', ', $updates) . ' WHERE id = :id AND societe_id = :sid';
            $params['sid'] = $societeId;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            set_flash('success', 'Statut mis a jour.');
        }
        redirect_to('societe_suivi', ['id' => $societeId]);
    }

    // Update dates
    if (isset($_POST['update_dates'])) {
        $etapeId = (int) ($_POST['etape_id'] ?? 0);
        $dateDebut = $_POST['date_debut'] ?: null;
        $dateFin = $_POST['date_fin'] ?: null;
        if ($etapeId > 0) {
            $stmt = $pdo->prepare('UPDATE societe_suivi_etapes SET date_debut = :dd, date_fin = :df WHERE id = :id AND societe_id = :sid');
            $stmt->execute(['dd' => $dateDebut, 'df' => $dateFin, 'id' => $etapeId, 'sid' => $societeId]);
            set_flash('success', 'Dates mises a jour.');
        }
        redirect_to('societe_suivi', ['id' => $societeId]);
    }

    // Update notes
    if (isset($_POST['update_notes'])) {
        $etapeId = (int) ($_POST['etape_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($etapeId > 0) {
            $stmt = $pdo->prepare('UPDATE societe_suivi_etapes SET notes = :notes WHERE id = :id AND societe_id = :sid');
            $stmt->execute(['notes' => $notes ?: null, 'id' => $etapeId, 'sid' => $societeId]);
            set_flash('success', 'Notes mises a jour.');
        }
        redirect_to('societe_suivi', ['id' => $societeId]);
    }

    // Upload document
    if (isset($_POST['upload_document']) && isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $etapeId = (int) ($_POST['etape_id'] ?? 0);
        $nomDoc = trim($_POST['doc_nom'] ?? ($documentSuggestions[0] ?? 'Document'));
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $extension = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
        if ($etapeId > 0 && in_array($_FILES['doc_file']['type'], $allowedTypes, true)) {
            $uploadDir = __DIR__ . '/../../uploads/suivi/' . $societeId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = $etapeId . '_' . time() . '.' . $extension;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare('INSERT INTO societe_suivi_documents (etape_id, nom, fichier) VALUES (:eid, :nom, :fichier)');
                $stmt->execute(['eid' => $etapeId, 'nom' => $nomDoc, 'fichier' => 'uploads/suivi/' . $societeId . '/' . $filename]);
                set_flash('success', 'Document ajoute.');
            }
        } else {
            set_flash('error', 'Type de fichier non autorise. Formats acceptes: PDF, JPEG, PNG, DOC, DOCX.');
        }
        redirect_to('societe_suivi', ['id' => $societeId]);
    }

    // Delete document
    if (isset($_POST['delete_document'])) {
        $docId = (int) ($_POST['doc_id'] ?? 0);
        if ($docId > 0) {
            $stmt = $pdo->prepare('SELECT d.id, d.fichier, e.societe_id FROM societe_suivi_documents d JOIN societe_suivi_etapes e ON e.id = d.etape_id WHERE d.id = :id');
            $stmt->execute(['id' => $docId]);
            $doc = $stmt->fetch();
            if ($doc && (int) $doc['societe_id'] === $societeId) {
                $filePath = __DIR__ . '/../../' . $doc['fichier'];
                if (file_exists($filePath)) unlink($filePath);
                $stmt = $pdo->prepare('DELETE FROM societe_suivi_documents WHERE id = :id');
                $stmt->execute(['id' => $docId]);
                set_flash('success', 'Document supprime.');
            }
        }
        redirect_to('societe_suivi', ['id' => $societeId]);
    }
}

$progress = count($etapes) > 0 ? round(count(array_filter($etapes, fn($e) => $e['statut'] === 'termine')) / count($etapes) * 100) : 0;
$completed = count(array_filter($etapes, fn($e) => $e['statut'] === 'termine'));
$inProgress = count(array_filter($etapes, fn($e) => $e['statut'] === 'en_cours'));
$pending = count($etapes) - $completed - $inProgress;

// Detect overdue steps (started > 7 days ago, not finished)
$overdue = 0;
$today = new DateTime();
foreach ($etapes as $e) {
    if ($e['statut'] !== 'termine' && $e['date_debut']) {
        $started = new DateTime($e['date_debut']);
        if ($today->diff($started)->days > 7) {
            $overdue++;
        }
    }
}

// Find current step (first en_cours)
$currentStepId = null;
foreach ($etapes as $e) {
    if ($e['statut'] === 'en_cours') {
        $currentStepId = (int) $e['id'];
        break;
    }
}

$kanbanView = isset($_GET['view']) && $_GET['view'] === 'kanban';
?>

<div class="section-title-row">
    <h2>Suivi administratif — <?= e($societe['societe_raison_sociale'] ?? '-') ?></h2>
    <div class="table-actions">
        <div class="view-toggle">
            <button class="<?= !$kanbanView ? 'active' : '' ?>" onclick="location.href='<?= e(app_url('societe_suivi', ['id' => $societeId] + (!$kanbanView ? [] : ['view' => '']))) ?>'"><span class="material-symbols-outlined" style="font-size:1rem">view_list</span> Detail</button>
            <button class="<?= $kanbanView ? 'active' : '' ?>" onclick="location.href='<?= e(app_url('societe_suivi', ['id' => $societeId, 'view' => 'kanban'])) ?>'"><span class="material-symbols-outlined" style="font-size:1rem">view_kanban</span> Pipeline</button>
        </div>
        <a class="btn btn-info" href="<?= e(app_url('suivi_pdf', ['id' => $societeId])) ?>" target="_blank"><span class="material-symbols-outlined">picture_as_pdf</span> PDF</a>
        <a class="btn btn-info" href="<?= e(app_url('societe', ['id' => $societeId])) ?>"><span class="material-symbols-outlined">info</span> Fiche</a>
        <a class="btn btn-back" href="<?= e(app_url($isCreation ? 'creations' : 'domiciliations')) ?>"><span class="material-symbols-outlined">arrow_back</span> Retour</a>
    </div>
</div>

<section class="stats small stats-bottom-margin">
    <article class="stat">
        <span>Progression</span>
        <strong><?= $completed ?>/<?= count($etapes) ?></strong>
    </article>
    <article class="stat">
        <span>En cours</span>
        <strong><?= $inProgress ?></strong>
    </article>
    <article class="stat">
        <span>Termine</span>
        <strong><?= $completed ?></strong>
    </article>
    <?php if ($overdue > 0): ?>
    <article class="stat" style="border-left:3px solid var(--danger)">
        <span style="color:var(--danger)">En retard</span>
        <strong style="color:var(--danger)"><?= $overdue ?></strong>
    </article>
    <?php endif; ?>
    <article class="stat">
        <span>Type</span>
        <strong><span class="statut-badge <?= $isCreation ? 'valide' : 'warning' ?>"><?= $isCreation ? 'Creation' : 'Domiciliation' ?></span></strong>
    </article>
</section>

<div class="progress-bar" style="height:6px;background:var(--line);border-radius:3px;margin-bottom:1.5rem;overflow:hidden">
    <div style="height:100%;width:<?= $progress ?>%;background:<?= $progress === 100 ? 'var(--success)' : ($overdue > 0 ? 'var(--danger)' : 'var(--primary)') ?>;border-radius:3px;transition:width .3s ease"></div>
</div>

<?php if (!$etapes): ?>
    <section class="card stack">
        <h3>Aucune etape de suivi</h3>
        <p class="table-empty">Aucune etape de suivi n'est definie pour cette societe.</p>
    </section>
<?php elseif ($kanbanView): ?>
    <!-- ─── Vue Pipeline Kanban ──────────────────────────────────── -->
    <?php
    $grouped = ['en_attente' => [], 'en_cours' => [], 'termine' => []];
    foreach ($etapes as $e) {
        $grouped[$e['statut']][] = $e;
    }
    ?>
    <div class="suivi-kanban" id="kanban-board">
        <?php foreach (['en_attente', 'en_cours', 'termine'] as $colStatut):
            $colEtapes = $grouped[$colStatut];
            $colLabel = $statutLabels[$colStatut];
        ?>
        <div class="kanban-col" data-statut="<?= $colStatut ?>">
            <div class="kanban-col-header <?= $colStatut ?>">
                <span><?= $colLabel ?></span>
                <span class="badge"><?= count($colEtapes) ?></span>
            </div>
            <?php foreach ($colEtapes as $e):
                $eid = (int) $e['id'];
                $total = count($etapes);
                $termine = count(array_filter($etapes, fn($x) => $x['statut'] === 'termine'));
                $pct = $total > 0 ? round($termine / $total * 100) : 0;
                $isOverdue = $e['statut'] !== 'termine' && $e['date_debut'] && (new DateTime())->diff(new DateTime($e['date_debut']))->days > 7;
            ?>
            <div class="kanban-card" draggable="true" data-etape-id="<?= $eid ?>" data-statut="<?= $colStatut ?>">
                <div class="kanban-card-title">
                    <a href="<?= e(app_url('societe_suivi', ['id' => $societeId, 'open' => $eid])) ?>"><?= $stepLabels[$e['etape']] ?? e($e['etape']) ?></a>
                </div>
                <div class="kanban-card-meta">
                    <span class="material-symbols-outlined" style="font-size:.9rem"><?= e($stepIcons[$e['etape']] ?? 'help') ?></span>
                    <?php if ($isOverdue): ?>
                        <span class="statut-badge retard">En retard</span>
                    <?php endif; ?>
                    <?php if ($e['date_fin']): ?>
                        <span>Fin: <?= format_date($e['date_fin']) ?></span>
                    <?php endif; ?>
                    <?php $docCount = count($docsByEtape[$eid] ?? []); if ($docCount > 0): ?>
                        <span class="material-symbols-outlined" style="font-size:.85rem">description</span> <?= $docCount ?>
                    <?php endif; ?>
                </div>
                <div class="kanban-card-progress">
                    <div class="kanban-card-progress-fill" style="width:<?= $e['statut'] === 'termine' ? 100 : ($e['statut'] === 'en_cours' ? 50 : 0) ?>%;background:<?= $e['statut'] === 'termine' ? 'var(--success)' : ($e['statut'] === 'en_cours' ? 'var(--warning)' : 'var(--line)') ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($colEtapes)): ?>
                <p style="font-size:.78rem;color:var(--text-muted);text-align:center;padding:1rem 0">Aucune etape</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    (function() {
        var board = document.getElementById('kanban-board');
        if (!board) return;
        var dragged = null;

        board.addEventListener('dragstart', function(e) {
            var card = e.target.closest('.kanban-card');
            if (!card) return;
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.etapeId);
        });

        board.addEventListener('dragend', function(e) {
            if (dragged) dragged.classList.remove('dragging');
            dragged = null;
            board.querySelectorAll('.kanban-col').forEach(function(c) { c.classList.remove('drag-over'); });
        });

        board.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var col = e.target.closest('.kanban-col');
            if (col) col.classList.add('drag-over');
        });

        board.addEventListener('dragleave', function(e) {
            var col = e.target.closest('.kanban-col');
            if (col && !col.contains(e.relatedTarget)) col.classList.remove('drag-over');
        });

        board.addEventListener('drop', function(e) {
            e.preventDefault();
            var col = e.target.closest('.kanban-col');
            if (!col || !dragged) return;
            col.classList.remove('drag-over');
            var newStatut = col.dataset.statut;
            var etapeId = dragged.dataset.etapeId;
            if (newStatut === dragged.dataset.statut) return;

            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<?= csrf_input() ?>' +
                '<input type="hidden" name="etape_id" value="' + etapeId + '">' +
                '<input type="hidden" name="update_statut" value="1">' +
                '<input type="hidden" name="statut" value="' + newStatut + '">';
            document.body.appendChild(form);
            form.submit();
        });
    })();
    </script>

<?php else: ?>
    <!-- ─── Vue Detail avec Stepper ──────────────────────────────── -->
    <div class="suivi-layout">
        <nav class="suivi-stepper">
            <div class="suivi-stepper-title">Etapes</div>
            <?php foreach ($etapes as $e): $eid = (int) $e['id']; ?>
            <a class="suivi-step" href="#etape-<?= $eid ?>" onclick="openStep(<?= $eid ?>)">
                <div class="suivi-step-dot <?= $e['statut'] === 'termine' ? 'success' : ($e['statut'] === 'en_cours' ? 'warning' : '') ?>">
                    <?= match ($e['statut']) {
                        'termine' => '<span class="material-symbols-outlined">check</span>',
                        'en_cours' => '<span class="material-symbols-outlined">play_arrow</span>',
                        default => ($stepLabels[$e['etape']] ?? $e['etape'])[0],
                    } ?>
                    <span class="suivi-step-line"></span>
                </div>
                <div class="suivi-step-info">
                    <div class="step-label <?= $currentStepId === $eid ? 'active' : '' ?>"><?= $stepLabels[$e['etape']] ?? e($e['etape']) ?></div>
                    <div class="step-meta">
                        <?php if ($e['date_fin']): ?>
                            <?= format_date($e['date_fin']) ?>
                        <?php elseif ($e['date_debut']): ?>
                            Depuis <?= format_date($e['date_debut']) ?>
                        <?php else: ?>
                            <?= $statutLabels[$e['statut']] ?>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="suivi-content">
            <section class="timeline" style="display:flex;flex-direction:column;gap:.75rem">
                <?php foreach ($etapes as $e): $eid = (int) $e['id'];
                    $isOverdue = $e['statut'] !== 'termine' && $e['date_debut'] && (new DateTime())->diff(new DateTime($e['date_debut']))->days > 7;
                    $docCount = count($docsByEtape[$eid] ?? []);
                ?>
                <article class="card" id="etape-<?= $eid ?>" data-statut="<?= e($e['statut']) ?>">
                    <div class="timeline-header" style="display:flex;align-items:center;gap:.75rem;cursor:pointer" onclick="toggleStep(<?= $eid ?>)">
                        <span class="material-symbols-outlined" style="font-size:1.5rem;color:<?= $e['statut'] === 'termine' ? 'var(--success)' : ($e['statut'] === 'en_cours' ? 'var(--warning)' : 'var(--text-muted)') ?>">
                            <?= match ($e['statut']) {
                                'termine' => 'check_circle',
                                'en_cours' => 'radio_button_checked',
                                default => 'radio_button_unchecked',
                            } ?>
                        </span>
                        <span class="material-symbols-outlined" style="font-size:1.3rem;color:var(--text-muted)"><?= e($stepIcons[$e['etape']] ?? 'help') ?></span>
                        <strong style="flex:1"><?= $stepLabels[$e['etape']] ?? e($e['etape']) ?></strong>
                        <?php if ($isOverdue): ?>
                            <span class="statut-badge retard">En retard</span>
                        <?php endif; ?>
                        <span class="statut-badge <?= $statutBadges[$e['statut']] ?? 'brouillon' ?>"><?= $statutLabels[$e['statut']] ?? e($e['statut']) ?></span>
                        <?php if ($docCount > 0): ?>
                            <span style="display:inline-flex;align-items:center;gap:2px;font-size:.75rem;color:var(--text-muted)"><span class="material-symbols-outlined" style="font-size:.9rem">description</span><?= $docCount ?></span>
                        <?php endif; ?>
                        <?php if ($e['date_fin']): ?>
                        <small style="color:var(--text-muted)"><?= format_date($e['date_fin']) ?></small>
                        <?php endif; ?>
                        <span class="material-symbols-outlined toggle-icon" style="transition:transform .2s;color:var(--text-muted)" data-target="step-detail-<?= $eid ?>">expand_more</span>
                    </div>

                    <div id="step-detail-<?= $eid ?>" class="step-detail" style="display:none;margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--line)">
                        <!-- Quick statut change -->
                        <div class="form-inline" style="display:flex;gap:.5rem;align-items:center;margin-bottom:.75rem;flex-wrap:wrap">
                            <span style="font-size:.85rem;color:var(--text-muted)">Statut :</span>
                            <form method="post" style="display:inline-flex;gap:.25rem">
                                <?= csrf_input() ?>
                                <input type="hidden" name="etape_id" value="<?= $eid ?>">
                                <input type="hidden" name="update_statut" value="1">
                                <?php foreach (['en_attente', 'en_cours', 'termine'] as $s): ?>
                                <button type="submit" name="statut" value="<?= $s ?>" class="btn <?= $e['statut'] === $s ? 'btn-next' : '' ?>" style="font-size:.8rem;padding:3px 10px"><?= $statutLabels[$s] ?></button>
                                <?php endforeach; ?>
                            </form>
                        </div>

                        <!-- Dates -->
                        <form method="post" style="display:flex;gap:.75rem;align-items:center;margin-bottom:.75rem;flex-wrap:wrap" class="form-inline">
                            <?= csrf_input() ?>
                            <input type="hidden" name="etape_id" value="<?= $eid ?>">
                            <input type="hidden" name="update_dates" value="1">
                            <label style="font-size:.85rem;color:var(--text-muted)">Debut :
                                <input type="date" name="date_debut" value="<?= e($e['date_debut'] ?? '') ?>" style="padding:3px 8px;border:1px solid var(--line);border-radius:4px;font-size:.85rem">
                            </label>
                            <label style="font-size:.85rem;color:var(--text-muted)">Fin :
                                <input type="date" name="date_fin" value="<?= e($e['date_fin'] ?? '') ?>" style="padding:3px 8px;border:1px solid var(--line);border-radius:4px;font-size:.85rem">
                            </label>
                            <button type="submit" class="btn" style="font-size:.8rem;padding:3px 10px"><span class="material-symbols-outlined">calendar_month</span> Dates</button>
                        </form>

                        <!-- Notes -->
                        <form method="post" style="margin-bottom:.75rem">
                            <?= csrf_input() ?>
                            <input type="hidden" name="etape_id" value="<?= $eid ?>">
                            <input type="hidden" name="update_notes" value="1">
                            <textarea name="notes" rows="2" placeholder="Notes..." style="width:100%;padding:6px 10px;border:1px solid var(--line);border-radius:4px;font-size:.85rem;resize:vertical"><?= e($e['notes'] ?? '') ?></textarea>
                            <button type="submit" class="btn" style="margin-top:.25rem;font-size:.8rem;padding:3px 10px"><span class="material-symbols-outlined">note</span> Enregistrer</button>
                        </form>

                        <!-- Documents -->
                        <div style="margin-bottom:.5rem">
                            <strong style="font-size:.85rem">Documents :</strong>
                            <?php if (!empty($docsByEtape[$eid])): ?>
                            <ul style="list-style:none;padding:0;margin:.5rem 0">
                                <?php foreach ($docsByEtape[$eid] as $doc): ?>
                                <li style="display:flex;align-items:center;gap:.5rem;padding:.25rem 0;font-size:.85rem">
                                    <span class="material-symbols-outlined" style="font-size:1.1rem;color:var(--primary)">description</span>
                                    <span><?= e($doc['nom']) ?></span>
                                    <a href="<?= e(word_url($doc['fichier'])) ?>" class="btn-icon primary" title="Telecharger" target="_blank">
                                        <span class="material-symbols-outlined">download</span>
                                    </a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce document ?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="delete_document" value="1">
                                        <input type="hidden" name="doc_id" value="<?= (int) $doc['id'] ?>">
                                        <button type="submit" class="btn-icon danger" title="Supprimer">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                    <small style="color:var(--text-muted)"><?= format_date($doc['uploaded_at']) ?></small>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p style="font-size:.85rem;color:var(--text-muted);margin:.25rem 0">Aucun document.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Upload document -->
                        <form method="post" enctype="multipart/form-data" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;padding:.5rem;background:var(--bg);border-radius:4px">
                            <?= csrf_input() ?>
                            <input type="hidden" name="etape_id" value="<?= $eid ?>">
                            <input type="hidden" name="upload_document" value="1">
                            <input type="text" name="doc_nom" placeholder="Nom du document" list="doc-suggest-<?= $eid ?>" style="padding:4px 8px;border:1px solid var(--line);border-radius:4px;font-size:.85rem;flex:1;min-width:140px" value="<?= e(($documentSuggestions[$e['etape']] ?? [null])[0]) ?>">
                            <datalist id="doc-suggest-<?= $eid ?>">
                                <?php foreach ($documentSuggestions[$e['etape']] ?? [] as $s): ?>
                                <option value="<?= e($s) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <input type="file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required style="font-size:.85rem;max-width:160px">
                            <button type="submit" class="btn btn-next" style="font-size:.8rem;padding:4px 12px"><span class="material-symbols-outlined">upload</span> Ajouter</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleStep(id) {
    var detail = document.getElementById('step-detail-' + id);
    var icon = document.querySelector('#etape-' + id + ' .toggle-icon');
    if (!detail || !icon) return;
    var isOpen = detail.style.display !== 'none';
    detail.style.display = isOpen ? 'none' : 'block';
    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
function openStep(id) {
    var detail = document.getElementById('step-detail-' + id);
    var icon = document.querySelector('#etape-' + id + ' .toggle-icon');
    if (detail) { detail.style.display = 'block'; }
    if (icon) { icon.style.transform = 'rotate(180deg)'; }
}
<?php if (isset($_GET['open'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    var id = <?= json_encode((string) $_GET['open']) ?>;
    var el = document.getElementById('step-detail-' + id);
    if (el) { el.style.display = 'block';
        var icon = document.querySelector('#etape-' + id + ' .toggle-icon');
        if (icon) icon.style.transform = 'rotate(180deg)';
        el.closest('.card')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
});
<?php elseif ($currentStepId): ?>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('etape-<?= $currentStepId ?>');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
<?php endif; ?>
</script>
