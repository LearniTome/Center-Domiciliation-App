<?php
declare(strict_types=1);

if (is_post() && $step === 2) {
    verify_csrf();
    $navAction = $_POST['nav_action'] ?? 'next';

    $wizard['resultat_net'] = $_POST['resultat_net'] ?? '0';
    $wizard['resultat_type'] = $_POST['resultat_type'] ?? 'benefice';
    $wizard['report_a_nouveau_debiteur'] = $_POST['report_a_nouveau_debiteur'] ?? '0';
    $wizard['reserve_legale_existante'] = $_POST['reserve_legale_existante'] ?? '0';
    $wizard['reserve_statutaire_existante'] = $_POST['reserve_statutaire_existante'] ?? '0';
    $wizard['reserve_facultative_existante'] = $_POST['reserve_facultative_existante'] ?? '0';
    $wizard['affectation_option'] = $_POST['affectation_option'] ?? 'profit_distribution';
    $wizard['dividende_total'] = $_POST['dividende_total'] ?? '0';
    $wizard['reserve_statutaire_dotation'] = $_POST['reserve_statutaire_dotation'] ?? '0';
    $wizard['reserve_facultative_dotation'] = $_POST['reserve_facultative_dotation'] ?? '0';
    $wizard['perte_reserve_prelevement'] = $_POST['perte_reserve_prelevement'] ?? '0';

    if ($navAction === 'back') {
        redirect_to('pv_ago', ['step' => 1]);
    }
    redirect_to('pv_ago', ['step' => 3]);
}

if ($step === 2):
    $isBenefice = ($wizard['resultat_type'] ?? 'benefice') === 'benefice';
    $resultatNet = (float) ($wizard['resultat_net'] ?? 0);
    $capitalSocLocal = (float) ($selectedSociete['societe_capital'] ?? $wizard['societe']['societe_capital'] ?? 0);
    $plafondRL = $capitalSocLocal * 0.10;
    $baseRL = max(0, $resultatNet - (float) ($wizard['report_a_nouveau_debiteur'] ?? 0));
    $RLExistante = (float) ($wizard['reserve_legale_existante'] ?? 0);
    $RLDotation = min($baseRL * 0.05, max(0, $plafondRL - $RLExistante));
    $affectationOption = $wizard['affectation_option'] ?? 'profit_distribution';
?>
<div class="stack">
    <div class="section-header">
        <h2>Donnees financieres</h2>
    </div>

    <form method="post" class="form" id="finances-form">
        <?= csrf_input() ?>

        <fieldset class="card" style="padding:16px;margin-bottom:16px">
            <legend style="font-weight:600;font-size:0.9rem;padding:0 6px">Resultat de l'exercice</legend>
            <div class="form-grid cols-2">
                <div class="field">
                    <span>Type de resultat</span>
                    <select name="resultat_type" id="resultat-type" onchange="toggleResultatType()">
                        <option value="benefice" <?= $isBenefice ? 'selected' : '' ?>>Benefice</option>
                        <option value="perte" <?= !$isBenefice ? 'selected' : '' ?>>Perte</option>
                    </select>
                </div>
                <div class="field">
                    <span>Resultat net (DH)</span>
                    <input type="text" name="resultat_net" id="resultat-net" value="<?= e($wizard['resultat_net'] ?? '') ?>" required>
                </div>
            </div>
        </fieldset>

        <div id="benefice-fields" style="display:<?= $isBenefice ? 'block' : 'none' ?>">
            <fieldset class="card" style="padding:16px;margin-bottom:16px">
                <legend style="font-weight:600;font-size:0.9rem;padding:0 6px">Reserves existantes (avant affectation)</legend>
                <div class="form-grid cols-2">
                    <div class="field">
                        <span>Report a nouveau debiteur anterieur (DH)</span>
                        <input type="text" name="report_a_nouveau_debiteur" value="<?= e($wizard['report_a_nouveau_debiteur'] ?? '0') ?>">
                    </div>
                    <div class="field">
                        <span>Reserve legale existante (DH) <small style="color:var(--text-muted)">plafond: <?= number_format($plafondRL, 2, ',', ' ') ?> DH</small></span>
                        <input type="text" name="reserve_legale_existante" value="<?= e($wizard['reserve_legale_existante'] ?? '0') ?>">
                    </div>
                    <div class="field">
                        <span>Reserve statutaire existante (DH)</span>
                        <input type="text" name="reserve_statutaire_existante" value="<?= e($wizard['reserve_statutaire_existante'] ?? '0') ?>">
                    </div>
                    <div class="field">
                        <span>Reserve facultative existante (DH)</span>
                        <input type="text" name="reserve_facultative_existante" value="<?= e($wizard['reserve_facultative_existante'] ?? '0') ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset class="card" style="padding:16px;margin-bottom:16px">
                <legend style="font-weight:600;font-size:0.9rem;padding:0 6px">Affectation du benefice</legend>
                <div class="field" style="margin-bottom:12px">
                    <span>Option d'affectation</span>
                    <select name="affectation_option" id="affectation-benefice" onchange="toggleAffectation()">
                        <option value="profit_distribution" <?= $affectationOption === 'profit_distribution' ? 'selected' : '' ?>>Distribution de dividendes</option>
                        <option value="loss_carryforward" <?= $affectationOption === 'loss_carryforward' ? 'selected' : '' ?>>Report a nouveau (sans distribution)</option>
                    </select>
                </div>
                <div class="form-grid cols-2">
                    <div class="field">
                        <span>Dotation reserve legale (5%) <small style="color:var(--text-muted)">auto: <?= number_format($RLDotation, 2, ',', ' ') ?> DH</small></span>
                        <input type="text" name="reserve_legale_dotation_display" value="<?= number_format($RLDotation, 2, ',', ' ') ?>" disabled style="background:#f0f0f0">
                    </div>
                    <div class="field">
                        <span>Dotation reserve statutaire (DH)</span>
                        <input type="text" name="reserve_statutaire_dotation" value="<?= e($wizard['reserve_statutaire_dotation'] ?? '0') ?>">
                    </div>
                    <div class="field">
                        <span>Dotation reserve facultative (DH)</span>
                        <input type="text" name="reserve_facultative_dotation" value="<?= e($wizard['reserve_facultative_dotation'] ?? '0') ?>">
                    </div>
                    <div class="field" id="dividende-field" style="display:<?= $affectationOption === 'profit_distribution' ? 'block' : 'none' ?>">
                        <span>Dividendes a distribuer (brut, DH)</span>
                        <input type="text" name="dividende_total" value="<?= e($wizard['dividende_total'] ?? '0') ?>">
                    </div>
                </div>
            </fieldset>
        </div>

        <div id="perte-fields" style="display:<?= !$isBenefice ? 'block' : 'none' ?>">
            <fieldset class="card" style="padding:16px;margin-bottom:16px">
                <legend style="font-weight:600;font-size:0.9rem;padding:0 6px">Traitement de la perte</legend>
                <div class="field" style="margin-bottom:12px">
                    <span>Option d'affectation de la perte</span>
                    <select name="affectation_option" id="affectation-perte" onchange="toggleAffectation()" disabled>
                        <option value="loss_carryforward" <?= $affectationOption === 'loss_carryforward' ? 'selected' : '' ?>>Report a nouveau debiteur</option>
                        <option value="loss_reserves" <?= $affectationOption === 'loss_reserves' ? 'selected' : '' ?>>Imputation sur les reserves</option>
                    </select>
                </div>
                <div class="form-grid cols-2">
                    <div class="field" id="reserve-prelev-field" style="display:<?= $affectationOption === 'loss_reserves' ? 'block' : 'none' ?>">
                        <span>Prelevement sur reserves facultatives (DH)</span>
                        <input type="text" name="perte_reserve_prelevement" value="<?= e($wizard['perte_reserve_prelevement'] ?? '0') ?>">
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="table-actions">
            <button type="submit" name="nav_action" value="back" class="btn btn-back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
            <button type="submit" name="nav_action" value="next" class="btn btn-next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
        </div>
    </form>
</div>

<script>
function toggleResultatType() {
    var isBenefice = document.getElementById('resultat-type').value === 'benefice';
    document.getElementById('benefice-fields').style.display = isBenefice ? 'block' : 'none';
    document.getElementById('perte-fields').style.display = isBenefice ? 'none' : 'block';
    var benefSelect = document.getElementById('affectation-benefice');
    var perteSelect = document.getElementById('affectation-perte');
    if (benefSelect) benefSelect.disabled = !isBenefice;
    if (perteSelect) perteSelect.disabled = isBenefice;
    toggleAffectation();
}
function toggleAffectation() {
    var benefSelect = document.getElementById('affectation-benefice');
    var perteSelect = document.getElementById('affectation-perte');
    var val = 'profit_distribution';
    if (benefSelect && !benefSelect.disabled) val = benefSelect.value;
    if (perteSelect && !perteSelect.disabled) val = perteSelect.value;
    document.getElementById('dividende-field').style.display = val === 'profit_distribution' ? 'block' : 'none';
    document.getElementById('reserve-prelev-field').style.display = val === 'loss_reserves' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() { toggleResultatType(); });
</script>
<?php endif; ?>
