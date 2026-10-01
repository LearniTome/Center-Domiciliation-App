// ---------------------------------------------------------------------------
// Design system — accordéon des cartes (.ds-card)
// Fonctionne en parallèle avec l'ancien `[data-saas-card]` (rétrocompatible).
// ---------------------------------------------------------------------------
(function () {
    var query = window.matchMedia('(max-width: 768px)');
    var choix = new WeakMap();

    function cartesDs() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-ds-card]'));
    }

    function sectionAReplier(carte) {
        return carte.querySelector('.ds-card__note, .ds-card__body');
    }

    function appliquer(carte, ouvert) {
        carte.dataset.collapsed = ouvert ? 'false' : 'true';
        var contenu = sectionAReplier(carte);
        if (contenu) {
            contenu.hidden = !ouvert;
        }
        var bouton = carte.querySelector('[data-ds-toggle]');
        if (bouton) {
            bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        }
    }

    function rendre() {
        var mobile = query.matches;
        cartesDs().forEach(function (carte, index) {
            if (!mobile) {
                delete carte.dataset.collapsed;
                var contenu = sectionAReplier(carte);
                if (contenu) {
                    contenu.hidden = false;
                }
                var bouton = carte.querySelector('[data-ds-toggle]');
                if (bouton) {
                    bouton.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            if (!choix.has(carte)) {
                choix.set(carte, index === 0);
            }
            appliquer(carte, choix.get(carte));
        });
    }

    document.addEventListener('click', function (event) {
        if (!query.matches || !(event.target instanceof Element)) {
            return;
        }
        var bouton = event.target.closest('[data-ds-toggle]');
        if (!bouton) {
            return;
        }
        var carte = bouton.closest('[data-ds-card]');
        if (!carte) {
            return;
        }
        var etatOuvert = carte.dataset.collapsed === 'false';
        if (etatOuvert) {
            appliquer(carte, false);
            choix.set(carte, false);
            return;
        }
        cartesDs().forEach(function (c) {
            if (c === carte) { return; }
            appliquer(c, false);
            choix.set(c, false);
        });
        appliquer(carte, true);
        choix.set(carte, true);
    });

    query.addEventListener ? query.addEventListener('change', rendre) : query.addListener(rendre);
    document.addEventListener('DOMContentLoaded', rendre);
    window.addEventListener('load', rendre);
    setTimeout(rendre, 0);
})();
