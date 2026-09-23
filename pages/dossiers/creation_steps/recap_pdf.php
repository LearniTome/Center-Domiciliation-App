<?php

declare(strict_types=1);

// Page PDF serveur (texte vectoriel, selectable) du recap du wizard de creation.
// Suit exactement le pattern de pages/dossiers/suivi_pdf.php (Dompdf).

require __DIR__ . '/_init.php';

if (empty($_SESSION['creation_wizard']) || !is_array($_SESSION['creation_wizard'])) {
    redirect_to('creation', ['step' => 1]);
}

// Auth minimum : il faut une session de wizard valide
$wizardUser = current_user();
if (!$wizardUser) {
    redirect_to('connexion');
}

$isCreation = (($societeData['societe_type_generation'] ?? '') === 'creation');

// --- Assembly du recap (meme logique que step_04_Recap.php) ---
$dossierNumDomi = $societeData['societe_dossier_domiciliation_number'] ?? '';
$dossierNumCre  = $societeData['societe_dossier_creation_number'] ?? '';

$associesRows = '';
foreach ($associesData as $i => $associe) {
    $estGerant = ((string) ($associe['associe_est_gerant'] ?? '0') === '1');
    $gerance = $estGerant ? 'Oui' . ($associe['associe_duree_gerance'] ? ' - ' . $associe['associe_duree_gerance'] : '') : 'Non';
    $associesRows .= '
    <tr>
        <td style="padding:5px 8px;border-bottom:1px solid #eee;font-size:10px">' . htmlspecialchars((string) ($associe['associe_nom_complet'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee;font-size:10px">' . htmlspecialchars((string) ($associe['associe_cin'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee;font-size:10px">' . htmlspecialchars((string) ($associe['associe_qualite'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee;font-size:10px">' . htmlspecialchars((string) ($associe['associe_nationalite'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee;font-size:10px">' . htmlspecialchars((string) ($associe['associe_parts'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee;font-size:10px">' . htmlspecialchars($gerance, ENT_QUOTES, 'UTF-8') . '</td>
    </tr>';
}
if ($associesRows === '') {
    $associesRows = '<tr><td colspan="6" style="padding:8px;font-size:10px;color:#888">Aucun associe.</td></tr>';
}

$today = (new DateTime())->format('d/m/Y');

$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 24px; }
    h1 { font-size: 15px; color: #1a1a2e; margin: 0 0 4px; }
    .subtitle { font-size: 10px; color: #666; margin: 0 0 14px; }
    h3 { font-size: 11px; color: #1a1a2e; margin: 14px 0 6px; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; }
    .info-grid { width: 100%; font-size: 10px; }
    .info-grid td { padding: 3px 6px; vertical-align: top; width: 50%; }
    .info-grid .label, td .label { color: #777; font-size: 9px; display: block; }
    .info-grid .value { font-weight: bold; font-size: 10px; display: block; }
    thead th { background: #f0f0f5; padding: 5px 8px; text-align: left; font-size: 9px; text-transform: uppercase; color: #666; border-bottom: 2px solid #ddd; }
    .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #888; }
</style>
</head>
<body>

<h1>Recapitulatif du dossier de ' . ($isCreation ? 'creation' : 'domiciliation') . '</h1>
<p class="subtitle">Dossier n° ' . htmlspecialchars($dossierNumDomi ?: '-', ENT_QUOTES, 'UTF-8') . ' — Genere le ' . $today . '</p>
' . ($isCreation ? '<p class="subtitle">Dossier creation n° ' . htmlspecialchars($dossierNumCre ?: '-', ENT_QUOTES, 'UTF-8') . '</p>' : '') . '

<h3>Societe</h3>
<table class="info-grid">
    <tr>
        <td><span class="label">Raison sociale</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_raison_sociale'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
        <td><span class="label">Forme juridique</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_forme_juridique'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
    </tr>
    <tr>
        <td><span class="label">ICE</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_ice'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
        <td><span class="label">Capital</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_capital'] ?: '-'), ENT_QUOTES, 'UTF-8') . ' DH</span></td>
    </tr>
    <tr>
        <td><span class="label">Ville</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_ville'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
        <td><span class="label">Tribunal</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_tribunal'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
    </tr>
    <tr>
        <td colspan="2"><span class="label">Adresse siege</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_adresse_siege'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
    </tr>
    <tr>
        <td><span class="label">Email</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_email'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
        <td><span class="label">Telephone</span><span class="value">' . htmlspecialchars((string) ($societeData['societe_telephone'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
    </tr>
</table>

<h3>Associes (' . count($associesData) . ')</h3>
<table>
    <thead><tr><th>Nom complet</th><th>CIN</th><th>Qualite</th><th>Nationalite</th><th>Parts</th><th>Gerant</th></tr></thead>
    <tbody>' . $associesRows . '</tbody>
</table>

<h3>Contrat</h3>
<table class="info-grid">
    <tr>
        <td><span class="label">Type contrat</span><span class="value">' . htmlspecialchars((string) ($contratData['contrat_type'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
        <td><span class="label">Type domiciliation</span><span class="value">' . htmlspecialchars((string) ($contratData['contrat_type_domiciliation'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
    </tr>
    <tr>
        <td><span class="label">Date contrat</span><span class="value">' . htmlspecialchars((string) ($contratData['contrat_date'] ?: '-'), ENT_QUOTES, 'UTF-8') . '</span></td>
        <td><span class="label">Duree</span><span class="value">' . htmlspecialchars((string) ($contratData['contrat_duree_mois'] ?: '-'), ENT_QUOTES, 'UTF-8') . ' mois</span></td>
    </tr>
    <tr>
        <td><span class="label">Loyer HT</span><span class="value">' . htmlspecialchars((string) ($contratData['contrat_loyer_ht'] ?: '-'), ENT_QUOTES, 'UTF-8') . ' DH</span></td>
        <td><span class="label">Loyer TTC/mois</span><span class="value">' . htmlspecialchars((string) ($contratData['contrat_loyer_ttc'] ?: '-'), ENT_QUOTES, 'UTF-8') . ' DH</span></td>
    </tr>
</table>

<div class="footer">
    <span>Centre Domiciliation — Recapitulatif du dossier</span>
</div>

</body>
</html>';

// Dompdf
$autoload = __DIR__ . '/../../../vendor/autoload.php';
if (!is_file($autoload) || !class_exists(\Dompdf\Dompdf::class) && !file_exists(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    echo 'Dompdf non disponible.';
    return;
}
require_once $autoload;

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$prefix = ($isCreation ? 'CRE' : 'DOM') . '-' . date('Y-m');
$raisonSlug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) ($societeData['societe_raison_sociale'] ?? '')), '-'));
$filename = 'Recapitulatif_' . $prefix . ($raisonSlug !== '' ? '_' . $raisonSlug : '') . '.pdf';

$dompdf->stream($filename, ['Attachment' => true]);
exit;
