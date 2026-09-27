<?php

declare(strict_types=1);

/**
 * Telechargement du modele Excel d'import (.xlsx).
 *
 * URL : index.php?page=import-modele&table=<table>
 *   table = societes | associes | contrats | collaborateurs | cessions
 *
 * Le classeur genere contient :
 *   - onglet « Données »   : ligne 1 = en-tetes attendus par l'import
 *                           ligne 2 = une ligne d'exemple (a supprimer)
 *   - onglet « Consignes » : notice de chaque colonne + regles d'import
 *
 * L'onglet actif est « Données » : c'est celui que lise l'import
 * (import_excel_preview / loadSpreadsheetData utilisent getActiveSheet()).
 * L'import exige au moins 2 lignes, d'ou la ligne d'exemple.
 *
 * Meme source de verite que l'import : includes/import_excel_config.php
 */

require_once __DIR__ . '/../../includes/import_excel_config.php';

$table = trim((string) ($_GET['table'] ?? ''));
$config = import_excel_table($table);

if ($config === null) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Modèle indisponible : table d'import inconnue.";
    exit;
}

// Meme droit que le bouton « Importer Excel » de la page liste.
require_permission($table . '.import');

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    if (!is_file($autoload)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "PhpSpreadsheet n'est pas installé. Lancez \"composer install\" à la racine du projet.";
        exit;
    }
    require_once $autoload;
}

$columnMap = $config['columnMap'];
$notices = import_excel_column_notices()[$table] ?? [];
$headers = array_keys($columnMap);

// Colonnes NOT NULL sans valeur par defaut => renseignement obligatoire.
// Interroge information_schema pour rester exact apres evolution du schema.
$required = [];
if (($pdo ?? null) instanceof PDO) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table
                AND IS_NULLABLE = 'NO'
                AND COLUMN_DEFAULT IS NULL
                AND EXTRA <> 'auto_increment'"
        );
        $stmt->execute([':table' => $table]);
        $required = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (\Throwable $e) {
        $required = [];
    }
}

$label = import_excel_table_label($table);

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$spreadsheet->getProperties()->setTitle('Modèle d\'import - ' . $label);
$spreadsheet->getProperties()->setCreator('Center Domiciliation');

$headFill = 'FF1F4E79';
$headFont = 'FFFFFFFF';
$exampleFill = 'FFFFF2CC';
$border = 'FFB7C3D0';

// ── Onglet 1 : Données (onglet actif, lu par l'import) ──
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Données');

$col = 1;
foreach ($headers as $header) {
    $sheet->setCellValue([$col, 1], $header);
    $col++;
}

$lastCol = count($headers);
$headStyle = $sheet->getStyle([1, 1, $lastCol, 1]);
$headStyle->getFont()->setBold(true)->getColor()->setARGB($headFont);
$headStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB($headFill);
$headStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$headStyle->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$headStyle->getBorders()->getInside()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$sheet->getRowDimension(1)->setRowHeight(22);

// Ligne 2 : exemple, a supprimer avant import.
$col = 1;
foreach ($headers as $header) {
    $sheet->setCellValue([$col, 2], (string) ($notices[$header]['ex'] ?? ''));
    $col++;
}
$exampleStyle = $sheet->getStyle([1, 2, $lastCol, 2]);
$exampleStyle->getFont()->setItalic(true);
$exampleStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB($exampleFill);

$sheet->freezePane('A2');
for ($i = 1; $i <= $lastCol; $i++) {
    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
}

// ── Onglet 2 : Consignes ──
$guide = $spreadsheet->createSheet();
$guide->setTitle('Consignes');
$guide->setCellValue('A1', 'Consignes d\'import - ' . $label);
$guide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$guide->setCellValue('A2', 'IMPORTANT : supprimez la ligne 2 de l\'onglet « Données » (ligne d\'exemple) avant de lancer l\'import.');
$guide->getStyle('A2')->getFont()->setBold(true)->getColor()->setARGB('FFC00000');
$guide->setCellValue('A3', 'Ne renommez pas les en-têtes de la ligne 1 : l\'import reconnaît chaque colonne par son libellé exact.');
$guide->setCellValue('A4', 'Laissez vides les colonnes dont la description l\'indique (numéros générés automatiquement).');

$guideHeaders = ['Colonne (en-tête Excel)', 'Colonne SQL', 'Obligatoire', 'Description', 'Exemple'];
$guideCol = 1;
foreach ($guideHeaders as $guideHeader) {
    $guide->setCellValue([$guideCol, 6], $guideHeader);
    $guideCol++;
}
$guideHeadStyle = $guide->getStyle([1, 6, count($guideHeaders), 6]);
$guideHeadStyle->getFont()->setBold(true)->getColor()->setARGB($headFont);
$guideHeadStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB($headFill);

$row = 7;
foreach ($columnMap as $header => $dbCol) {
    $guide->setCellValue([1, $row], $header);
    $guide->setCellValue([2, $row], $dbCol);
    $guide->setCellValue([3, $row], in_array($dbCol, $required, true) ? 'Oui' : '');
    $guide->setCellValue([4, $row], (string) ($notices[$header]['desc'] ?? ''));
    $guide->setCellValue([5, $row], (string) ($notices[$header]['ex'] ?? ''));
    $row++;
}
$guide->getStyle([1, 7, count($guideHeaders), $row - 1])
    ->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$guide->getStyle([1, 7, count($guideHeaders), $row - 1])
    ->getBorders()->getInside()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$guide->getColumnDimension('A')->setAutoSize(true);
$guide->getColumnDimension('B')->setAutoSize(true);
$guide->getColumnDimension('C')->setAutoSize(true);
$guide->getColumnDimension('D')->setWidth(60);
$guide->getColumnDimension('E')->setAutoSize(true);

// L'import lit getActiveSheet() : on force « Données ».
$spreadsheet->setActiveSheetIndex(0);

if (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = 'modele-import-' . $table . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
