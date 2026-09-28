<?php

declare(strict_types=1);

/**
 * Bandeau d'abonnement NON bloquant.
 *
 * Volontairement informatif : aucune redirection, aucune restriction d'acces.
 * Un adherent sans abonnement valide continue a utiliser l'application et
 * voit simplement un rappel. Le blocage eventuel reste porte par
 * require_active_subscription(), volontairement non appele.
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
