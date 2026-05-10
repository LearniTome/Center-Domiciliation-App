<?php

declare(strict_types=1);

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingRecord = $editingId ? fetch_record($pdo ?? null, 'societes', $editingId) : null;

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
        'raison_sociale' => $raisonSociale,
        'forme_juridique' => field_value($_POST, 'forme_juridique'),
        'ice' => field_value($_POST, 'ice'),
        'rc' => field_value($_POST, 'rc'),
        'if_number' => field_value($_POST, 'if_number'),
        'adresse' => field_value($_POST, 'adresse'),
        'ville' => field_value($_POST, 'ville'),
        'email' => field_value($_POST, 'email'),
        'telephone' => field_value($_POST, 'telephone'),
        'capital' => money_value($_POST, 'capital'),
    ];

    if (!empty($_POST['id'])) {
        $payload['id'] = (int) $_POST['id'];
        $stmt = $pdo->prepare('
            UPDATE societes
            SET raison_sociale = :raison_sociale, forme_juridique = :forme_juridique, ice = :ice, rc = :rc,
                if_number = :if_number, adresse = :adresse, ville = :ville, email = :email,
                telephone = :telephone, capital = :capital
            WHERE id = :id
        ');
        $stmt->execute($payload);
        set_flash('success', 'Societe mise a jour.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO societes (raison_sociale, forme_juridique, ice, rc, if_number, adresse, ville, email, telephone, capital)
            VALUES (:raison_sociale, :forme_juridique, :ice, :rc, :if_number, :adresse, :ville, :email, :telephone, :capital)
        ');
        $stmt->execute($payload);
        set_flash('success', 'Societe ajoutee avec succes.');
    }

    redirect_to('societes');
}

$societes = fetch_all_records($pdo ?? null, 'societes');
$formData = $editingRecord ?? [
    'id' => '',
    'raison_sociale' => '',
    'forme_juridique' => '',
    'ice' => '',
    'rc' => '',
    'if_number' => '',
    'adresse' => '',
    'ville' => '',
    'email' => '',
    'telephone' => '',
    'capital' => '',
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
                    <span>Raison sociale</span>
                    <input name="raison_sociale" required value="<?= e((string) $formData['raison_sociale']) ?>">
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
                <h2>Societes enregistrees</h2>
                <p class="help-text"><?= count($societes) ?> enregistrement(s)</p>
            </div>
        </div>
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

