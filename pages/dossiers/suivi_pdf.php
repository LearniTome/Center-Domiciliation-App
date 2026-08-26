<?php

declare(strict_types=1);

$societeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($societeId <= 0 || !($pdo ?? null) instanceof PDO) {
    http_response_code(404);
    echo 'Dossier introuvable.';
    return;
}

$societe = fetch_record($pdo, 'societes', $societeId);
if (!$societe) {
    http_response_code(404);
    echo 'Societe introuvable.';
    return;
}

$isCreation = (string) ($societe['societe_type_generation'] ?? '') === 'creation';

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

$stepLabels = $isCreation ? $stepLabelsCreation : $stepLabelsDomiciliation;
$stepOrdres = array_flip(array_keys($stepLabels));

$statutLabels = [
    'en_attente' => 'En attente',
    'en_cours'   => 'En cours',
    'termine'    => 'Termine',
];

$statutColors = [
    'en_attente' => '#a0a0af',
    'en_cours'   => '#ffab00',
    'termine'    => '#00b894',
];

// Fetch steps
$stmt = $pdo->prepare('SELECT * FROM societe_suivi_etapes WHERE societe_id = :id ORDER BY ordre');
$stmt->execute(['id' => $societeId]);
$etapes = $stmt->fetchAll();

// Fetch documents
$stmt = $pdo->prepare('
    SELECT d.*, e.etape, e.ordre
    FROM societe_suivi_documents d
    JOIN societe_suivi_etapes e ON e.id = d.etape_id
    WHERE e.societe_id = :id
    ORDER BY e.ordre, d.uploaded_at
');
$stmt->execute(['id' => $societeId]);
$documents = $stmt->fetchAll();
$docsByEtape = [];
foreach ($documents as $d) {
    $docsByEtape[$d['etape_id']][] = $d;
}

// Compute progress
$total = count($etapes);
$termine = count(array_filter($etapes, fn($e) => $e['statut'] === 'termine'));
$enCours = count(array_filter($etapes, fn($e) => $e['statut'] === 'en_cours'));
$pct = $total > 0 ? round($termine / $total * 100) : 0;

// Find next steps
$nextSteps = [];
$foundCurrent = false;
foreach ($etapes as $e) {
    if ($e['statut'] === 'termine') continue;
    if ($e['statut'] === 'en_cours') {
        $foundCurrent = true;
    }
    $nextSteps[] = $e;
    if (count($nextSteps) >= 3) break;
}

// Estimated delays per step
$delaisEstimes = [
    'certificat_negatif'  => '1-2 jours',
    'contrat_domiciliation' => '1-2 jours',
    'redaction_statuts'   => '1-3 jours',
    'redaction'           => '1-3 jours',
    'signature'           => '1 jour',
    'enregistrement'      => '1 jour',
    'depot_greffe'        => '1-2 jours',
    'publication_jal_bo'  => '15-30 jours',
    'publication_jal'     => '15-30 jours',
    'rc'                  => '5-10 jours',
    'rc_modificatif'      => '5-10 jours',
    'remise'              => '1 jour',
];

// Collaborator info
$collaborateur = null;
if (!empty($societe['societe_collaborateur_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM collaborateurs WHERE id = :id');
    $stmt->execute(['id' => $societe['societe_collaborateur_id']]);
    $collaborateur = $stmt->fetch();
}

// Build HTML
$today = (new DateTime())->format('d/m/Y');
$raisonSociale = $societe['societe_raison_sociale'] ?? '-';
$dossierNum = $isCreation
    ? ($societe['societe_dossier_creation_number'] ?? '-')
    : ($societe['societe_dossier_domiciliation_number'] ?? '-');
$formeJuridique = $societe['societe_forme_juridique'] ?? '-';
$typeLabel = $isCreation ? 'Creation' : 'Domiciliation';
$collabNom = $collaborateur ? trim(($collaborateur['collaborateur_prenom'] ?? '') . ' ' . ($collaborateur['collaborateur_nom'] ?? '')) : 'Non attribue';

// Calculate dates
$dateCreation = !empty($societe['societe_date_creation'])
    ? (new DateTime($societe['societe_date_creation']))->format('d/m/Y')
    : '-';

// Next steps list
$nextStepsHtml = '';
foreach ($nextSteps as $ns) {
    $label = $stepLabels[$ns['etape']] ?? $ns['etape'];
    $delai = $delaisEstimes[$ns['etape']] ?? '-';
    $nextStepsHtml .= '<li><strong>' . htmlspecialchars($label) . '</strong> — delai estime : ' . htmlspecialchars($delai) . '</li>';
}
if (empty($nextSteps)) {
    $nextStepsHtml = '<li>Toutes les etapes sont terminees.</li>';
}

// Steps table rows
$stepsRows = '';
foreach ($etapes as $e) {
    $label = $stepLabels[$e['etape']] ?? $e['etape'];
    $statutLabel = $statutLabels[$e['statut']] ?? $e['statut'];
    $statutColor = $statutColors[$e['statut']] ?? '#a0a0af';
    $dateDebut = $e['date_debut'] ? (new DateTime($e['date_debut']))->format('d/m/Y') : '-';
    $dateFin = $e['date_fin'] ? (new DateTime($e['date_fin']))->format('d/m/Y') : '-';
    $delai = $delaisEstimes[$e['etape']] ?? '-';
    $notes = $e['notes'] ? nl2br(htmlspecialchars($e['notes'])) : '-';
    $eid = (int) $e['id'];
    $docList = '';
    if (!empty($docsByEtape[$eid])) {
        foreach ($docsByEtape[$eid] as $d) {
            $docList .= '<div style="margin:1px 0;font-size:9px">- ' . htmlspecialchars($d['nom']) . ' <span style="color:#888">(' . (new DateTime($d['uploaded_at']))->format('d/m/Y') . ')</span></div>';
        }
    } else {
        $docList = '<span style="color:#888">Aucun</span>';
    }

    $stepsRows .= '
    <tr>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px"><strong>' . htmlspecialchars($label) . '</strong></td>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px">
            <span style="display:inline-block;padding:1px 8px;border-radius:3px;font-size:9px;font-weight:600;color:#fff;background:' . $statutColor . '">' . htmlspecialchars($statutLabel) . '</span>
        </td>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px">' . $dateDebut . '</td>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px">' . $dateFin . '</td>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px">' . $delai . '</td>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px">' . $notes . '</td>
        <td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:10px">' . $docList . '</td>
    </tr>';
}

$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
    h1 { font-size: 16px; color: #1a1a2e; margin: 0 0 4px; }
    h2 { font-size: 12px; color: #555; margin: 0 0 12px; font-weight: normal; }
    h3 { font-size: 12px; color: #1a1a2e; margin: 16px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; margin-bottom: 16px; }
    .header-left { flex: 1; }
    .header-right { text-align: right; font-size: 10px; color: #888; }
    .info-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
    .info-box { flex: 1; min-width: 200px; background: #f8f9fa; padding: 8px 12px; border-radius: 4px; }
    .info-box strong { display: block; font-size: 9px; color: #888; text-transform: uppercase; margin-bottom: 2px; }
    .info-box span { font-size: 11px; }
    .progress-container { background: #eee; border-radius: 4px; height: 8px; margin: 8px 0; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; background: ' . ($pct === 100 ? '#00b894' : '#4a6cf7') . '; }
    table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    th { background: #f0f0f5; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; color: #666; border-bottom: 2px solid #ddd; }
    .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #888; display: flex; justify-content: space-between; }
    ul { margin: 4px 0; padding-left: 16px; }
    li { margin: 2px 0; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1>Suivi administratif — ' . htmlspecialchars($raisonSociale) . '</h1>
        <h2>Dossier ' . htmlspecialchars($dossierNum) . ' | ' . htmlspecialchars($typeLabel) . ' | ' . htmlspecialchars($formeJuridique) . '</h2>
    </div>
    <div class="header-right">
        Genere le ' . $today . '<br>
        Centre Domiciliation
    </div>
</div>

<div class="info-grid">
    <div class="info-box">
        <strong>Societe</strong>
        <span>' . htmlspecialchars($raisonSociale) . '</span>
    </div>
    <div class="info-box">
        <strong>Numero de dossier</strong>
        <span>' . htmlspecialchars($dossierNum) . '</span>
    </div>
    <div class="info-box">
        <strong>Forme juridique</strong>
        <span>' . htmlspecialchars($formeJuridique) . '</span>
    </div>
    <div class="info-box">
        <strong>Type</strong>
        <span>' . htmlspecialchars($typeLabel) . '</span>
    </div>
    <div class="info-box">
        <strong>Collaborateur</strong>
        <span>' . htmlspecialchars($collabNom) . '</span>
    </div>
    <div class="info-box">
        <strong>Date de creation</strong>
        <span>' . $dateCreation . '</span>
    </div>
    <div class="info-box">
        <strong>Progression</strong>
        <span>' . $termine . '/' . $total . ' (' . $pct . '%)</span>
        <div class="progress-container"><div class="progress-fill" style="width:' . $pct . '%"></div></div>
    </div>
    <div class="info-box">
        <strong>En cours</strong>
        <span>' . $enCours . '</span>
    </div>
</div>

<h3>Plan de travail — Etapes</h3>
<table>
    <thead>
        <tr>
            <th>Etape</th>
            <th>Statut</th>
            <th>Debut</th>
            <th>Fin</th>
            <th>Delai estime</th>
            <th>Notes</th>
            <th>Documents</th>
        </tr>
    </thead>
    <tbody>
        ' . $stepsRows . '
    </tbody>
</table>

<h3>Prochaines etapes</h3>
<ul>' . $nextStepsHtml . '</ul>

<div class="footer">
    <span>Centre Domiciliation — Suivi administratif</span>
    <span>Page 1/1</span>
</div>

</body>
</html>';

// Generate PDF
if (!class_exists('\Dompdf\Dompdf')) {
    http_response_code(500);
    echo 'Dompdf non disponible.';
    return;
}

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Suivi_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $raisonSociale) . '_' . $today . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
