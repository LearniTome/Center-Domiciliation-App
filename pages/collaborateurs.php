<?php

declare(strict_types=1);

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingRecord = $editingId ? fetch_record($pdo ?? null, 'collaborateurs', $editingId) : null;
$societesOptions = fetch_societes_options($pdo ?? null);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM collaborateurs WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Collaborateur supprime avec succes.');
        redirect_to('collaborateurs');
    }

    $nomComplet = field_value($_POST, 'nom_complet');
    if ($nomComplet === '') {
        set_flash('error', 'Le nom complet est obligatoire.');
        redirect_to('collaborateurs', $editingId ? ['edit' => $editingId] : []);
    }

    $payload = [
        'societe_id' => int_value($_POST, 'societe_id'),
        'nom_complet' => $nomComplet,
        'fonction' => field_value($_POST, 'fonction'),
        'email' => field_value($_POST, 'email'),
        'telephone' => field_value($_POST, 'telephone'),
        'date_debut' => field_value($_POST, 'date_debut'),
        'statut' => field_value($_POST, 'statut', 'actif'),
        'notes' => field_value($_POST, 'notes'),
    ];

    if (!empty($_POST['id'])) {
        $payload['id'] = (int) $_POST['id'];
        $stmt = $pdo->prepare('
            UPDATE collaborateurs
            SET societe_id = :societe_id, nom_complet = :nom_complet, fonction = :fonction, email = :email,
                telephone = :telephone, date_debut = :date_debut, statut = :statut, notes = :notes
            WHERE id = :id
        ');
        $stmt->execute($payload);
        set_flash('success', 'Collaborateur mis a jour.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO collaborateurs (societe_id, nom_complet, fonction, email, telephone, date_debut, statut, notes)
            VALUES (:societe_id, :nom_complet, :fonction, :email, :telephone, :date_debut, :statut, :notes)
        ');
        $stmt->execute($payload);
        set_flash('success', 'Collaborateur ajoute.');
    }

    redirect_to('collaborateurs');
}

$collaborateurs = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT collaborateurs.*, societes.raison_sociale
        FROM collaborateurs
        LEFT JOIN societes ON societes.id = collaborateurs.societe_id
        ORDER BY collaborateurs.id DESC
    ')->fetchAll()
    : [];

$formData = $editingRecord ?? [
    'id' => '',
    'societe_id' => '',
    'nom_complet' => '',
    'fonction' => '',
    'email' => '',
    'telephone' => '',
    'date_debut' => '',
    'statut' => 'actif',
    'notes' => '',
];
?>
<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2><?= $editingRecord ? 'Modifier un collaborateur' : 'Nouveau collaborateur' ?></h2>
                <p class="help-text">Gestion des intervenants internes et lies aux societes.</p>
            </div>
            <?php if ($editingRecord): ?>
                <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs')) ?>">Annuler</a>
            <?php endif; ?>
        </div>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
            <div class="form-grid">
                <label class="field">
                    <span>Societe</span>
                    <select name="societe_id">
                        <option value="">Aucune</option>
                        <?php foreach ($societesOptions as $societe): ?>
                            <option value="<?= e((string) $societe['id']) ?>" <?= (string) $formData['societe_id'] === (string) $societe['id'] ? 'selected' : '' ?>>
                                <?= e($societe['raison_sociale']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Nom complet</span>
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>">
                </label>
                <label class="field">
                    <span>Fonction</span>
                    <input name="fonction" value="<?= e((string) $formData['fonction']) ?>">
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
                    <span>Date debut</span>
                    <input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>">
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="statut">
                        <?php foreach (['actif', 'inactif', 'archive'] as $statut): ?>
                            <option value="<?= e($statut) ?>" <?= (string) $formData['statut'] === $statut ? 'selected' : '' ?>>
                                <?= e(ucfirst($statut)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field full">
                    <span>Notes</span>
                    <textarea name="notes"><?= e((string) $formData['notes']) ?></textarea>
                </label>
            </div>
            <button type="submit"><?= $editingRecord ? 'Mettre a jour' : 'Enregistrer' ?></button>
        </form>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Collaborateurs</h2>
                <p class="help-text"><?= count($collaborateurs) ?> enregistrement(s)</p>
            </div>
        </div>
        <?php if (!$collaborateurs): ?>
            <p class="table-empty">Aucun collaborateur pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Fonction</th>
                    <th>Societe</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($collaborateurs as $collaborateur): ?>
                    <tr>
                        <td><?= e($collaborateur['nom_complet']) ?></td>
                        <td><?= e($collaborateur['fonction']) ?></td>
                        <td><?= e($collaborateur['raison_sociale'] ?? '-') ?></td>
                        <td><?= e($collaborateur['statut']) ?></td>
                        <td class="table-actions">
                            <a class="btn btn-secondary" href="<?= e(app_url('collaborateurs', ['edit' => (int) $collaborateur['id']])) ?>">Modifier</a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $collaborateur['id']) ?>">
                                <button class="btn-danger" type="submit" data-confirm="Supprimer ce collaborateur ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

