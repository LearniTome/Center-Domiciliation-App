<?php

declare(strict_types=1);

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingRecord = $editingId ? fetch_record($pdo ?? null, 'contrats', $editingId) : null;
$societesOptions = fetch_societes_options($pdo ?? null);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM contrats WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Contrat supprime avec succes.');
        redirect_to('contrats');
    }

    $societeId = int_value($_POST, 'societe_id');
    $typeContrat = field_value($_POST, 'type_contrat');
    if (!$societeId || $typeContrat === '') {
        set_flash('error', 'La societe et le type de contrat sont obligatoires.');
        redirect_to('contrats', $editingId ? ['edit' => $editingId] : []);
    }

    $payload = [
        'societe_id' => $societeId,
        'type_contrat' => $typeContrat,
        'date_debut' => field_value($_POST, 'date_debut'),
        'date_fin' => field_value($_POST, 'date_fin'),
        'loyer_mensuel_ttc' => money_value($_POST, 'loyer_mensuel_ttc'),
        'caution_montant' => money_value($_POST, 'caution_montant'),
        'statut' => field_value($_POST, 'statut', 'actif'),
        'notes' => field_value($_POST, 'notes'),
    ];

    if (!empty($_POST['id'])) {
        $payload['id'] = (int) $_POST['id'];
        $stmt = $pdo->prepare('
            UPDATE contrats
            SET societe_id = :societe_id, type_contrat = :type_contrat, date_debut = :date_debut,
                date_fin = :date_fin, loyer_mensuel_ttc = :loyer_mensuel_ttc, caution_montant = :caution_montant,
                statut = :statut, notes = :notes
            WHERE id = :id
        ');
        $stmt->execute($payload);
        set_flash('success', 'Contrat mis a jour.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO contrats (societe_id, type_contrat, date_debut, date_fin, loyer_mensuel_ttc, caution_montant, statut, notes)
            VALUES (:societe_id, :type_contrat, :date_debut, :date_fin, :loyer_mensuel_ttc, :caution_montant, :statut, :notes)
        ');
        $stmt->execute($payload);
        set_flash('success', 'Contrat ajoute.');
    }

    redirect_to('contrats');
}

$contrats = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT contrats.*, societes.raison_sociale
        FROM contrats
        INNER JOIN societes ON societes.id = contrats.societe_id
        ORDER BY contrats.id DESC
    ')->fetchAll()
    : [];

$formData = $editingRecord ?? [
    'id' => '',
    'societe_id' => '',
    'type_contrat' => '',
    'date_debut' => '',
    'date_fin' => '',
    'loyer_mensuel_ttc' => '',
    'caution_montant' => '',
    'statut' => 'actif',
    'notes' => '',
];
?>
<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2><?= $editingRecord ? 'Modifier un contrat' : 'Nouveau contrat' ?></h2>
                <p class="help-text">Gestion des contrats de domiciliation et packs associes.</p>
            </div>
            <?php if ($editingRecord): ?>
                <a class="btn btn-secondary" href="<?= e(app_url('contrats')) ?>">Annuler</a>
            <?php endif; ?>
        </div>
        <form method="post" class="stack">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= e((string) $formData['id']) ?>">
            <div class="form-grid">
                <label class="field">
                    <span>Societe</span>
                    <select name="societe_id" required>
                        <option value="">Selectionner</option>
                        <?php foreach ($societesOptions as $societe): ?>
                            <option value="<?= e((string) $societe['id']) ?>" <?= (string) $formData['societe_id'] === (string) $societe['id'] ? 'selected' : '' ?>>
                                <?= e($societe['raison_sociale']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Type de contrat</span>
                    <input name="type_contrat" required value="<?= e((string) $formData['type_contrat']) ?>">
                </label>
                <label class="field">
                    <span>Date debut</span>
                    <input type="date" name="date_debut" value="<?= e((string) $formData['date_debut']) ?>">
                </label>
                <label class="field">
                    <span>Date fin</span>
                    <input type="date" name="date_fin" value="<?= e((string) $formData['date_fin']) ?>">
                </label>
                <label class="field">
                    <span>Loyer mensuel TTC</span>
                    <input type="number" step="0.01" name="loyer_mensuel_ttc" value="<?= e((string) $formData['loyer_mensuel_ttc']) ?>">
                </label>
                <label class="field">
                    <span>Caution</span>
                    <input type="number" step="0.01" name="caution_montant" value="<?= e((string) $formData['caution_montant']) ?>">
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="statut">
                        <?php foreach (['actif', 'expire', 'brouillon'] as $statut): ?>
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
                <h2>Contrats</h2>
                <p class="help-text"><?= count($contrats) ?> enregistrement(s)</p>
            </div>
        </div>
        <?php if (!$contrats): ?>
            <p class="table-empty">Aucun contrat pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Societe</th>
                    <th>Type</th>
                    <th>Periode</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['raison_sociale']) ?></td>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><?= e(($contrat['date_debut'] ?: '-') . ' -> ' . ($contrat['date_fin'] ?: '-')) ?></td>
                        <td><?= e($contrat['statut']) ?></td>
                        <td class="table-actions">
                            <a class="btn btn-secondary" href="<?= e(app_url('contrats', ['edit' => (int) $contrat['id']])) ?>">Modifier</a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $contrat['id']) ?>">
                                <button class="btn-danger" type="submit" data-confirm="Supprimer ce contrat ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

