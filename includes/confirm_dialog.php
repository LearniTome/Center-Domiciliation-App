<?php
declare(strict_types=1);

/**
 * Dialogue de confirmation global, declenche par l'attribut `data-confirm`.
 *
 * Rend une seule fois dans le layout (includes/pied_page.php) : tout element
 * portant `data-confirm` dans la page declenche la meme fenetre d'avertissement.
 *
 * Attributs lus sur l'element declencheur :
 *   data-confirm            message (obligatoire)
 *   data-confirm-title      titre de la fenetre (defaut « Confirmation »)
 *   data-confirm-ok         libelle du bouton de confirmation (defaut « Confirmer »)
 *   data-confirm-cancel     libelle du bouton d'annulation (defaut « Annuler »)
 *   data-confirm-tone       « danger » (defaut) ou « primary » pour le bouton de confirmation
 */
?>
<div class="modal-overlay" data-confirm-dialog role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
    <div class="modal-panel confirm-panel">
        <div class="modal-header">
            <h3 id="confirm-dialog-title">
                <span class="material-symbols-outlined confirm-dialog-icon">warning</span>
                <span data-confirm-dialog-title>Confirmation</span>
            </h3>
            <button class="btn-icon" type="button" data-confirm-dialog-cancel title="Fermer"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="modal-body">
            <p id="confirm-dialog-message" data-confirm-dialog-message></p>
        </div>
        <div class="form-actions confirm-dialog-actions">
            <button type="button" class="btn btn-cancel" data-confirm-dialog-cancel><span class="material-symbols-outlined">close</span> <span data-confirm-dialog-cancel-label>Annuler</span></button>
            <button type="button" class="btn btn-danger" data-confirm-dialog-ok><span class="material-symbols-outlined">check</span> <span data-confirm-dialog-ok-label>Confirmer</span></button>
        </div>
    </div>
</div>
