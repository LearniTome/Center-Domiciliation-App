<?php

declare(strict_types=1);

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingRecord = $editingId ? fetch_record($pdo ?? null, 'societes', $editingId) : null;
$query = search_term();
$tribunauxOptions = fetch_reference_options($pdo ?? null, 'ref_tribunaux', 'tribunal');
$adressesOptions = fetch_reference_options($pdo ?? null, 'ref_ste_adresses', 'ste_adresse');

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM societes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Societe supprimee avec succes.');
        redirect_to('societes');
    }

    $raisonSociale = field_value($_POST, 'raison_sociale');
    if ($raisonSociale === '') {
        set_flash('error', 'La raison sociale est obligatoire.');
        redirect_to('societes', $editingId ? ['edit' => $editingId] : []);
    }

    $payload = [
        'dossier_domiciliation' => field_value($_POST, 'dossier_domiciliation'),
        'raison_sociale' => $raisonSociale,
        'den_ste' => field_value($_POST, 'den_ste'),
        'forme_juridique' => field_value($_POST, 'forme_juridique'),
        'ice' => field_value($_POST, 'ice'),
        'date_ice' => field_value($_POST, 'date_ice') ?: null,
        'rc' => field_value($_POST, 'rc'),
        'if_number' => field_value($_POST, 'if_number'),
        'part_social' => int_value($_POST, 'part_social'),
        'valeur_nominale' => money_value($_POST, 'valeur_nominale'),
        'date_exp_cert_neg' => field_value($_POST, 'date_exp_cert_neg') ?: null,
        'adresse' => field_value($_POST, 'adresse'),
        'ste_adress' => field_value($_POST, 'ste_adress'),
        'ville' => field_value($_POST, 'ville'),
        'tribunal' => field_value($_POST, 'tribunal'),
        'email' => field_value($_POST, 'email'),
        'telephone' => field_value($_POST, 'telephone'),
        'capital' => money_value($_POST, 'capital'),
        'type_generation' => field_value($_POST, 'type_generation'),
        'procedure_creation' => field_value($_POST, 'procedure_creation'),
        'mode_depot_creation' => field_value($_POST, 'mode_depot_creation'),
    ];

    if (!empty($_POST['id'])) {
        $payload['id'] = (int) $_POST['id'];
        $stmt = $pdo->prepare('
            UPDATE societes
            SET dossier_domiciliation = :dossier_domiciliation, raison_sociale = :raison_sociale, den_ste = :den_ste,
                forme_juridique = :forme_juridique, forme_jur = :forme_juridique, ice = :ice, date_ice = :date_ice, rc = :rc,
                if_number = :if_number, capital = :capital, part_social = :part_social, valeur_nominale = :valeur_nominale,
                date_exp_cert_neg = :date_exp_cert_neg, adresse = :adresse, ste_adress = :ste_adress, ville = :ville,
                tribunal = :tribunal, email = :email, telephone = :telephone, type_generation = :type_generation,
                procedure_creation = :procedure_creation, mode_depot_creation = :mode_depot_creation
            WHERE id = :id
        ');
        $stmt->execute($payload);
        set_flash('success', 'Societe mise a jour.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO societes (
                dossier_domiciliation, raison_sociale, den_ste, forme_juridique, forme_jur, ice, date_ice, rc, if_number,
                capital, part_social, valeur_nominale, date_exp_cert_neg, adresse, ste_adress, ville, tribunal, email,
                telephone, type_generation, procedure_creation, mode_depot_creation
            ) VALUES (
                :dossier_domiciliation, :raison_sociale, :den_ste, :forme_juridique, :forme_juridique, :ice, :date_ice, :rc, :if_number,
                :capital, :part_social, :valeur_nominale, :date_exp_cert_neg, :adresse, :ste_adress, :ville, :tribunal, :email,
                :telephone, :type_generation, :procedure_creation, :mode_depot_creation
            )
        ');
        $stmt->execute($payload);
        set_flash('success', 'Societe ajoutee avec succes.');
    }

    redirect_to('societes');
}

if (($pdo ?? null) instanceof PDO) {
    if ($query !== '') {
        $stmt = $pdo->prepare('
            SELECT *
            FROM societes
            WHERE raison_sociale LIKE :term OR forme_juridique LIKE :term OR ice LIKE :term OR ville LIKE :term
            ORDER BY id DESC
        ');
        $stmt->execute(['term' => like_term($query)]);
        $societes = $stmt->fetchAll();
    } else {
        $societes = fetch_all_records($pdo, 'societes');
    }

    if (($_GET['export'] ?? '') === 'csv') {
        $rows = array_map(static function (array $societe): array {
            return [
                $societe['id'],
                $societe['raison_sociale'],
                $societe['dossier_domiciliation'],
                $societe['den_ste'],
                $societe['forme_juridique'],
                $societe['ice'],
                $societe['date_ice'],
                $societe['rc'],
                $societe['if_number'],
                $societe['tribunal'],
                $societe['ville'],
                $societe['email'],
                $societe['telephone'],
                $societe['capital'],
            ];
        }, $societes);

        export_csv('societes.csv', [
            'ID',
            'Raison sociale',
            'Dossier domiciliation',
            'Denomination interne',
            'Forme juridique',
            'ICE',
            'Date ICE',
            'RC',
            'IF',
            'Tribunal',
            'Ville',
            'Email',
            'Telephone',
            'Capital',
        ], $rows);
    }
} else {
    $societes = [];
}

$formData = $editingRecord ?? [
    'id' => '',
    'dossier_domiciliation' => '',
    'raison_sociale' => '',
    'den_ste' => '',
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
];
?>
<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2><?= $editingRecord ? 'Modifier une societe' : 'Nouvelle societe' ?></h2>
                <p class="help-text">Gestion du noyau societaire pour la domiciliation.</p>
            </div>
            <?php if ($editingRecord): ?>
                <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>">Annuler</a>
            <?php endif; ?>
        </div>

        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
            <div class="form-grid">
                <label class="field">
                    <span>Dossier domiciliation</span>
                    <input name="dossier_domiciliation" value="<?= e((string) $formData['dossier_domiciliation']) ?>">
                </label>
                <label class="field">
                    <span>Raison sociale</span>
                    <input name="raison_sociale" required value="<?= e((string) $formData['raison_sociale']) ?>">
                </label>
                <label class="field">
                    <span>Denomination interne</span>
                    <input name="den_ste" value="<?= e((string) $formData['den_ste']) ?>">
                </label>
                <label class="field">
                    <span>Forme juridique</span>
                    <input name="forme_juridique" value="<?= e((string) $formData['forme_juridique']) ?>">
                </label>
                <label class="field">
                    <span>ICE</span>
                    <input name="ice" value="<?= e((string) $formData['ice']) ?>">
                </label>
                <label class="field">
                    <span>Date ICE</span>
                    <input type="date" name="date_ice" value="<?= e((string) $formData['date_ice']) ?>">
                </label>
                <label class="field">
                    <span>RC</span>
                    <input name="rc" value="<?= e((string) $formData['rc']) ?>">
                </label>
                <label class="field">
                    <span>IF</span>
                    <input name="if_number" value="<?= e((string) $formData['if_number']) ?>">
                </label>
                <label class="field">
                    <span>Ville</span>
                    <input name="ville" value="<?= e((string) $formData['ville']) ?>">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e((string) $formData['email']) ?>">
                </label>
                <label class="field">
                    <span>Telephone</span>
                    <input name="telephone" value="<?= e((string) $formData['telephone']) ?>">
                </label>
                <label class="field">
                    <span>Capital</span>
                    <input type="number" step="0.01" name="capital" value="<?= e((string) $formData['capital']) ?>">
                </label>
                <label class="field">
                    <span>Part social</span>
                    <input type="number" name="part_social" value="<?= e((string) $formData['part_social']) ?>">
                </label>
                <label class="field">
                    <span>Valeur nominale</span>
                    <input type="number" step="0.01" name="valeur_nominale" value="<?= e((string) $formData['valeur_nominale']) ?>">
                </label>
                <label class="field">
                    <span>Date exp. cert. neg.</span>
                    <input type="date" name="date_exp_cert_neg" value="<?= e((string) $formData['date_exp_cert_neg']) ?>">
                </label>
                <label class="field">
                    <span>Tribunal</span>
                    <select name="tribunal">
                        <option value="">Selectionner</option>
                        <?php foreach ($tribunauxOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $formData['tribunal'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Type generation</span>
                    <input name="type_generation" value="<?= e((string) $formData['type_generation']) ?>">
                </label>
                <label class="field">
                    <span>Procedure creation</span>
                    <input name="procedure_creation" value="<?= e((string) $formData['procedure_creation']) ?>">
                </label>
                <label class="field">
                    <span>Mode depot creation</span>
                    <input name="mode_depot_creation" value="<?= e((string) $formData['mode_depot_creation']) ?>">
                </label>
                <label class="field full">
                    <span>Adresse</span>
                    <textarea name="adresse"><?= e((string) $formData['adresse']) ?></textarea>
                </label>
                <label class="field full">
                    <span>Adresse de reference</span>
                    <select name="ste_adress">
                        <option value="">Selectionner</option>
                        <?php foreach ($adressesOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string) $formData['ste_adress'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button type="submit"><?= $editingRecord ? 'Mettre a jour' : 'Enregistrer' ?></button>
        </form>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Societes enregistrees</h2>
                <p class="help-text"><?= count($societes) ?> enregistrement(s)</p>
            </div>
            <div class="table-actions">
                <a class="btn btn-secondary" href="<?= e(app_url('societes', ['export' => 'csv', 'q' => $query])) ?>">Exporter CSV</a>
            </div>
        </div>
        <form method="get" class="stack search-bar">
            <input type="hidden" name="page" value="societes">
            <div class="inline-form">
                <input
                    type="search"
                    name="q"
                    placeholder="Rechercher par societe, ICE, forme ou ville"
                    value="<?= e($query) ?>"
                >
                <button type="submit">Rechercher</button>
                <?php if ($query !== ''): ?>
                    <a class="btn btn-secondary" href="<?= e(app_url('societes')) ?>">Effacer</a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$societes): ?>
            <p class="table-empty">Aucune societe pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Raison sociale</th>
                    <th>Forme</th>
                    <th>Ville</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($societes as $societe): ?>
                    <tr>
                        <td><?= e($societe['raison_sociale']) ?></td>
                        <td><?= e($societe['forme_juridique']) ?></td>
                        <td><?= e($societe['ville']) ?></td>
                        <td><?= e($societe['telephone']) ?></td>
                        <td class="table-actions">
                            <a class="btn btn-secondary" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>">Voir</a>
                            <a class="btn btn-secondary" href="<?= e(app_url('societes', ['edit' => (int) $societe['id']])) ?>">Modifier</a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $societe['id']) ?>">
                                <button class="btn-danger" type="submit" data-confirm="Supprimer cette societe ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>
