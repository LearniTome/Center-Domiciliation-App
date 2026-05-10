<?php

declare(strict_types=1);

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingRecord = $editingId ? fetch_record($pdo ?? null, 'associes', $editingId) : null;
$societesOptions = fetch_societes_options($pdo ?? null);

if (is_post() && ($pdo ?? null) instanceof PDO) {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM associes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        set_flash('success', 'Associe supprime avec succes.');
        redirect_to('associes');
    }

    $societeId = int_value($_POST, 'societe_id');
    $nomComplet = field_value($_POST, 'nom_complet');
    if (!$societeId || $nomComplet === '') {
        set_flash('error', 'La societe et le nom complet sont obligatoires.');
        redirect_to('associes', $editingId ? ['edit' => $editingId] : []);
    }

    $payload = [
        'societe_id' => $societeId,
        'nom_complet' => $nomComplet,
        'cin' => field_value($_POST, 'cin'),
        'adresse' => field_value($_POST, 'adresse'),
        'nationalite' => field_value($_POST, 'nationalite'),
        'parts' => int_value($_POST, 'parts'),
        'is_gerant' => isset($_POST['is_gerant']) && $_POST['is_gerant'] === '1' ? 1 : 0,
    ];

    if (!empty($_POST['id'])) {
        $payload['id'] = (int) $_POST['id'];
        $stmt = $pdo->prepare('
            UPDATE associes
            SET societe_id = :societe_id, nom_complet = :nom_complet, cin = :cin, adresse = :adresse,
                nationalite = :nationalite, parts = :parts, is_gerant = :is_gerant
            WHERE id = :id
        ');
        $stmt->execute($payload);
        set_flash('success', 'Associe mis a jour.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO associes (societe_id, nom_complet, cin, adresse, nationalite, parts, is_gerant)
            VALUES (:societe_id, :nom_complet, :cin, :adresse, :nationalite, :parts, :is_gerant)
        ');
        $stmt->execute($payload);
        set_flash('success', 'Associe ajoute.');
    }

    redirect_to('associes');
}

$associes = ($pdo ?? null) instanceof PDO
    ? $pdo->query('
        SELECT associes.*, societes.raison_sociale
        FROM associes
        INNER JOIN societes ON societes.id = associes.societe_id
        ORDER BY associes.id DESC
    ')->fetchAll()
    : [];

$formData = $editingRecord ?? [
    'id' => '',
    'societe_id' => '',
    'nom_complet' => '',
    'cin' => '',
    'adresse' => '',
    'nationalite' => '',
    'parts' => '',
    'is_gerant' => 0,
];
?>
<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2><?= $editingRecord ? 'Modifier un associe' : 'Nouvel associe' ?></h2>
                <p class="help-text">Association des personnes aux societes.</p>
            </div>
            <?php if ($editingRecord): ?>
                <a class="btn btn-secondary" href="<?= e(app_url('associes')) ?>">Annuler</a>
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
                    <span>Nom complet</span>
                    <input name="nom_complet" required value="<?= e((string) $formData['nom_complet']) ?>">
                </label>
                <label class="field">
                    <span>CIN</span>
                    <input name="cin" value="<?= e((string) $formData['cin']) ?>">
                </label>
                <label class="field">
                    <span>Nationalite</span>
                    <input name="nationalite" value="<?= e((string) $formData['nationalite']) ?>">
                </label>
                <label class="field">
                    <span>Parts</span>
                    <input type="number" name="parts" value="<?= e((string) $formData['parts']) ?>">
                </label>
                <label class="field">
                    <span>Gerant</span>
                    <select name="is_gerant">
                        <option value="0" <?= (int) $formData['is_gerant'] === 0 ? 'selected' : '' ?>>Non</option>
                        <option value="1" <?= (int) $formData['is_gerant'] === 1 ? 'selected' : '' ?>>Oui</option>
                    </select>
                </label>
                <label class="field full">
                    <span>Adresse</span>
                    <textarea name="adresse"><?= e((string) $formData['adresse']) ?></textarea>
                </label>
            </div>
            <button type="submit"><?= $editingRecord ? 'Mettre a jour' : 'Enregistrer' ?></button>
        </form>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Associes</h2>
                <p class="help-text"><?= count($associes) ?> enregistrement(s)</p>
            </div>
        </div>
        <?php if (!$associes): ?>
            <p class="table-empty">Aucun associe pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Societe</th>
                    <th>CIN</th>
                    <th>Gerant</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($associes as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['raison_sociale']) ?></td>
                        <td><?= e($associe['cin']) ?></td>
                        <td><?= (int) $associe['is_gerant'] === 1 ? 'Oui' : 'Non' ?></td>
                        <td class="table-actions">
                            <a class="btn btn-secondary" href="<?= e(app_url('associes', ['edit' => (int) $associe['id']])) ?>">Modifier</a>
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $associe['id']) ?>">
                                <button class="btn-danger" type="submit" data-confirm="Supprimer cet associe ?">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

