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
$typeLabel = $typeGen === 'creation' ? 'Creation' : 'Domiciliation';

function dossier_sanitize(string $str): string
{
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    $str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
    $str = trim($str, '_');
    return $str !== '' ? $str : 'Dossier';
}

$raisonSociale = trim((string) ($soc['societe_raison_sociale'] ?? 'Societe'));
$formeJuridique = trim((string) ($soc['societe_forme_juridique'] ?? ''));

$socSanitized = dossier_sanitize($raisonSociale);
$formeSanitized = dossier_sanitize($formeJuridique);

$stmtContrat = $pdo->prepare('SELECT contrat_date FROM contrats WHERE societe_id = :sid ORDER BY id DESC LIMIT 1');
$stmtContrat->execute(['sid' => $societeId]);
$contratDate = $stmtContrat->fetchColumn();
$folderDate = $contratDate ?: date('Y-m-d');

$socUpper = strtoupper($socSanitized);
$formeUpper = strtoupper($formeSanitized);
$zipName = $folderDate . '_' . $typeLabel . '_' . $socSanitized;
if ($formeSanitized !== '' && !str_ends_with($socUpper, $formeUpper)) {
    $zipName .= '_' . $formeSanitized;
}
$zipName .= '.zip';

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'zip_');
if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    set_flash('error', 'Impossible de creer l\'archive ZIP');
    redirect_to('generation', ['societe_id' => $societeId]);
}

$added = 0;
foreach ($docs as $doc) {
    if ($type === 'word' || $type === 'both') {
        $docx = $doc['fichier_docx'] ?? '';
        if ($docx !== '' && file_exists($docx)) {
            $zip->addFile($docx, 'Word/' . basename($docx));
            $added++;
        }
    }
    if ($type === 'pdf' || $type === 'both') {
        $pdf = $doc['fichier_pdf'] ?? '';
        if ($pdf !== '' && file_exists($pdf)) {
            $zip->addFile($pdf, 'PDF/' . basename($pdf));
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
