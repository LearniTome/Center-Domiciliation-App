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
        'raison_sociale' => 'Raison sociale',
        'forme_juridique' => 'Forme juridique',
        'ice' => 'ICE',
        'rc' => 'RC',
        'if_number' => 'IF',
        'capital' => 'Capital',
        'part_social' => 'Nombre de parts sociales',
        'valeur_nominale' => 'Valeur nominale',
        'ville' => 'Ville du siege',
        'tribunal' => 'Tribunal competent',
        'ste_adress' => 'Adresse du siege social',
        'email' => 'Email',
        'telephone' => 'Telephone',
        'dossier_domiciliation' => 'Dossier domiciliation',
        'type_generation' => 'Type de generation',
        'procedure_creation' => 'Procedure de creation',
        'mode_depot_creation' => 'Mode de depot',
        'date_ice' => 'Date immatriculation ICE',
        'date_exp_cert_neg' => 'Date expiration certificat negatif',
    ],
    'Associe' => [
        'nom_complet' => 'Nom complet',
        'nom' => 'Nom de famille',
        'prenom' => 'Prenom',
        'civilite' => 'Civilite',
        'cin' => 'CIN',
        'date_naiss' => 'Date naissance',
        'lieu_naiss' => 'Lieu naissance',
        'nationalite' => 'Nationalite',
        'adresse' => 'Adresse',
        'telephone' => 'Telephone',
        'email' => 'Email',
        'qualite_associe' => 'Qualite',
        'parts' => 'Nombre de parts',
        'capital_detenu' => 'Capital detenu',
        'is_gerant' => 'Gerant (Oui/Non)',
    ],
    'Contrat' => [
        'type_contrat_domiciliation' => 'Type de domiciliation',
        'date_contrat' => 'Date contrat',
        'date_debut' => 'Date debut',
        'date_fin' => 'Date fin',
        'duree_contrat_mois' => 'Duree (mois)',
        'loyer_mensuel_ttc' => 'Loyer mensuel TTC',
        'loyer_mensuel_ht' => 'Loyer mensuel HT',
        'taux_tva_pourcent' => 'Taux TVA %',
        'montant_total_ht_contrat' => 'Montant total HT',
        'frais_intermediaire_contrat' => 'Frais intermediaire',
        'caution_montant' => 'Caution',
        'statut' => 'Statut',
        'mode_signature' => 'Mode de signature',
        'montant_pack_demarrage_ttc' => 'Pack demarrage montant TTC',
        'loyer_mensuel_pack_demarrage_ttc' => 'Pack demarrage loyer TTC',
        'type_renouvellement' => 'Type de renouvellement',
        'taux_tva_renouvellement_pourcent' => 'TVA renouvellement %',
        'loyer_mensuel_ht_renouvellement' => 'Loyer HT renouvellement',
        'loyer_mensuel_renouvellement_ttc' => 'Loyer TTC renouvellement',
        'loyer_annuel_renouvellement_ttc' => 'Loyer annuel TTC renouvellement',
    ],
];

$variableLabels = [
    'SOCIETE_RAISON_SOCIALE' => ['Societe', 'raison_sociale'],
    'SOCIETE_FORME_JURIDIQUE' => ['Societe', 'forme_juridique'],
    'SOCIETE_ICE' => ['Societe', 'ice'],
    'SOCIETE_RC' => ['Societe', 'rc'],
    'SOCIETE_IF' => ['Societe', 'if_number'],
    'SOCIETE_CAPITAL' => ['Societe', 'capital'],
    'SOCIETE_PART_SOCIAL' => ['Societe', 'part_social'],
    'SOCIETE_VALEUR_NOMINALE' => ['Societe', 'valeur_nominale'],
    'SOCIETE_VILLE' => ['Societe', 'ville'],
    'SOCIETE_TRIBUNAL' => ['Societe', 'tribunal'],
    'SOCIETE_ADRESSE_SIEGE' => ['Societe', 'ste_adress'],
    'SOCIETE_EMAIL' => ['Societe', 'email'],
    'SOCIETE_TELEPHONE' => ['Societe', 'telephone'],
    'SOCIETE_DOSSIER' => ['Societe', 'dossier_domiciliation'],
    'SOCIETE_TYPE_GENERATION' => ['Societe', 'type_generation'],
    'SOCIETE_PROCEDURE_CREATION' => ['Societe', 'procedure_creation'],
    'SOCIETE_MODE_DEPOT' => ['Societe', 'mode_depot_creation'],
    'SOCIETE_DATE_ICE' => ['Societe', 'date_ice'],
    'SOCIETE_DATE_EXP_CERT_NEG' => ['Societe', 'date_exp_cert_neg'],
    'ASSOCIE_NOM_COMPLET' => ['Associe', 'nom_complet'],
    'ASSOCIE_NOM' => ['Associe', 'nom'],
    'ASSOCIE_PRENOM' => ['Associe', 'prenom'],
    'ASSOCIE_CIVILITE' => ['Associe', 'civilite'],
    'ASSOCIE_CIN' => ['Associe', 'cin'],
    'ASSOCIE_DATE_NAISSANCE' => ['Associe', 'date_naiss'],
    'ASSOCIE_LIEU_NAISSANCE' => ['Associe', 'lieu_naiss'],
    'ASSOCIE_NATIONALITE' => ['Associe', 'nationalite'],
    'ASSOCIE_ADRESSE' => ['Associe', 'adresse'],
    'ASSOCIE_TELEPHONE' => ['Associe', 'telephone'],
    'ASSOCIE_EMAIL' => ['Associe', 'email'],
    'ASSOCIE_QUALITE' => ['Associe', 'qualite_associe'],
    'ASSOCIE_PARTS' => ['Associe', 'parts'],
    'ASSOCIE_CAPITAL_DETENU' => ['Associe', 'capital_detenu'],
    'ASSOCIE_EST_GERANT' => ['Associe', 'is_gerant'],
    'CONTRAT_TYPE' => ['Contrat', 'type_contrat_domiciliation'],
    'CONTRAT_TYPE_DOMICILIATION' => ['Contrat', 'type_contrat_domiciliation'],
    'CONTRAT_DATE' => ['Contrat', 'date_contrat'],
    'CONTRAT_DATE_DEBUT' => ['Contrat', 'date_debut'],
    'CONTRAT_DATE_FIN' => ['Contrat', 'date_fin'],
    'CONTRAT_DUREE_MOIS' => ['Contrat', 'duree_contrat_mois'],
    'CONTRAT_LOYER_TTC' => ['Contrat', 'loyer_mensuel_ttc'],
    'CONTRAT_LOYER_HT' => ['Contrat', 'loyer_mensuel_ht'],
    'CONTRAT_TVA_POURCENT' => ['Contrat', 'taux_tva_pourcent'],
    'CONTRAT_TOTAL_HT' => ['Contrat', 'montant_total_ht_contrat'],
    'CONTRAT_FRAIS_INTERMEDIAIRE' => ['Contrat', 'frais_intermediaire_contrat'],
    'CONTRAT_CAUTION' => ['Contrat', 'caution_montant'],
    'CONTRAT_STATUT' => ['Contrat', 'statut'],
    'CONTRAT_MODE_SIGNATURE' => ['Contrat', 'mode_signature'],
    'CONTRAT_PACK_MONTANT_TTC' => ['Contrat', 'montant_pack_demarrage_ttc'],
    'CONTRAT_PACK_LOYER_TTC' => ['Contrat', 'loyer_mensuel_pack_demarrage_ttc'],
    'CONTRAT_TYPE_RENOUVELLEMENT' => ['Contrat', 'type_renouvellement'],
    'CONTRAT_RENOUV_TVA_POURCENT' => ['Contrat', 'taux_tva_renouvellement_pourcent'],
    'CONTRAT_RENOUV_LOYER_HT' => ['Contrat', 'loyer_mensuel_ht_renouvellement'],
    'CONTRAT_RENOUV_LOYER_TTC' => ['Contrat', 'loyer_mensuel_renouvellement_ttc'],
    'CONTRAT_RENOUV_ANNUEL_TTC' => ['Contrat', 'loyer_annuel_renouvellement_ttc'],
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
