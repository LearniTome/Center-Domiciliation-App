<?php

declare(strict_types=1);

if (!isset($_SESSION['creation_wizard']) || !is_array($_SESSION['creation_wizard'])) {
    $defaults = load_defaults();

    $associeDefaults = $defaults['associe'] ?? [];

    $_SESSION['creation_wizard'] = [
        'societe' => $defaults['societe'] ?? [],
        'associes' => [[
            'nom_complet' => '',
            'cin' => '',
            'adresse' => '',
            'date_naiss' => '',
            'lieu_naiss' => '',
            'nationalite' => $associeDefaults['nationalite'] ?? '',
            'phone' => '',
            'email' => '',
            'qualite_associe' => $associeDefaults['qualite_associe'] ?? '',
            'parts' => $associeDefaults['parts'] ?? '',
            'is_gerant' => ($associeDefaults['is_gerant'] ?? false) ? '1' : '0',
        ]],
        'contrat' => $defaults['contrat'] ?? [],
    ];
}

$wizard = &$_SESSION['creation_wizard'];
$step = max(1, min(3, (int) ($_GET['step'] ?? 1)));
$tribunauxOptions = fetch_reference_options($pdo ?? null, 'ref_tribunaux', 'tribunal');
$adressesOptions = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');
$nationalitesOptions = fetch_reference_options($pdo ?? null, 'ref_nationalites', 'nationalite');

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['creation_wizard']);
    set_flash('success', 'Assistant reinitialise.');
    redirect_to('creation');
}

if (is_post()) {
    verify_csrf();
    $postedStep = max(1, min(3, (int) ($_POST['step'] ?? $step)));
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($postedStep === 1) {
        $societe = [
            'dossier_domiciliation' => field_value($_POST, 'dossier_domiciliation'),
            'raison_sociale' => field_value($_POST, 'raison_sociale'),
            'forme_juridique' => field_value($_POST, 'forme_juridique'),
            'ice' => field_value($_POST, 'ice'),
            'date_ice' => field_value($_POST, 'date_ice'),
            'rc' => field_value($_POST, 'rc'),
            'if_number' => field_value($_POST, 'if_number'),
            'part_social' => field_value($_POST, 'part_social'),
            'valeur_nominale' => field_value($_POST, 'valeur_nominale'),
            'date_exp_cert_neg' => field_value($_POST, 'date_exp_cert_neg'),
            'adresse' => field_value($_POST, 'adresse'),
            'ste_adress' => field_value($_POST, 'ste_adress'),
            'ville' => field_value($_POST, 'ville'),
            'tribunal' => field_value($_POST, 'tribunal'),
            'email' => field_value($_POST, 'email'),
            'telephone' => field_value($_POST, 'telephone'),
            'capital' => field_value($_POST, 'capital'),
            'type_generation' => field_value($_POST, 'type_generation'),
            'procedure_creation' => field_value($_POST, 'procedure_creation'),
            'mode_depot_creation' => field_value($_POST, 'mode_depot_creation'),
        ];

        $wizard['societe'] = $societe;
        if ($societe['raison_sociale'] === '') {
            set_flash('error', 'La raison sociale est obligatoire.');
            redirect_to('creation', ['step' => 1]);
        }

        redirect_to('creation', ['step' => 2]);
    }

    if ($postedStep === 2) {
        $associes = $_POST['associes'] ?? [];
        $normalizedAssocies = [];

        if (is_array($associes)) {
            foreach ($associes as $associe) {
                if (!is_array($associe)) {
                    continue;
                }

                $item = [
                    'nom_complet' => trim((string) ($associe['nom_complet'] ?? '')),
                    'cin' => trim((string) ($associe['cin'] ?? '')),
                    'adresse' => trim((string) ($associe['adresse'] ?? '')),
                    'date_naiss' => trim((string) ($associe['date_naiss'] ?? '')),
                    'lieu_naiss' => trim((string) ($associe['lieu_naiss'] ?? '')),
                    'nationalite' => trim((string) ($associe['nationalite'] ?? '')),
                    'phone' => trim((string) ($associe['phone'] ?? '')),
                    'email' => trim((string) ($associe['email'] ?? '')),
                    'qualite_associe' => trim((string) ($associe['qualite_associe'] ?? '')),
                    'parts' => trim((string) ($associe['parts'] ?? '')),
                    'is_gerant' => ((string) ($associe['is_gerant'] ?? '0') === '1') ? '1' : '0',
                ];

                $isEmpty = $item['nom_complet'] === ''
                    && $item['cin'] === ''
                    && $item['adresse'] === ''
                    && $item['nationalite'] === ''
                    && $item['parts'] === '';

                if (!$isEmpty) {
                    $normalizedAssocies[] = $item;
                }
            }
        }

        $wizard['associes'] = count($normalizedAssocies) > 0 ? $normalizedAssocies : $wizard['associes'];

        if ($navAction === 'back') {
            redirect_to('creation', ['step' => 1]);
        }

        if (count($normalizedAssocies) === 0) {
            set_flash('error', 'Ajoutez au moins un associe.');
            redirect_to('creation', ['step' => 2]);
        }

        redirect_to('creation', ['step' => 3]);
    }

    if ($postedStep === 3) {
        $contrat = [
            'type_contrat' => field_value($_POST, 'type_contrat'),
            'date_contrat' => field_value($_POST, 'date_contrat'),
            'duree_contrat_mois' => field_value($_POST, 'duree_contrat_mois'),
            'type_contrat_domiciliation' => field_value($_POST, 'type_contrat_domiciliation'),
            'type_contrat_domiciliation_autre' => field_value($_POST, 'type_contrat_domiciliation_autre'),
            'date_debut' => field_value($_POST, 'date_debut'),
            'date_fin' => field_value($_POST, 'date_fin'),
            'loyer_mensuel_ttc' => field_value($_POST, 'loyer_mensuel_ttc'),
            'frais_intermediaire_contrat' => field_value($_POST, 'frais_intermediaire_contrat'),
            'caution_montant' => field_value($_POST, 'caution_montant'),
            'taux_tva_pourcent' => field_value($_POST, 'taux_tva_pourcent'),
            'loyer_mensuel_ht' => field_value($_POST, 'loyer_mensuel_ht'),
            'montant_total_ht_contrat' => field_value($_POST, 'montant_total_ht_contrat'),
            'montant_pack_demarrage_ttc' => field_value($_POST, 'montant_pack_demarrage_ttc'),
            'loyer_mensuel_pack_demarrage_ttc' => field_value($_POST, 'loyer_mensuel_pack_demarrage_ttc'),
            'type_renouvellement' => field_value($_POST, 'type_renouvellement'),
            'taux_tva_renouvellement_pourcent' => field_value($_POST, 'taux_tva_renouvellement_pourcent'),
            'loyer_mensuel_ht_renouvellement' => field_value($_POST, 'loyer_mensuel_ht_renouvellement'),
            'montant_total_ht_renouvellement' => field_value($_POST, 'montant_total_ht_renouvellement'),
            'loyer_mensuel_renouvellement_ttc' => field_value($_POST, 'loyer_mensuel_renouvellement_ttc'),
            'loyer_annuel_renouvellement_ttc' => field_value($_POST, 'loyer_annuel_renouvellement_ttc'),
            'statut' => field_value($_POST, 'statut', 'actif'),
            'notes' => field_value($_POST, 'notes'),
        ];

        $wizard['contrat'] = $contrat;

        if ($navAction === 'back') {
            redirect_to('creation', ['step' => 2]);
        }

        if ($contrat['type_contrat'] === '') {
            set_flash('error', 'Le type de contrat est obligatoire.');
            redirect_to('creation', ['step' => 3]);
        }

        if (!(($pdo ?? null) instanceof PDO)) {
            set_flash('error', 'Connexion MySQL indisponible.');
            redirect_to('creation', ['step' => 3]);
        }

        try {
            $pdo->beginTransaction();

            $societeStmt = $pdo->prepare('
                INSERT INTO societes (
                    dossier_domiciliation, raison_sociale, den_ste, forme_juridique, ice, date_ice, rc, if_number,
                    capital, part_social, valeur_nominale, date_exp_cert_neg, adresse, ste_adress, ville, tribunal, email,
                    telephone, type_generation, procedure_creation, mode_depot_creation
                ) VALUES (
                    :dossier_domiciliation, :raison_sociale, :den_ste, :forme_juridique, :ice, :date_ice, :rc, :if_number,
                    :capital, :part_social, :valeur_nominale, :date_exp_cert_neg, :adresse, :ste_adress, :ville, :tribunal, :email,
                    :telephone, :type_generation, :procedure_creation, :mode_depot_creation
                )
            ');
            $societeStmt->execute([
                'dossier_domiciliation' => $wizard['societe']['dossier_domiciliation'] ?? null,
                'raison_sociale' => $wizard['societe']['raison_sociale'] ?? '',
                'den_ste' => ($wizard['societe']['den_ste'] ?? '') !== '' ? $wizard['societe']['den_ste'] : ($wizard['societe']['raison_sociale'] ?? ''),
                'forme_juridique' => $wizard['societe']['forme_juridique'] ?? '',
                'ice' => $wizard['societe']['ice'] ?? '',
                'date_ice' => ($wizard['societe']['date_ice'] ?? '') !== '' ? $wizard['societe']['date_ice'] : null,
                'rc' => $wizard['societe']['rc'] ?? '',
                'if_number' => $wizard['societe']['if_number'] ?? '',
                'adresse' => $wizard['societe']['adresse'] ?? '',
                'ste_adress' => ($wizard['societe']['ste_adress'] ?? '') !== '' ? $wizard['societe']['ste_adress'] : ($wizard['societe']['adresse'] ?? ''),
                'ville' => $wizard['societe']['ville'] ?? '',
                'tribunal' => $wizard['societe']['tribunal'] ?? '',
                'email' => $wizard['societe']['email'] ?? '',
                'telephone' => $wizard['societe']['telephone'] ?? '',
                'capital' => ($wizard['societe']['capital'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['societe']['capital']) : null,
                'part_social' => ($wizard['societe']['part_social'] ?? '') !== '' ? (int) $wizard['societe']['part_social'] : null,
                'valeur_nominale' => ($wizard['societe']['valeur_nominale'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['societe']['valeur_nominale']) : null,
                'date_exp_cert_neg' => ($wizard['societe']['date_exp_cert_neg'] ?? '') !== '' ? $wizard['societe']['date_exp_cert_neg'] : null,
                'type_generation' => $wizard['societe']['type_generation'] ?? '',
                'procedure_creation' => $wizard['societe']['procedure_creation'] ?? '',
                'mode_depot_creation' => $wizard['societe']['mode_depot_creation'] ?? '',
            ]);

            $societeId = (int) $pdo->lastInsertId();

            $associeStmt = $pdo->prepare('
                INSERT INTO associes (societe_id, nom_complet, cin, adresse, nationalite, parts, is_gerant)
                VALUES (:societe_id, :nom_complet, :cin, :adresse, :nationalite, :parts, :is_gerant)
            ');

            foreach ($wizard['associes'] as $associe) {
                $associeStmt->execute([
                    'societe_id' => $societeId,
                    'nom_complet' => $associe['nom_complet'] ?? '',
                    'cin' => $associe['cin'] ?? '',
                    'adresse' => $associe['adresse'] ?? '',
                    'nationalite' => $associe['nationalite'] ?? '',
                    'parts' => ($associe['parts'] ?? '') !== '' ? (int) $associe['parts'] : null,
                    'is_gerant' => ((string) ($associe['is_gerant'] ?? '0') === '1') ? 1 : 0,
                ]);
            }

            $contratStmt = $pdo->prepare('
                INSERT INTO contrats (
                    societe_id, type_contrat, date_contrat, duree_contrat_mois, type_contrat_domiciliation,
                    type_contrat_domiciliation_autre, date_debut, date_fin,
                    loyer_mensuel_ttc, frais_intermediaire_contrat, caution_montant, taux_tva_pourcent, loyer_mensuel_ht,
                    montant_total_ht_contrat, montant_pack_demarrage_ttc, loyer_mensuel_pack_demarrage_ttc,
                    type_renouvellement, taux_tva_renouvellement_pourcent, loyer_mensuel_ht_renouvellement,
                    montant_total_ht_renouvellement, loyer_mensuel_renouvellement_ttc, loyer_annuel_renouvellement_ttc,
                    statut, notes
                ) VALUES (
                    :societe_id, :type_contrat, :date_contrat, :duree_contrat_mois, :type_contrat_domiciliation,
                    :type_contrat_domiciliation_autre, :date_debut, :date_fin,
                    :loyer_mensuel_ttc, :frais_intermediaire_contrat, :caution_montant, :taux_tva_pourcent, :loyer_mensuel_ht,
                    :montant_total_ht_contrat, :montant_pack_demarrage_ttc, :loyer_mensuel_pack_demarrage_ttc,
                    :type_renouvellement, :taux_tva_renouvellement_pourcent, :loyer_mensuel_ht_renouvellement,
                    :montant_total_ht_renouvellement, :loyer_mensuel_renouvellement_ttc, :loyer_annuel_renouvellement_ttc,
                    :statut, :notes
                )
            ');
            $contratStmt->execute([
                'societe_id' => $societeId,
                'type_contrat' => $wizard['contrat']['type_contrat'] ?? '',
                'date_contrat' => ($wizard['contrat']['date_contrat'] ?? '') !== '' ? $wizard['contrat']['date_contrat'] : null,
                'duree_contrat_mois' => ($wizard['contrat']['duree_contrat_mois'] ?? '') !== '' ? (int) $wizard['contrat']['duree_contrat_mois'] : null,
                'type_contrat_domiciliation' => $wizard['contrat']['type_contrat_domiciliation'] ?? '',
                'type_contrat_domiciliation_autre' => ($wizard['contrat']['type_contrat_domiciliation_autre'] ?? '') !== '' ? $wizard['contrat']['type_contrat_domiciliation_autre'] : null,
                'date_debut' => ($wizard['contrat']['date_debut'] ?? '') !== '' ? $wizard['contrat']['date_debut'] : null,
                'date_fin' => ($wizard['contrat']['date_fin'] ?? '') !== '' ? $wizard['contrat']['date_fin'] : null,
                'loyer_mensuel_ttc' => ($wizard['contrat']['loyer_mensuel_ttc'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_ttc']) : null,
                'frais_intermediaire_contrat' => ($wizard['contrat']['frais_intermediaire_contrat'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['frais_intermediaire_contrat']) : null,
                'caution_montant' => ($wizard['contrat']['caution_montant'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['caution_montant']) : null,
                'taux_tva_pourcent' => ($wizard['contrat']['taux_tva_pourcent'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['taux_tva_pourcent']) : null,
                'loyer_mensuel_ht' => ($wizard['contrat']['loyer_mensuel_ht'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_ht']) : null,
                'montant_total_ht_contrat' => ($wizard['contrat']['montant_total_ht_contrat'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['montant_total_ht_contrat']) : null,
                'montant_pack_demarrage_ttc' => ($wizard['contrat']['montant_pack_demarrage_ttc'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['montant_pack_demarrage_ttc']) : null,
                'loyer_mensuel_pack_demarrage_ttc' => ($wizard['contrat']['loyer_mensuel_pack_demarrage_ttc'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_pack_demarrage_ttc']) : null,
                'type_renouvellement' => $wizard['contrat']['type_renouvellement'] ?? '',
                'taux_tva_renouvellement_pourcent' => ($wizard['contrat']['taux_tva_renouvellement_pourcent'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['taux_tva_renouvellement_pourcent']) : null,
                'loyer_mensuel_ht_renouvellement' => ($wizard['contrat']['loyer_mensuel_ht_renouvellement'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_ht_renouvellement']) : null,
                'montant_total_ht_renouvellement' => ($wizard['contrat']['montant_total_ht_renouvellement'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['montant_total_ht_renouvellement']) : null,
                'loyer_mensuel_renouvellement_ttc' => ($wizard['contrat']['loyer_mensuel_renouvellement_ttc'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_renouvellement_ttc']) : null,
                'loyer_annuel_renouvellement_ttc' => ($wizard['contrat']['loyer_annuel_renouvellement_ttc'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_annuel_renouvellement_ttc']) : null,
                'statut' => $wizard['contrat']['statut'] ?? 'actif',
                'notes' => $wizard['contrat']['notes'] ?? '',
            ]);

            $pdo->commit();
            unset($_SESSION['creation_wizard']);
            set_flash('success', 'Le dossier a ete cree avec succes.');
            redirect_to('societe', ['id' => $societeId]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash('error', 'Erreur lors de la creation du dossier: ' . $exception->getMessage());
            redirect_to('creation', ['step' => 3]);
        }
    }
}

$societeData = array_merge([
    'dossier_domiciliation' => '',
    'raison_sociale' => '',
    'forme_juridique' => '',
    'ice' => '',
    'date_ice' => '',
    'rc' => '',
    'if_number' => '',
    'part_social' => '',
    'valeur_nominale' => '',
    'date_exp_cert_neg' => '',
    'adresse' => '',
    'ste_adress' => '',
    'ville' => '',
    'tribunal' => '',
    'email' => '',
    'telephone' => '',
    'capital' => '',
    'type_generation' => '',
    'procedure_creation' => '',
    'mode_depot_creation' => '',
], $wizard['societe']);

$associesData = $wizard['associes'];
if (!is_array($associesData) || $associesData === []) {
    $associeDefaults = load_defaults('associe');
    $associesData = [[
        'nom_complet' => '',
        'cin' => '',
        'adresse' => '',
        'date_naiss' => '',
        'lieu_naiss' => '',
        'nationalite' => $associeDefaults['nationalite'] ?? '',
        'phone' => '',
        'email' => '',
        'qualite_associe' => $associeDefaults['qualite_associe'] ?? '',
        'parts' => $associeDefaults['parts'] ?? '',
        'is_gerant' => ($associeDefaults['is_gerant'] ?? false) ? '1' : '0',
    ]];
}

$contratData = array_merge([
    'type_contrat' => '',
    'date_contrat' => '',
    'duree_contrat_mois' => '',
    'type_contrat_domiciliation' => '',
    'type_contrat_domiciliation_autre' => '',
    'date_debut' => '',
    'date_fin' => '',
    'loyer_mensuel_ttc' => '',
    'frais_intermediaire_contrat' => '',
    'caution_montant' => '',
    'taux_tva_pourcent' => '',
    'loyer_mensuel_ht' => '',
    'montant_total_ht_contrat' => '',
    'montant_pack_demarrage_ttc' => '',
    'loyer_mensuel_pack_demarrage_ttc' => '',
    'type_renouvellement' => '',
    'taux_tva_renouvellement_pourcent' => '',
    'loyer_mensuel_ht_renouvellement' => '',
    'montant_total_ht_renouvellement' => '',
    'loyer_mensuel_renouvellement_ttc' => '',
    'loyer_annuel_renouvellement_ttc' => '',
    'statut' => 'actif',
    'notes' => '',
], $wizard['contrat']);
?>
<section class="card stack">
    <div class="section-header">
        <div>
            <h2>Assistant de creation d'un dossier</h2>
            <p class="help-text">Parcours guide: societe, associes, puis contrat, dans un seul flux.</p>
        </div>
        <a class="btn btn-secondary" href="<?= e(app_url('creation', ['reset' => '1'])) ?>" data-confirm="Reinitialiser cet assistant ?">Reinitialiser</a>
    </div>

    <div class="wizard-steps">
        <div class="wizard-step <?= $step === 1 ? 'active' : '' ?>">
            <strong>Etape 1</strong>
            <span>Societe</span>
        </div>
        <div class="wizard-step <?= $step === 2 ? 'active' : '' ?>">
            <strong>Etape 2</strong>
            <span>Associes</span>
        </div>
        <div class="wizard-step <?= $step === 3 ? 'active' : '' ?>">
            <strong>Etape 3</strong>
            <span>Contrat</span>
        </div>
    </div>

    <?php if ($step === 1): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="step" value="1">
            <div class="form-grid">
                <label class="field">
                    <span>Dossier domiciliation</span>
                    <input name="dossier_domiciliation" value="<?= e((string) $societeData['dossier_domiciliation']) ?>">
                </label>
                <label class="field">
                    <span>Raison sociale</span>
                    <input name="raison_sociale" required value="<?= e((string) $societeData['raison_sociale']) ?>">
                </label>
                <label class="field">
                    <span>Forme juridique</span>
                    <select name="forme_juridique">
                        <?php foreach (['SARL AU', 'SARL', 'Personne Physique', 'SA', 'Succurssale Etrangère', 'Succurssale Marocaine'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societeData['forme_juridique'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>ICE</span>
                    <input name="ice" value="<?= e((string) $societeData['ice']) ?>">
                </label>
                <label class="field">
                    <span>Date ICE</span>
                    <input type="date" name="date_ice" value="<?= e((string) $societeData['date_ice']) ?>">
                </label>
                <label class="field">
                    <span>RC</span>
                    <input name="rc" value="<?= e((string) $societeData['rc']) ?>">
                </label>
                <label class="field">
                    <span>IF</span>
                    <input name="if_number" value="<?= e((string) $societeData['if_number']) ?>">
                </label>
                <label class="field">
                    <span>Ville</span>
                    <input name="ville" value="<?= e((string) $societeData['ville']) ?>">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e((string) $societeData['email']) ?>">
                </label>
                <label class="field">
                    <span>Telephone</span>
                    <input name="telephone" value="<?= e((string) $societeData['telephone']) ?>">
                </label>
                <label class="field">
                    <span>Capital</span>
                    <input type="number" step="0.01" name="capital" value="<?= e((string) $societeData['capital']) ?>">
                </label>
                <label class="field">
                    <span>Part social</span>
                    <input type="number" name="part_social" value="<?= e((string) $societeData['part_social']) ?>">
                </label>
                <label class="field">
                    <span>Valeur nominale</span>
                    <input type="number" step="0.01" name="valeur_nominale" value="<?= e((string) $societeData['valeur_nominale']) ?>">
                </label>
                <label class="field">
                    <span>Date expiration certificat negatif</span>
                    <input type="date" name="date_exp_cert_neg" value="<?= e((string) $societeData['date_exp_cert_neg']) ?>">
                </label>
                <label class="field full">
                    <span>Adresse</span>
                    <textarea name="adresse"><?= e((string) $societeData['adresse']) ?></textarea>
                </label>
                <label class="field full">
                    <span>Adresse de reference</span>
                    <select name="ste_adress">
                        <option value="">Selectionner</option>
                        <?php foreach ($adressesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societeData['ste_adress'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Tribunal</span>
                    <select name="tribunal">
                        <option value="">Selectionner</option>
                        <?php foreach ($tribunauxOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $societeData['tribunal'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Type generation</span>
                    <input name="type_generation" value="<?= e((string) $societeData['type_generation']) ?>">
                </label>
                <label class="field">
                    <span>Procedure creation</span>
                    <input name="procedure_creation" value="<?= e((string) $societeData['procedure_creation']) ?>">
                </label>
                <label class="field">
                    <span>Mode depot creation</span>
                    <input name="mode_depot_creation" value="<?= e((string) $societeData['mode_depot_creation']) ?>">
                </label>
            </div>
            <div class="table-actions">
                <button class="btn btn-secondary" type="button" data-fill-test>Remplir automatiquement</button>
                <button type="submit" name="nav_action" value="next">Continuer</button>
            </div>
        </form>
    <?php elseif ($step === 2): ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="step" value="2">
            <div class="section-header">
                <div>
                    <h2>Associes de <?= e((string) ($societeData['raison_sociale'] ?: 'la societe')) ?></h2>
                    <p class="help-text">Ajoutez autant d'associes que necessaire.</p>
                </div>
                <button class="btn btn-secondary" type="button" data-add-associe>Ajouter un associe</button>
            </div>

            <div class="stack" data-associes-container>
                <?php foreach ($associesData as $index => $associe): ?>
                    <div class="associe-card" data-associe-item>
                        <div class="associe-card-header">
                            <strong data-associe-title>Associe <?= $index + 1 ?></strong>
                            <button class="btn btn-secondary" type="button" data-remove-associe>Retirer</button>
                        </div>
                        <div class="form-grid">
                            <label class="field">
                                <span>Nom complet</span>
                                <input data-field-name="nom_complet" name="associes[<?= $index ?>][nom_complet]" required value="<?= e((string) ($associe['nom_complet'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>CIN</span>
                                <input data-field-name="cin" name="associes[<?= $index ?>][cin]" value="<?= e((string) ($associe['cin'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Nationalite</span>
                                <select data-field-name="nationalite" name="associes[<?= $index ?>][nationalite]">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($nationalitesOptions as $option): ?>
                                        <option value="<?= e($option) ?>" <?= (string) ($associe['nationalite'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field">
                                <span>Date naissance</span>
                                <input data-field-name="date_naiss" type="date" name="associes[<?= $index ?>][date_naiss]" value="<?= e((string) ($associe['date_naiss'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Lieu naissance</span>
                                <input data-field-name="lieu_naiss" name="associes[<?= $index ?>][lieu_naiss]" value="<?= e((string) ($associe['lieu_naiss'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Telephone</span>
                                <input data-field-name="phone" name="associes[<?= $index ?>][phone]" value="<?= e((string) ($associe['phone'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Email</span>
                                <input data-field-name="email" type="email" name="associes[<?= $index ?>][email]" value="<?= e((string) ($associe['email'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Qualite</span>
                                <input data-field-name="qualite_associe" name="associes[<?= $index ?>][qualite_associe]" value="<?= e((string) ($associe['qualite_associe'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Parts</span>
                                <input data-field-name="parts" type="number" name="associes[<?= $index ?>][parts]" value="<?= e((string) ($associe['parts'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Gerant</span>
                                <select data-field-name="is_gerant" name="associes[<?= $index ?>][is_gerant]">
                                    <option value="0" <?= (string) ($associe['is_gerant'] ?? '0') === '0' ? 'selected' : '' ?>>Non</option>
                                    <option value="1" <?= (string) ($associe['is_gerant'] ?? '0') === '1' ? 'selected' : '' ?>>Oui</option>
                                </select>
                            </label>
                            <label class="field full">
                                <span>Adresse</span>
                                <textarea data-field-name="adresse" name="associes[<?= $index ?>][adresse]"><?= e((string) ($associe['adresse'] ?? '')) ?></textarea>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <template data-associe-template>
                <div class="associe-card" data-associe-item>
                    <div class="associe-card-header">
                        <strong data-associe-title>Associe</strong>
                        <button class="btn btn-secondary" type="button" data-remove-associe>Retirer</button>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span>Nom complet</span>
                            <input data-field-name="nom_complet" required value="">
                        </label>
                        <label class="field">
                            <span>CIN</span>
                            <input data-field-name="cin" value="">
                        </label>
                        <label class="field">
                            <span>Nationalite</span>
                            <select data-field-name="nationalite">
                                <option value="">Selectionner</option>
                                <?php foreach ($nationalitesOptions as $option): ?>
                                    <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Date naissance</span>
                            <input data-field-name="date_naiss" type="date" value="">
                        </label>
                        <label class="field">
                            <span>Lieu naissance</span>
                            <input data-field-name="lieu_naiss" value="">
                        </label>
                        <label class="field">
                            <span>Telephone</span>
                            <input data-field-name="phone" value="">
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input data-field-name="email" type="email" value="">
                        </label>
                        <label class="field">
                            <span>Qualite</span>
                            <input data-field-name="qualite_associe" value="">
                        </label>
                        <label class="field">
                            <span>Parts</span>
                            <input data-field-name="parts" type="number" value="">
                        </label>
                        <label class="field">
                            <span>Gerant</span>
                            <select data-field-name="is_gerant">
                                <option value="0" selected>Non</option>
                                <option value="1">Oui</option>
                            </select>
                        </label>
                        <label class="field full">
                            <span>Adresse</span>
                            <textarea data-field-name="adresse"></textarea>
                        </label>
                    </div>
                </div>
            </template>

            <div class="table-actions">
                <button class="btn btn-secondary" type="submit" name="nav_action" value="back">Retour</button>
                <button class="btn btn-secondary" type="button" data-fill-test>Remplir automatiquement</button>
                <button type="submit" name="nav_action" value="next">Continuer</button>
            </div>
        </form>
    <?php else: ?>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="step" value="3">
            <div class="form-grid">
                <label class="field">
                    <span>Type de contrat</span>
                    <input name="type_contrat" required value="<?= e((string) $contratData['type_contrat']) ?>">
                </label>
                <label class="field">
                    <span>Date contrat</span>
                    <input type="date" name="date_contrat" value="<?= e((string) $contratData['date_contrat']) ?>">
                </label>
                <label class="field">
                    <span>Duree (mois)</span>
                    <input type="number" name="duree_contrat_mois" value="<?= e((string) $contratData['duree_contrat_mois']) ?>">
                </label>
                <label class="field">
                    <span>Type contrat domiciliation</span>
                    <select name="type_contrat_domiciliation">
                        <option value="">Selectionner</option>
                        <?php foreach (['Personne Morale', 'Personne Physique', 'Association', 'Fondation', 'Autres'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $contratData['type_contrat_domiciliation'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Type autre</span>
                    <input name="type_contrat_domiciliation_autre" value="<?= e((string) $contratData['type_contrat_domiciliation_autre']) ?>">
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="statut">
                        <?php foreach (['actif', 'expire', 'brouillon'] as $statut): ?>
                            <option value="<?= e($statut) ?>" <?= (string) $contratData['statut'] === $statut ? 'selected' : '' ?>>
                                <?= e(ucfirst($statut)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Date debut</span>
                    <input type="date" name="date_debut" value="<?= e((string) $contratData['date_debut']) ?>">
                </label>
                <label class="field">
                    <span>Date fin</span>
                    <input type="date" name="date_fin" value="<?= e((string) $contratData['date_fin']) ?>">
                </label>
                <label class="field">
                    <span>Loyer mensuel TTC</span>
                    <input type="number" step="0.01" name="loyer_mensuel_ttc" value="<?= e((string) $contratData['loyer_mensuel_ttc']) ?>">
                </label>
                <label class="field">
                    <span>Frais intermediaire</span>
                    <input type="number" step="0.01" name="frais_intermediaire_contrat" value="<?= e((string) $contratData['frais_intermediaire_contrat']) ?>">
                </label>
                <label class="field">
                    <span>Caution</span>
                    <input type="number" step="0.01" name="caution_montant" value="<?= e((string) $contratData['caution_montant']) ?>">
                </label>
                <label class="field">
                    <span>TVA %</span>
                    <input type="number" step="0.01" name="taux_tva_pourcent" value="<?= e((string) $contratData['taux_tva_pourcent']) ?>">
                </label>
                <label class="field">
                    <span>Loyer mensuel HT</span>
                    <input type="number" step="0.01" name="loyer_mensuel_ht" value="<?= e((string) $contratData['loyer_mensuel_ht']) ?>">
                </label>
                <label class="field">
                    <span>Montant total HT</span>
                    <input type="number" step="0.01" name="montant_total_ht_contrat" value="<?= e((string) $contratData['montant_total_ht_contrat']) ?>">
                </label>
                <label class="field">
                    <span>Montant pack demarrage TTC</span>
                    <input type="number" step="0.01" name="montant_pack_demarrage_ttc" value="<?= e((string) $contratData['montant_pack_demarrage_ttc']) ?>">
                </label>
                <label class="field">
                    <span>Loyer pack demarrage TTC</span>
                    <input type="number" step="0.01" name="loyer_mensuel_pack_demarrage_ttc" value="<?= e((string) $contratData['loyer_mensuel_pack_demarrage_ttc']) ?>">
                </label>
                <label class="field">
                    <span>Type renouvellement</span>
                    <select name="type_renouvellement">
                        <option value="">Selectionner</option>
                        <?php foreach (['Mensuel', 'Trimestriel', 'Annuel', '2 ans', '3 ans', '4 ans', '5 ans'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $contratData['type_renouvellement'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>TVA renouvellement %</span>
                    <input type="number" step="0.01" name="taux_tva_renouvellement_pourcent" value="<?= e((string) $contratData['taux_tva_renouvellement_pourcent']) ?>">
                </label>
                <label class="field">
                    <span>Loyer renouvellement HT</span>
                    <input type="number" step="0.01" name="loyer_mensuel_ht_renouvellement" value="<?= e((string) $contratData['loyer_mensuel_ht_renouvellement']) ?>">
                </label>
                <label class="field">
                    <span>Montant renouvellement HT</span>
                    <input type="number" step="0.01" name="montant_total_ht_renouvellement" value="<?= e((string) $contratData['montant_total_ht_renouvellement']) ?>">
                </label>
                <label class="field">
                    <span>Loyer renouvellement TTC</span>
                    <input type="number" step="0.01" name="loyer_mensuel_renouvellement_ttc" value="<?= e((string) $contratData['loyer_mensuel_renouvellement_ttc']) ?>">
                </label>
                <label class="field">
                    <span>Loyer annuel renouvellement TTC</span>
                    <input type="number" step="0.01" name="loyer_annuel_renouvellement_ttc" value="<?= e((string) $contratData['loyer_annuel_renouvellement_ttc']) ?>">
                </label>
                <label class="field full">
                    <span>Notes</span>
                    <textarea name="notes"><?= e((string) $contratData['notes']) ?></textarea>
                </label>
            </div>
            <div class="table-actions">
                <button class="btn btn-secondary" type="submit" name="nav_action" value="back">Retour</button>
                <button class="btn btn-secondary" type="button" data-fill-test>Remplir automatiquement</button>
                <button type="submit" name="nav_action" value="finish">Creer le dossier complet</button>
            </div>
        </form>
    <?php endif; ?>
</section>
