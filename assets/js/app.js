document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
        const message = element.getAttribute('data-confirm') || 'Confirmer cette action ?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

(function () {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const shell = document.querySelector('.shell');
    if (toggle && shell) {
        toggle.addEventListener('click', () => {
            shell.classList.toggle('collapsed');
        });
    }
})();

(function () {
    const tables = document.querySelectorAll('[data-col-toggle]');
    if (!tables.length) return;
    const pageKey = new URLSearchParams(window.location.search).get('page') || 'unknown';
    const storageKey = 'col_visible_' + pageKey;

    var updateCount = function (btn, total, visibleCount) {
        var countEl = btn.querySelector('[data-col-count]');
        if (!countEl) return;
        countEl.textContent = visibleCount + '/' + total;
        countEl.style.background = visibleCount === total ? 'var(--success)' : 'var(--danger)';
    };

    tables.forEach(function (table) {
        var headers = table.querySelectorAll('thead th[data-col]');
        if (!headers.length) return;

        var container = table.closest('.card')?.querySelector('[data-col-toggle-btn]');
        if (!container) return;

        var panel = document.createElement('div');
        panel.className = 'col-toggle-panel';

        var saved = (function () {
            try {
                return JSON.parse(localStorage.getItem(storageKey));
            } catch (e) { return null; }
        })();

        var total = headers.length;
        var visibleCount = 0;

        headers.forEach(function (th, idx) {
            var colKey = th.getAttribute('data-col');
            var label = th.textContent.trim();

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            var visible = saved ? saved[colKey] !== false : true;
            checkbox.checked = visible;
            if (visible) visibleCount++;
            if (!visible) {
                th.classList.add('col-hidden');
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    var cell = row.children[idx];
                    if (cell) cell.classList.add('col-hidden');
                });
            }

            checkbox.addEventListener('change', function () {
                var isVisible = checkbox.checked;
                th.classList.toggle('col-hidden', !isVisible);
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    var cell = row.children[idx];
                    if (cell) cell.classList.toggle('col-hidden', !isVisible);
                });
                saved[colKey] = isVisible;
                try {
                    localStorage.setItem(storageKey, JSON.stringify(saved));
                } catch (e) {}
                var cv = 0;
                panel.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    if (cb.checked) cv++;
                });
                updateCount(container, total, cv);
            });

            var wrap = document.createElement('label');
            wrap.appendChild(checkbox);
            var txt = document.createElement('span');
            txt.textContent = label;
            wrap.appendChild(txt);
            panel.appendChild(wrap);
        });

        container.parentNode.appendChild(panel);
        updateCount(container, total, visibleCount);

        container.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.toggle('open');
            var cv = 0;
            panel.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                if (cb.checked) cv++;
            });
            updateCount(container, total, cv);
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && e.target !== container) {
                panel.classList.remove('open');
            }
        });
    });
})();

const formatFR = (v, decimals = 2) => {
    if (v === null || v === undefined || isNaN(v)) return '';
    return Number(v).toLocaleString('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
};

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
    'type_contrat_autre': '',
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
    'capital_detenu': '100000',
    'part_percent': '',
    'is_gerant': '1',
    'adresse': '123 Rue Mohammed V, Casablanca',
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

    form.querySelectorAll('input, select, textarea').forEach(function(field) {
        var name = field.getAttribute('name');
        if (!name) return;
        var key = name.replace(/^associes\[\d+\]\[(\w+)\]$/, '$1');
        if (testData[key] === undefined) return;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
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
        if (ttcMoisField) ttcMoisField.value = formatFR(ttcMois);
        if (totalField) totalField.value = formatFR(ttcMois * duree);
    };

    const calculerLoyerRenouvellement = () => {
        const ht = parseNum(document.querySelector('[data-loyer-ht-renouvellement]')?.value);
        const tva = parseNum(document.querySelector('[data-tva-renouvellement-pourcent]')?.value);
        const ttcMois = Math.round(ht * (1 + tva / 100) * 100) / 100;
        const ttcMoisField = document.querySelector('[data-loyer-ttc-renouvellement-mois]');
        const totalField = document.querySelector('[data-montant-total-renouvellement]');
        if (ttcMoisField) ttcMoisField.value = formatFR(ttcMois);
        if (totalField) totalField.value = formatFR(ttcMois * dureeMoisRenouvellement());
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

(function () {
    const parseMoney = (v) => parseFloat(String(v).replace(',', '.')) || 0;

    let updatingLock = false;

    const recalcPctFromCapital = (items) => {
        const totalCapital = Array.from(items).reduce((s, it) => {
            return s + parseMoney(it.querySelector('[data-capital-input]')?.value);
        }, 0);
        items.forEach((item, i) => {
            const pctInput = item.querySelector('[data-percent-input]');
            if (!pctInput) return;
            const capital = parseMoney(item.querySelector('[data-capital-input]')?.value);
            if (totalCapital > 0) {
                const pct = Math.round(capital / totalCapital * 100);
                pctInput.value = i === items.length - 1
                    ? Math.max(0, 100 - Array.from(items).slice(0, -1).reduce((s, it2) => {
                        return s + (parseInt(it2.querySelector('[data-percent-input]')?.value, 10) || 0);
                    }, 0))
                    : Math.min(100, Math.max(0, pct));
            } else {
                pctInput.value = 0;
            }
        });
    };

    const recalcCapitalFromPct = (items) => {
        const refCapital = parseMoney(document.getElementById('societe-capital')?.value);
        const refParts = parseMoney(document.getElementById('societe-part-social')?.value);
        const total = refCapital > 0 ? refCapital : 100000;
        items.forEach((item, i) => {
            const capInput = item.querySelector('[data-capital-input]');
            const partsInput = item.querySelector('[data-field-name="parts"]');
            const pct = parseInt(item.querySelector('[data-percent-input]')?.value, 10) || 0;
            const isLast = i === items.length - 1;
            if (capInput) {
                if (isLast) {
                    const used = Array.from(items).slice(0, -1).reduce((s, it2) => {
                        const p = parseInt(it2.querySelector('[data-percent-input]')?.value, 10) || 0;
                        return s + Math.round(total * p / 100);
                    }, 0);
                    capInput.value = (total - used).toFixed(2);
                } else {
                    capInput.value = Math.round(total * pct / 100).toFixed(2);
                }
            }
            if (partsInput && refParts > 0) {
                if (isLast) {
                    const used = Array.from(items).slice(0, -1).reduce((s, it2) => {
                        const p = parseInt(it2.querySelector('[data-percent-input]')?.value, 10) || 0;
                        return s + Math.round(refParts * p / 100);
                    }, 0);
                    partsInput.value = Math.max(0, refParts - used);
                } else {
                    partsInput.value = Math.round(refParts * pct / 100);
                }
            }
        });
    };

    const updateCapitalSummary = () => {
        const container = document.querySelector('[data-associes-container]');
        const summary = document.querySelector('[data-associe-summary]');
        if (!container || !summary) return;

        const refCapitalEl = document.getElementById('ref-capital');
        const refPartsEl = document.getElementById('ref-parts');
        const refCapital = parseMoney(document.getElementById('societe-capital')?.value);
        const refParts = parseMoney(document.getElementById('societe-part-social')?.value);
        if (refCapitalEl) refCapitalEl.textContent = formatFR(refCapital) + ' DH';
        if (refPartsEl) refPartsEl.textContent = formatFR(refParts, 0);

        const items = container.querySelectorAll('[data-associe-item]');
        let totalParts = 0;
        let totalCapital = 0;

        items.forEach((item) => {
            const parts = parseInt(item.querySelector('[data-field-name="parts"]')?.value, 10) || 0;
            const capital = parseMoney(item.querySelector('[data-capital-input]')?.value);
            totalParts += parts;
            totalCapital += capital;
        });

        document.getElementById('total-parts').textContent = formatFR(totalParts, 0);
        document.getElementById('total-capital').textContent = formatFR(totalCapital) + ' DH';

        if (!updatingLock) {
            updatingLock = true;
            recalcPctFromCapital(items);
            updatingLock = false;
        }

        const totalPct = Array.from(items).reduce((sum, item) => {
            return sum + (parseInt(item.querySelector('[data-percent-input]')?.value, 10) || 0);
        }, 0);

        document.getElementById('total-percent').textContent = formatFR(totalPct, 0) + ' %';

        const statusEl = document.getElementById('capital-status');
        const partsMatch = refParts <= 0 || totalParts === refParts;
        const capitalMatch = refCapital <= 0 || Math.abs(totalCapital - refCapital) < 0.01;
        const pctOk = Math.abs(totalPct - 100) < 1;

        if (totalCapital > 0 || totalParts > 0) {
            if (pctOk && partsMatch && capitalMatch) {
                statusEl.textContent = 'Equilibre';
                statusEl.style.color = 'var(--success)';
            } else {
                const issues = [];
                if (!pctOk) issues.push(formatFR(totalPct, 0) + ' %');
                if (!partsMatch) issues.push('parts: ' + formatFR(totalParts, 0) + '/' + formatFR(refParts, 0));
                if (!capitalMatch) issues.push('capital: ' + formatFR(totalCapital) + '/' + formatFR(refCapital));
                statusEl.textContent = 'Desequilibre (' + issues.join(', ') + ')';
                statusEl.style.color = 'var(--warning)';
            }
        } else {
            statusEl.textContent = 'Incomplet';
            statusEl.style.color = 'var(--danger)';
        }
    };

    const repartirCapital = () => {
        const container = document.querySelector('[data-associes-container]');
        const capital = document.getElementById('societe-capital')?.value || '';
        const partSocial = document.getElementById('societe-part-social')?.value || '';
        if (!container) return;
        const items = container.querySelectorAll('[data-associe-item]');
        const count = items.length;
        if (count === 0) return;
        const cap = parseMoney(capital);
        const parts = parseMoney(partSocial);
        items.forEach((item, i) => {
            const capitalInput = item.querySelector('[data-field-name="capital_detenu"]');
            const partsInput = item.querySelector('[data-field-name="parts"]');
            const isLast = i === count - 1;
            if (capitalInput) {
                const capVal = isLast ? cap - Math.floor(cap / count) * (count - 1) : Math.floor(cap / count);
                capitalInput.value = capVal.toFixed(2);
            }
            if (partsInput) {
                const partsVal = parts > 0 ? (isLast ? parts - Math.floor(parts / count) * (count - 1) : Math.floor(parts / count)) : 0;
                partsInput.value = partsVal;
            }
        });
    };

    const toggleCapitalFields = () => {
        const formeJur = document.querySelector('[name="forme_juridique"]')?.value || '';
        const isSarl = /SARL/i.test(formeJur);
        const isSarlAu = formeJur === 'SARL AU';
        const fields = document.querySelectorAll('[data-capital-field]');
        const summary = document.querySelector('[data-associe-summary]');
        const addBtn = document.querySelector('[data-add-associe]');
        fields.forEach((el) => {
            el.style.display = isSarl ? '' : 'none';
        });
        if (summary) {
            summary.style.display = isSarl ? '' : 'none';
        }
        if (addBtn) {
            addBtn.disabled = isSarlAu;
        }
        if (isSarl) {
            repartirCapital();
            updateCapitalSummary();
        }
        if (isSarlAu) {
            document.querySelectorAll('[data-associe-item]').forEach((item) => {
                const qualite = item.querySelector('[data-field-name="qualite_associe"]');
                const gerant = item.querySelector('[data-field-name="is_gerant"]');
                if (qualite) qualite.value = 'Gerant';
                if (gerant) gerant.value = '1';
            });
        }
    };

    document.addEventListener('change', (e) => {
        if (e.target.matches('[name="forme_juridique"]')) {
            toggleCapitalFields();
        }
    });

    document.addEventListener('input', (e) => {
        if (e.target.closest('[data-capital-input]')) {
            updateCapitalSummary();
        }
    });

    document.addEventListener('change', (e) => {
        const pctInput = e.target.closest('[data-percent-input]');
        if (!pctInput) return;
        const container = document.querySelector('[data-associes-container]');
        if (!container) return;
        const items = container.querySelectorAll('[data-associe-item]');
        const idx = Array.from(items).indexOf(pctInput.closest('[data-associe-item]'));
        if (idx === -1) return;
        updatingLock = true;
        recalcCapitalFromPct(items);
        updatingLock = false;
        updateCapitalSummary();
    });

    const origRefresh = window._refreshIndices;
    const origAdd = document.querySelector('[data-associes-container]')?.__origAdd;
    const associesContainer = document.querySelector('[data-associes-container]');
    if (associesContainer) {
        const observer = new MutationObserver(() => {
            toggleCapitalFields();
            updateCapitalSummary();
        });
        observer.observe(associesContainer, { childList: true });
    }

    toggleCapitalFields();
    updateCapitalSummary();
})();


