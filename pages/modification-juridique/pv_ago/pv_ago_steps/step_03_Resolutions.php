<?php
declare(strict_types=1);

function pv_ago_calculs(array $w, array $soc): array
{
    $capital = (float) ($soc['societe_capital'] ?? $w['societe']['societe_capital'] ?? 0);
    $totalParts = (int) ($w['total_parts'] ?? $soc['societe_part_social'] ?? 0);
    $partsPresentes = (int) ($w['parts_presentes'] ?? $totalParts);
    $resultatNet = (float) ($w['resultat_net'] ?? 0);
    $isBenefice = ($w['resultat_type'] ?? 'benefice') === 'benefice';
    $reportDebiteur = (float) ($w['report_a_nouveau_debiteur'] ?? 0);
    $RLExistante = (float) ($w['reserve_legale_existante'] ?? 0);
    $affectation = $w['affectation_option'] ?? '';
    $dividendeTotal = (float) ($w['dividende_total'] ?? 0);
    $reserveStatutaire = (float) ($w['reserve_statutaire_dotation'] ?? 0);
    $reserveFacultative = (float) ($w['reserve_facultative_dotation'] ?? 0);
    $pertePrelevement = (float) ($w['perte_reserve_prelevement'] ?? 0);

    $plafondRL = $capital * 0.10;
    $baseRL = max(0, $resultatNet - $reportDebiteur);
    $RLDotation = 0;
    if ($isBenefice && $baseRL > 0) {
        $calc = $baseRL * 0.05;
        $RLDotation = min($calc, max(0, $plafondRL - $RLExistante));
    }
    $tpa = $dividendeTotal * 0.10;
    $dividendeNet = $dividendeTotal - $tpa;
    $reportNv = 0;
    if ($isBenefice) {
        if ($affectation === 'profit_distribution') {
            $reportNv = $baseRL - $RLDotation - $reserveStatutaire - $reserveFacultative - $dividendeTotal;
        } else {
            $reportNv = $baseRL - $RLDotation - $reserveStatutaire - $reserveFacultative;
        }
    } else {
        $reportNv = $affectation === 'loss_carryforward' ? -abs($resultatNet) : 0;
    }

    $rsFmt = fn($v) => number_format($v, 2, ',', ' ');
    $pctPresence = $totalParts > 0 ? round(($partsPresentes / $totalParts) * 100, 1) : 0;

    $resolutions = [];
    $socName = $soc['societe_raison_sociale'] ?? $w['societe']['societe_raison_sociale'] ?? 'la societe';
    $forme = $soc['societe_forme_juridique'] ?? $w['societe']['societe_forme_juridique'] ?? 'SARL';
    $president = $w['president_nom'] ?? 'le Gerant';
    $presidentQualite = $w['president_qualite'] ?? 'Gerant';
    $exercice = $w['exercice_clos'] ?? '31/12/2025';
    $dateAgo = $w['date_ago'] ?? date('Y-m-d');

    // Resolution 1: Approbation des comptes
    $resolutions[] = [
        'title' => 'Approbation des comptes et quitus a la gerance',
        'content' => "L'Assemblee Generale, apres avoir entendu la lecture du rapport de gestion sur l'activite et le resultat de l'exercice clos le {$exercice}, approuve les comptes dudit exercice, tels qu'ils ont ete presentes, faisant ressortir " . ($isBenefice ? "un benefice net comptable de {$rsFmt($resultatNet)} DH." : "une perte nette comptable de {$rsFmt(abs($resultatNet))} DH.") . "\n\nEn consequence, l'Assemblee donne quitus entier, definitif et sans reserve a la gerance pour l'execution de son mandat au cours dudit exercice.\n\n(Cette resolution est adoptee a l'unanimite)"
    ];

    // Resolution 2: Affectation du resultat
    $affectContent = "L'Assemblee Generale, statuant sur la proposition de la gerance, decide d'affecter le resultat net de l'exercice clos le {$exercice} comme suit :\n\n";
    if ($isBenefice) {
        $affectContent .= "Benefice net de l'exercice : {$rsFmt($resultatNet)} DH\n";
        if ($reportDebiteur > 0) {
            $affectContent .= "Apurement du Report a nouveau debiteur anterieur : {$rsFmt($reportDebiteur)} DH\n";
            $affectContent .= "Base de calcul de la Reserve Legale : {$rsFmt($baseRL)} DH\n";
        }
        if ($RLDotation > 0) {
            $affectContent .= "Dotation a la Reserve Legale (5%) : {$rsFmt($RLDotation)} DH\n";
        } else {
            $affectContent .= "Reserve Legale : deja au plafond legal de 10% du capital social\n";
        }
        if ($reserveStatutaire > 0) {
            $affectContent .= "Dotation a la Reserve Statutaire : {$rsFmt($reserveStatutaire)} DH\n";
        }
        if ($reserveFacultative > 0) {
            $affectContent .= "Dotation aux Reserves Facultatives : {$rsFmt($reserveFacultative)} DH\n";
        }
        if ($affectation === 'profit_distribution' && $dividendeTotal > 0) {
            $affectContent .= "Distribution de dividendes aux associes : {$rsFmt($dividendeTotal)} DH\n";
            $affectContent .= "Retenue a la source TPA (10%) : {$rsFmt($tpa)} DH\n";
            $affectContent .= "Dividendes nets verses aux associes : {$rsFmt($dividendeNet)} DH\n";
        }
        $affectContent .= "Report a nouveau crediteur (solde) : {$rsFmt(max(0, $reportNv))} DH";
    } else {
        if ($affectation === 'loss_carryforward') {
            $affectContent .= "L'Assemblee Generale decide d'affecter l'integralite de la perte nette de l'exercice, s'elevant a {$rsFmt(abs($resultatNet))} DH, au compte Report a nouveau debiteur.\n\nLe solde cumule de ce compte sera apure sur les benefices des exercices ulterieurs.";
        } else {
            $affectContent .= "L'Assemblee Generale decide d'absorber la perte nette de l'exercice, s'elevant a {$rsFmt(abs($resultatNet))} DH, par imputation directe sur les reserves facultatives a concurrence de {$rsFmt($pertePrelevement)} DH.";
        }
    }
    $affectContent .= "\n\n(Cette resolution est adoptee a l'unanimite)";
    $resolutions[] = [
        'title' => 'Affectation du resultat',
        'content' => $affectContent
    ];

    // Resolution 3: Pouvoirs pour formalites
    $resolutions[] = [
        'title' => 'Pouvoirs pour l\'accomplissement des formalites',
        'content' => "L'Assemblee Generale confere tous pouvoirs au porteur d'un exemplaire ou d'une copie du present proces-verbal a l'effet d'accomplir toutes les formalites de depot legal (Tribunal de Commerce / Greffe) et de publicite (Bulletin Officiel / Journal d'Annonces Legales) prevues par la legislation marocaine en vigueur.\n\n(Cette resolution est adoptee a l'unanimite)"
    ];

    return [
        'calculs' => [
            'capital' => $capital,
            'total_parts' => $totalParts,
            'parts_presentes' => $partsPresentes,
            'pct_presence' => $pctPresence,
            'resultat_net' => $resultatNet,
            'is_benefice' => $isBenefice,
            'report_debiteur' => $reportDebiteur,
            'base_RL' => $baseRL,
            'plafond_RL' => $plafondRL,
            'RL_existante' => $RLExistante,
            'RL_dotation' => $RLDotation,
            'RL_nouveau_solde' => $RLExistante + $RLDotation,
            'reserve_statutaire' => $reserveStatutaire,
            'reserve_facultative' => $reserveFacultative,
            'dividende_brut' => $dividendeTotal,
            'tpa' => $tpa,
            'dividende_net' => $dividendeNet,
            'report_nouveau' => $reportNv,
            'perte_prelevement' => $pertePrelevement,
            'affectation' => $affectation,
            'rsFmt' => $rsFmt,
        ],
        'resolutions' => $resolutions,
    ];
}

if (is_post() && $step === 3) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 2]);
    }
    if ($navAction === 'reset') {
        unset($wizard['resolutions']);
        set_flash('success', 'Resolutions reinitialisees.');
        redirect_to('pv_ago', ['step' => 3]);
    }
    if ($navAction === 'save_resolutions') {
        $titles = $_POST['res_title'] ?? [];
        $contents = $_POST['res_content'] ?? [];
        $resolutions = [];
        foreach ($titles as $i => $title) {
            $title = trim((string) $title);
            $content = trim((string) ($contents[$i] ?? ''));
            if ($title !== '' || $content !== '') {
                $resolutions[] = ['title' => $title, 'content' => $content];
            }
        }
        $wizard['resolutions'] = $resolutions;
        set_flash('success', 'Resolutions enregistrees.');
        redirect_to('pv_ago', ['step' => 3]);
    }
    redirect_to('pv_ago', ['step' => 4]);
}

if ($step === 3):
    $socForCalc = $selectedSociete ?: ($wizard['societe'] ?? []);
    $calcResult = pv_ago_calculs($wizard, $socForCalc);
    $calc = $calcResult['calculs'];
    $rsFmt = $calc['rsFmt'];

    // Use saved resolutions or auto-generated
    $resolutions = $wizard['resolutions'] ?? [];
    if (empty($resolutions)) {
        $resolutions = $calcResult['resolutions'];
    }
?>
<div class="stack">
    <div class="section-header">
        <h2>Resolutions et calculs</h2>
    </div>

    <div class="card card-box" style="background:var(--bg-secondary)">
        <h4 style="margin:0 0 8px">Recapitulatif des calculs</h4>
        <div class="form-grid cols-3" style="font-size:0.85rem">
            <div class="calc-line">Capital social : <strong><?= $rsFmt($calc['capital']) ?> DH</strong></div>
            <div class="calc-line">Parts sociales : <strong><?= $calc['total_parts'] ?></strong></div>
            <div class="calc-line">Parts presentes : <strong><?= $calc['parts_presentes'] ?> (<?= $calc['pct_presence'] ?>%)</strong></div>
            <div class="calc-line <?= $calc['is_benefice'] ? '' : 'calc-total' ?>">Resultat net : <strong><?= $rsFmt($calc['resultat_net']) ?> DH</strong></div>
            <?php if ($calc['report_debiteur'] > 0): ?>
            <div class="calc-line">Report a nouveau debiteur : <strong>-<?= $rsFmt($calc['report_debiteur']) ?> DH</strong></div>
            <div class="calc-line">Base reserve legale : <strong><?= $rsFmt($calc['base_RL']) ?> DH</strong></div>
            <?php endif; ?>
            <?php if ($calc['is_benefice']): ?>
            <div class="calc-line">Reserve legale existante : <strong><?= $rsFmt($calc['RL_existante']) ?> DH</strong></div>
            <div class="calc-line">Plafond RL (10%) : <strong><?= $rsFmt($calc['plafond_RL']) ?> DH</strong></div>
            <div class="calc-line">Dotation reserve legale : <strong><?= $rsFmt($calc['RL_dotation']) ?> DH</strong></div>
            <?php if ($calc['reserve_statutaire'] > 0): ?>
            <div class="calc-line">Reserve statutaire : <strong><?= $rsFmt($calc['reserve_statutaire']) ?> DH</strong></div>
            <?php endif; ?>
            <?php if ($calc['reserve_facultative'] > 0): ?>
            <div class="calc-line">Reserve facultative : <strong><?= $rsFmt($calc['reserve_facultative']) ?> DH</strong></div>
            <?php endif; ?>
            <?php if ($calc['dividende_brut'] > 0): ?>
            <div class="calc-line">Dividende brut : <strong><?= $rsFmt($calc['dividende_brut']) ?> DH</strong></div>
            <div class="calc-line">TPA (10%) : <strong><?= $rsFmt($calc['tpa']) ?> DH</strong></div>
            <div class="calc-line">Dividende net : <strong><?= $rsFmt($calc['dividende_net']) ?> DH</strong></div>
            <?php endif; ?>
            <div class="calc-line calc-total">Report a nouveau : <strong><?= $rsFmt(max(0, $calc['report_nouveau'])) ?> DH</strong></div>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" class="form">
        <?= csrf_input() ?>

        <div id="resolutions-container">
            <?php foreach ($resolutions as $i => $r): ?>
            <div class="res-block" data-idx="<?= $i ?>">
                <div class="res-block-header">
                    <h4>Resolution <?= $i + 1 ?></h4>
                    <button type="button" class="btn-icon danger" onclick="this.closest('.res-block').remove()" title="Supprimer"><span class="material-symbols-outlined">delete</span></button>
                </div>
                <div class="field" style="margin-bottom:6px">
                    <span>Titre</span>
                    <input type="text" name="res_title[]" value="<?= e($r['title'] ?? '') ?>">
                </div>
                <div class="field">
                    <span>Contenu</span>
                    <textarea name="res_content[]" rows="6" class="res-title-input"><?= e($r['content'] ?? '') ?></textarea>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="table-actions" style="margin-top:8px">
            <button type="button" class="btn btn-info" onclick="addResolution()"><span class="material-symbols-outlined">add</span> Ajouter une resolution</button>
            <button type="submit" name="nav_action" value="save_resolutions" class="btn btn-secondary"><span class="material-symbols-outlined">save</span> Enregistrer les resolutions</button>
            <button type="submit" name="nav_action" value="reset" class="btn btn-cancel"><span class="material-symbols-outlined">refresh</span> Reinitialiser</button>
        </div>

        <hr class="divider">

        <div class="table-actions">
            <button type="submit" name="nav_action" value="back" class="btn btn-back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button type="submit" name="nav_action" value="next" class="btn btn-next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </form>
</div>

<script>
function addResolution() {
    var container = document.getElementById('resolutions-container');
    var idx = container.children.length;
    var div = document.createElement('div');
    div.className = 'res-block';
    div.innerHTML = '<div class="res-block-header">'
        + '<h4>Resolution ' + (idx + 1) + '</h4>'
        + '<button type="button" class="btn-icon danger" onclick="this.closest(\'.res-block\').remove()" title="Supprimer"><span class="material-symbols-outlined">delete</span></button></div>'
        + '<div class="field" style="margin-bottom:6px"><span>Titre</span><input type="text" name="res_title[]" value=""></div>'
        + '<div class="field"><span>Contenu</span><textarea name="res_content[]" rows="6" class="res-title-input"></textarea></div>';
    container.appendChild(div);
}
</script>
<?php endif; ?>
