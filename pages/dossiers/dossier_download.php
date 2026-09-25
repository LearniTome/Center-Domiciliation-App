<?php

declare(strict_types=1);

$societeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($societeId <= 0) {
    set_flash('error', 'Parametre invalide.');
    redirect_to('creations');
}

$soc = fetch_record($pdo ?? null, 'societes', $societeId);
if (!$soc) {
    set_flash('error', 'Societe introuvable.');
    redirect_to('creations');
}

$typeGen = (string) ($soc['societe_type_generation'] ?? '');

// L'archive reproduit le dossier genere tel qu'il existe sur le disque : on
// reprend le nom fige a la premiere generation plutot que de le recalculer,
// sinon l'extraction creerait un second dossier au lieu du dossier d'origine.
$numeroDossier = $typeGen === 'creation'
    ? (string) ($soc['societe_dossier_creation_number'] ?? '')
    : (string) ($soc['societe_dossier_domiciliation_number'] ?? '');

$folderName = DossierNaming::nomDossierArchive($pdo ?? null, $societeId, $soc, $numeroDossier);
if ($folderName === '') {
    // Donnees incompletes (raison sociale absente) : on ne bloque pas le
    // telechargement pour autant, un nom generique unique suffit.
    $folderName = 'Dossier-SOC-' . $societeId;
}
$zipName = $folderName . '.zip';

$stmtGen = $pdo->prepare('SELECT * FROM documents_generes WHERE societe_id = :sid');
$stmtGen->execute(['sid' => $societeId]);
$docsGeneres = $stmtGen->fetchAll();

$stmtUp = $pdo->prepare('SELECT * FROM uploaded_docs WHERE societe_id = :sid ORDER BY uploaded_at');
$stmtUp->execute(['sid' => $societeId]);
$docsUploades = $stmtUp->fetchAll();

if (empty($docsGeneres) && empty($docsUploades)) {
    set_flash('error', 'Aucun document a telecharger pour ce dossier.');
    redirect_to('societe', ['id' => $societeId]);
}

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'dossier_zip_');
if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    set_flash('error', "Impossible de creer l'archive ZIP.");
    redirect_to('societe', ['id' => $societeId]);
}

$added = 0;
$rootFolder = $folderName . '/';

foreach ($docsGeneres as $doc) {
    $docx = $doc['fichier_docx'] ?? '';
    if ($docx !== '' && file_exists($docx)) {
        $zip->addFile($docx, $rootFolder . 'Word/' . basename($docx));
        $added++;
    }
    $pdf = $doc['fichier_pdf'] ?? '';
    if ($pdf !== '' && file_exists($pdf)) {
        $zip->addFile($pdf, $rootFolder . 'PDF/' . basename($pdf));
        $added++;
    }
}

foreach ($docsUploades as $ud) {
    $filepath = $ud['filepath'] ?? '';
    if ($filepath !== '' && file_exists($filepath)) {
        $zip->addFile($filepath, $rootFolder . 'Uploads/' . basename($filepath));
        $added++;
    }
}

$zip->close();

if ($added === 0) {
    @unlink($tmpFile);
    set_flash('error', 'Aucun fichier trouve a telecharger.');
    redirect_to('societe', ['id' => $societeId]);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: no-cache');
header('Cache-Control: must-revalidate');
header('Expires: 0');
readfile($tmpFile);
@unlink($tmpFile);
exit;
