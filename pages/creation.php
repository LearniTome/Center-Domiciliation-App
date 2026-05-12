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
$villesOptions = fetch_reference_options($pdo ?? null, 'ref_villes', 'ville');
$nationalitesOptions = fetch_reference_options($pdo ?? null, 'ref_nationalites', 'nationalite');
$lieuxNaissanceOptions = fetch_reference_options($pdo ?? null, 'ref_lieux_naissance', 'lieu_naissance');
$qualitesAssocieOptions = fetch_reference_options($pdo ?? null, 'ref_qualites_associe', 'qualite_associe');
$formesJuridiquesOptions = fetch_reference_options($pdo ?? null, 'ref_formes_juridiques', 'forme_juridique');

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

                $civilite = trim((string) ($associe['civilite'] ?? ''));
                $nom = trim((string) ($associe['nom'] ?? ''));
                $prenom = trim((string) ($associe['prenom'] ?? ''));
                $nomComplet = trim((string) ($associe['nom_complet'] ?? ''));
                if ($nomComplet === '' && $nom !== '' && $prenom !== '') {
                    $nomComplet = $civilite !== '' ? "$civilite $prenom $nom" : "$prenom $nom";
                }

                $item = [
                    'civilite' => $civilite,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'nom_complet' => $nomComplet,
                    'cin' => trim((string) ($associe['cin'] ?? '')),
                    'date_validite_cin' => trim((string) ($associe['date_validite_cin'] ?? '')),
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
        $typeContratVal = field_value($_POST, 'type_contrat');
        $typeContratAutre = field_value($_POST, 'type_contrat_autre');
        if ($typeContratVal === 'autre' && $typeContratAutre !== '') {
            $typeContratVal = $typeContratAutre;
        }
        $contrat = [
            'type_contrat' => $typeContratVal,
            'date_contrat' => field_value($_POST, 'date_contrat'),
            'duree_contrat_mois' => field_value($_POST, 'duree_contrat_mois'),
            'type_contrat_domiciliation' => field_value($_POST, 'type_contrat_domiciliation'),
            'type_contrat_domiciliation_autre' => field_value($_POST, 'type_contrat_domiciliation_autre'),
            'date_debut' => field_value($_POST, 'date_debut'),
            'date_fin' => field_value($_POST, 'date_fin'),
            'taux_tva_pourcent' => field_value($_POST, 'taux_tva_pourcent'),
            'loyer_mensuel_ht' => field_value($_POST, 'loyer_mensuel_ht'),
            'loyer_ttc_mois' => field_value($_POST, 'loyer_ttc_mois'),
            'montant_total_loyer' => field_value($_POST, 'montant_total_loyer'),
            'type_renouvellement' => field_value($_POST, 'type_renouvellement'),
            'taux_tva_renouvellement_pourcent' => field_value($_POST, 'taux_tva_renouvellement_pourcent'),
            'loyer_mensuel_ht_renouvellement' => field_value($_POST, 'loyer_mensuel_ht_renouvellement'),
            'loyer_ttc_renouvellement_mois' => field_value($_POST, 'loyer_ttc_renouvellement_mois'),
            'montant_total_renouvellement' => field_value($_POST, 'montant_total_renouvellement'),
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
                    capital, part_social, valeur_nominale, date_exp_cert_neg, ste_adress, ville, tribunal, email,
                    telephone, type_generation, procedure_creation, mode_depot_creation
                ) VALUES (
                    :dossier_domiciliation, :raison_sociale, :den_ste, :forme_juridique, :ice, :date_ice, :rc, :if_number,
                    :capital, :part_social, :valeur_nominale, :date_exp_cert_neg, :ste_adress, :ville, :tribunal, :email,
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
                'ste_adress' => $wizard['societe']['ste_adress'] ?? '',
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
                INSERT INTO associes (societe_id, civilite, nom, prenom, nom_complet, cin, date_validite_cin, date_naiss, lieu_naiss, nationalite, adresse, phone, email, qualite_associe, parts, is_gerant)
                VALUES (:societe_id, :civilite, :nom, :prenom, :nom_complet, :cin, :date_validite_cin, :date_naiss, :lieu_naiss, :nationalite, :adresse, :phone, :email, :qualite_associe, :parts, :is_gerant)
            ');

            foreach ($wizard['associes'] as $associe) {
                $associeStmt->execute([
                    'societe_id' => $societeId,
                    'civilite' => $associe['civilite'] ?? '',
                    'nom' => $associe['nom'] ?? '',
                    'prenom' => $associe['prenom'] ?? '',
                    'nom_complet' => $associe['nom_complet'] ?? '',
                    'cin' => $associe['cin'] ?? '',
                    'date_validite_cin' => ($associe['date_validite_cin'] ?? '') !== '' ? $associe['date_validite_cin'] : null,
                    'date_naiss' => ($associe['date_naiss'] ?? '') !== '' ? $associe['date_naiss'] : null,
                    'lieu_naiss' => $associe['lieu_naiss'] ?? '',
                    'nationalite' => $associe['nationalite'] ?? '',
                    'adresse' => $associe['adresse'] ?? '',
                    'phone' => $associe['phone'] ?? '',
                    'email' => $associe['email'] ?? '',
                    'qualite_associe' => $associe['qualite_associe'] ?? '',
                    'parts' => ($associe['parts'] ?? '') !== '' ? (int) $associe['parts'] : null,
                    'is_gerant' => ((string) ($associe['is_gerant'] ?? '0') === '1') ? 1 : 0,
                ]);
            }

            $contratStmt = $pdo->prepare('
                INSERT INTO contrats (
                    societe_id, type_contrat, date_contrat, duree_contrat_mois, type_contrat_domiciliation,
                    type_contrat_domiciliation_autre, date_debut, date_fin,
                    taux_tva_pourcent, loyer_mensuel_ht, loyer_ttc_mois, montant_total_loyer,
                    type_renouvellement, taux_tva_renouvellement_pourcent, loyer_mensuel_ht_renouvellement,
                    loyer_ttc_renouvellement_mois, montant_total_renouvellement,
                    statut, notes
                ) VALUES (
                    :societe_id, :type_contrat, :date_contrat, :duree_contrat_mois, :type_contrat_domiciliation,
                    :type_contrat_domiciliation_autre, :date_debut, :date_fin,
                    :taux_tva_pourcent, :loyer_mensuel_ht, :loyer_ttc_mois, :montant_total_loyer,
                    :type_renouvellement, :taux_tva_renouvellement_pourcent, :loyer_mensuel_ht_renouvellement,
                    :loyer_ttc_renouvellement_mois, :montant_total_renouvellement,
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
                'taux_tva_pourcent' => ($wizard['contrat']['taux_tva_pourcent'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['taux_tva_pourcent']) : null,
                'loyer_mensuel_ht' => ($wizard['contrat']['loyer_mensuel_ht'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_ht']) : null,
                'loyer_ttc_mois' => ($wizard['contrat']['loyer_ttc_mois'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_ttc_mois']) : null,
                'montant_total_loyer' => ($wizard['contrat']['montant_total_loyer'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['montant_total_loyer']) : null,
                'type_renouvellement' => $wizard['contrat']['type_renouvellement'] ?? '',
                'taux_tva_renouvellement_pourcent' => ($wizard['contrat']['taux_tva_renouvellement_pourcent'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['taux_tva_renouvellement_pourcent']) : null,
                'loyer_mensuel_ht_renouvellement' => ($wizard['contrat']['loyer_mensuel_ht_renouvellement'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_mensuel_ht_renouvellement']) : null,
                'loyer_ttc_renouvellement_mois' => ($wizard['contrat']['loyer_ttc_renouvellement_mois'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['loyer_ttc_renouvellement_mois']) : null,
                'montant_total_renouvellement' => ($wizard['contrat']['montant_total_renouvellement'] ?? '') !== '' ? (float) str_replace(',', '.', (string) $wizard['contrat']['montant_total_renouvellement']) : null,
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
        'civilite' => '',
        'nom' => '',
        'prenom' => '',
        'nom_complet' => '',
        'cin' => '',
        'date_validite_cin' => '',
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
    'taux_tva_pourcent' => '20',
    'loyer_mensuel_ht' => '',
    'montant_total_ht_contrat' => '',
    'montant_pack_demarrage_ttc' => '',
    'loyer_mensuel_pack_demarrage_ttc' => '',
    'type_renouvellement' => '',
    'taux_tva_renouvellement_pourcent' => '20',
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
                <h3 class="section-title">Procedure</h3>
                <label class="field">
                    <span>Type generation</span>
                    <select name="type_generation">
                        <option value="">Selectionner</option>
                        <option value="creation" <?= (string) $societeData['type_generation'] === 'creation' ? 'selected' : '' ?>>Création</option>
                        <option value="domiciliation" <?= (string) $societeData['type_generation'] === 'domiciliation' ? 'selected' : '' ?>>Domiciliation</option>
                    </select>
                </label>
                <label class="field">
                    <span>Procedure creation</span>
                    <select name="procedure_creation">
                        <option value="">Selectionner</option>
                        <option value="normal" <?= (string) $societeData['procedure_creation'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="acceleree" <?= (string) $societeData['procedure_creation'] === 'acceleree' ? 'selected' : '' ?>>Accélérer</option>
                    </select>
                </label>
                <label class="field">
                    <span>Mode depot creation</span>
                    <select name="mode_depot_creation">
                        <option value="">Selectionner</option>
                        <option value="depot_physique" <?= (string) $societeData['mode_depot_creation'] === 'depot_physique' ? 'selected' : '' ?>>Dépôt Physique</option>
                        <option value="depot_en_ligne" <?= (string) $societeData['mode_depot_creation'] === 'depot_en_ligne' ? 'selected' : '' ?>>Dépôt En Ligne</option>
                    </select>
                </label>

                <h3 class="section-title">Identifiants</h3>
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
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="forme_juridique" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($formesJuridiquesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societeData['forme_juridique'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= e(app_url('configuration', ['tab' => 'formes-juridiques'])) ?>" target="_blank" title="Gerer les formes juridiques" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                    </div>
                </label>
                <label class="field">
                    <span>ICE</span>
                    <input name="ice" value="<?= e((string) $societeData['ice']) ?>">
                </label>
                <label class="field">
                    <span>Date de cert. negatif</span>
                    <input type="date" name="date_ice" value="<?= e((string) $societeData['date_ice']) ?>">
                </label>
                <label class="field">
                    <span>Date exp. cert. negatif</span>
                    <input type="date" name="date_exp_cert_neg" value="<?= e((string) $societeData['date_exp_cert_neg']) ?>">
                </label>
                <label class="field">
                    <span>RC</span>
                    <input name="rc" value="<?= e((string) $societeData['rc']) ?>">
                </label>
                <label class="field">
                    <span>IF</span>
                    <input name="if_number" value="<?= e((string) $societeData['if_number']) ?>">
                </label>

                <h3 class="section-title">Capital</h3>
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

                <h3 class="section-title">Adresse</h3>
                <label class="field full">
                    <span>Adresse de reference</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="ste_adress" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($adressesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societeData['ste_adress'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= e(app_url('configuration', ['tab' => 'adresses'])) ?>" target="_blank" title="Gerer les adresses" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                    </div>
                </label>
                <label class="field">
                    <span>Ville</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="ville" style="flex:1">
                            <option value="">Selectionner</option>
                            <?php foreach ($villesOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $societeData['ville'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= e(app_url('configuration', ['tab' => 'villes'])) ?>" target="_blank" title="Gerer les villes" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                    </div>
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

                <h3 class="section-title">Contact</h3>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e((string) $societeData['email']) ?>">
                </label>
                <label class="field">
                    <span>Telephone</span>
                    <input name="telephone" value="<?= e((string) $societeData['telephone']) ?>">
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
                <button class="btn-icon" type="button" data-add-associe title="Ajouter un associe"><span class="mdi mdi-plus"></span></button>
            </div>

            <div class="stack" data-associes-container>
                <?php foreach ($associesData as $index => $associe): ?>
                    <div class="associe-card" data-associe-item>
                        <div class="associe-card-header">
                            <strong data-associe-title>Associe <?= $index + 1 ?></strong>
                            <button class="btn btn-secondary" type="button" data-remove-associe>Retirer</button>
                        </div>
                        <div class="form-grid">
                            <h3 class="section-title">Identite</h3>
                            <label class="field">
                                <span>Civilite</span>
                                <select data-field-name="civilite" name="associes[<?= $index ?>][civilite]">
                                    <option value="">Selectionner</option>
                                    <option value="Mr" <?= (string) ($associe['civilite'] ?? '') === 'Mr' ? 'selected' : '' ?>>Mr</option>
                                    <option value="Mme" <?= (string) ($associe['civilite'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                                    <option value="Mlle" <?= (string) ($associe['civilite'] ?? '') === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Nom</span>
                                <input data-field-name="nom" name="associes[<?= $index ?>][nom]" value="<?= e((string) ($associe['nom'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Prenom</span>
                                <input data-field-name="prenom" name="associes[<?= $index ?>][prenom]" value="<?= e((string) ($associe['prenom'] ?? '')) ?>">
                            </label>
                            <input type="hidden" data-field-name="nom_complet" name="associes[<?= $index ?>][nom_complet]" value="<?= e((string) ($associe['nom_complet'] ?? '')) ?>">
                            <label class="field">
                                <span>N CIN/Sejour/Passport</span>
                                <input data-field-name="cin" name="associes[<?= $index ?>][cin]" value="<?= e((string) ($associe['cin'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Date validite CIN</span>
                                <input data-field-name="date_validite_cin" type="date" name="associes[<?= $index ?>][date_validite_cin]" value="<?= e((string) ($associe['date_validite_cin'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Nationalite</span>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <select data-field-name="nationalite" name="associes[<?= $index ?>][nationalite]" style="flex:1">
                                        <option value="">Selectionner</option>
                                        <?php foreach ($nationalitesOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= (string) ($associe['nationalite'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="<?= e(app_url('configuration', ['tab' => 'nationalites'])) ?>" target="_blank" title="Gerer les nationalites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                                </div>
                            </label>
                            <label class="field">
                                <span>Date naissance</span>
                                <input data-field-name="date_naiss" type="date" name="associes[<?= $index ?>][date_naiss]" value="<?= e((string) ($associe['date_naiss'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Lieu naissance</span>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <select data-field-name="lieu_naiss" name="associes[<?= $index ?>][lieu_naiss]" style="flex:1">
                                        <option value="">Selectionner</option>
                                        <?php foreach ($lieuxNaissanceOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= (string) ($associe['lieu_naiss'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="<?= e(app_url('configuration', ['tab' => 'lieux-naissance'])) ?>" target="_blank" title="Gerer les lieux de naissance" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                                </div>
                            </label>
                            <h3 class="section-title">Contact</h3>
                            <label class="field">
                                <span>Telephone</span>
                                <input data-field-name="phone" name="associes[<?= $index ?>][phone]" value="<?= e((string) ($associe['phone'] ?? '')) ?>">
                            </label>
                            <label class="field">
                                <span>Email</span>
                                <input data-field-name="email" type="email" name="associes[<?= $index ?>][email]" value="<?= e((string) ($associe['email'] ?? '')) ?>">
                            </label>
                            <label class="field full">
                                <span>Adresse</span>
                                <textarea data-field-name="adresse" name="associes[<?= $index ?>][adresse]"><?= e((string) ($associe['adresse'] ?? '')) ?></textarea>
                            </label>
                            <h3 class="section-title">Participation</h3>
                            <label class="field">
                                <span>Qualite associe</span>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <select data-field-name="qualite_associe" name="associes[<?= $index ?>][qualite_associe]" style="flex:1">
                                        <option value="">Selectionner</option>
                                        <?php foreach ($qualitesAssocieOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= (string) ($associe['qualite_associe'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="<?= e(app_url('configuration', ['tab' => 'qualites-associe'])) ?>" target="_blank" title="Gerer les qualites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                                </div>
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
                        <h3 class="section-title">Identite</h3>
                        <label class="field">
                            <span>Civilite</span>
                            <select data-field-name="civilite">
                                <option value="">Selectionner</option>
                                <option value="Mr">Mr</option>
                                <option value="Mme">Mme</option>
                                <option value="Mlle">Mlle</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Nom</span>
                            <input data-field-name="nom" value="">
                        </label>
                        <label class="field">
                            <span>Prenom</span>
                            <input data-field-name="prenom" value="">
                        </label>
                        <input type="hidden" data-field-name="nom_complet" value="">
                        <label class="field">
                            <span>N CIN/Sejour/Passport</span>
                            <input data-field-name="cin" value="">
                        </label>
                        <label class="field">
                            <span>Date validite CIN</span>
                            <input data-field-name="date_validite_cin" type="date" value="">
                        </label>
                        <label class="field">
                            <span>Nationalite</span>
                            <div style="display:flex;gap:8px;align-items:center">
                                <select data-field-name="nationalite" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($nationalitesOptions as $option): ?>
                                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= e(app_url('configuration', ['tab' => 'nationalites'])) ?>" target="_blank" title="Gerer les nationalites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                            </div>
                        </label>
                        <label class="field">
                            <span>Date naissance</span>
                            <input data-field-name="date_naiss" type="date" value="">
                        </label>
                        <label class="field">
                            <span>Lieu naissance</span>
                            <div style="display:flex;gap:8px;align-items:center">
                                <select data-field-name="lieu_naiss" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($lieuxNaissanceOptions as $option): ?>
                                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= e(app_url('configuration', ['tab' => 'lieux-naissance'])) ?>" target="_blank" title="Gerer les lieux de naissance" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                            </div>
                        </label>
                        <h3 class="section-title">Contact</h3>
                        <label class="field">
                            <span>Telephone</span>
                            <input data-field-name="phone" value="">
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input data-field-name="email" type="email" value="">
                        </label>
                        <label class="field full">
                            <span>Adresse</span>
                            <textarea data-field-name="adresse"></textarea>
                        </label>
                        <h3 class="section-title">Participation</h3>
                        <label class="field">
                            <span>Qualite associe</span>
                            <div style="display:flex;gap:8px;align-items:center">
                                <select data-field-name="qualite_associe" style="flex:1">
                                    <option value="">Selectionner</option>
                                    <?php foreach ($qualitesAssocieOptions as $option): ?>
                                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= e(app_url('configuration', ['tab' => 'qualites-associe'])) ?>" target="_blank" title="Gerer les qualites" style="color:var(--primary);text-decoration:none;font-size:1.4rem;line-height:1">&plus;</a>
                            </div>
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
                <h3 class="section-title">Type de contrat</h3>
                <label class="field">
                    <span>Type de contrat</span>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select name="type_contrat" style="flex:1" data-calc-trigger>
                            <option value="">Selectionner</option>
                            <option value="Domiciliation commerciale" <?= (string) $contratData['type_contrat'] === 'Domiciliation commerciale' ? 'selected' : '' ?>>Domiciliation commerciale</option>
                            <option value="Domiciliation professionnelle" <?= (string) $contratData['type_contrat'] === 'Domiciliation professionnelle' ? 'selected' : '' ?>>Domiciliation professionnelle</option>
                            <option value="Domiciliation simple" <?= (string) $contratData['type_contrat'] === 'Domiciliation simple' ? 'selected' : '' ?>>Domiciliation simple</option>
                            <option value="autre" <?= (string) $contratData['type_contrat'] === 'autre' ? 'selected' : '' ?>>Autre (specifier)</option>
                        </select>
                    </div>
                </label>
                <label class="field" data-show-if="type_contrat" data-show-value="autre">
                    <span>Autre type</span>
                    <input name="type_contrat_autre" value="<?= e((string) ($contratData['type_contrat_autre'] ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Date du contrat</span>
                    <input type="date" name="date_contrat" value="<?= e((string) ($contratData['date_contrat'] ?: date('Y-m-d'))) ?>">
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

                <h3 class="section-title">Periode</h3>
                <label class="field">
                    <span>Date de debut</span>
                    <input type="date" name="date_debut" data-date-debut value="<?= e((string) ($contratData['date_debut'] ?: date('Y-m-d'))) ?>">
                </label>
                <label class="field">
                    <span>Duree (mois)</span>
                    <input type="number" name="duree_contrat_mois" data-duree-mois value="<?= e((string) $contratData['duree_contrat_mois']) ?>">
                </label>
                <label class="field">
                    <span>Date de fin</span>
                    <input type="date" name="date_fin" data-date-fin value="<?= e((string) $contratData['date_fin']) ?>" readonly>
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

                <h3 class="section-title">Loyer (Initial)</h3>
                <label class="field">
                    <span>Loyer HT (Mois)</span>
                    <input type="number" step="0.01" name="loyer_mensuel_ht" data-loyer-ht value="<?= e((string) $contratData['loyer_mensuel_ht']) ?>">
                </label>
                <label class="field">
                    <span>TVA %</span>
                    <select name="taux_tva_pourcent" data-tva-pourcent>
                        <option value="">Selectionner</option>
                        <option value="7" <?= (string) $contratData['taux_tva_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                        <option value="10" <?= (string) $contratData['taux_tva_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                        <option value="14" <?= (string) $contratData['taux_tva_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                        <option value="20" <?= (string) $contratData['taux_tva_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                    </select>
                </label>
                <label class="field">
                    <span>Loyer TTC (Mois)</span>
                    <input type="number" step="0.01" name="loyer_ttc_mois" data-loyer-ttc-mois value="<?= e((string) ($contratData['loyer_ttc_mois'] ?? '')) ?>" readonly>
                </label>
                <label class="field">
                    <span>Montant Total du Loyer</span>
                    <input type="number" step="0.01" name="montant_total_loyer" data-montant-total-loyer value="<?= e((string) ($contratData['montant_total_loyer'] ?? '')) ?>" readonly>
                </label>

                <h3 class="section-title">Renouvellement</h3>
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
                    <span>Loyer HT (Mois)</span>
                    <input type="number" step="0.01" name="loyer_mensuel_ht_renouvellement" data-loyer-ht-renouvellement value="<?= e((string) ($contratData['loyer_mensuel_ht_renouvellement'] ?: '166.67')) ?>">
                </label>
                <label class="field">
                    <span>TVA %</span>
                    <select name="taux_tva_renouvellement_pourcent" data-tva-renouvellement-pourcent>
                        <option value="">Selectionner</option>
                        <option value="7" <?= (string) $contratData['taux_tva_renouvellement_pourcent'] === '7' ? 'selected' : '' ?>>7%</option>
                        <option value="10" <?= (string) $contratData['taux_tva_renouvellement_pourcent'] === '10' ? 'selected' : '' ?>>10%</option>
                        <option value="14" <?= (string) $contratData['taux_tva_renouvellement_pourcent'] === '14' ? 'selected' : '' ?>>14%</option>
                        <option value="20" <?= (string) $contratData['taux_tva_renouvellement_pourcent'] === '20' ? 'selected' : '' ?>>20%</option>
                    </select>
                </label>
                <label class="field">
                    <span>Loyer TTC (Mois)</span>
                    <input type="number" step="0.01" name="loyer_ttc_renouvellement_mois" data-loyer-ttc-renouvellement-mois value="<?= e((string) ($contratData['loyer_ttc_renouvellement_mois'] ?? '')) ?>" readonly>
                </label>
                <label class="field">
                    <span>Montant Total du Loyer</span>
                    <input type="number" step="0.01" name="montant_total_renouvellement" data-montant-total-renouvellement value="<?= e((string) ($contratData['montant_total_renouvellement'] ?? '')) ?>" readonly>
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
