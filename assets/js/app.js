document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
        const message = element.getAttribute('data-confirm') || 'Confirmer cette action ?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

const testData = {
    'dossier_domiciliation': 'DOM-TEST-2026',
    'raison_sociale': 'TEST SARL AU',
    'forme_juridique': 'SARL AU',
    'ice': '123456789000012',
    'date_ice': '2025-01-15',
    'rc': '123456',
    'if_number': '12345678',
    'ville': 'Casablanca',
    'email': 'contact@test-sarl.ma',
    'telephone': '0522123456',
    'capital': '100000',
    'part_social': '1000',
    'valeur_nominale': '100',
    'date_exp_cert_neg': '2027-01-15',
    'adresse': '123 Rue Mohammed V, Casablanca',
    'ste_adress': '123 Boulevard Hassan II',
    'tribunal': 'Casablanca',
    'type_generation': 'creation',
    'procedure_creation': 'normal',
    'mode_depot_creation': 'depot_physique',
    'type_contrat': 'Domiciliation commerciale',
    'date_contrat': '2026-01-01',
    'duree_contrat_mois': '12',
    'type_contrat_domiciliation': 'Personne Morale',
    'type_contrat_domiciliation_autre': '',
    'date_debut': '2026-01-01',
    'date_fin': '2026-12-31',
    'taux_tva_pourcent': '20',
    'loyer_mensuel_ht': '83.33',
    'loyer_ttc_mois': '100',
    'montant_total_loyer': '1200',
    'type_renouvellement': 'Annuel',
    'taux_tva_renouvellement_pourcent': '20',
    'loyer_mensuel_ht_renouvellement': '166.67',
    'loyer_ttc_renouvellement_mois': '200',
    'montant_total_renouvellement': '2400',
    'statut': 'actif',
    'notes': 'Contrat de test pour validation',
    'civilite': 'Mr',
    'nom': 'BENANI',
    'prenom': 'Ahmed',
    'nom_complet': 'Mr Ahmed BENANI',
    'cin': 'AB123456',
    'date_validite_cin': '2028-05-15',
    'date_naiss': '1990-05-15',
    'lieu_naiss': 'Casablanca',
    'nationalite': 'Marocaine',
    'phone': '0612345678',
    'qualite_associe': 'Associe',
    'parts': '1000',
    'is_gerant': '1',
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-fill-test]');
    if (!button) return;
    event.preventDefault();

    const form = button.closest('form');
    if (!form) return;

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        const name = field.getAttribute('name');
        if (!name) return;

        const key = name.replace(/^associes\[\d+\]\[(\w+)\]$/, '$1');
        const value = testData[key];
        if (value === undefined) return;

        if (field.tagName === 'SELECT') {
            const option = Array.from(field.options).find((opt) => opt.value === value);
            if (option) field.value = value;
        } else if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = String(field.value) === String(value);
        } else {
            field.value = value;
        }
    });

    var typeGen = form.querySelector('[name="type_generation"]');
    if (typeGen) {
        var evt = new Event('change', { bubbles: true });
        typeGen.dispatchEvent(evt);
    }
});

const associesContainer = document.querySelector('[data-associes-container]');
const associeTemplate = document.querySelector('[data-associe-template]');
const addAssocieButton = document.querySelector('[data-add-associe]');

if (associesContainer && associeTemplate && addAssocieButton) {
    const refreshIndices = () => {
        associesContainer.querySelectorAll('[data-associe-item]').forEach((item, index) => {
            item.querySelectorAll('[data-field-name]').forEach((field) => {
                const fieldName = field.getAttribute('data-field-name');
                if (fieldName) {
                    field.setAttribute('name', `associes[${index}][${fieldName}]`);
                }
            });

            const title = item.querySelector('[data-associe-title]');
            if (title) {
                title.textContent = `Associe ${index + 1}`;
            }
        });
    };

    addAssocieButton.addEventListener('click', () => {
        const clone = associeTemplate.content.firstElementChild.cloneNode(true);
        associesContainer.appendChild(clone);
        refreshIndices();
    });

    associesContainer.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const removeButton = target.closest('[data-remove-associe]');
        if (!removeButton) {
            return;
        }

        const items = associesContainer.querySelectorAll('[data-associe-item]');
        if (items.length <= 1) {
            return;
        }

        const associeItem = removeButton.closest('[data-associe-item]');
        if (associeItem) {
            associeItem.remove();
            refreshIndices();
        }
    });

    refreshIndices();
}

(function () {
    var toggleProcedureFields = function (typeGen) {
        var form = typeGen.closest('form');
        if (!form) return;
        var procCreation = form.querySelector('[name="procedure_creation"]');
        var modeDepot = form.querySelector('[name="mode_depot_creation"]');
        if (!procCreation || !modeDepot) return;
        var isDomiciliation = typeGen.value === 'domiciliation';
        procCreation.disabled = isDomiciliation;
        modeDepot.disabled = isDomiciliation;
        if (isDomiciliation) {
            procCreation.value = '';
            modeDepot.value = '';
        }
    };

    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches('[name="type_generation"]')) {
            toggleProcedureFields(e.target);
        }
    });

    var typeGen = document.querySelector('[name="type_generation"]');
    if (typeGen) {
        toggleProcedureFields(typeGen);
    }
})();

const updateNomComplet = (container) => {
    const civilite = container.querySelector('[data-field-name="civilite"]');
    const nom = container.querySelector('[data-field-name="nom"]');
    const prenom = container.querySelector('[data-field-name="prenom"]');
    const nomComplet = container.querySelector('[data-field-name="nom_complet"]');
    if (!nom || !prenom || !nomComplet) return;
    const parts = [];
    if (civilite && civilite.value) parts.push(civilite.value);
    if (prenom.value.trim()) parts.push(prenom.value.trim());
    if (nom.value.trim()) parts.push(nom.value.trim());
    nomComplet.value = parts.join(' ');
};

document.addEventListener('change', (e) => {
    const field = e.target.closest('[data-field-name="civilite"]');
    if (field) updateNomComplet(field.closest('[data-associe-item]'));
});

document.addEventListener('input', (e) => {
    const field = e.target.closest('[data-field-name="nom"], [data-field-name="prenom"]');
    if (field) updateNomComplet(field.closest('[data-associe-item]'));
});

(function() {
    const dateDebut = document.querySelector('[data-date-debut]');
    const dureeMois = document.querySelector('[data-duree-mois]');
    const dateFin = document.querySelector('[data-date-fin]');

    function calculateDateFin() {
        if (!dateDebut || !dureeMois || !dateFin) return;
        const debut = dateDebut.value;
        const mois = parseInt(dureeMois.value, 10);
        if (!debut || isNaN(mois) || mois <= 0) {
            dateFin.value = '';
            return;
        }
        const startDate = new Date(debut);
        startDate.setMonth(startDate.getMonth() + mois);
        const yyyy = startDate.getFullYear();
        const mm = String(startDate.getMonth() + 1).padStart(2, '0');
        const dd = String(startDate.getDate()).padStart(2, '0');
        dateFin.value = `${yyyy}-${mm}-${dd}`;
    }

    if (dateDebut) dateDebut.addEventListener('input', calculateDateFin);
    if (dureeMois) dureeMois.addEventListener('input', calculateDateFin);

    const parseNum = (v) => {
        if (v === null || v === undefined || v === '') return 0;
        return parseFloat(String(v).replace(',', '.')) || 0;
    };

    const dureeMoisRenouvellement = () => {
        const type = document.querySelector('[name="type_renouvellement"]')?.value || '';
        const map = {
            'Mensuel': 1,
            'Trimestriel': 3,
            'Annuel': 12,
            '2 ans': 24,
            '3 ans': 36,
            '4 ans': 48,
            '5 ans': 60,
        };
        return map[type] || 0;
    };

    const calculerLoyerInitial = () => {
        const ht = parseNum(document.querySelector('[data-loyer-ht]')?.value);
        const tva = parseNum(document.querySelector('[data-tva-pourcent]')?.value);
        const duree = parseNum(document.querySelector('[data-duree-mois]')?.value);
        const ttcMois = Math.round(ht * (1 + tva / 100) * 100) / 100;
        const ttcMoisField = document.querySelector('[data-loyer-ttc-mois]');
        const totalField = document.querySelector('[data-montant-total-loyer]');
        if (ttcMoisField) ttcMoisField.value = ttcMois.toFixed(2);
        if (totalField) totalField.value = (ttcMois * duree).toFixed(2);
    };

    const calculerLoyerRenouvellement = () => {
        const ht = parseNum(document.querySelector('[data-loyer-ht-renouvellement]')?.value);
        const tva = parseNum(document.querySelector('[data-tva-renouvellement-pourcent]')?.value);
        const ttcMois = Math.round(ht * (1 + tva / 100) * 100) / 100;
        const ttcMoisField = document.querySelector('[data-loyer-ttc-renouvellement-mois]');
        const totalField = document.querySelector('[data-montant-total-renouvellement]');
        if (ttcMoisField) ttcMoisField.value = ttcMois.toFixed(2);
        if (totalField) totalField.value = (ttcMois * dureeMoisRenouvellement()).toFixed(2);
    };

    const recalcAll = () => {
        calculateDateFin();
        calculerLoyerInitial();
        calculerLoyerRenouvellement();
    };

    document.querySelector('[data-loyer-ht]')?.addEventListener('input', calculerLoyerInitial);
    document.querySelector('[data-tva-pourcent]')?.addEventListener('change', calculerLoyerInitial);
    document.querySelector('[data-duree-mois]')?.addEventListener('input', recalcAll);

    document.querySelector('[data-loyer-ht-renouvellement]')?.addEventListener('input', calculerLoyerRenouvellement);
    document.querySelector('[data-tva-renouvellement-pourcent]')?.addEventListener('change', calculerLoyerRenouvellement);
    document.querySelector('[name="type_renouvellement"]')?.addEventListener('change', calculerLoyerRenouvellement);

    recalcAll();

    document.querySelectorAll('[data-show-if]').forEach((field) => {
        const showIf = field.getAttribute('data-show-if');
        const showValue = field.getAttribute('data-show-value');
        const trigger = document.querySelector(`[name="${showIf}"]`);
        const updateVisibility = () => {
            field.style.display = (trigger?.value === showValue) ? '' : 'none';
        };
        if (trigger) {
            trigger.addEventListener('change', updateVisibility);
            updateVisibility();
        }
    });
})();


