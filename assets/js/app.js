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
    'type_generation': 'Standard',
    'procedure_creation': 'Creation',
    'mode_depot_creation': 'Electronique',
    'type_contrat': 'Domiciliation commerciale',
    'date_contrat': '2026-01-01',
    'duree_contrat_mois': '12',
    'type_contrat_domiciliation': 'Personne Morale',
    'type_contrat_domiciliation_autre': '',
    'date_debut': '2026-01-01',
    'date_fin': '2026-12-31',
    'loyer_mensuel_ttc': '100',
    'frais_intermediaire_contrat': '50',
    'caution_montant': '500',
    'taux_tva_pourcent': '20',
    'loyer_mensuel_ht': '83.33',
    'montant_total_ht_contrat': '1000',
    'montant_pack_demarrage_ttc': '200',
    'loyer_mensuel_pack_demarrage_ttc': '50',
    'type_renouvellement': 'Mensuel',
    'taux_tva_renouvellement_pourcent': '20',
    'loyer_mensuel_ht_renouvellement': '166.67',
    'montant_total_ht_renouvellement': '2000',
    'loyer_mensuel_renouvellement_ttc': '200',
    'loyer_annuel_renouvellement_ttc': '2400',
    'statut': 'actif',
    'notes': 'Contrat de test pour validation',
    'nom_complet': 'Ahmed BENANI',
    'cin': 'AB123456',
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


