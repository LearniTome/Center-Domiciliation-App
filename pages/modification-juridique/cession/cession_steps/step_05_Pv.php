<?php
declare(strict_types=1);

// Step 5: PV Cession editable — dynamic resolutions
if (is_post() && $step === 5) {
    verify_csrf();

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

    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'save') {
        set_flash('success', 'Contenu du PV enregistre.');
        redirect_to('cession', ['step' => 5]);
    }
    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 4]);
    }
    if ($navAction === 'reset_defaults') {
        unset($wizard['pv_resolutions']);
        set_flash('success', 'Resolutions reinitialisees aux valeurs par defaut.');
        redirect_to('cession', ['step' => 5]);
    }
    if ($navAction === 'ai_generate') {
        require_once __DIR__ . '/../../../../src/service_claude.php';
        if (!ClaudeService::isAvailable()) {
            set_flash('error', 'IA non disponible : cle API manquante.');
            redirect_to('cession', ['step' => 5]);
        }
        try {
            $soc = $wizard['mode'] === 'existante' ? $selectedSociete : ($wizard['societe'] ?? []);
            $_totalParts = (int) ($soc['societe_part_social'] ?? 0);
            $_totalCapital = (float) ($soc['societe_capital'] ?? 0);
            $_valeurNominale = (float) ($soc['societe_valeur_nominale'] ?? 0);
            $_totalPrix = 0;
            $_totalPartsCedees = 0;
            foreach ($wizard['parts'] as $p) {
                $_totalPrix += (float) ($p['prix_total'] ?? 0);
                $_totalPartsCedees += (int) ($p['parts_cedees'] ?? 0);
            }
            $_firstPart = $wizard['parts'][0] ?? [];
            $_cedantNom = $_firstPart['cedant_nom_complet'] ?? '';
            $_cessionnaireNom = $_firstPart['cessionnaire_nom_complet'] ?? '';
            $_cessionnaireCivilite = $_firstPart['cessionnaire_civilite'] ?? 'M.';
            $_cessionnaireNationalite = $_firstPart['cessionnaire_nationalite'] ?? '';
            $_cessionnaireAdresse = $_firstPart['cessionnaire_adresse'] ?? '';
            $_cessionnaireFull = "$_cessionnaireCivilite $_cessionnaireNom";
            $_meta = $wizard['cession_metadata'] ?? [];
            $_hasResign = false;
            $_hasNominate = false;
            foreach ($_meta['cedants_gerant_map'] ?? [] as $gInfo) {
                if (!empty($gInfo['is_gerant']) && ($gInfo['action'] ?? 'stay') === 'resign') $_hasResign = true;
            }
            $_hasNominate = !empty($_meta['new_gerant_cessionnaire_indices'] ?? []);
            $_needsTransform = $_meta['needs_transformation'] ?? false;
            $_nouveauGerantNom = '';
            foreach ($wizard['parts'] as $p) {
                if (!empty($p['nommer_gerant']) && !empty($p['cessionnaire_nom_complet'])) {
                    $_nouveauGerantNom = $p['cessionnaire_nom_complet'];
                }
            }
            $prompt = "Tu es un assistant juridique spécialisé en droit des sociétés marocain. Génère les résolutions d'un procès-verbal de cession de parts sociales au format JSON valide (un tableau d'objets avec les champs \"title\" et \"content\"). Les résolutions doivent être rédigées en français juridique formel, adaptées au contexte suivant :\n\n"
                . "- Raison sociale : " . ($soc['societe_raison_sociale'] ?? 'N/A') . "\n"
                . "- Forme juridique : " . ($soc['societe_forme_juridique'] ?? 'N/A') . "\n"
                . "- Capital social : " . $_totalCapital . " DH\n"
                . "- Nombre de parts : " . $_totalParts . "\n"
                . "- Valeur nominale : " . $_valeurNominale . " DH\n"
                . "- Parts cédées : " . $_totalPartsCedees . "\n"
                . "- Prix total de cession : " . $_totalPrix . " DH\n"
                . "- Cédant : " . ($_cedantNom ?: 'N/A') . "\n"
                . "- Cessionnaire : " . ($_cessionnaireFull ?: 'N/A') . "\n"
                . "- Nationalité cessionnaire : " . ($_cessionnaireNationalite ?: 'N/A') . "\n"
                . "- Adresse cessionnaire : " . ($_cessionnaireAdresse ?: 'N/A') . "\n\n"
                . ($_hasResign ? "- Démission du gérant actuel : OUI\n" : "")
                . ($_hasNominate ? "- Nomination d'un nouveau gérant : OUI (" . ($_nouveauGerantNom ?: 'N/A') . ")\n" : "")
                . ($_needsTransform ? "- Transformation SARL AU → SARL : OUI\n" : "")
                . "\nRetourne UNIQUEMENT le tableau JSON, sans balises markdown, sans commentaires.";
            $result = ClaudeService::ask($prompt);
            $json = trim($result);
            if (preg_match('/\[.*\]/s', $json, $m)) $json = $m[0];
            $parsed = json_decode($json, true);
            if (is_array($parsed) && count($parsed) > 0) {
                $wizard['pv_resolutions'] = $parsed;
                set_flash('success', count($parsed) . ' resolution(s) generees par IA.');
            } else {
                set_flash('error', 'Reponse IA invalide. Veuillez reessayer.');
            }
        } catch (Throwable $e) {
            set_flash('error', 'Erreur IA : ' . $e->getMessage());
        }
        redirect_to('cession', ['step' => 5]);
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
$pvTitleFull = $isSarlAu ? "PROCÈS-VERBAL DES DÉCISIONS DE L'ASSOCIÉ UNIQUE" : "PROCÈS-VERBAL D'ASSEMBLÉE GÉNÉRALE EXTRAORDINAIRE (AGE)";
$pvTypeLabel = $isSarlAu ? "des Décisions de l'Associé Unique" : "d'Assemblée Générale Extraordinaire (AGE)";
$reuniLieu = $isSarlAu ? "s'est réuni au siège social" : "se sont réunis en Assemblée Générale Extraordinaire au siège social";

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

// Load preconfig templates from DB
$preconfigTemplates = [];
if (($pdo ?? null) instanceof PDO) {
    try {
        $ptStmt = $pdo->query("SELECT id, title, content, category FROM pv_resolutions_templates ORDER BY sort_order ASC, title ASC");
        $preconfigTemplates = $ptStmt->fetchAll();
    } catch (PDOException) {
        $preconfigTemplates = [];
    }
}

$resolutions = $wizard['pv_resolutions'] ?? [];
if (empty($resolutions)) {
    $resolutions = $defaultResolutions;
}

$viewMode = $_GET['pv_view'] ?? 'edit';
?>
<style>
.pv-edit-form { display:flex;flex-direction:column;gap:16px; }
.pv-edit-block { padding:4px 0;position:relative; }
.pv-edit-block .pv-title-input { width:100%;font-size:0.9rem;font-weight:600;padding:6px 8px;border:none;background:transparent;color:var(--primary);font-family:inherit; }
.pv-edit-block .pv-title-input:focus { outline:none; }
.pv-edit-block .pv-title-input::placeholder { color:var(--text-muted);font-weight:400; }
.pv-edit-block textarea { width:100%;min-height:0;font-family:inherit;font-size:0.82rem;padding:8px;border:1px solid var(--line);border-radius:4px;resize:none;background:var(--bg-main);color:var(--text);overflow:hidden;box-sizing:border-box; }
.pv-edit-block textarea:focus { outline:none;border-color:var(--primary);box-shadow:0 0 0 2px rgba(74,108,247,0.15); }
.pv-edit-block .btn-icon { width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:4px;color:var(--text-muted);transition:color 0.12s,background 0.12s; }
.step-badge { display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;font-size:0.7rem;font-weight:700;border-radius:4px;padding:0 6px;background:var(--primary);color:#fff;flex-shrink:0; }
.pv-edit-block .btn-icon:hover { color:var(--primary);background:rgba(74,108,247,0.08); }
.pv-edit-block .btn-icon.danger:hover { color:var(--danger);background:rgba(252,66,74,0.08); }
.pv-view-toggle { display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap; }
.pv-view-toggle .btn.active { background:rgba(74,108,247,0.12);border-color:var(--primary); }
.recap-a4 .pv-preview-block { border:1px dashed var(--line);border-radius:6px;padding:12px;margin-bottom:12px;white-space:pre-wrap; }
.recap-a4 .pv-preview-block h3 { font-size:0.85rem;margin:0 0 6px;color:var(--primary); }
.recap-a4 .pv-preview-block p { margin:4px 0; }
.recap-a4 .pv-preview-block ul, .recap-a4 .pv-preview-block ol { margin:4px 0;padding-left:1.2rem; }
.recap-section h3 { font-size:0.92rem;font-weight:700;color:var(--primary);margin:0 0 8px; }
.recap-header h2 { font-size:1.05rem;font-weight:700;color:var(--primary);text-transform:uppercase; }
#pv-order-of-day li::marker { color:var(--primary);font-weight:600; }
.pv-preconfig-wrap { position:relative;display:inline-block; }
.pv-preconfig-menu { display:none;position:absolute;top:100%;left:0;min-width:320px;max-height:360px;overflow-y:auto;background:var(--bg-secondary);border:1px solid var(--line);border-radius:6px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:100;padding:4px 0; }
.pv-preconfig-menu::-webkit-scrollbar { width:6px; }
.pv-preconfig-menu::-webkit-scrollbar-track { background:transparent; }
.pv-preconfig-menu::-webkit-scrollbar-thumb { background:var(--line);border-radius:2px; }
.pv-preconfig-menu::-webkit-scrollbar-thumb:hover { background:var(--text-muted); }
.pv-preconfig-menu a { display:block;padding:8px 14px;color:var(--text);text-decoration:none;font-size:0.85rem;border-bottom:1px solid var(--line);cursor:pointer; }
.pv-preconfig-menu a:last-child { border-bottom:none; }
.pv-preconfig-menu a:hover { background:rgba(74,108,247,0.08); }
.pv-preconfig-menu .pv-preconfig-cat { padding:6px 14px 2px;font-size:0.7rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.04em;border-bottom:1px solid var(--line);background:var(--bg-secondary);position:sticky;top:0; }
</style>

<div class="stack">
    <div class="recap-a4">
        <div class="recap-header">
            <h2><?= $pvTypeLabel ?></h2>
        </div>

        <div class="recap-section">
            <div class="recap-grid">
                <div class="item"><span class="label">Dénomination sociale</span><span class="value"><?= e($socData['societe_raison_sociale'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Forme juridique</span><span class="value"><?= e($socData['societe_forme_juridique'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Capital social</span><span class="value"><?= e(number_format($totalCapital, 2, ',', ' ')) ?> DH</span></div>
                <div class="item"><span class="label">Siège social</span><span class="value"><?= e($socData['societe_adresse_siege'] ?: $socData['societe_ville'] ?: '-') ?></span></div>
                <div class="item"><span class="label">RC</span><span class="value"><?= e($socData['societe_rc'] ?: '-') ?> — Tribunal de <?= e($socData['societe_ville'] ?: '-') ?></span></div>
            </div>
        </div>

        <div class="recap-section">
            <h3><?= $pvTitleFull ?></h3>
            <div class="recap-grid">
                <div class="item"><span class="label">Date</span><span class="value"><?= e(format_date($wizard['cession_date'] ?: date('Y-m-d'))) ?></span></div>
                <div class="item"><span class="label">Lieu</span><span class="value"><?= e($socData['societe_ville'] ?: $socData['societe_adresse_siege'] ?: '-') ?></span></div>
                <div class="item"><span class="label">Président de séance</span><span class="value"><?= e($cedantNom ?: '-') ?></span></div>
            </div>
        </div>

        <div class="recap-section">
            <p>L'an deux mille <?= date('Y') ?>, le <?= e(format_date($wizard['cession_date'] ?: date('Y-m-d'))) ?>, <?= $associeLabelFr ?> de la société <?= e($socData['societe_raison_sociale'] ?: '-') ?> <?= $reuniLieu ?>.</p>
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
                    <span class="step-badge"><?= $i + 1 ?></span>
                    <input type="text" name="pv_title[]" class="pv-title-input" value="<?= e($r['title']) ?>" placeholder="Titre de la résolution">
                    <div style="display:flex;gap:2px;margin-left:auto">
                        <button type="button" class="btn-icon pv-move-up" onclick="moveResolution(this, -1)" title="Monter">
                            <span class="material-symbols-outlined">arrow_upward</span>
                        </button>
                        <button type="button" class="btn-icon pv-move-down" onclick="moveResolution(this, 1)" title="Descendre">
                            <span class="material-symbols-outlined">arrow_downward</span>
                        </button>
                        <button type="button" class="btn-icon danger pv-remove-btn" onclick="this.closest('.pv-edit-block').remove();updateOrder()" title="Supprimer">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>
                <textarea name="pv_content[]" placeholder="Contenu de la résolution..." oninput="autoResize(this)"><?= e($r['content']) ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="footer-actions" style="justify-content:space-between;flex-wrap:wrap;gap:8px">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" class="btn btn-secondary" id="pv-add-resolution">
                    <span class="material-symbols-outlined">add</span> Ajouter
                </button>
                <div class="pv-preconfig-wrap">
                    <button type="button" class="btn btn-info" id="pv-preconfig-btn">
                        <span class="material-symbols-outlined">playlist_add</span> Préconfiguré <span style="font-size:0.7em">▾</span>
                    </button>
                    <div class="pv-preconfig-menu" id="pv-preconfig-menu"></div>
                </div>
                <button type="button" class="btn btn-secondary" id="pv-add-ai-resolution" title="Ajouter une résolution générée par IA">
                    <span class="material-symbols-outlined">auto_awesome</span> Ajouter avec IA
                </button>
                <a class="btn btn-info" href="<?= e(app_url('cession', ['step' => 5, 'pv_view' => 'preview'])) ?>">
                    <span class="material-symbols-outlined">visibility</span> Aperçu
                </a>
                <button class="btn btn-info" type="submit" name="nav_action" value="save">
                    <span class="material-symbols-outlined">save</span> Enregistrer
                </button>
                <button class="btn btn-cancel" type="submit" name="nav_action" value="reset_defaults" data-confirm="Réinitialiser toutes les résolutions ?">
                    <span class="material-symbols-outlined">restart_alt</span> Réinitialiser
                </button>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn btn-back" type="submit" name="nav_action" value="back">
                    <span class="material-symbols-outlined">arrow_back</span> Retour
                </button>
                <button class="btn btn-next" type="submit" name="nav_action" value="next">
                    <span class="material-symbols-outlined">arrow_forward</span> Suivant
                </button>
            </div>
        </div>
    </form>

    <script>
    window._pvPreconfig = <?= json_encode($preconfigTemplates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>

    <template id="pv-resolution-template">
        <div class="pv-edit-block" data-index="__IDX__">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span class="step-badge">__NUM__</span>
                <input type="text" name="pv_title[]" class="pv-title-input" placeholder="Titre de la résolution">
                <div style="display:flex;gap:2px;margin-left:auto">
                    <button type="button" class="btn-icon pv-move-up" onclick="moveResolution(this, -1)" title="Monter">
                        <span class="material-symbols-outlined">arrow_upward</span>
                    </button>
                    <button type="button" class="btn-icon pv-move-down" onclick="moveResolution(this, 1)" title="Descendre">
                        <span class="material-symbols-outlined">arrow_downward</span>
                    </button>
                    <button type="button" class="btn-icon danger pv-remove-btn" onclick="this.closest('.pv-edit-block').remove();updateOrder()" title="Supprimer">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>
            <textarea name="pv_content[]" placeholder="Contenu de la résolution..." oninput="autoResize(this)"></textarea>
        </div>
    </template>
    <?php endif; ?>

    <?php if ($viewMode === 'preview'): ?>
    <div class="recap-a4">
        <?php foreach ($resolutions as $i => $r): ?>
        <div class="recap-section">
            <h3><?= e($r['title'] ?: '(Sans titre)') ?></h3>
            <?php if (trim($r['content']) !== ''): ?>
                <div class="pv-preview-block"><?= nl2br(preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($r['content']))) ?></div>
            <?php else: ?>
                <p class="help-text" style="font-style:italic;color:var(--text-muted)">(Non renseigné)</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="recap-section">
            <h3>Clôture de la séance</h3>
            <p>Plus rien n'étant à l'ordre du jour, la séance est levée.</p>
            <div class="recap-grid" style="margin-top:1rem">
                <div class="item"><span class="label">Fait à</span><span class="value"><?= e($socData['societe_ville'] ?: '-') ?>, le <?= e(format_date($wizard['cession_date'] ?: date('Y-m-d'))) ?></span></div>
            </div>
            <p style="margin-top:1.5rem"><strong><?= $isSarlAu ? "L'Associé Unique" : "Les Associés" ?></strong></p>
            <p><?= e($cedantNom) ?></p>
        </div>
    </div>

    <form method="post" class="footer-actions">
        <?= csrf_input() ?>
        <input type="hidden" name="step" value="5">
        <div style="display:flex;gap:8px;margin-left:auto">
            <button class="btn btn-back" type="submit" name="nav_action" value="back">
                <span class="material-symbols-outlined">arrow_back</span> Retour
            </button>
            <a class="btn btn-info" href="<?= e(app_url('cession', ['step' => 5, 'pv_view' => 'edit'])) ?>">
                <span class="material-symbols-outlined">edit</span> Modifier
            </a>
            <button class="btn btn-next" type="submit" name="nav_action" value="next">
                <span class="material-symbols-outlined">arrow_forward</span> Suivant
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

document.querySelectorAll('.pv-edit-block textarea').forEach(autoResize);

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
        b.dataset.index = i;
    });
}

function $escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function moveResolution(btn, direction) {
    var block = btn.closest('.pv-edit-block');
    if (!block) return;
    var list = document.getElementById('pv-resolution-list');
    var sibling = direction === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (!sibling || !sibling.matches('.pv-edit-block')) return;
    if (direction === -1) {
        list.insertBefore(block, sibling);
    } else {
        list.insertBefore(sibling, block);
    }
    updateOrder();
}

function $createResolutionBlock(title, content) {
    var list = document.getElementById('pv-resolution-list');
    if (!list) return;
    var template = document.getElementById('pv-resolution-template');
    var html = template.innerHTML.replace(/__IDX__/g, list.children.length).replace(/__NUM__/g, list.children.length + 1);
    var div = document.createElement('div');
    div.innerHTML = html;
    var block = div.firstElementChild;
    block.querySelector('.pv-title-input').value = title || '';
    var ta = block.querySelector('textarea');
    ta.value = content || '';
    list.appendChild(block);
    autoResize(ta);
    updateOrder();
    return block;
}

document.getElementById('pv-add-resolution')?.addEventListener('click', function() {
    $createResolutionBlock('', '');
    var list = document.getElementById('pv-resolution-list');
    list.lastElementChild.querySelector('input')?.focus();
});

document.getElementById('pv-add-ai-resolution')?.addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Génération...';
    <?php if (ClaudeService::isAvailable()): ?>
    fetch('<?= e(app_url('cession', ['step' => 5])) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            'nav_action': 'ai_generate',
            'csrf_token': '<?= e($_SESSION['csrf_token'] ?? '') ?>'
        })
    }).then(function(r) { return r.text(); }).then(function() {
        window.location.reload();
    }).catch(function() {
        window.location.reload();
    });
    <?php else: ?>
    btn.innerHTML = '<span class="material-symbols-outlined">auto_awesome</span> Ajouter avec IA';
    btn.disabled = false;
    alert('IA non disponible : cle API manquante.');
    <?php endif; ?>
});

document.querySelectorAll('#pv-resolution-list .pv-title-input').forEach(function(input) {
    input.addEventListener('input', updateOrder);
});

document.addEventListener('input', function(e) {
    if (e.target.matches('#pv-resolution-list .pv-title-input')) {
        updateOrder();
    }
    if (e.target.matches('#pv-resolution-list textarea')) {
        autoResize(e.target);
    }
});

// Preconfig dropdown
(function() {
    var preconfig = window._pvPreconfig || [];
    var menu = document.getElementById('pv-preconfig-menu');
    var btn = document.getElementById('pv-preconfig-btn');
    if (!menu || !btn || !preconfig.length) return;

    function buildMenu() {
        menu.innerHTML = '';
        var lastCat = '';
        preconfig.forEach(function(item) {
            if (item.category !== lastCat) {
                var catDiv = document.createElement('div');
                catDiv.className = 'pv-preconfig-cat';
                catDiv.textContent = item.category === 'cession' ? 'Cession de parts' : 'Général';
                menu.appendChild(catDiv);
                lastCat = item.category;
            }
            var a = document.createElement('a');
            a.textContent = item.title;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                $createResolutionBlock(item.title, item.content);
                menu.style.display = 'none';
            });
            menu.appendChild(a);
        });
    }
    buildMenu();

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    });

    document.addEventListener('click', function() {
        menu.style.display = 'none';
    });

    menu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
})();
</script>
<?php endif; ?>
