<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/amorcage.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../src/analyseur_templates.php';
require_once __DIR__ . '/../src/rendu_document.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

header('Content-Type: application/json');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST requis']);
    exit;
}

verify_csrf();

$action = $_POST['action'] ?? '';

if ($action === 'generate_docx') {
    $societeId = (int) ($_POST['societe_id'] ?? 0);
    $templatePath = $_POST['template_path'] ?? '';
    if ($societeId <= 0 || $templatePath === '' || !file_exists($templatePath)) {
        echo json_encode(['success' => false, 'error' => 'Parametres invalides ou template introuvable']);
        exit;
    }
    try {
        $soc = fetch_record($pdo ?? null, 'societes', $societeId);
        if (!$soc) {
            echo json_encode(['success' => false, 'error' => 'Societe introuvable']);
            exit;
        }
        $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);
        $forme = $soc['societe_forme_juridique'] ?? 'PP';
        $today = date('Y-m-d');
        $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $soc['societe_raison_sociale'] ?? 'Client')));
        $clientName = preg_replace('/-+/', '-', $clientName);
        $clientName = trim($clientName, '-');
        $folderDate = $context['contrat_date'] ?? $today;
        $folderName = $folderDate . '_' . $forme . '_' . $clientName;
        $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
        $outputDir = __DIR__ . '/../dossiers_generer/dossiers_domiciliation/' . $folderName;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $renderer = new DocumentRenderer($templatePath, $outputDir);
        $filename = pathinfo($templatePath, PATHINFO_FILENAME);
        $parts = explode('_', $filename);
        $docType = '';
        if (count($parts) >= 4) {
            $docType = preg_replace('/_?Template$/i', '', implode('_', array_slice($parts, 2)));
        } elseif (count($parts) === 3) {
            $docType = preg_replace('/_?Template$/i', '', $parts[1]);
        }
        if ($docType !== '' && ($pdo ?? null) instanceof PDO) {
            // Societe de type domiciliation : uniquement les types autorises (Attestation + Contrat)
            if (($soc['societe_type_generation'] ?? '') === 'domiciliation') {
                $allowedTypes = null;
                if (file_exists(__DIR__ . '/../config/templates.php')) {
                    $tplCfg = require __DIR__ . '/../config/templates.php';
                    $allowedTypes = $tplCfg['template_mapping']['domiciliation'] ?? [];
                }
                if (is_array($allowedTypes) && !in_array($docType, $allowedTypes, true)) {
                    echo json_encode(['success' => false, 'error' => 'Type de document non autorise pour une societe en domiciliation']);
                    exit;
                }
            }
            $vStmt = $pdo->prepare("SELECT COUNT(*) FROM documents_generes WHERE societe_id = ? AND doc_type = ? AND valide = 1");
            $vStmt->execute([$societeId, $docType]);
            if ((int) $vStmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Document deja valide. Utilisez "Restaurer en brouillon" pour le modifier.', 'skipped' => true]);
                exit;
            }
        }
        $base = $forme . '_' . $today . '_' . $docType . '_' . $clientName;
        $outName = $base . '_Brouillon.docx';
        $docxPath = $renderer->render($context, $outName);

        if ($docxPath && file_exists($docxPath) && ($pdo ?? null) instanceof PDO) {
            $insertStmt = $pdo->prepare('INSERT INTO documents_generes (societe_id, template_source, doc_type, fichier_docx, fichier_pdf, taille_ko) VALUES (:societe_id, :template_source, :doc_type, :fichier_docx, :fichier_pdf, :taille_ko)');
            $insertStmt->execute([
                'societe_id' => $societeId,
                'template_source' => $templatePath,
                'doc_type' => $docType,
                'fichier_docx' => $docxPath,
                'fichier_pdf' => null,
                'taille_ko' => round(filesize($docxPath) / 1024, 1),
            ]);
            log_activity($pdo, 'generate', 'document', $societeId, 'Generation AJAX — ' . basename($docxPath));
            echo json_encode(['success' => true, 'docx_path' => $docxPath, 'name' => $outName, 'doc_id' => (int) $pdo->lastInsertId()]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Echec rendu DOCX']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'generate_pdf') {
    $docId = (int) ($_POST['doc_id'] ?? 0);
    $societeId = (int) ($_POST['societe_id'] ?? 0);
    if ($docId <= 0 || $societeId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Parametres invalides']);
        exit;
    }
    try {
        $stmt = $pdo->prepare('SELECT * FROM documents_generes WHERE id = :id AND societe_id = :soc AND valide = 1');
        $stmt->execute(['id' => $docId, 'soc' => $societeId]);
        $doc = $stmt->fetch();
        if (!$doc) {
            echo json_encode(['success' => false, 'error' => 'Document introuvable ou non valide']);
            exit;
        }

        $docxPath = $doc['fichier_docx'];
        $docxDir = dirname($docxPath);

        if (!file_exists($docxPath)) {
            $soc = fetch_record($pdo ?? null, 'societes', $societeId);
            $today = date('Y-m-d');
            $clientName = trim(preg_replace('/[^a-zA-Z0-9-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $soc['societe_raison_sociale'] ?? 'Client')));
            $clientName = preg_replace('/-+/', '-', $clientName);
            $clientName = trim($clientName, '-');
            $forme = $soc['societe_forme_juridique'] ?? 'PP';
            $context = DocumentRenderer::buildContextFromDb($pdo, $societeId);
            $folderDate = $context['contrat_date'] ?? $today;
            $folderName = $folderDate . '_' . $forme . '_' . $clientName;
            $folderName = trim(preg_replace('/[^a-zA-Z0-9_-]/', '-', $folderName), '-');
            $docxDir = __DIR__ . '/../dossiers_generer/dossiers_domiciliation/' . $folderName;
            if (!is_dir($docxDir)) {
                mkdir($docxDir, 0777, true);
            }
            $tplSrc = $doc['template_source'] ?? '';
            if (($tplSrc === '' || !file_exists($tplSrc)) && !empty($doc['doc_type'])) {
                $templatesScan = TemplateAnalyzer::scanTemplates(__DIR__ . '/../templates');
                foreach ($templatesScan as $tpl) {
                    if ($tpl['doc_type'] === $doc['doc_type']) {
                        $tplSrc = $tpl['path'];
                        break;
                    }
                }
            }
            if ($tplSrc === '' || !file_exists($tplSrc)) {
                echo json_encode(['success' => false, 'error' => 'Template source introuvable']);
                exit;
            }
            $outName = basename($docxPath);
            $renderer = new DocumentRenderer($tplSrc, $docxDir);
            $regenerated = $renderer->render($context, $outName);
            if (!$regenerated || !file_exists($regenerated)) {
                echo json_encode(['success' => false, 'error' => 'Echec regeneration DOCX']);
                exit;
            }
            $docxPath = $regenerated;
        }

        $pdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
        $renderer = new DocumentRenderer('', $docxDir);
        $pdfPath = $renderer->tryConvertToPdf($docxPath, $pdfName);

        if ($pdfPath && file_exists($pdfPath)) {
            $updateStmt = $pdo->prepare('UPDATE documents_generes SET fichier_docx = :docx, fichier_pdf = :pdf WHERE id = :id');
            $updateStmt->execute(['docx' => $docxPath, 'pdf' => $pdfPath, 'id' => $docId]);
            log_activity($pdo, 'convert_pdf', 'document', $societeId, 'Conversion PDF AJAX — ' . basename($pdfPath));
            echo json_encode(['success' => true, 'pdf_path' => $pdfPath, 'docx_path' => $docxPath]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Echec conversion PDF']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Action invalide']);
