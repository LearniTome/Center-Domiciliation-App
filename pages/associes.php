<?php

declare(strict_types=1);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRecord = $editId > 0 ? fetch_record($pdo ?? null, 'associes', $editId) : null;

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'delete';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM associes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Associe supprime avec succes.');
        redirect_to('associes');
    }

    if ($action === 'update' && $editRecord) {
        $stmt = $pdo->prepare('
            UPDATE associes SET
                civilite = :civilite, nom = :nom, prenom = :prenom, nom_complet = :nom_complet,
                cin = :cin, date_validite_cin = :date_validite_cin,
                date_naiss = :date_naiss, lieu_naiss = :lieu_naiss, nationalite = :nationalite,
                adresse = :adresse, phone = :phone, email = :email,
                qualite_associe = :qualite_associe, parts = :parts,
                capital_detenu = :capital_detenu, part_percent = :part_percent, is_gerant = :is_gerant
            WHERE id = :id
        ');
        $stmt->execute([
            'civilite' => field_value($_POST, 'civilite'),
            'nom' => field_value($_POST, 'nom'),
            'prenom' => field_value($_POST, 'prenom'),
            'nom_complet' => field_value($_POST, 'nom_complet'),
            'cin' => field_value($_POST, 'cin'),
            'date_validite_cin' => field_value($_POST, 'date_validite_cin'),
            'date_naiss' => field_value($_POST, 'date_naiss'),
            'lieu_naiss' => field_value($_POST, 'lieu_naiss'),
            'nationalite' => field_value($_POST, 'nationalite'),
            'adresse' => field_value($_POST, 'adresse'),
            'phone' => field_value($_POST, 'phone'),
            'email' => field_value($_POST, 'email'),
            'qualite_associe' => field_value($_POST, 'qualite_associe'),
            'parts' => int_value($_POST, 'parts'),
            'capital_detenu' => money_value($_POST, 'capital_detenu'),
            'part_percent' => money_value($_POST, 'part_percent'),
            'is_gerant' => (field_value($_POST, 'is_gerant') === '1') ? 1 : 0,
            'id' => $editId,
        ]);
        set_flash('success', 'Associe mis a jour.');
        redirect_to('associes');
    }
}

$associes = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT associes.*, societes.raison_sociale
        FROM associes
        INNER JOIN societes ON societes.id = associes.societe_id
        ORDER BY associes.id DESC
    ')->fetchAll()
    : [];

?>
<section>
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Associes</h2>
                <p class="help-text"><?= count($associes) ?> enregistrement(s)</p>
            </div>
            <div class="table-actions">
                <button class="btn-icon" type="button" data-col-toggle-btn title="Colonnes a afficher"><span class="mdi mdi-table-column"></span></button>
            </div>
        </div>

        <?php if ($editRecord): ?>
            <form method="post" class="stack" style="margin-bottom:1rem">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <h3>Modifier l'associe</h3>
                <div class="form-grid">
                    <label class="field">
                        <span>Civilite</span>
                        <select name="civilite">
                            <option value="">Selectionner</option>
                            <option value="Mr" <?= (string) $editRecord['civilite'] === 'Mr' ? 'selected' : '' ?>>Mr</option>
                            <option value="Mme" <?= (string) $editRecord['civilite'] === 'Mme' ? 'selected' : '' ?>>Mme</option>
                            <option value="Mlle" <?= (string) $editRecord['civilite'] === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nom</span>
                        <input name="nom" value="<?= e((string) $editRecord['nom']) ?>">
                    </label>
                    <label class="field">
                        <span>Prenom</span>
                        <input name="prenom" value="<?= e((string) $editRecord['prenom']) ?>">
                    </label>
                    <label class="field">
                        <span>Nom complet</span>
                        <input name="nom_complet" value="<?= e((string) $editRecord['nom_complet']) ?>">
                    </label>
                    <label class="field">
                        <span>CIN</span>
                        <input name="cin" value="<?= e((string) $editRecord['cin']) ?>">
                    </label>
                    <label class="field">
                        <span>Date validite CIN</span>
                        <input type="date" name="date_validite_cin" value="<?= e((string) $editRecord['date_validite_cin']) ?>">
                    </label>
                    <label class="field">
                        <span>Date naissance</span>
                        <input type="date" name="date_naiss" value="<?= e((string) $editRecord['date_naiss']) ?>">
                    </label>
                    <label class="field">
                        <span>Lieu naissance</span>
                        <input name="lieu_naiss" value="<?= e((string) $editRecord['lieu_naiss']) ?>">
                    </label>
                    <label class="field">
                        <span>Nationalite</span>
                        <input name="nationalite" value="<?= e((string) $editRecord['nationalite']) ?>">
                    </label>
                    <label class="field">
                        <span>Telephone</span>
                        <input name="phone" value="<?= e((string) $editRecord['phone']) ?>">
                    </label>
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= e((string) $editRecord['email']) ?>">
                    </label>
                    <label class="field full">
                        <span>Adresse</span>
                        <textarea name="adresse"><?= e((string) $editRecord['adresse']) ?></textarea>
                    </label>
                    <label class="field">
                        <span>Qualite associe</span>
                        <input name="qualite_associe" value="<?= e((string) $editRecord['qualite_associe']) ?>">
                    </label>
                    <label class="field">
                        <span>Parts</span>
                        <input type="number" name="parts" value="<?= e((string) $editRecord['parts']) ?>">
                    </label>
                    <label class="field">
                        <span>Capital detenu (DH)</span>
                        <input type="number" step="0.01" name="capital_detenu" value="<?= e((string) $editRecord['capital_detenu']) ?>">
                    </label>
                    <label class="field">
                        <span>% Capital social</span>
                        <input type="number" step="0.01" name="part_percent" value="<?= e((string) $editRecord['part_percent']) ?>">
                    </label>
                    <label class="field">
                        <span>Gerant</span>
                        <select name="is_gerant">
                            <option value="0" <?= (string) $editRecord['is_gerant'] === '0' ? 'selected' : '' ?>>Non</option>
                            <option value="1" <?= (string) $editRecord['is_gerant'] === '1' ? 'selected' : '' ?>>Oui</option>
                        </select>
                    </label>
                </div>
                <div class="table-actions">
                    <a class="btn btn-secondary" href="<?= e(app_url('associes')) ?>">Annuler</a>
                    <button class="btn btn-next" type="submit">Enregistrer</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if (!$associes): ?>
            <p class="table-empty">Aucun associe pour le moment.</p>
        <?php else: ?>
            <table data-col-toggle>
                <thead>
                <tr>
                    <th data-col="nom-complet">Nom complet</th>
                    <th data-col="societe">Societe</th>
                    <th data-col="cin">CIN</th>
                    <th data-col="date-naiss">Date naissance</th>
                    <th data-col="lieu-naiss">Lieu naissance</th>
                    <th data-col="nationalite">Nationalite</th>
                    <th data-col="telephone">Telephone</th>
                    <th data-col="email">Email</th>
                    <th data-col="qualite">Qualite</th>
                    <th data-col="parts">Parts</th>
                    <th data-col="gerant">Gerant</th>
                    <th data-col="creation">Creation</th>
                    <th data-col="modification">Modification</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['raison_sociale']) ?></td>
                        <td><?= e($associe['cin'] ?? '-') ?></td>
                        <td><?= e($associe['date_naiss'] ?? '-') ?></td>
                        <td><?= e($associe['lieu_naiss'] ?? '-') ?></td>
                        <td><?= e($associe['nationalite'] ?? '-') ?></td>
                        <td><?= e($associe['phone'] ?? '-') ?></td>
                        <td><?= e($associe['email'] ?? '-') ?></td>
                        <td><?= e($associe['qualite_associe'] ?? '-') ?></td>
                        <td><?= $associe['parts'] !== null ? e((string) $associe['parts']) : '-' ?></td>
                        <td><?= (int) $associe['is_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td><?= e(substr($associe['created_at'], 0, 10)) ?></td>
                        <td><?= e(substr($associe['updated_at'], 0, 10)) ?></td>
                        <td class="table-actions">
                            <a class="btn-icon" href="<?= e(app_url('associes', ['edit' => (int) $associe['id']])) ?>" title="Modifier"><span class="mdi mdi-pencil"></span></a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $associe['id']) ?>">
                                <button class="btn-icon danger" type="submit" data-confirm="Supprimer cet associe ?" title="Supprimer"><span class="mdi mdi-delete"></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </article>
</section>

