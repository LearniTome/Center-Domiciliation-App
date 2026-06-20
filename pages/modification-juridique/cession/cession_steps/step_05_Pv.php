<?php
declare(strict_types=1);

// Step 5: PV Cession editable — dynamic resolutions
if (is_post() && $step === 5) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($navAction === 'save') {
        $titles = $_POST['pv_title'] ?? [];
        $contents = $_POST['pv_content'] ?? [];
        $resolutions = [];
        foreach ($titles as $i => $title) {
            $title = trim((string) $title);
            $content = trim((string) ($contents[$i] ?? ''));
            if ($title !== '' || $content !== '') {
                $resolutions[] = ['title' => $title, 'content' => $content];
            }
        }
        $wizard['pv_resolutions'] = $resolutions;
        set_flash('success', 'Contenu du PV enregistre.');
        redirect_to('cession', ['step' => 5]);
    }

    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 4]);
    }
    redirect_to('cession', ['step' => 6]);
}

if ($step === 5):
$socData = $wizard['mode'] === 'existante' ? $selectedSociete : ($wizard['societe'] ?? []);
$totalParts = (int) ($socData['societe_part_social'] ?? 0);
$totalCapital = (float) ($socData['societe_capital'] ?? 0);
$valeurNominale = (float) ($socData['societe_valeur_nominale'] ?? 0);
$totalPrix = 0;
foreach ($wizard['parts'] as $p) {
    $totalPrix += (float) ($p['prix_total'] ?? 0);
}
$totalPartsCedees = 0;
foreach ($wizard['parts'] as $p) {
    $totalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
}
$firstPart = $wizard['parts'][0] ?? [];
$cedantNom = $firstPart['cedant_nom_complet'] ?? '';
$cessionnaireNom = $firstPart['cessionnaire_nom_complet'] ?? '';
$cessionnaireCivilite = $firstPart['cessionnaire_civilite'] ?? 'M.';
$cessionnaireNationalite = $firstPart['cessionnaire_nationalite'] ?? '';
$cessionnaireAdresse = $firstPart['cessionnaire_adresse'] ?? '';

$ancienGerantNom = '';
$nouveauGerantNom = '';
if (!empty($gerantsList)) {
    $ancienGerantNom = $gerantsList[0]['associe_nom_complet'] ?? '';
}
foreach ($wizard['parts'] as $p) {
    if (!empty($p['nommer_gerant']) && !empty($p['cessionnaire_nom_complet'])) {
        $nouveauGerantNom = $p['cessionnaire_nom_complet'];
    }
}

$capitalFormatted = e(number_format($totalCapital, 2, ',', ' '));
$prixTotalFormatted = e(number_format($totalPrix, 2, ',', ' '));
$vnomFormatted = e(number_format($valeurNominale, 2, ',', ' '));
$cedantNomEscaped = e($cedantNom);
$cessionnaireFull = e("$cessionnaireCivilite $cessionnaireNom");
$cessionnaireNat = e($cessionnaireNationalite ?: '-');
$cessionnaireAdr = e($cessionnaireAdresse ?: '-');
$partsRestantes = $totalParts - $totalPartsCedees;
$ancienGerantNomEscaped = e($ancienGerantNom ?: $cedantNom);
$nouveauGerantNomEscaped = e($nouveauGerantNom ?: $cessionnaireNom);

// Build default resolutions based on case metadata
$meta = $wizard['cession_metadata'] ?? [];
$isSarlAu = $meta['is_sarl_au'] ?? false;
$needsTransform = $meta['needs_transformation'] ?? false;
$gerantMap = $meta['cedants_gerant_map'] ?? [];
$newGerantIndices = $meta['new_gerant_cessionnaire_indices'] ?? [];

$hasResign = false;
$hasNominate = false;
foreach ($gerantMap as $gInfo) {
    if (!empty($gInfo['is_gerant']) && ($gInfo['action'] ?? 'stay') === 'resign') {
        $hasResign = true;
    }
}
$hasNominate = !empty($newGerantIndices);

$associeLabel = $isSarlAu ? "l'Associé Unique" : "les associés";
$associeLabelUpper = $isSarlAu ? "DE L'ASSOCIÉ UNIQUE" : "DES ASSOCIÉS";
$associeLabelFr = $isSarlAu ? "l'Associé Unique" : "les Associés";
$vDeclare = $isSarlAu ? "déclare" : "déclarent";
$vAccepte = $isSarlAu ? "accepte" : "acceptent";
$vAgree = $isSarlAu ? "agrée" : "agréent";
$vDecide = $isSarlAu ? "décide" : "décident";
$vPrend = $isSarlAu ? "prend" : "prennent";
$vReuni = $isSarlAu ? "s'est réuni" : "se sont réunis";
$vExamine = $isSarlAu ? "l'Associé Unique examine" : "les associés examinent";
$vRemercie = $isSarlAu ? "remercie" : "remercient";

$defaultResolutions = [
    ['title' => 'Cession de parts sociales', 'content' => "$associeLabel « $cedantNom », $vDeclare céder à « $cessionnaireFull », de nationalité $cessionnaireNat, demeurant à $cessionnaireAdr, $totalPartsCedees parts sociales de $vnomFormatted DH chacune, pour un montant total de $prixTotalFormatted DH.\n\n$associeLabel $vAccepte expressément cette cession et reconnaît que le prix de cession a été réglé entre les parties."],
    ['title' => "Agrément du ou des nouveaux associés", 'content' => "$associeLabel $vAgree la cession susmentionnée et accepte l'entrée du nouvel associé dans le capital social de la société."],
    ['title' => 'Modification des statuts', 'content' => "En conséquence de la cession, $associeLabel $vDecide de modifier l'article 7 des statuts relatif à la répartition du capital social, lequel sera désormais rédigé comme suit :\n\nArticle 7 — Capital Social\n\nLe capital social est fixé à la somme de $capitalFormatted DH, divisé en $totalParts parts sociales de $vnomFormatted DH chacune, réparties comme suit :\n\n- $cessionnaireFull : $totalPartsCedees parts" . ($partsRestantes > 0 ? "\n- $cedantNom : $partsRestantes parts" : '')],
];

if ($hasResign) {
    $defaultResolutions[] = ['title' => 'Démission de l\'ancien gérant', 'content' => "$associeLabel $vPrend acte de la démission de « $ancienGerantNomEscaped » de ses fonctions de gérant de la société, avec effet à compter de ce jour, et le $vRemercie pour les services rendus."];
}

if ($hasNominate) {
    $defaultResolutions[] = ['title' => 'Nomination du nouveau gérant', 'content' => "$associeLabel $vDecide de nommer « $nouveauGerantNomEscaped » en qualité de nouveau gérant de la société, pour une durée indéterminée, avec tous les pouvoirs nécessaires à l'exercice de ses fonctions."];
}

if ($needsTransform) {
    $defaultResolutions[] = ['title' => 'Transformation de la forme juridique', 'content' => "$associeLabel $vDecide de transformer la forme juridique de la société de SARL AU (SARL à Associé Unique) en SARL (Société à Responsabilité Limitée) à associés multiples, conformément aux dispositions de la loi 5-96 modifiée.\n\nEn conséquence, les statuts seront modifiés en conséquence pour tenir compte de la nouvelle forme sociale."];
}

$defaultResolutions[] = ['title' => 'Pouvoirs pour formalités', 'content' => "Tous pouvoirs sont donnés à $cedantNom, pour effectuer toutes formalités de dépôt et d'inscription modificative auprès du greffe du tribunal de commerce, ainsi que toutes autres démarches requises par la loi."];

$resolutions = $wizard['pv_resolutions'] ?? [];
if (empty($resolutions)) {
    $resolutions = $defaultResolutions;
}

$viewMode = $_GET['pv_view'] ?? 'edit';
?>
<style>
.pv-edit-form { display:flex;flex-direction:column;gap:16px; }
.pv-edit-block { border:1px solid var(--line);border-radius:6px;padding:12px;background:var(--bg-card);position:relative; }
.pv-edit-block .pv-title-input { width:100%;font-size:0.9rem;font-weight:600;padding:6px 8px;border:1px solid var(--line);border-radius:4px;margin-bottom:6px;background:var(--bg-main);color:var(--text);font-family:inherit; }
.pv-edit-block .pv-title-input:focus { outline:none;border-color:var(--primary);box-shadow:0 0 0 2px rgba(74,108,247,0.15); }
.pv-edit-block h3 { margin:0 0 6px;font-size:0.9rem;display:flex;align-items:center;gap:6px; }
.pv-edit-block h3 .step-badge { font-size:0.65rem;padding:1px 6px;border-radius:3px; }
.pv-edit-block textarea { width:100%;min-height:80px;font-family:inherit;font-size:0.82rem;padding:8px;border:1px solid var(--line);border-radius:4px;resize:vertical;background:var(--bg-main);color:var(--text); }
.pv-edit-block textarea:focus { outline:none;border-color:var(--primary);box-shadow:0 0 0 2px rgba(74,108,247,0.15); }
.pv-edit-block .pv-remove-btn { position:absolute;top:8px;right:8px; }
.pv-view-toggle { display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap; }
.pv-view-toggle .btn.active { background:rgba(74,108,247,0.12);border-color:var(--primary); }
.recap-a4 .pv-preview-block { border:1px dashed var(--line);border-radius:6px;padding:12px;margin-bottom:12px;white-space:pre-wrap; }
.recap-a4 .pv-preview-block h3 { font-size:0.85rem;margin:0 0 6px;color:var(--primary); }
.recap-a4 .pv-preview-block p { margin:4px 0; }
.recap-a4 .pv-preview-block ul, .recap-a4 .pv-preview-block ol { margin:4px 0;padding-left:1.2rem; }
</style>

<div class="stack">
    <div class="section-header">
        <h2>Procès-Verbal de l'Assemblée Générale — Contenu modifiable</h2>
    </div>

    <div class="recap-a4">
        <div class="recap-header">
            <h2>Procès-Verbal de l'Assemblée Générale Ordinaire</h2>
        </div>

        <div class="recap-section">
            <div class="recap-grid">
                <div class="item"><span class="label">Dénomination sociale</span><span class="value"><?= e($socData['societe_raison_sociale'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Forme juridique</span><span class="value"><?= e($socData['societe_forme_juridique'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Capital social</span><span class="value"><?= e(number_format($totalCapital, 2, ',', ' ')) ?> DH</span></div>
                <div class="item"><span class="label">Siège social</span><span class="value"><?= e($socData['societe_adresse_siege'] ?: '-') ?></span></div>
                <div class="item"><span class="label">RC</span><span class="value"><?= e($socData['societe_rc'] ?: '-') ?> — Tribunal de <?= e($socData['societe_ville'] ?: '-') ?></span></div>
            </div>
        </div>

        <div class="recap-section">
            <h3>PROCÈS-VERBAL DE L'ASSEMBLÉE GÉNÉRALE ORDINAIRE <?= $associeLabelUpper ?></h3>
            <div class="recap-grid">
                <div class="item"><span class="label">Date</span><span class="value"><?= e(format_date($wizard['cession_date'] ?? '')) ?></span></div>
                <div class="item"><span class="label">Lieu</span><span class="value"><?= e($socData['societe_adresse_siege'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Président de séance</span><span class="value"><?= e($cedantNom ?: '-') ?></span></div>
            </div>
        </div>

        <div class="recap-section">
            <p>L'an deux mille <?= date('Y') ?>, le <?= e(format_date($wizard['cession_date'] ?? '')) ?>, <?= $associeLabelFr ?> de la société <?= e($socData['societe_raison_sociale'] ?: '-') ?> <?= $vReuni ?> en Assemblée Générale Ordinaire au siège social.</p>
            <p>Après avoir constaté que toutes les dispositions légales et statutaires ont été respectées, <?= $vExamine ?> l'ordre du jour suivant :</p>
        </div>

        <div class="recap-section">
            <h3>ORDRE DU JOUR</h3>
            <ol id="pv-order-of-day">
                <?php foreach ($resolutions as $r): ?>
                <li><?= e($r['title'] ?: '(Sans titre)') ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>

    <div class="pv-view-toggle">
        <a class="btn <?= $viewMode === 'edit' ? 'active' : '' ?>" href="<?= e(app_url('cession', ['step' => 5, 'pv_view' => 'edit'])) ?>">
            <span class="material-symbols-outlined">edit</span> Modifier
        </a>
        <a class="btn <?= $viewMode === 'preview' ? 'active' : '' ?>" href="<?= e(app_url('cession', ['step' => 5, 'pv_view' => 'preview'])) ?>">
            <span class="material-symbols-outlined">visibility</span> Aperçu
        </a>
    </div>

    <?php if ($viewMode === 'edit'): ?>
    <form method="post" class="pv-edit-form" id="pv-edit-form">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="5">

        <div id="pv-resolution-list">
            <?php foreach ($resolutions as $i => $r): ?>
            <div class="pv-edit-block" data-index="<?= $i ?>">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="step-badge" style="background:var(--primary);color:#fff;flex-shrink:0"><?= $i + 1 ?></span>
                    <input type="text" name="pv_title[]" class="pv-title-input" value="<?= e($r['title']) ?>" placeholder="Titre de la résolution">
                    <button type="button" class="btn-icon danger pv-remove-btn" onclick="this.closest('.pv-edit-block').remove();updateOrder()" title="Supprimer">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <textarea name="pv_content[]" rows="5" placeholder="Contenu de la résolution..."><?= e($r['content']) ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="footer-actions" style="justify-content:flex-start">
            <button type="button" class="btn btn-secondary" id="pv-add-resolution">
                <span class="material-symbols-outlined">add</span> Ajouter une résolution
            </button>
            <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 5, 'pv_view' => 'preview'])) ?>">
                <span class="material-symbols-outlined">visibility</span> Voir l'aperçu
            </a>
            <button class="btn btn-info" type="submit" name="nav_action" value="save">
                <span class="material-symbols-outlined">save</span> Enregistrer
            </button>
        </div>
    </form>

    <template id="pv-resolution-template">
        <div class="pv-edit-block" data-index="__IDX__">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span class="step-badge" style="background:var(--primary);color:#fff;flex-shrink:0">__NUM__</span>
                <input type="text" name="pv_title[]" class="pv-title-input" placeholder="Titre de la résolution">
                <button type="button" class="btn-icon danger pv-remove-btn" onclick="this.closest('.pv-edit-block').remove();updateOrder()" title="Supprimer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <textarea name="pv_content[]" rows="5" placeholder="Contenu de la résolution..."></textarea>
        </div>
    </template>
    <?php endif; ?>

    <?php if ($viewMode === 'preview'): ?>
    <div class="recap-a4">
        <?php foreach ($resolutions as $i => $r): ?>
        <div class="recap-section">
            <h3><?= ($i + 1) . '. ' . e($r['title'] ?: '(Sans titre)') ?></h3>
            <?php if (trim($r['content']) !== ''): ?>
                <div class="pv-preview-block"><?= nl2br(e($r['content'])) ?></div>
            <?php else: ?>
                <p class="help-text" style="font-style:italic;color:var(--text-muted)">(Non renseigné)</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="recap-section">
            <h3>Clôture de la séance</h3>
            <p>Plus rien n'étant à l'ordre du jour, la séance est levée.</p>
            <div class="recap-grid" style="margin-top:1rem">
                <div class="item"><span class="label">Fait à</span><span class="value"><?= e($socData['societe_ville'] ?: '-') ?>, le <?= e(format_date($wizard['cession_date'] ?? '')) ?></span></div>
            </div>
            <p style="margin-top:1.5rem"><strong><?= $isSarlAu ? "L'Associé Unique" : "Les Associés" ?></strong></p>
            <p><?= e($cedantNom) ?></p>
        </div>
    </div>

    <form method="post" class="footer-actions">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="5">
        <button class="btn btn-back" type="submit" name="nav_action" value="back">
            <span class="material-symbols-outlined">arrow_back</span> Retour
        </button>
        <a class="btn btn-info" href="<?= e(app_url('cession', ['step' => 5, 'pv_view' => 'edit'])) ?>">
            <span class="material-symbols-outlined">edit</span> Modifier
        </a>
        <button class="btn btn-next" type="submit" name="nav_action" value="next">
            <span class="material-symbols-outlined">arrow_forward</span> Suivant
        </button>
        <button class="btn btn-cancel" type="button" onclick="window.print()">
            <span class="material-symbols-outlined">print</span> Imprimer
        </button>
        <a class="btn btn-cancel" href="<?= e(app_url('cessions')) ?>">
            <span class="material-symbols-outlined">close</span> Annuler
        </a>
        <a class="btn btn-back" href="<?= e(app_url('cession', ['reset' => '1'])) ?>" data-confirm="Réinitialiser l'assistant ?">
            <span class="material-symbols-outlined">restart_alt</span> Réinitialiser
        </a>
    </form>
    <?php endif; ?>
</div>

<script>
function updateOrder() {
    var blocks = document.querySelectorAll('#pv-resolution-list .pv-edit-block');
    var orderList = document.getElementById('pv-order-of-day');
    if (orderList) {
        var titles = [];
        blocks.forEach(function(b) {
            var input = b.querySelector('.pv-title-input');
            titles.push(input ? input.value : '(Sans titre)');
        });
        orderList.innerHTML = titles.map(function(t) { return '<li>' + $escapeHtml(t) + '</li>'; }).join('');
    }
    blocks.forEach(function(b, i) {
        var badge = b.querySelector('.step-badge');
        if (badge) badge.textContent = i + 1;
    });
}

function $escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

document.getElementById('pv-add-resolution')?.addEventListener('click', function() {
    var list = document.getElementById('pv-resolution-list');
    if (!list) return;
    var template = document.getElementById('pv-resolution-template');
    var html = template.innerHTML.replace(/__IDX__/g, list.children.length).replace(/__NUM__/g, list.children.length + 1);
    var div = document.createElement('div');
    div.innerHTML = html;
    list.appendChild(div.firstElementChild);
    updateOrder();
    list.lastElementChild.querySelector('input')?.focus();
});

document.querySelectorAll('#pv-resolution-list .pv-title-input').forEach(function(input) {
    input.addEventListener('input', updateOrder);
});

document.addEventListener('input', function(e) {
    if (e.target.matches('#pv-resolution-list .pv-title-input')) {
        updateOrder();
    }
});
</script>
<?php endif; ?>
