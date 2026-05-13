<?php

declare(strict_types=1);

$pdo = $pdo ?? null;
$isConnected = $pdo instanceof PDO;

// --- Stats enrichies ---
$totalSocietes = $isConnected ? (int) $pdo->query('SELECT COUNT(*) FROM societes')->fetchColumn() : 0;
$contratsActifs = $isConnected ? (int) $pdo->query("SELECT COUNT(*) FROM contrats WHERE statut = 'actif'")->fetchColumn() : 0;
$contratsResilies = $isConnected ? (int) $pdo->query("SELECT COUNT(*) FROM contrats WHERE statut = 'resilie'")->fetchColumn() : 0;
$collaborateursCount = $isConnected ? (int) $pdo->query('SELECT COUNT(*) FROM collaborateurs')->fetchColumn() : 0;

$dossiersComplets = $isConnected
    ? (int) $pdo->query('
        SELECT COUNT(*) FROM societes s
        WHERE EXISTS (SELECT 1 FROM associes a WHERE a.societe_id = s.id)
        AND EXISTS (SELECT 1 FROM contrats c WHERE c.societe_id = s.id)
    ')->fetchColumn()
    : 0;

$dossiersIncomplets = max(0, $totalSocietes - $dossiersComplets);

// --- Alertes ---
$sansAssocie = $isConnected
    ? $pdo->query('
        SELECT s.id, s.raison_sociale FROM societes s
        LEFT JOIN associes a ON a.societe_id = s.id
        WHERE a.id IS NULL
        ORDER BY s.raison_sociale
        LIMIT 10
    ')->fetchAll()
    : [];

$sansContrat = $isConnected
    ? $pdo->query('
        SELECT s.id, s.raison_sociale FROM societes s
        LEFT JOIN contrats c ON c.societe_id = s.id
        WHERE c.id IS NULL
        ORDER BY s.raison_sociale
        LIMIT 10
    ')->fetchAll()
    : [];

$expirants = $isConnected
    ? $pdo->query('
        SELECT c.id, c.type_contrat, c.date_fin, s.raison_sociale
        FROM contrats c
        INNER JOIN societes s ON s.id = c.societe_id
        WHERE c.statut = "actif"
        AND c.date_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY c.date_fin
        LIMIT 10
    ')->fetchAll()
    : [];

$hasAlerts = $sansAssocie || $sansContrat || $expirants;

// --- Tableaux recents ---
$recentSocietes = $isConnected
    ? $pdo->query('SELECT id, raison_sociale, forme_juridique, ville FROM societes ORDER BY id DESC LIMIT 5')->fetchAll()
    : [];

$recentContrats = $isConnected
    ? $pdo->query('
        SELECT contrats.id, contrats.type_contrat, contrats.statut, societes.raison_sociale
        FROM contrats
        INNER JOIN societes ON societes.id = contrats.societe_id
        ORDER BY contrats.id DESC
        LIMIT 5
    ')->fetchAll()
    : [];

$recentAssocies = $isConnected
    ? $pdo->query('
        SELECT associes.nom_complet, associes.cin, associes.qualite_associe, associes.is_gerant, societes.raison_sociale
        FROM associes
        INNER JOIN societes ON societes.id = associes.societe_id
        ORDER BY associes.id DESC
        LIMIT 5
    ')->fetchAll()
    : [];

$recentCollaborateurs = $isConnected
    ? $pdo->query('
        SELECT nom_complet, collaborateur_type, fonction, statut
        FROM collaborateurs
        ORDER BY id DESC
        LIMIT 5
    ')->fetchAll()
    : [];

$pctComplets = $totalSocietes > 0 ? round(($dossiersComplets / $totalSocietes) * 100) : 0;
?>
<section class="card hero-card stack">
    <div class="section-header">
        <div>
            <h2>Lancer un nouveau dossier</h2>
            <p class="help-text">Flux guide en 3 etapes: societe, associes, puis contrat.</p>
        </div>
        <a class="btn btn-next" href="<?= e(app_url('creation')) ?>"><span class="mdi mdi-plus-circle"></span> Creer un dossier</a>
    </div>
    <div class="stack">
        <span class="pill">Etape 1: informations societe</span>
        <span class="pill">Etape 2: un ou plusieurs associes</span>
        <span class="pill">Etape 3: contrat et validation</span>
    </div>
</section>

<section class="stats compact">
    <article class="stat">
        <span>Societes</span>
        <strong><?= $totalSocietes ?></strong>
    </article>
    <article class="stat">
        <span>Contrats actifs</span>
        <strong><?= $contratsActifs ?></strong>
    </article>
    <article class="stat">
        <span>Contrats resilies</span>
        <strong><?= $contratsResilies ?></strong>
    </article>
    <article class="stat">
        <span>Dossiers complets</span>
        <strong><?= $dossiersComplets ?></strong>
        <?php if ($totalSocietes > 0): ?>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:<?= $pctComplets ?>%"></div></div>
        <?php endif; ?>
    </article>
    <article class="stat">
        <span>Dossiers incomplets</span>
        <strong><?= $dossiersIncomplets ?></strong>
    </article>
    <article class="stat">
        <span>Collaborateurs</span>
        <strong><?= $collaborateursCount ?></strong>
    </article>
</section>

<section class="quick-actions">
    <a class="card quick-action" href="<?= e(app_url('template_edit', ['path' => ''])) ?>">
        <span class="mdi mdi-file-document-edit quick-icon" style="color:var(--primary)"></span>
        <div>
            <strong>Editeur de template</strong>
            <span class="help-text">Modifier les documents Word</span>
        </div>
    </a>
    <a class="card quick-action" href="<?= e(app_url('generation')) ?>">
        <span class="mdi mdi-file-document-outline quick-icon" style="color:var(--success)"></span>
        <div>
            <strong>Generation de documents</strong>
            <span class="help-text">Produire les documents depuis les templates</span>
        </div>
    </a>
    <a class="card quick-action" href="<?= e(app_url('configuration')) ?>">
        <span class="mdi mdi-cog quick-icon" style="color:var(--info)"></span>
        <div>
            <strong>Configuration</strong>
            <span class="help-text">Tables de reference (formes, villes, etc.)</span>
        </div>
    </a>
</section>

<?php if ($hasAlerts): ?>
<section>
    <article class="card">
        <div class="section-header">
            <h2><span class="mdi mdi-alert" style="color:var(--warning);margin-right:6px"></span>Alertes</h2>
        </div>
        <div class="alerts-list">
            <?php if ($sansAssocie): ?>
                <div class="alert-group">
                    <span class="alert-label">Societes sans associe</span>
                    <?php foreach ($sansAssocie as $s): ?>
                        <a class="alert-item" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>">
                            <span class="mdi mdi-account-remove" style="color:var(--danger)"></span>
                            <?= e($s['raison_sociale']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($sansContrat): ?>
                <div class="alert-group">
                    <span class="alert-label">Societes sans contrat</span>
                    <?php foreach ($sansContrat as $s): ?>
                        <a class="alert-item" href="<?= e(app_url('societe', ['id' => (int) $s['id']])) ?>">
                            <span class="mdi mdi-file-remove" style="color:var(--warning)"></span>
                            <?= e($s['raison_sociale']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($expirants): ?>
                <div class="alert-group">
                    <span class="alert-label">Contrats expirant dans < 30 jours</span>
                    <?php foreach ($expirants as $c): ?>
                        <a class="alert-item" href="<?= e(app_url('contrats')) ?>">
                            <span class="mdi mdi-clock-alert" style="color:var(--warning)"></span>
                            <?= e($c['raison_sociale']) ?> — <?= e($c['type_contrat']) ?> (<?= e($c['date_fin']) ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</section>
<?php endif; ?>

<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Dernieres societes</h2>
                <p class="help-text">Acces rapide aux fiches societes creees recemment.</p>
            </div>
            <a class="btn btn-info" href="<?= e(app_url('societes')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <?php if (!$recentSocietes): ?>
            <p class="table-empty">Aucune societe disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Societe</th>
                    <th>Forme</th>
                    <th>Ville</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentSocietes as $societe): ?>
                    <tr>
                        <td><?= e($societe['raison_sociale']) ?></td>
                        <td><?= e($societe['forme_juridique']) ?></td>
                        <td><?= e($societe['ville']) ?></td>
                        <td><a class="btn-icon" href="<?= e(app_url('societe', ['id' => (int) $societe['id']])) ?>" title="Ouvrir"><span class="mdi mdi-eye"></span></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Derniers contrats</h2>
                <p class="help-text">Suivi rapide des contrats les plus recents.</p>
            </div>
            <a class="btn btn-info" href="<?= e(app_url('contrats')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <?php if (!$recentContrats): ?>
            <p class="table-empty">Aucun contrat disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Societe</th>
                    <th>Type</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentContrats as $contrat): ?>
                    <tr>
                        <td><?= e($contrat['raison_sociale']) ?></td>
                        <td><?= e($contrat['type_contrat']) ?></td>
                        <td><span class="statut-badge <?= strtolower($contrat['statut']) === 'actif' ? 'actif' : 'resilie' ?>"><?= e($contrat['statut']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>

<section class="grid two">
    <article class="card">
        <div class="section-header">
            <div>
                <h2>Derniers associes</h2>
                <p class="help-text">Acces rapide aux associes recemment ajoutes.</p>
            </div>
            <a class="btn btn-info" href="<?= e(app_url('associes')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <?php if (!$recentAssocies): ?>
            <p class="table-empty">Aucun associe disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Societe</th>
                    <th>Qualite</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentAssocies as $associe): ?>
                    <tr>
                        <td><?= e($associe['nom_complet']) ?></td>
                        <td><?= e($associe['raison_sociale']) ?></td>
                        <td><?= e($associe['qualite_associe'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-header">
            <div>
                <h2>Derniers collaborateurs</h2>
                <p class="help-text">Experts, comptables, coursiers, etc.</p>
            </div>
            <a class="btn btn-info" href="<?= e(app_url('collaborateurs')) ?>"><span class="mdi mdi-eye"></span> Voir tout</a>
        </div>
        <?php if (!$recentCollaborateurs): ?>
            <p class="table-empty">Aucun collaborateur disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentCollaborateurs as $c): ?>
                    <tr>
                        <td><?= e($c['nom_complet']) ?></td>
                        <td><?= e($c['collaborateur_type'] ?? '-') ?></td>
                        <td><span class="statut-badge <?= strtolower($c['statut']) === 'actif' ? 'actif' : 'resilie' ?>"><?= e($c['statut']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </article>
</section>
