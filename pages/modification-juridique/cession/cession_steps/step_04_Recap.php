<?php
declare(strict_types=1);

// Step 4: Récap (just navigation)
if (is_post() && $step === 4) {
    $navAction = $_POST['nav_action'] ?? 'next';
    if ($navAction === 'back') {
        redirect_to('cession', ['step' => 3]);
    }
    redirect_to('cession', ['step' => 5]);
}
if ($step === 4):
    $socData = $wizard['mode'] === 'existante' ? $selectedSociete : ($wizard['societe'] ?? []);
    $assocData = $wizard['mode'] === 'existante' ? $selectedAssocies : ($wizard['associes'] ?? []);
    $raisonSlug = preg_replace('/[^a-zA-Z0-9\s-]/', '', ($socData['societe_raison_sociale'] ?? 'Dossier'));
    $raisonSlug = preg_replace('/\s+/', '-', $raisonSlug);
    $raisonSlug = preg_replace('/-+/', '-', $raisonSlug);
    $raisonSlug = trim($raisonSlug, '-') ?: 'Dossier';
    $forme = $socData['societe_forme_juridique'] ?? '';
    $prefixMap = ['SARL AU' => 'SARL-AU', 'SARL' => 'SARL', 'SA' => 'SA', 'Personne Physique' => 'PP'];
    $pdfPrefix = $prefixMap[$forme] ?? 'CESSION';
?>
        <div class="stack">
            <div class="section-header">
                <h2>Recapitulatif de la cession</h2>
            </div>

            <div class="step-4-controls table-actions" style="margin-bottom:0.75rem">
                <button class="btn btn-info" onclick="window.print()"><span class="material-symbols-outlined">print</span> Imprimer</button>
                <button class="btn btn-info" id="btn-pdf-recap-cession" data-prefix="<?= e($pdfPrefix) ?>" data-raison="<?= e($raisonSlug) ?>"><span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF</button>
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 1])) ?>"><span class="material-symbols-outlined">edit</span> Modifier societe</a>
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 2])) ?>"><span class="material-symbols-outlined">edit</span> Modifier associes</a>
                <a class="btn btn-back" href="<?= e(app_url('cession', ['step' => 3])) ?>"><span class="material-symbols-outlined">edit</span> Modifier cession</a>
            </div>

            <div class="recap-a4">
                <div class="recap-header">
                    <h2>Recapitulatif de cession de parts sociales</h2>
                    <p>Societe : <?= e($socData['societe_raison_sociale'] ?? '-') ?> — Date : <?= e(format_date($wizard['cession_date'] ?? '')) ?></p>
                </div>

                <div class="recap-section">
                    <h3>Societe</h3>
                    <div class="recap-grid">
                        <div class="item"><span class="label">Raison sociale</span><span class="value"><?= e($socData['societe_raison_sociale'] ?: '-') ?></span></div>
                        <div class="item"><span class="label">Forme juridique</span><span class="value"><?= e($socData['societe_forme_juridique'] ?: '-') ?></span></div>
                        <div class="item"><span class="label">ICE</span><span class="value"><?= e($socData['societe_ice'] ?: '-') ?></span></div>
                        <div class="item"><span class="label">Capital</span><span class="value"><?= e($socData['societe_capital'] ? number_format((float) $socData['societe_capital'], 2, ',', ' ') : '-') ?> DH</span></div>
                        <div class="item"><span class="label">Nombre de parts</span><span class="value"><?= e((string) ($socData['societe_part_social'] ?: '-')) ?></span></div>
                        <div class="item"><span class="label">Ville</span><span class="value"><?= e($socData['societe_ville'] ?: '-') ?></span></div>
                        <div class="item"><span class="label">Tribunal</span><span class="value"><?= e($socData['societe_tribunal'] ?: '-') ?></span></div>
                        <div class="item"><span class="label">Email</span><span class="value"><?= e($socData['societe_email'] ?: '-') ?></span></div>
                        <div class="item full"><span class="label">Adresse</span><span class="value"><?= e($socData['societe_adresse_siege'] ?: '-') ?></span></div>
                    </div>
                </div>

                <div class="recap-section">
                    <h3>Associes (<?= count($assocData) ?>)</h3>
                    <?php
                    $socCapital = (float) ($socData['societe_capital'] ?? 0);
                    $socParts = (int) ($socData['societe_part_social'] ?? 0);
                    $totalParts = $socParts;
                    $totalCapital = $socCapital;

                    $cedantNames = [];
                    $cessionnaireNames = [];
                    foreach (($wizard['parts'] ?? []) as $p) {
                        $cedantNames[] = trim($p['cedant_nom_complet'] ?? '');
                        $cessionnaireNames[] = trim($p['cessionnaire_nom_complet'] ?? '');
                    }
                    ?>
                        <?php foreach ($assocData as $i => $assoc):
                            $parts = (int) ($assoc['associe_parts'] ?? 0);
                            $capital = (float) ($assoc['associe_capital_detenu'] ?? 0);
                            $pct = $socParts > 0 ? round(($parts / $socParts) * 100, 1) : ($socCapital > 0 ? round(($capital / $socCapital) * 100, 1) : 0);
                            $assocNom = trim($assoc['associe_nom_complet'] ?? '');
                            $isCedant = in_array($assocNom, $cedantNames);
                            $isCessionnaire = in_array($assocNom, $cessionnaireNames);
                            $roleBadges = [];
                            if ($isCedant) $roleBadges[] = '<span class="badge" style="background:var(--danger);color:#fff;font-size:0.65rem;padding:1px 6px;border-radius:3px">Cedant</span>';
                            if ($isCessionnaire) $roleBadges[] = '<span class="badge" style="background:var(--success);color:#fff;font-size:0.65rem;padding:1px 6px;border-radius:3px">Cessionnaire</span>';
                        ?>
                        <div class="recap-associe">
                            <div class="associe-num">
                                Associe n°<?= $i + 1 ?> — <?= e($assoc['associe_civilite'] ?? '') ?> <?= e($assoc['associe_nom_complet'] ?: '') ?>
                                <?= !empty($roleBadges) ? ' ' . implode(' ', $roleBadges) : '' ?>
                            </div>
                            <div class="recap-grid">
                                <span class="item"><span class="label">CIN</span><span class="value" style="font-family:monospace"><?= e($assoc['associe_cin'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Date naissance</span><span class="value"><?= !empty($assoc['associe_date_naissance']) ? e(date('d/m/Y', strtotime($assoc['associe_date_naissance']))) : '-' ?></span></span>
                                <span class="item"><span class="label">Lieu naissance</span><span class="value"><?= e($assoc['associe_lieu_naissance'] ?? '-') ?></span></span>
                                <span class="item"><span class="label">Nationalite</span><span class="value"><?= e($assoc['associe_nationalite'] ?? '-') ?></span></span>
                                <span class="item"><span class="label">Telephone</span><span class="value"><?= e($assoc['associe_telephone'] ?? '-') ?></span></span>
                                <span class="item"><span class="label">Email</span><span class="value"><?= e($assoc['associe_email'] ?? '-') ?></span></span>
                                <span class="item"><span class="label">Adresse</span><span class="value"><?= e($assoc['associe_adresse'] ?? '-') ?></span></span>
                                <span class="item"><span class="label">Qualite</span><span class="value"><?= e($assoc['associe_qualite'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Parts</span><span class="value"><?= $parts ? number_format($parts, 0, ',', ' ') : '-' ?></span></span>
                                <span class="item"><span class="label">Capital detenu</span><span class="value"><?= $capital ? number_format($capital, 2, ',', ' ') . ' DH' : '-' ?></span></span>
                                <span class="item"><span class="label">% capital</span><span class="value"><?= $pct > 0 ? number_format($pct, 1, ',', ' ') . '%' : '-' ?></span></span>
                                <span class="item"><span class="label">Gerant</span><span class="value"><?= ((string) ($assoc['associe_est_gerant'] ?? '0') === '1') ? 'Oui' : 'Non' ?></span></span>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    <?php
                    // Cessionnaires cards (from parts, not in assocData)
                    $cessionnairesInParts = [];
                    foreach (($wizard['parts'] ?? []) as $p) {
                        $key = $p['cessionnaire_nom_complet'] ?? '';
                        if ($key === '' || in_array(trim($key), $cedantNames)) continue;
                        if (!isset($cessionnairesInParts[$key])) {
                            $partsRecues = (int) ($p['parts_cedees'] ?? 0);
                            $capitalRecalcule = $totalParts > 0 ? round(($partsRecues / $totalParts) * $totalCapital, 2) : 0;
                            $cessionnairesInParts[$key] = [
                                'nom' => $key,
                                'cin' => $p['cessionnaire_cin'] ?? '',
                                'civilite' => $p['cessionnaire_civilite'] ?? 'M.',
                                'date_naissance' => $p['cessionnaire_date_naissance'] ?? '',
                                'lieu_naissance' => $p['cessionnaire_lieu_naissance'] ?? '',
                                'nationalite' => $p['cessionnaire_nationalite'] ?? '',
                                'adresse' => $p['cessionnaire_adresse'] ?? '',
                                'telephone' => $p['cessionnaire_telephone'] ?? '',
                                'email' => $p['cessionnaire_email'] ?? '',
                                'qualite' => $p['cessionnaire_qualite'] ?? '',
                                'parts' => $partsRecues,
                                'capital_detenu' => $capitalRecalcule,
                                'est_gerant' => $p['cessionnaire_est_gerant'] ?? 0,
                            ];
                        }
                    }
                    ?>
                    <?php if (!empty($cessionnairesInParts)): ?>
                        <?php foreach ($cessionnairesInParts as $ck => $cp): ?>
                        <div class="recap-associe">
                            <div class="associe-num">
                                <?= e($cp['civilite']) ?> <?= e($cp['nom']) ?>
                                <span class="badge" style="background:var(--success);color:#fff;font-size:0.65rem;padding:1px 6px;border-radius:3px">Cessionnaire</span>
                            </div>
                            <div class="recap-grid">
                                <span class="item"><span class="label">CIN</span><span class="value" style="font-family:monospace"><?= e($cp['cin'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Date naissance</span><span class="value"><?= !empty($cp['date_naissance']) ? e(date('d/m/Y', strtotime($cp['date_naissance']))) : '-' ?></span></span>
                                <span class="item"><span class="label">Lieu naissance</span><span class="value"><?= e($cp['lieu_naissance'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Nationalite</span><span class="value"><?= e($cp['nationalite'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Telephone</span><span class="value"><?= e($cp['telephone'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Email</span><span class="value"><?= e($cp['email'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Adresse</span><span class="value"><?= e($cp['adresse'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Qualite</span><span class="value"><?= e($cp['qualite'] ?: '-') ?></span></span>
                                <span class="item"><span class="label">Parts</span><span class="value"><?= $cp['parts'] > 0 ? number_format((int) $cp['parts'], 0, ',', ' ') : '-' ?></span></span>
                                <span class="item"><span class="label">Capital detenu</span><span class="value"><?= (float) $cp['capital_detenu'] > 0 ? number_format((float) $cp['capital_detenu'], 2, ',', ' ') . ' DH' : '-' ?></span></span>
                                <span class="item"><span class="label">Gerant</span><span class="value"><?= !empty($cp['est_gerant']) ? 'Oui' : 'Non' ?></span></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Repartition du capital apres cession -->
                <div class="recap-section">
                    <h3>Repartition du capital apres cession</h3>
                    <?php
                    // Build a map of each associate's actual holdings from assocData
                    $assocHoldings = [];
                    foreach ($assocData as $a) {
                        $aid = (int) ($a['id'] ?? 0);
                        $anom = trim($a['associe_nom_complet'] ?? '');
                        $assocHoldings[$aid] = $assocHoldings[$anom] = [
                            'parts' => (int) ($a['associe_parts'] ?? 0),
                            'capital' => (float) ($a['associe_capital_detenu'] ?? 0),
                            'nom' => $anom,
                        ];
                    }

                    // Build cedant deductions using their actual holdings
                    $cedantDeductions = [];
                    foreach ($wizard['parts'] as $p) {
                        $ck = $p['cedant_associe_id'] ?: $p['cedant_nom_complet'];
                        if (!isset($cedantDeductions[$ck])) {
                            $h = $assocHoldings[$ck] ?? ['parts' => 0, 'capital' => 0, 'nom' => $p['cedant_nom_complet']];
                            $cedantDeductions[$ck] = [
                                'nom' => $p['cedant_nom_complet'],
                                'parts_avant' => $h['parts'],
                                'capital_avant' => $h['capital'],
                                'parts' => 0,
                                'capital' => 0,
                            ];
                        }
                        $dedParts = (int) ($p['parts_cedees'] ?? 0);
                        $cedantDeductions[$ck]['parts'] += $dedParts;
                        $pa = $cedantDeductions[$ck]['parts_avant'];
                        $ca = $cedantDeductions[$ck]['capital_avant'];
                        if ($pa > 0 && $ca > 0) {
                            $capDed = round(($dedParts / $pa) * $ca, 2);
                        } else {
                            $capDed = $totalParts > 0 ? round(($dedParts / $totalParts) * $totalCapital, 2) : 0;
                        }
                        $cedantDeductions[$ck]['capital'] += $capDed;
                    }

                    // Build cessionnaire additions
                    $cessionnaireAdditions = [];
                    foreach ($wizard['parts'] as $p) {
                        $ck = $p['cessionnaire_associe_id'] ?: $p['cessionnaire_nom_complet'];
                        if (!isset($cessionnaireAdditions[$ck])) {
                            $cessionnaireAdditions[$ck] = [
                                'nom' => $p['cessionnaire_nom_complet'],
                                'parts' => 0,
                            ];
                        }
                        $partsRecues = (int) ($p['parts_cedees'] ?? 0);
                        $cessionnaireAdditions[$ck]['parts'] += $partsRecues;
                    }

                    ?>
                    <div style="overflow-x:auto;width:100%">
                    <table class="recap-table">
                        <thead>
                            <tr>
                                <th>Associe</th>
                                <th class="right">Avant</th>
                                <th class="center">Operation</th>
                                <th class="right">Cedees/Recues</th>
                                <th class="right">Apres</th>
                                <th class="right">Capital apres</th>
                                <th class="right">% apres</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cedantDeductions as $ded):
                                $partsApres = max(0, $ded['parts_avant'] - $ded['parts']);
                                $capitalApres = max(0, $ded['capital_avant'] - $ded['capital']);
                                $pctApres = $totalParts > 0 ? ($partsApres / $totalParts) * 100 : 0;
                            ?>
                            <tr>
                                <td class="bold"><?= e($ded['nom']) ?></td>
                                <td class="right"><?= $ded['parts_avant'] ?: '-' ?></td>
                                <td class="center">
                                    <span class="icon-inline" style="color:var(--danger)">
                                        <span class="material-symbols-outlined">remove_circle</span> Cede
                                    </span>
                                </td>
                                <td class="right badge-danger">-<?= $ded['parts'] ?></td>
                                <td class="right bold"><?= $partsApres ?></td>
                                <td class="right"><?= e(number_format($capitalApres, 2, ',', ' ') . ' DH') ?></td>
                                <td class="right"><?= number_format($pctApres, 1, ',', ' ') . '%' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php
                            foreach ($cessionnaireAdditions as $key => $add):
                                if (isset($cedantDeductions[$key])) continue;
                                $capApres = $totalParts > 0 ? round(($add['parts'] / $totalParts) * $totalCapital, 2) : 0;
                                $pctApres = $totalParts > 0 ? ($add['parts'] / $totalParts) * 100 : 0;
                            ?>
                            <tr>
                                <td class="bold"><?= e($add['nom']) ?></td>
                                <td class="right">-</td>
                                <td class="center">
                                    <span class="icon-inline" style="color:var(--success)">
                                        <span class="material-symbols-outlined">add_circle</span> Recu
                                    </span>
                                </td>
                                <td class="right badge-success">+<?= $add['parts'] ?></td>
                                <td class="right bold"><?= $add['parts'] ?></td>
                                <td class="right"><?= e(number_format($capApres, 2, ',', ' ') . ' DH') ?></td>
                                <td class="right"><?= number_format($pctApres, 1, ',', ' ') . '%' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="right"><?= $totalParts ?></td>
                                <td></td>
                                <td></td>
                                <td class="right"><?= $totalParts ?></td>
                                <td class="right"><?= e(number_format($totalCapital, 2, ',', ' ') . ' DH') ?></td>
                                <td class="right">100,0 %</td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>

                    <div class="recap-grid" style="margin-top:0.75rem;grid-template-columns:1fr 1fr">
                        <div class="capital-card">
                            <span class="label">Avant cession</span>
                            <span class="value"><strong>Capital :</strong> <?= e(number_format($totalCapital, 2, ',', ' ') . ' DH') ?> &mdash; <strong>Parts :</strong> <?= $totalParts ?></span>
                        </div>
                        <div class="capital-card">
                            <span class="label">Apres cession</span>
                            <span class="value"><strong>Capital :</strong> <?= e(number_format($totalCapital, 2, ',', ' ') . ' DH') ?> &mdash; <strong>Parts :</strong> <?= $totalParts ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" class="step-4-controls" style="margin-top:0.75rem">
                <?= csrf_input() ?>
                <input type="hidden" name="step" value="4">
                <div style="display:flex;gap:8px">
                    <button class="btn btn-back" type="submit" name="nav_action" value="back"><span class="material-symbols-outlined">arrow_back</span> Retour</button>
                    <button class="btn btn-next" type="submit" name="nav_action" value="next"><span class="material-symbols-outlined">arrow_forward</span> Suivant</button>
                </div>
            </form>
        </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
document.getElementById('btn-pdf-recap-cession')?.addEventListener('click', function () {
    var element = document.querySelector('.recap-a4');
    if (!element) return;

    var prefix = this.getAttribute('data-prefix') || 'CESSION';
    var raison = this.getAttribute('data-raison') || 'Dossier';
    var now = new Date();
    var yyyy = now.getFullYear();
    var mm = String(now.getMonth() + 1).padStart(2, '0');
    var filename = prefix + '_' + yyyy + '-' + mm + '_Recapitulatif-Cession-' + raison + '.pdf';

    this.disabled = true;
    this.innerHTML = '<span class="material-symbols-outlined spin">sync</span> Generation...';

    element.classList.add('recap-pdf-mode');

    var opt = {
        margin:       10,
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save().then(function () {
        element.classList.remove('recap-pdf-mode');
        document.getElementById('btn-pdf-recap-cession').disabled = false;
        document.getElementById('btn-pdf-recap-cession').innerHTML = '<span class="material-symbols-outlined">picture_as_pdf</span> Sauvegarder PDF';
    });
});
</script>
<?php endif; ?>
