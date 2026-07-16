<?php
declare(strict_types=1);
require_once 'E:/Dev_Project/Center-Domiciliation-App/includes/amorcage.php';

// Delete orphaned documents_generes records for societe_id=32
// where the physical files no longer exist
$stmt = $pdo->prepare("SELECT id, fichier_docx, doc_type, valide FROM documents_generes WHERE societe_id = ?");
$stmt->execute([32]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deleted = 0;
foreach ($rows as $r) {
    $path = $r['fichier_docx'];
    if ($path && !file_exists($path)) {
        echo "Deleting orphaned record #{$r['id']} ({$r['doc_type']}, valide={$r['valide']}) — file missing: $path\n";
        $del = $pdo->prepare("DELETE FROM documents_generes WHERE id = ?");
        $del->execute([$r['id']]);
        $deleted++;
    } else {
        echo "Keeping record #{$r['id']} ({$r['doc_type']}, valide={$r['valide']}) — file exists\n";
    }
}
echo "\nTotal deleted: $deleted\n";
