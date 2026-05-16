<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TemplateAnalyzer.php';

$templatesConfig = require __DIR__ . '/../config/templates.php';
$templatesDir = __DIR__ . '/../templates';
$templatePath = isset($_GET['path']) ? realpath((string) $_GET['path']) : '';

if ($templatePath === '' || !str_starts_with($templatePath, realpath($templatesDir)) || !file_exists($templatePath)) {
    ?>
    <section class="card stack">
        <h2>Template introuvable</h2>
        <p>Le fichier demande n'existe pas ou n'est pas accessible.</p>
        <a class="btn" href="<?= e(app_url('templates')) ?>">Retour aux templates</a>
    </section>
    <?php
    return;
}

$info = TemplateAnalyzer::extractTemplateInfo($templatePath);
$variables = TemplateAnalyzer::extractVariables($templatePath);

$dbFields = [
    'Societe' => [
        'societe_raison_sociale' => 'Raison sociale',
        'societe_forme_juridique' => 'Forme juridique',
        'societe_ice' => 'ICE',
        'societe_rc' => 'RC',
        'societe_if' => 'IF',
        'societe_capital' => 'Capital',
        'societe_part_social' => 'Nombre de parts sociales',
        'societe_valeur_nominale' => 'Valeur nominale',
        'societe_ville' => 'Ville du siege',
        'societe_tribunal' => 'Tribunal competent',
        'societe_adresse_siege' => 'Adresse du siege social',
        'societe_email' => 'Email',
        'societe_telephone' => 'Telephone',
        'societe_dossier' => 'Dossier domiciliation',
        'societe_type_generation' => 'Type de generation',
        'societe_procedure_creation' => 'Procedure de creation',
        'societe_mode_depot' => 'Mode de depot',
        'societe_date_ice' => 'Date immatriculation ICE',
        'societe_date_exp_cert_neg' => 'Date expiration certificat negatif',
    ],
    'Associe' => [
        'associe_nom_complet' => 'Nom complet',
        'associe_nom' => 'Nom de famille',
        'associe_prenom' => 'Prenom',
        'associe_civilite' => 'Civilite',
        'associe_cin' => 'CIN',
        'associe_date_naissance' => 'Date naissance',
        'associe_lieu_naissance' => 'Lieu naissance',
        'associe_nationalite' => 'Nationalite',
        'associe_adresse' => 'Adresse',
        'associe_telephone' => 'Telephone',
        'associe_email' => 'Email',
        'associe_qualite' => 'Qualite',
        'associe_parts' => 'Nombre de parts',
        'associe_capital_detenu' => 'Capital detenu',
        'associe_est_gerant' => 'Gerant (Oui/Non)',
    ],
    'Contrat' => [
        'contrat_type_domiciliation' => 'Type de domiciliation',
        'contrat_date' => 'Date contrat',
        'contrat_date_debut' => 'Date debut',
        'contrat_date_fin' => 'Date fin',
        'contrat_duree_mois' => 'Duree (mois)',
        'contrat_loyer_ttc' => 'Loyer mensuel TTC',
        'contrat_loyer_ht' => 'Loyer mensuel HT',
        'contrat_tva_pourcent' => 'Taux TVA %',
        'contrat_total_ht' => 'Montant total HT',
        'contrat_frais_intermediaire' => 'Frais intermediaire',
        'contrat_caution' => 'Caution',
        'contrat_statut' => 'Statut',
        'contrat_mode_signature' => 'Mode de signature',
        'contrat_pack_montant_ttc' => 'Pack demarrage montant TTC',
        'contrat_pack_loyer_ttc' => 'Pack demarrage loyer TTC',
        'contrat_type_renouvellement' => 'Type de renouvellement',
        'contrat_renouv_tva_pourcent' => 'TVA renouvellement %',
        'contrat_renouv_loyer_ht' => 'Loyer HT renouvellement',
        'contrat_renouv_loyer_ttc' => 'Loyer TTC renouvellement',
        'contrat_renouv_annuel_ttc' => 'Loyer annuel TTC renouvellement',
    ],
];

$variableLabels = [
    'SOCIETE_RAISON_SOCIALE' => ['Societe', 'societe_raison_sociale'],
    'SOCIETE_FORME_JURIDIQUE' => ['Societe', 'societe_forme_juridique'],
    'SOCIETE_ICE' => ['Societe', 'societe_ice'],
    'SOCIETE_RC' => ['Societe', 'societe_rc'],
    'SOCIETE_IF' => ['Societe', 'societe_if'],
    'SOCIETE_CAPITAL' => ['Societe', 'societe_capital'],
    'SOCIETE_PART_SOCIAL' => ['Societe', 'societe_part_social'],
    'SOCIETE_VALEUR_NOMINALE' => ['Societe', 'societe_valeur_nominale'],
    'SOCIETE_VILLE' => ['Societe', 'societe_ville'],
    'SOCIETE_TRIBUNAL' => ['Societe', 'societe_tribunal'],
    'SOCIETE_ADRESSE_SIEGE' => ['Societe', 'societe_adresse_siege'],
    'SOCIETE_EMAIL' => ['Societe', 'societe_email'],
    'SOCIETE_TELEPHONE' => ['Societe', 'societe_telephone'],
    'SOCIETE_DOSSIER' => ['Societe', 'societe_dossier'],
    'SOCIETE_TYPE_GENERATION' => ['Societe', 'societe_type_generation'],
    'SOCIETE_PROCEDURE_CREATION' => ['Societe', 'societe_procedure_creation'],
    'SOCIETE_MODE_DEPOT' => ['Societe', 'societe_mode_depot'],
    'SOCIETE_DATE_ICE' => ['Societe', 'societe_date_ice'],
    'SOCIETE_DATE_EXP_CERT_NEG' => ['Societe', 'societe_date_exp_cert_neg'],
    'ASSOCIE_NOM_COMPLET' => ['Associe', 'associe_nom_complet'],
    'ASSOCIE_NOM' => ['Associe', 'associe_nom'],
    'ASSOCIE_PRENOM' => ['Associe', 'associe_prenom'],
    'ASSOCIE_CIVILITE' => ['Associe', 'associe_civilite'],
    'ASSOCIE_CIN' => ['Associe', 'associe_cin'],
    'ASSOCIE_DATE_NAISSANCE' => ['Associe', 'associe_date_naissance'],
    'ASSOCIE_LIEU_NAISSANCE' => ['Associe', 'associe_lieu_naissance'],
    'ASSOCIE_NATIONALITE' => ['Associe', 'associe_nationalite'],
    'ASSOCIE_ADRESSE' => ['Associe', 'associe_adresse'],
    'ASSOCIE_TELEPHONE' => ['Associe', 'associe_telephone'],
    'ASSOCIE_EMAIL' => ['Associe', 'associe_email'],
    'ASSOCIE_QUALITE' => ['Associe', 'associe_qualite'],
    'ASSOCIE_PARTS' => ['Associe', 'associe_parts'],
    'ASSOCIE_CAPITAL_DETENU' => ['Associe', 'associe_capital_detenu'],
    'ASSOCIE_EST_GERANT' => ['Associe', 'associe_est_gerant'],
    'CONTRAT_TYPE' => ['Contrat', 'contrat_type_domiciliation'],
    'CONTRAT_TYPE_DOMICILIATION' => ['Contrat', 'contrat_type_domiciliation'],
    'CONTRAT_DATE' => ['Contrat', 'contrat_date'],
    'CONTRAT_DATE_DEBUT' => ['Contrat', 'contrat_date_debut'],
    'CONTRAT_DATE_FIN' => ['Contrat', 'contrat_date_fin'],
    'CONTRAT_DUREE_MOIS' => ['Contrat', 'contrat_duree_mois'],
    'CONTRAT_LOYER_TTC' => ['Contrat', 'contrat_loyer_ttc'],
    'CONTRAT_LOYER_HT' => ['Contrat', 'contrat_loyer_ht'],
    'CONTRAT_TVA_POURCENT' => ['Contrat', 'contrat_tva_pourcent'],
    'CONTRAT_TOTAL_HT' => ['Contrat', 'contrat_total_ht'],
    'CONTRAT_FRAIS_INTERMEDIAIRE' => ['Contrat', 'contrat_frais_intermediaire'],
    'CONTRAT_CAUTION' => ['Contrat', 'contrat_caution'],
    'CONTRAT_STATUT' => ['Contrat', 'contrat_statut'],
    'CONTRAT_MODE_SIGNATURE' => ['Contrat', 'contrat_mode_signature'],
    'CONTRAT_PACK_MONTANT_TTC' => ['Contrat', 'contrat_pack_montant_ttc'],
    'CONTRAT_PACK_LOYER_TTC' => ['Contrat', 'contrat_pack_loyer_ttc'],
    'CONTRAT_TYPE_RENOUVELLEMENT' => ['Contrat', 'contrat_type_renouvellement'],
    'CONTRAT_RENOUV_TVA_POURCENT' => ['Contrat', 'contrat_renouv_tva_pourcent'],
    'CONTRAT_RENOUV_LOYER_HT' => ['Contrat', 'contrat_renouv_loyer_ht'],
    'CONTRAT_RENOUV_LOYER_TTC' => ['Contrat', 'contrat_renouv_loyer_ttc'],
    'CONTRAT_RENOUV_ANNUEL_TTC' => ['Contrat', 'contrat_renouv_annuel_ttc'],
];

$docTypes = $templatesConfig['document_types'];
$legalForms = $templatesConfig['legal_forms'];
?>
<section class="grid two">
    <article class="card stack">
        <div class="section-header">
            <div>
                <h2><?= e($docTypes[$info['doc_type']] ?? $info['doc_type']) ?></h2>
                <p class="help-text"><?= e($legalForms[$info['folder']] ?? $info['folder']) ?></p>
            </div>
            <div class="table-actions">
<a class="btn-icon" href="<?= e(app_url('template_edit', ['path' => $templatePath])) ?>" title="Editer"><span class="mdi mdi-pencil"></span></a>
                <a class="btn-icon" href="<?= e(app_url('templates')) ?>" title="Retour"><span class="mdi mdi-arrow-left"></span></a>
                <a class="btn-icon" href="<?= e($templatePath) ?>" download title="Telecharger"><span class="mdi mdi-download"></span></a>
            </div>
        </div>

        <div class="info-grid">
            <div><strong>Fichier</strong><span><?= e(basename($templatePath)) ?></span></div>
            <div><strong>Dossier</strong><span><?= e($legalForms[$info['folder']] ?? $info['folder']) ?></span></div>
            <div><strong>Taille</strong><span><?= e(number_format(filesize($templatePath) / 1024, 1)) ?> KB</span></div>
            <div><strong>Modifie</strong><span><?= e(date('d/m/Y H:i', filemtime($templatePath))) ?></span></div>
        </div>
    </article>

    <article class="card stack">
        <div class="section-header">
            <div>
                <h2>Variables detectees</h2>
                <p class="help-text"><?= count($variables) ?> variable(s) dans ce template</p>
            </div>
        </div>

        <?php if (!$variables): ?>
            <p class="table-empty">Aucune variable detectee.</p>
        <?php else: ?>
            <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Variable</th>
                    <th>Mapping</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($variables as $var): ?>
                    <?php
                    $upperVar = strtoupper($var);
                    $mapped = $variableLabels[$upperVar] ?? null;
                    ?>
                    <tr>
                        <td><code style="color:var(--primary)">{{ <?= e($var) ?> }}</code></td>
                        <td>
                            <?php if ($mapped): ?>
                                <span style="color:var(--text-secondary);font-size:0.8rem">
                                    <?= e($mapped[0]) ?> &rarr; <?= e($mapped[1]) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:var(--danger);font-size:0.8rem">Non mappe</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>
