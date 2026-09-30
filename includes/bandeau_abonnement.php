<?php

declare(strict_types=1);

/**
 * Bandeau d'abonnement.
 *
 * Le blocage est porte par la PORTE DE CONNEXION (pages/auth/connexion.php,
 * via abonnement_autorise_acces()) : un adherent dont l'abonnement n'autorise
 * plus l'acces n'ouvre pas de session, il ne peut donc jamais atteindre une
 * page affichee par ce bandeau.
 *
 * Ce bandeau reste donc purement informatif : aucune redirection, aucune
 * restriction de page. Il sert au cas ou la situation evolue EN COURS DE
 * SESSION -- abonnement suspendu alors que l'user etait connecte, echeance
 * franchisee pendant la journee -- que la connexion, deja passee, ne peut
 * plus rattraper.
 *
 * Un second usage non bloquant : la session d'un adherent peut preceder la
 * creation de son abonnement (le compte est ouvert par le Centre juste apres
 * la souscription), le bandeau sert alors a le rediriger vers la facturation.
 *
 * Ne s'affiche pas pour les comptes internes du Centre (pas de cabinet) ni
 * lorsque la base est injoignable : abonnement_bandeau() renvoie alors null.
 */

$_subBandeau = abonnement_bandeau();

if (is_array($_subBandeau) && ($_subBandeau['tone'] ?? '') !== ''):
    $_subTone = $_subBandeau['tone'] === 'error' ? 'error' : 'warning';
    $_subIcon = $_subTone === 'error' ? 'error_outline' : 'schedule';
    ?>
    <div class="sub-banner sub-banner-<?= e($_subTone) ?>" role="status">
        <span class="material-symbols-outlined"><?= e($_subIcon) ?></span>
        <span class="sub-banner-text"><?= e($_subBandeau['message']) ?></span>
        <?php if (has_permission('mon_abonnement.view')): ?>
            <a class="btn btn-info" href="<?= e(app_url('mon_abonnement')) ?>"><span class="material-symbols-outlined">open_in_new</span> Mon abonnement</a>
        <?php endif; ?>
    </div>
<?php endif;

unset($_subBandeau, $_subTone, $_subIcon);
