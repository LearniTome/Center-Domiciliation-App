<?php

declare(strict_types=1);

$societeId = isset($_GET['societe_id']) ? (int) $_GET['societe_id'] : 0;
$type = $_GET['type'] ?? 'both';

if ($societeId <= 0 || !in_array($type, ['word', 'pdf', 'both'], true)) {
    set_flash('error', 'Parametres invalides');
    redirect_to('generation', ['societe_id' => $societeId]);
}

$soc = fetch_record($pdo ?? null, 'societes', $societeId);
if (!$soc) {
    set_flash('error', 'Societe introuvable');
    redirect_to('generation');
}

$stmt = $pdo->prepare("SELECT * FROM documents_generes WHERE societe_id = ? AND valide = 1");
$stmt->execute([$societeId]);
$docs = $stmt->fetchAll();

if (empty($docs)) {
    set_flash('error', 'Aucun document valide a telecharger');
    redirect_to('generation', ['societe_id' => $societeId]);
}

$typeGen = (string) ($soc['societe_type_generation'] ?? '');

// Nom de l'archive aligne sur le dossier reellement genere (nom fige a la
// premiere generation), comme pour le telechargement complet du dossier.
$numeroDossier = $typeGen === 'creation'
    ? (string) ($soc['societe_dossier_creation_number'] ?? '')
    : (string) ($soc['societe_dossier_domiciliation_number'] ?? '');

$folderName = DossierNaming::nomDossierArchive($pdo ?? null, $societeId, $soc, $numeroDossier);
if ($folderName === '') {
    // Donnees incompletes : on ne bloque pas le telechargement pour autant.
    $folderName = 'Dossier-SOC-' . $societeId;
}
$zipName = $folderName . '.zip';

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'zip_');
if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    set_flash('error', 'Impossible de creer l\'archive ZIP');
    redirect_to('generation', ['societe_id' => $societeId]);
}

$rootFolder = $folderName . '/';

$added = 0;
foreach ($docs as $doc) {
    if ($type === 'word' || $type === 'both') {
        $docx = $doc['fichier_docx'] ?? '';
        if ($docx !== '' && file_exists($docx)) {
            $zip->addFile($docx, $rootFolder . basename($docx));
            $added++;
        }
    }
    if ($type === 'pdf' || $type === 'both') {
        $pdf = $doc['fichier_pdf'] ?? '';
        if ($pdf !== '' && file_exists($pdf)) {
            $zip->addFile($pdf, $rootFolder . 'PDF/' . basename($pdf));
            $added++;
        }
    }
}

if ($type === 'both') {
    $stmtUploads = $pdo->prepare('SELECT * FROM uploaded_docs WHERE societe_id = :sid ORDER BY uploaded_at');
    $stmtUploads->execute(['sid' => $societeId]);
    $docsUploades = $stmtUploads->fetchAll();

    foreach ($docsUploades as $ud) {
        $filepath = $ud['filepath'] ?? '';
        if ($filepath !== '' && file_exists($filepath)) {
            $zip->addFile($filepath, $rootFolder . 'Uploads/' . basename($filepath));
            $added++;
        }
    }
}

$zip->close();

if ($added === 0) {
    @unlink($tmpFile);
    set_flash('error', 'Aucun fichier trouve a telecharger');
    redirect_to('generation', ['societe_id' => $societeId]);
}

$pageTitle = 'Telechargement...';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: no-cache');
readfile($tmpFile);
@unlink($tmpFile);
exit;
