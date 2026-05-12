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
        'den_ste' => 'Denomination interne',
        'forme_juridique' => 'Forme juridique',
        'ice' => 'ICE',
        'rc' => 'RC',
        'if_number' => 'IF',
        'capital' => 'Capital',
        'ville' => 'Ville',
        'tribunal' => 'Tribunal',
        'adresse' => 'Adresse',
        'ste_adress' => 'Adresse siege',
        'email' => 'Email',
        'telephone' => 'Telephone',
        'dossier_domiciliation' => 'Dossier domiciliation',
    ],
    'Associe' => [
        'nom_complet' => 'Nom complet',
        'cin' => 'CIN',
        'date_naiss' => 'Date naissance',
        'lieu_naiss' => 'Lieu naissance',
        'nationalite' => 'Nationalite',
        'adresse' => 'Adresse',
        'phone' => 'Telephone',
        'email' => 'Email',
        'qualite_associe' => 'Qualite',
        'parts' => 'Parts',
        'is_gerant' => 'Gerant (Oui/Non)',
    ],
    'Contrat' => [
        'type_contrat' => 'Type contrat',
        'date_contrat' => 'Date contrat',
        'duree_contrat_mois' => 'Duree (mois)',
        'date_debut' => 'Date debut',
        'date_fin' => 'Date fin',
        'loyer_mensuel_ttc' => 'Loyer mensuel TTC',
        'caution_montant' => 'Caution',
        'statut' => 'Statut',
    ],
];

$variableLabels = [
    'RAISON_SOCIALE' => ['Societe', 'raison_sociale'],
    'DENOMINATION_SOCIALE' => ['Societe', 'raison_sociale'],
    'DEN_STE' => ['Societe', 'den_ste'],
    'FORME_JURIDIQUE' => ['Societe', 'forme_juridique'],
    'FORME_JUR' => ['Societe', 'forme_juridique'],
    'ICE' => ['Societe', 'ice'],
    'NUMERO_ICE' => ['Societe', 'ice'],
    'RC' => ['Societe', 'rc'],
    'IF' => ['Societe', 'if_number'],
    'CAPITAL' => ['Societe', 'capital'],
    'CAPITAL_SOCIAL' => ['Societe', 'capital'],
    'VILLE' => ['Societe', 'ville'],
    'TRIBUNAL' => ['Societe', 'tribunal'],
    'TRIBUNAL_COMPETENT' => ['Societe', 'tribunal'],
    'ADRESSE_SIEGE_SOCIAL' => ['Societe', 'ste_adress'],
    'STE_ADRESS' => ['Societe', 'ste_adress'],
    'EMAIL' => ['Societe', 'email'],
    'TELEPHONE' => ['Societe', 'telephone'],
    'DOSSIER_DOMICILIATION' => ['Societe', 'dossier_domiciliation'],
    'NOM_COMPLET' => ['Associe', 'nom_complet'],
    'NOM_ASSOCIE' => ['Associe', 'nom_complet'],
    'PRENOM_ASSOCIE' => ['Associe', 'nom_complet'],
    'CIN' => ['Associe', 'cin'],
    'NUMERO_CIN_ASSOCIE' => ['Associe', 'cin'],
    'DATE_NAISSANCE' => ['Associe', 'date_naiss'],
    'DATE_NAISSANCE_ASSOCIE' => ['Associe', 'date_naiss'],
    'LIEU_NAISSANCE' => ['Associe', 'lieu_naiss'],
    'LIEU_NAISSANCE_ASSOCIE' => ['Associe', 'lieu_naiss'],
    'NATIONALITE' => ['Associe', 'nationalite'],
    'NATIONALITE_ASSOCIE' => ['Associe', 'nationalite'],
    'ADRESSE_ASSOCIE' => ['Associe', 'adresse'],
    'TELEPHONE_ASSOCIE' => ['Associe', 'phone'],
    'EMAIL_ASSOCIE' => ['Associe', 'email'],
    'QUALITE_ASSOCIE' => ['Associe', 'qualite_associe'],
    'NOMBRE_PARTS' => ['Associe', 'parts'],
    'NOMBRE_PARTS_ASSOCIE' => ['Associe', 'parts'],
    'EST_GERANT' => ['Associe', 'is_gerant'],
    'TYPE_CONTRAT' => ['Contrat', 'type_contrat'],
    'DUREE_CONTRAT_MOIS' => ['Contrat', 'duree_contrat_mois'],
    'DATE_DEBUT_CONTRAT' => ['Contrat', 'date_debut'],
    'DATE_FIN_CONTRAT' => ['Contrat', 'date_fin'],
    'LOYER_MENSUEL_TTC' => ['Contrat', 'loyer_mensuel_ttc'],
    'CAUTION' => ['Contrat', 'caution_montant'],
    'STATUT' => ['Contrat', 'statut'],
];

$docTypes = $templatesConfig['document_types'];
$legalForms = $templatesConfig['legal_forms'];
$aliases = $templatesConfig['variable_aliases'];
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
                <a class="btn btn-secondary" href="<?= e(app_url('templates')) ?>">Retour</a>
                <a class="btn btn-secondary" href="<?= e($templatePath) ?>" download>Telecharger</a>
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
                    <th>Alias</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($variables as $var): ?>
                    <?php
                    $upperVar = strtoupper($var);
                    $mapped = $variableLabels[$upperVar] ?? null;
                    $alias = $aliases[$upperVar] ?? ($aliases[$var] ?? null);
                    ?>
                    <tr>
                        <td><code style="color:var(--primary)">{{ <?= e($var) ?> }}</code></td>
                        <td>
                            <?php if ($mapped): ?>
                                <span style="color:var(--text-secondary);font-size:0.8rem">
                                    <?= e($mapped[0]) ?> &rarr; <?= e($mapped[1]) ?>
                                </span>
                            <?php elseif ($alias): ?>
                                <span style="color:var(--warning);font-size:0.8rem">
                                    Alias &rarr; <?= e($alias) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:var(--danger);font-size:0.8rem">Non mappe</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-secondary);font-size:0.8rem">
                            <?= $alias ? e($alias) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>
