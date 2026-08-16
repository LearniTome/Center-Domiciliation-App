document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
        const message = element.getAttribute('data-confirm') || 'Confirmer cette action ?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

(function () {
    const shell = document.querySelector('.shell');
    const saveSidebarState = () => {
        try { localStorage.setItem('sidebar_collapsed', shell.classList.contains('collapsed') ? '1' : '0'); } catch (e) {}
    };
    const updateToggleTitle = () => {
        const btn = document.querySelector('[data-sidebar-toggle]');
        if (btn) {
            const isCollapsed = shell.classList.contains('collapsed');
            btn.title = isCollapsed ? 'Developper la barre de navigation' : 'Reduire la barre de navigation';
            var icon = btn.querySelector('.material-symbols-outlined');
            if (icon) { icon.textContent = isCollapsed ? 'chevron_right' : 'chevron_left'; }
        }
    };
    const toggleTrigger = selector => {
        const el = document.querySelector(selector);
        if (el && shell) {
            el.addEventListener('click', () => {
                shell.classList.toggle('collapsed');
                saveSidebarState();
                updateToggleTitle();
            });
        }
    };
    toggleTrigger('[data-sidebar-toggle]');
    toggleTrigger('.brand-badge');
    updateToggleTitle();

    const main = document.querySelector('.main');
    if (shell && main) {
        main.addEventListener('click', function (e) {
            if (!shell.classList.contains('collapsed')) {
                shell.classList.add('collapsed');
                saveSidebarState();
                updateToggleTitle();
            }
        });
    }
})();
(function () {
    function saveState() {
        var state = {};
        document.querySelectorAll('.nav-section').forEach(function (s) {
            var btn = s.querySelector('[data-nav-toggle]');
            if (btn) {
                state[btn.getAttribute('data-label')] = s.classList.contains('collapsed');
            }
        });
        try { localStorage.setItem('nav_sections', JSON.stringify(state)); } catch (e) {}
    }

    function restoreState() {
        try {
            var raw = localStorage.getItem('nav_sections');
            if (!raw) return;
            var state = JSON.parse(raw);
            document.querySelectorAll('.nav-section').forEach(function (s) {
                var btn = s.querySelector('[data-nav-toggle]');
                if (btn && state[btn.getAttribute('data-label')]) {
                    s.classList.add('collapsed');
                }
            });
        } catch (e) {}
    }

    restoreState();

    document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var section = this.closest('.nav-section');
            if (section) {
                section.classList.toggle('collapsed');
                saveState();
            }
        });
    });

    document.querySelector('[data-collapse-all]')?.addEventListener('click', function () {
        document.querySelectorAll('.nav-section:not(.collapsed)').forEach(function (s) {
            s.classList.add('collapsed');
        });
        saveState();
    });

    document.querySelector('[data-expand-all]')?.addEventListener('click', function () {
        document.querySelectorAll('.nav-section.collapsed').forEach(function (s) {
            s.classList.remove('collapsed');
        });
        saveState();
    });
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
        countEl.classList.toggle('all-visible', visibleCount === total);
        countEl.classList.toggle('some-hidden', visibleCount !== total);
    };

    var refreshCount = function (panel, btn, total) {
        var cv = 0;
        panel.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            if (cb.checked) cv++;
        });
        updateCount(btn, total, cv);
    };

    tables.forEach(function (table) {
        var headers = table.querySelectorAll('thead th[data-col]');
        if (!headers.length) return;

        var container = table.closest('.card')?.querySelector('[data-col-toggle-btn]');
        if (!container) return;

        var panel = document.createElement('div');
        panel.className = 'col-toggle-panel';

        var saved = {};
        try {
            var stored = localStorage.getItem(storageKey);
            if (stored) saved = JSON.parse(stored);
        } catch (e) {}

        var total = headers.length;

        headers.forEach(function (th, idx) {
            var colKey = th.getAttribute('data-col');
            var label = th.textContent.trim();

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            var visible = saved[colKey] !== false;
            checkbox.checked = visible;
            if (!visible) {
                th.classList.add('col-hidden');
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    var cell = row.children[idx];
                    if (cell) cell.classList.add('col-hidden');
                });
            }

            var wrap = document.createElement('label');
            wrap.appendChild(checkbox);
            var txt = document.createElement('span');
            txt.textContent = label;
            wrap.appendChild(txt);
            panel.appendChild(wrap);
        });

        container.parentNode.appendChild(panel);
        refreshCount(panel, container, total);

        panel.addEventListener('click', function (e) {
            var cb = e.target.closest('input[type="checkbox"]');
            if (!cb) cb = e.target.closest('label')?.querySelector('input[type="checkbox"]');
            if (!cb) return;
            var idx = Array.from(panel.querySelectorAll('input[type="checkbox"]')).indexOf(cb);
            if (idx === -1) return;
            var th = headers[idx];
            var colKey = th.getAttribute('data-col');
            var isVisible = cb.checked;
            th.classList.toggle('col-hidden', !isVisible);
            table.querySelectorAll('tbody tr').forEach(function (row) {
                var cell = row.children[idx];
                if (cell) cell.classList.toggle('col-hidden', !isVisible);
            });
            saved[colKey] = isVisible;
            try {
                localStorage.setItem(storageKey, JSON.stringify(saved));
            } catch (e) {}
            refreshCount(panel, container, total);
        });

        container.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.toggle('open');
            refreshCount(panel, container, total);
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

function randFrom(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
function randDate(startYear, endYear) {
    const y = randInt(startYear, endYear);
    const m = String(randInt(1, 12)).padStart(2, '0');
    const d = String(randInt(1, 28)).padStart(2, '0');
    return y + '-' + m + '-' + d;
}
function randPhone() {
    return '0' + randInt(5, 7) + String(randInt(10000000, 99999999));
}
function randCIN() {
    const letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    return letters[randInt(0, letters.length - 1)] + letters[randInt(0, letters.length - 1)] + randInt(100000, 999999);
}
function randEmail(prenom, nom) {
    const slug = (prenom + '.' + nom).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '');
    const domains = ['gmail.com', 'yahoo.fr', 'outlook.com', 'hotmail.ma'];
    return slug + randInt(1, 99) + '@' + randFrom(domains);
}
function randAddr(ville) {
    const rues = ['Rue Mohammed V', 'Boulevard Hassan II', 'Avenue des FAR', 'Rue Moulay Youssef', 'Boulevard Anfa', 'Rue Ibn Batouta', 'Avenue Fal Ould Oumeir', 'Rue Zerktouni'];
    return randInt(1, 200) + ' ' + randFrom(rues) + ', ' + ville;
}

const randNames = {
    noms: ['BENALI', 'IDRISSI', 'AMRANI', 'MOUSSAIDI', 'TAZI', 'BENNANI', 'CHAKIR', 'EL FASSI', 'ALAOUI', 'BENJELLOUN', 'MKOUK', 'KABBAJ', 'SAIDI', 'Ziani', 'BOUZID', 'TAHRI', 'HAMI', 'EL KHALDI', 'RHILO', 'GUERRAOUI', 'BLOUZA', 'MSSAoudi', 'BENKIRANE', 'CHRAIBI', 'LAKHRIFI'],
    prenoms_m: ['Ahmed', 'Mohamed', 'Youssef', 'Khalid', 'Hamid', 'Rachid', 'Omar', 'Karim', 'Said', 'Ali', 'Hassan', 'Mehdi', 'Amine', 'Driss', 'Reda', 'Taha', 'Ismail', 'Ayoub', 'Zakaria', 'Mounir'],
    prenoms_f: ['Fatima', 'Khadija', 'Amina', 'Salma', 'Nadia', 'Sara', 'Leila', 'Najat', 'Samira', 'Hanane', 'Imane', 'Asmae', 'Zineb', 'Houda', 'Siham', 'Latifa', 'Meryem', 'Soumia', 'Rajae', 'Wafae'],
    villes: ['Casablanca', 'Rabat', 'Marrakech', 'Fes', 'Tangier', 'Agadir', 'Meknes', 'Oujda', 'Kenitra', 'Tetouan', 'Safi', 'Mohamadia', 'Khemisset', 'Beni Mellal', 'Settat'],
    activites: ['Commerce general', 'Prestation de services', 'Conseil et audit', 'Transport et logistique', 'Informatique et technologies', 'Import-export', 'BTP et construction', 'Immobilier', 'Industrie manufacturiere', 'Habillement et textile']
};

function generateTestData() {
    var nom = randFrom(randNames.noms);
    var isF = Math.random() > 0.5;
    var prenom = isF ? randFrom(randNames.prenoms_f) : randFrom(randNames.prenoms_m);
    var civilite = isF ? 'Mme' : 'Mr';
    var ville = randFrom(randNames.villes);
    var capital = randFrom([50000, 100000, 200000, 500000, 1000000]);
    var partSocial = randFrom([100, 500, 1000, 2000, 5000, 10000]);
    var vn = randFrom([50, 100, 200, 500, 1000]);
    var loyerHt = randFrom([83.33, 166.67, 250, 500, 750, 1000, 1500]);
    var tva = 20;
    var loyerTtc = Math.round(loyerHt * (1 + tva / 100) * 100) / 100;
    var duree = randFrom([6, 12, 24, 36]);
    var debut = randDate(2025, 2026);
    var debutD = new Date(debut);
    var finD = new Date(debutD);
    finD.setMonth(finD.getMonth() + duree);
    var fin = finD.toISOString().slice(0, 10);
    var ice = String(randInt(100000000, 999999999)) + String(randInt(100000, 999999));

    return {
        _societe: {
            'societe_raison_sociale': 'SARL ' + randFrom(['ATLAS', 'MAGHREB', 'AL AMAL', 'ATLAS', 'SODIA', 'SOTRA', 'SOTEX', 'SOCIED', 'COTRA', 'INTRA']) + ' ' + randFrom(['TRADING', 'CONSULTING', 'SERVICES', 'GROUP', 'INVEST', 'TECH', 'SOLUTIONS', 'INDUSTRIE', 'DISTRIBUTION', 'INTERNATIONAL']),
            'societe_type_generation': 'creation',
            'societe_procedure_creation': 'normal',
            'societe_mode_depot': 'depot_physique',
            'societe_date_ice': randDate(2024, 2025),
            'societe_rc': String(randInt(100000, 999999)),
            'societe_if': String(randInt(100000, 999999)),
            'societe_email': 'contact@' + nom.toLowerCase().replace(/[^a-z]/g, '') + '.ma',
            'societe_telephone': randPhone(),
            'societe_capital': String(capital),
            'societe_part_social': String(partSocial),
            'societe_valeur_nominale': String(vn),
            'societe_date_exp_cert_neg': randDate(2027, 2030),
            'societe_ville': ville,
            'societe_adresse_siege': '',
            'societe_tribunal': '',
            'tribunal_type': 'Tribunal de commerce',
            'societe_activites_statuts': [],
            'societe_ice': ice
        },
        _associe: {
            'civilite': civilite,
            'nom': nom,
            'prenom': prenom,
            'nom_complet': civilite + ' ' + prenom + ' ' + nom,
            'cin': randCIN(),
            'date_validite_cin': randDate(2028, 2035),
            'date_naissance': randDate(1965, 2000),
            'lieu_naissance': '',
            'nationalite': 'Marocaine',
            'telephone': randPhone(),
            'email': randEmail(prenom, nom),
            'adresse': randAddr(ville),
            'qualite': '',
            'parts': String(partSocial),
            'capital_detenu': String(capital),
            'part_percent': '',
            'est_gerant': Math.random() > 0.5 ? '1' : '0',
            'duree_gerance': '12'
        },
        _contrat: {
            'contrat_type': 'Domiciliation commerciale',
            'contrat_date': randDate(2025, 2026),
            'contrat_duree_mois': String(duree),
            'contrat_type_domiciliation': 'Personne Morale',
            'contrat_type_autre': '',
            'contrat_date_debut': debut,
            'contrat_date_fin': fin,
            'contrat_tva_pourcent': String(tva),
            'contrat_loyer_ht': String(loyerHt),
            'contrat_loyer_ttc': String(loyerTtc),
            'contrat_total_ht': String(Math.round(loyerHt * duree * 100) / 100),
            'contrat_type_renouvellement': randFrom(['Annuel', 'Biennal', 'Tacite reconduction']),
            'contrat_renouv_tva_pourcent': String(tva),
            'contrat_renouv_loyer_ht': String(Math.round(loyerHt * 1.05 * 100) / 100),
            'contrat_renouv_loyer_ttc': String(Math.round(loyerHt * 1.05 * (1 + tva / 100) * 100) / 100),
            'contrat_renouv_total_ht': String(Math.round(loyerHt * 1.05 * duree * 100) / 100),
            'contrat_statut': 'actif',
            'contrat_notes': 'Contrat genere automatiquement pour test',
            'contrat_date_exp_cert_neg': ''
        }
    };
}

function isSelectEmpty(selectEl) {
    if (!selectEl || selectEl.tagName !== 'SELECT') return true;
    var v = selectEl.value;
    return !v || v === 'Selectionner' || v === 'selectionner';
}
function pickRandomOption(selectEl) {
    if (!selectEl || selectEl.tagName !== 'SELECT') return;
    var opts = Array.from(selectEl.options).filter(function(o) {
        return o.value && o.value !== 'Selectionner' && o.value !== 'selectionner' && !o.disabled;
    });
    if (opts.length) selectEl.value = randFrom(opts).value;
}

function pickRandomAddrOption(selectEl, villeVal) {
    if (!selectEl || selectEl.tagName !== 'SELECT') return;
    var opts = Array.from(selectEl.options).filter(function(o) {
        if (!o.value || o.value === 'Selectionner') return false;
        var v = o.getAttribute('data-ville');
        return !v || v === villeVal;
    });
    if (!opts.length) opts = Array.from(selectEl.options).filter(function(o) {
        return o.value && o.value !== 'Selectionner';
    });
    if (opts.length) selectEl.value = randFrom(opts).value;
}

document.addEventListener('click', function(event) {
    var button = event.target.closest('[data-fill-test]');
    if (!button) return;
    event.preventDefault();

    var form = button.closest('form');
    if (!form) return;

    var data = generateTestData();
    var step = button.getAttribute('data-fill-test');
    var source = {};

    if (step === '1' || (!step && form.querySelector('[name="societe_raison_sociale"]'))) {
        source = data._societe;
    } else if (step === '2' || (!step && form.querySelector('[name*="[nom]"]'))) {
        source = data._associe;
    } else if (step === '3' || (!step && form.querySelector('[name="contrat_type"]'))) {
        source = data._contrat;
    } else {
        source = Object.assign({}, data._societe, data._associe, data._contrat);
    }

    form.querySelectorAll('input, select, textarea').forEach(function(field) {
        var name = field.getAttribute('name');
        if (!name) return;

        var key = name.replace(/^associes\[\d+\]\[(\w+)\]$/, '$1')
                      .replace(/^associe_\w+\[\d+\]$/, '')
                      .replace(/^\w+\[\d+\]$/, '');

        var value = source[key];
        if (value === undefined || value === '') return;

        if (field.tagName === 'SELECT') {
            var opt = Array.from(field.options).find(function(o) { return o.value === value; });
            if (opt) field.value = value;
            else pickRandomOption(field);
        } else if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = String(field.value) === String(value);
        } else {
            field.value = value;
        }
    });

    var villeEl = form.querySelector('[name="societe_ville"]');
    var villeVal = villeEl ? villeEl.value : '';
    var addrEl = form.querySelector('[name="societe_adresse_siege"]');
    if (addrEl && isSelectEmpty(addrEl)) pickRandomAddrOption(addrEl, villeVal);

    var tribEl = form.querySelector('[name="societe_tribunal"]');
    if (tribEl && isSelectEmpty(tribEl)) pickRandomOption(tribEl);

    var fjEl = form.querySelector('[name="societe_forme_juridique"]');
    if (fjEl && isSelectEmpty(fjEl)) pickRandomOption(fjEl);

    var actStatuts = form.querySelector('[name="societe_activites_statuts[]"]');
    if (actStatuts && isSelectEmpty(actStatuts)) pickRandomOption(actStatuts);

    var actOmpic = form.querySelector('[name="societe_activites_ompic"]');
    if (actOmpic && isSelectEmpty(actOmpic)) pickRandomOption(actOmpic);

    var lieuxN = form.querySelectorAll('[name*="[lieu_naissance]"]');
    lieuxN.forEach(function(el) { if (isSelectEmpty(el)) pickRandomOption(el); });

    var quals = form.querySelectorAll('[name*="[qualite]"]');
    quals.forEach(function(el) { if (isSelectEmpty(el)) pickRandomOption(el); });

    var natSelects = form.querySelectorAll('[name*="[nationalite]"]');
    natSelects.forEach(function(el) {
        var natOpts = Array.from(el.options).filter(function(o) { return o.value === 'Marocaine'; });
        if (natOpts.length) el.value = 'Marocaine';
        else pickRandomOption(el);
    });

    form.querySelectorAll('input, select, textarea').forEach(function(f) {
        f.dispatchEvent(new Event('input', { bubbles: true }));
        f.dispatchEvent(new Event('change', { bubbles: true }));
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
        var procCreation = form.querySelector('[name="societe_procedure_creation"]');
        var modeDepot = form.querySelector('[name="societe_mode_depot"]');
        var statutsSection = form.querySelector('[data-statuts-section]');
        if (!procCreation || !modeDepot) return;
        var isDomiciliation = typeGen.value === 'domiciliation';
        procCreation.disabled = isDomiciliation;
        modeDepot.disabled = isDomiciliation;
        if (statutsSection) {
            statutsSection.style.display = isDomiciliation ? 'none' : '';
        }
        if (isDomiciliation) {
            procCreation.value = '';
            modeDepot.value = '';
        }
    };

    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches('[name="societe_type_generation"]')) {
            toggleProcedureFields(e.target);
        }
    });

    var typeGen = document.querySelector('[name="societe_type_generation"]');
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

(function () {
    document.querySelectorAll('[data-activites-group]').forEach(function (group) {
        const container = group.querySelector('[data-activites-container]');
        const template = group.querySelector('[data-activite-template]');
        const addBtn = group.querySelector('[data-add-activite]');
        const refBtn = group.querySelector('[data-add-activite-ref]');
        const multipleBtn = group.querySelector('[data-add-activites-multiple]');
        if (!container || !template) return;

        const selectName = function () {
            const firstSelect = container.querySelector('select');
            return firstSelect ? firstSelect.getAttribute('name') : 'activites[]';
        };

        const addOptionToSelects = function (value) {
            container.querySelectorAll('select').forEach(function (sel) {
                const exists = Array.from(sel.options).some(function (o) { return o.value === value; });
                if (!exists) {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = value;
                    sel.appendChild(opt);
                }
            });
        };

        const createActiviteItem = function (value) {
            const clone = template.content.firstElementChild.cloneNode(true);
            if (value) {
                const sel = clone.querySelector('select');
                if (sel) {
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = value;
                    sel.appendChild(opt);
                    sel.value = value;
                }
            }
            return clone;
        };

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                const clone = createActiviteItem();
                if (clone) container.appendChild(clone);
            });
        }

        if (refBtn) {
            refBtn.addEventListener('click', function () {
                const name = window.prompt('Saisissez le nom de la nouvelle activite:');
                if (!name || name.trim() === '') return;
                const form = this.closest('form');
                if (!form) return;
                const csrf = form.querySelector('input[name="csrf_token"]');
                if (!csrf) return;
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        csrf_token: csrf.value,
                        add_activite_ref: '1',
                        new_activite: name.trim()
                    })
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        addOptionToSelects(data.value);
                        var items = container.querySelectorAll('[data-activite-item]');
                        if (items.length > 0) {
                            var lastSelect = items[items.length - 1].querySelector('select');
                            if (lastSelect) lastSelect.value = data.value;
                        }
                    } else {
                        alert('Erreur lors de l\'ajout de l\'activite.');
                    }
                })
                .catch(function () {
                    alert('Erreur de communication avec le serveur.');
                });
            });
        }

        if (multipleBtn) {
            multipleBtn.addEventListener('click', function () {
                const count = window.prompt('Combien d\'activites voulez-vous ajouter ?', '2');
                const n = parseInt(count, 10);
                if (isNaN(n) || n < 1) return;
                for (let i = 0; i < n; i++) {
                    const clone = createActiviteItem();
                    if (clone) container.appendChild(clone);
                }
            });
        }

        container.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('[data-remove-activite]');
            if (!removeBtn) return;
            const items = container.querySelectorAll('[data-activite-item]');
            if (items.length <= 1) return;
            const item = removeBtn.closest('[data-activite-item]');
            if (item) item.remove();
        });
    });
})();

(function () {
    document.querySelectorAll('[data-add-activite-cn]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const code = window.prompt('Code OMPIC (ex: 4711B):');
            if (!code || code.trim() === '') return;
            const label = window.prompt('Libelle (ex: Commerce de detail alimentaire):');
            if (!label || label.trim() === '') return;
            const form = this.closest('form');
            if (!form) return;
            const csrf = form.querySelector('input[name="csrf_token"]');
            if (!csrf) return;
            const select = form.querySelector('[data-ompic-select]');
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    csrf_token: csrf.value,
                    add_activite_ref: '1',
                    type: 'cert_neg',
                    new_activite: label.trim(),
                    ompic_code: code.trim(),
                    nma_libelle: label.trim()
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && select) {
                    var display = data.code + ' - ' + data.libelle;
                    var exists = Array.from(select.options).some(function (o) { return o.value === data.code; });
                    if (!exists) {
                        var opt = document.createElement('option');
                        opt.value = data.code;
                        opt.textContent = display;
                        select.appendChild(opt);
                    }
                    select.value = data.code;
                } else {
                    alert('Erreur lors de l\'ajout de l\'activite.');
                }
            })
            .catch(function () {
                alert('Erreur de communication avec le serveur.');
            });
        });
    });
})();

(function () {
    document.querySelectorAll('[data-tribunal-type]').forEach(function (typeSelect) {
        var tribSelect = typeSelect.closest('.form-grid, .card')?.querySelector('[name="societe_tribunal"], [name="tribunal"]');
        if (!tribSelect) return;
        var filter = function () {
            var type = typeSelect.value;
            Array.from(tribSelect.options).forEach(function (opt) {
                if (opt.value === '') return;
                opt.style.display = !type || opt.getAttribute('data-type') === type ? '' : 'none';
            });
            if (tribSelect.value) {
                var selected = tribSelect.options[tribSelect.selectedIndex];
                if (selected && selected.style.display === 'none') {
                    tribSelect.value = '';
                }
            }
        };
        typeSelect.addEventListener('change', filter);
        filter();
    });
})();

(function () {
    document.querySelectorAll('[data-ville-filter]').forEach(function (villeSelect) {
        var addrSelect = villeSelect.closest('.form-grid, .card')?.querySelector('[name="societe_adresse_siege"]');
        if (!addrSelect) return;
        var filter = function () {
            var ville = villeSelect.value;
            Array.from(addrSelect.options).forEach(function (opt) {
                if (opt.value === '') return;
                opt.style.display = !ville || opt.getAttribute('data-ville') === ville ? '' : 'none';
            });
            if (addrSelect.value) {
                var selected = addrSelect.options[addrSelect.selectedIndex];
                if (selected && selected.style.display === 'none') {
                    addrSelect.value = '';
                }
            }
        };
        villeSelect.addEventListener('change', filter);
        filter();
    });
})();

(function () {
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
        const type = document.querySelector('[name="contrat_type_renouvellement"]')?.value || '';
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
    document.querySelector('[name="contrat_type_renouvellement"]')?.addEventListener('change', calculerLoyerRenouvellement);

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
                const qualite = item.querySelector('[data-field-name="qualite"]');
                const gerant = item.querySelector('[data-field-name="est_gerant"]');
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

(function () {
     document.querySelectorAll('table[data-sortable]').forEach(function (table) {
         var thead = table.querySelector('thead');
         if (!thead) return;
         var ths = thead.querySelectorAll('th[data-col]');
         var tbody = table.querySelector('tbody');
         if (!tbody) return;

         // Une valeur est numérique seulement si, une fois épurée (espaces, virgule décimale),
         // elle forme un nombre complet. "DOM-2026-001" contient des lettres => tri texte.
         function isFiniteNumber(v) {
             if (/[A-Za-z]/.test(v)) return false;
             var cleaned = v.replace(/\s/g, '').replace(',', '.');
             if (cleaned === '' || cleaned === '-') return false;
             var n = parseFloat(cleaned);
             return !isNaN(n) && String(n) === cleaned;
         }


        ths.forEach(function (th) {
            th.style.cursor = 'pointer';
            th.style.userSelect = 'none';

            var icon = document.createElement('span');
            icon.className = 'material-symbols-outlined';
            icon.textContent = 'unfold_more';
            icon.style.marginLeft = '4px';
            icon.style.fontSize = '0.85rem';
            icon.style.opacity = '0.35';
            icon.style.verticalAlign = 'middle';
            th.appendChild(icon);

            th.addEventListener('click', function () {
                var key = th.getAttribute('data-col');
                var order = th.getAttribute('data-order') || 'none';

                ths.forEach(function (other) {
                    other.removeAttribute('data-order');
                    var ic = other.querySelector('.material-symbols-outlined');
                    if (ic) { ic.className = 'material-symbols-outlined'; ic.textContent = 'unfold_more'; ic.style.opacity = '0.35'; }
                });

                var newOrder = order === 'asc' ? 'desc' : 'asc';
                th.setAttribute('data-order', newOrder);
                icon.className = 'material-symbols-outlined';
                icon.textContent = newOrder === 'asc' ? 'arrow_upward' : 'arrow_downward';
                icon.style.opacity = '1';

                var rows = Array.from(tbody.querySelectorAll('tr'));
                var colIdx = Array.from(th.parentNode.children).indexOf(th);

                rows.sort(function (a, b) {
                    var aTd = a.children[colIdx];
                    var bTd = b.children[colIdx];
                    if (!aTd || !bTd) return 0;
                    var aVal = aTd.textContent.trim();
                    var bVal = bTd.textContent.trim();

                    var aNum = parseFloat(aVal.replace(/[^\d.,-]/g, '').replace(',', '.'));
                    var bNum = parseFloat(bVal.replace(/[^\d.,-]/g, '').replace(',', '.'));
                    var isNum = isFiniteNumber(aVal) && isFiniteNumber(bVal);

                    var cmp = isNum ? aNum - bNum : aVal.localeCompare(bVal, 'fr', { numeric: true });
                    return newOrder === 'asc' ? cmp : -cmp;
                });

                rows.forEach(function (row) { tbody.appendChild(row); });
            });
        });
    });
})();

(function () {
    var bar = document.querySelector('.page-count-bar');
    var counts = document.querySelectorAll('.page-count');
    if (bar && counts.length) {
        counts.forEach(function (el) {
            bar.appendChild(el);
        });
    }
})();

document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-apply-ai-fill]');
    if (!btn) return;
    event.preventDefault();

    var suggestionsStr = btn.getAttribute('data-apply-ai-fill');
    if (!suggestionsStr) return;

    var suggestions;
    try {
        suggestions = JSON.parse(suggestionsStr);
    } catch (e) {
        return;
    }

    var form = btn.closest('form');
    if (!form) return;

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
        var name = field.getAttribute('name');
        if (!name) return;

        var value = suggestions[name] !== undefined ? suggestions[name] : suggestions[name.replace(/^associes\[\d+\]\[(\w+)\]$/, '$1')];
        if (value === undefined) return;

        if (field.tagName === 'SELECT') {
            var option = Array.from(field.options).find(function (opt) { return String(opt.value) === String(value); });
            if (option) field.value = value;
        } else if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = String(field.value) === String(value);
        } else {
            field.value = value;
        }
    });

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
        var name = field.getAttribute('name');
        if (!name) return;
        var key = name.replace(/^associes\[\d+\]\[(\w+)\]$/, '$1');
        if (suggestions[key] === undefined && suggestions[name] === undefined) return;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

// ──────────────────────────────────────────────
// Notification System (Dropdown, Polling, Toast)
// ──────────────────────────────────────────────
(function () {
    var bell = document.querySelector('[data-notif-bell]');
    var dropdown = document.querySelector('[data-notif-dropdown]');
    var list = document.querySelector('[data-notif-dropdown-list]');
    var countEl = document.querySelector('[data-notif-dropdown-count]');
    var markAllBtn = document.querySelector('[data-notif-mark-all]');
    var badge = document.querySelector('.notif-badge-count');
    var toastContainer = document.querySelector('.notif-toast-container');
    var csrfToken = null;
    var lastCount = parseInt(badge ? badge.textContent : '0', 10) || 0;
    var pollingInterval = null;

    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'notif-toast-container';
        document.body.appendChild(toastContainer);
    }

    function getCsrf() {
        if (csrfToken) return csrfToken;
        var inp = document.querySelector('input[name="csrf_token"]');
        if (inp) csrfToken = inp.value;
        return csrfToken || '';
    }

    function updateBadge(count) {
        lastCount = count;
        if (!badge && bell) {
            badge = document.createElement('span');
            badge.className = 'notif-badge notif-badge-count';
            bell.appendChild(badge);
        }
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
        var nb = document.querySelector('#nav-notif-badge');
        if (nb) {
            nb.textContent = count > 99 ? '99+' : count;
            nb.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }

    function renderEmpty() {
        list.innerHTML = '<div class="notif-dropdown-empty"><span class="material-symbols-outlined">notifications_off</span>Aucune notification</div>';
    }

    function fetchNotifications() {
        if (!list) return;
        list.innerHTML = '<div class="notif-dropdown-loading"><span class="material-symbols-outlined">sync</span> Chargement...</div>';
        fetch('index.php?page=notif-ajax&action=list&_=' + Date.now())
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.error) { list.innerHTML = '<div class="notif-dropdown-empty">Erreur</div>'; return; }
                updateBadge(d.count);
                if (countEl) countEl.textContent = d.total + ' non lue' + (d.total > 1 ? 's' : '');
                if (d.total === 0) { renderEmpty(); return; }
                list.innerHTML = d.html;
                // Make items clickable: clicking anywhere navigates to link
                list.querySelectorAll('.notif-item').forEach(function (item) {
                    var markBtn = item.querySelector('[data-notif-mark]');
                    var href = item.getAttribute('data-link');
                    if (!href || href === '#') href = null;
                    if (!href) return;
                    item.style.cursor = 'pointer';
                    item.addEventListener('click', function (e) {
                        if (e.target.closest('[data-notif-mark]')) return;
                        var id = item.getAttribute('data-notif-id');
                        // Auto-mark as read before navigating (fire-and-forget)
                        if (id) {
                            var t = getCsrf();
                            if (t) {
                                var fd = new FormData();
                                fd.append('csrf_token', t);
                                fd.append('action', 'mark_read');
                                fd.append('id', id);
                                fetch('index.php?page=notif-ajax&action=mark_read', { method: 'POST', body: fd }).catch(function(){});
                            }
                        }
                        window.location.href = href;
                    });
                });
            })
            .catch(function () { list.innerHTML = '<div class="notif-dropdown-empty">Erreur</div>'; });
    }

    function fetchCount() {
        fetch('index.php?page=notif-ajax&action=count&_=' + Date.now())
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.count !== undefined && d.count !== lastCount) {
                    updateBadge(d.count);
                    if (d.count > lastCount) {
                        if (bell) bell.classList.add('shake');
                        setTimeout(function () { if (bell) bell.classList.remove('shake'); }, 600);
                        showToast(d.count - lastCount);
                    }
                }
            })
            .catch(function () {});
    }

    function playChime() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var g = ctx.createGain();
            g.connect(ctx.destination);
            g.gain.value = 0.08;
            var o = ctx.createOscillator();
            o.type = 'sine';
            o.frequency.value = 660;
            o.connect(g);
            o.start(0);
            o.stop(ctx.currentTime + 0.12);
            var o2 = ctx.createOscillator();
            o2.type = 'sine';
            o2.frequency.value = 880;
            var g2 = ctx.createGain();
            g2.connect(ctx.destination);
            g2.gain.value = 0.06;
            o2.connect(g2);
            o2.start(ctx.currentTime + 0.1);
            o2.stop(ctx.currentTime + 0.25);
        } catch (e) {}
    }

    function showToast(newCount) {
        fetch('index.php?page=notif-ajax&action=list&_=' + Date.now())
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.html || d.total === 0) return;
                var temp = document.createElement('div');
                temp.innerHTML = d.html;
                var items = temp.querySelectorAll('.notif-item');
                for (var i = 0; i < Math.min(newCount, items.length); i++) {
                    (function (item) {
                        var t = document.createElement('div');
                        t.className = 'notif-toast';
                        var toastId = item.getAttribute('data-notif-id');
                        t.innerHTML = item.innerHTML;
                        var href = item.getAttribute('data-link');
                        if (href && href !== '#') {
                            t.style.cursor = 'pointer';
                            t.addEventListener('click', function () {
                                if (toastId) {
                                    var t2 = getCsrf();
                                    if (t2) {
                                        var fd = new FormData();
                                        fd.append('csrf_token', t2);
                                        fd.append('action', 'mark_read');
                                        fd.append('id', toastId);
                                        fetch('index.php?page=notif-ajax&action=mark_read', { method: 'POST', body: fd }).catch(function(){});
                                    }
                                }
                                window.location.href = href;
                            });
                        }
                        var mb = t.querySelector('[data-notif-mark]');
                        if (mb) mb.remove();
                        toastContainer.appendChild(t);
                        var timer = setTimeout(function () {
                            t.classList.add('removing');
                            setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 300);
                        }, 5000);
                        t.addEventListener('mouseenter', function () { clearTimeout(timer); });
                        t.addEventListener('mouseleave', function () {
                            timer = setTimeout(function () {
                                t.classList.add('removing');
                                setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 300);
                            }, 2000);
                        });
                        })(items[i]);
                    }
                    playChime();
                })
            .catch(function () {});
    }

    // ── Dropdown toggle ──
    if (bell && dropdown) {
        bell.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
            if (dropdown.classList.contains('open')) fetchNotifications();
        });

        // Mark individual
        dropdown.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-notif-mark]');
            if (!btn) return;
            e.stopPropagation();
            var id = btn.getAttribute('data-notif-mark');
            if (!id) return;
            var t = getCsrf();
            if (!t) return;
            var fd = new FormData();
            fd.append('csrf_token', t);
            fd.append('action', 'mark_read');
            fd.append('id', id);
            fetch('index.php?page=notif-ajax&action=mark_read', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.success) return;
                    var item = btn.closest('.notif-item');
                    if (item) item.remove();
                    updateBadge(d.count);
                    if (countEl) {
                        var rem = list.querySelectorAll('.notif-item').length;
                        countEl.textContent = rem + ' non lue' + (rem > 1 ? 's' : '');
                    }
                    if (list.querySelectorAll('.notif-item').length === 0) renderEmpty();
                })
                .catch(function () {});
        });

        // Close on "Voir tout"
        dropdown.querySelector('a[href*="page=notifications"]')?.addEventListener('click', function () {
            dropdown.classList.remove('open');
        });
    }

    // ── Mark all ──
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            var t = getCsrf();
            if (!t) return;
            var fd = new FormData();
            fd.append('csrf_token', t);
            fd.append('action', 'mark_all_read');
            fetch('index.php?page=notif-ajax&action=mark_all_read', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.success) return;
                    updateBadge(d.count);
                    if (list) renderEmpty();
                    if (countEl) countEl.textContent = '0 non lue';
                })
                .catch(function () {});
        });
    }

    // ── Close on outside click ──
    document.addEventListener('click', function (e) {
        var wrap = document.querySelector('.notif-bell-wrap');
        if (wrap && !wrap.contains(e.target) && dropdown) {
            dropdown.classList.remove('open');
        }
    });

    // ── Polling ──
    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(function () {
            if (!document.hidden) fetchCount();
        }, 30000);
    }
    startPolling();
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { fetchCount(); startPolling(); }
    });

    // ── AJAX mark-read on notifications full page ──
    var pageList = document.querySelector('.notif-list');
    if (pageList) {
        pageList.addEventListener('click', function (e) {
            var btn = e.target.closest('button[type="submit"]');
            if (!btn) return;
            var form = btn.closest('form');
            if (!form) return;
            var ai = form.querySelector('input[name="action"]');
            var ii = form.querySelector('input[name="id"]');
            if (!ai || ai.value !== 'mark_read') return;
            e.preventDefault();
            var id = ii ? ii.value : null;
            if (!id) return;
            var tok = form.querySelector('input[name="csrf_token"]');
            if (!tok) return;
            var fd = new FormData();
            fd.append('csrf_token', tok.value);
            fd.append('action', 'mark_read');
            fd.append('id', id);
            fetch('index.php?page=notif-ajax&action=mark_read', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.success) return;
                    var item = btn.closest('.notif-item');
                    if (item) {
                        item.classList.remove('unread');
                        var f = btn.closest('form');
                        if (f) f.remove();
                        // If no forms left, mark-all button might need updating
                        if (!pageList.querySelector('button[type="submit"]')) {
                            var headerForm = document.querySelector('.notif-page-header form');
                            if (headerForm) headerForm.style.display = 'none';
                        }
                    }
                    updateBadge(d.count);
                })
                .catch(function () {});
        });

        // Auto-mark read when clicking visibility link
        pageList.addEventListener('click', function (e) {
            var link = e.target.closest('a[title="Voir"]');
            if (!link) return;
            var item = link.closest('.notif-item');
            if (!item) return;
            var id = item.getAttribute('data-notif-id');
            if (!id) return;
            var t = getCsrf();
            if (!t) return;
            var fd = new FormData();
            fd.append('csrf_token', t);
            fd.append('action', 'mark_read');
            fd.append('id', id);
            fetch('index.php?page=notif-ajax&action=mark_read', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.success) return;
                    item.classList.remove('unread');
                    var f = item.querySelector('form');
                    if (f) f.remove();
                    updateBadge(d.count);
                    if (!pageList.querySelector('button[type="submit"]')) {
                        var headerForm = document.querySelector('.notif-page-header form');
                        if (headerForm) headerForm.style.display = 'none';
                    }
                })
                .catch(function () {});
        });

        // AJAX mark-all on full page
        var headerForm = document.querySelector('.notif-page-header form');
        if (headerForm) {
            headerForm.addEventListener('submit', function (e) {
                var ai = headerForm.querySelector('input[name="action"]');
                if (!ai || ai.value !== 'mark_all_read') return;
                e.preventDefault();
                var tok = headerForm.querySelector('input[name="csrf_token"]');
                if (!tok) return;
                var fd = new FormData();
                fd.append('csrf_token', tok.value);
                fd.append('action', 'mark_all_read');
                fetch('index.php?page=notif-ajax&action=mark_all_read', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d.success) return;
                        pageList.querySelectorAll('.notif-item').forEach(function (item) {
                            item.classList.remove('unread');
                        });
                        pageList.querySelectorAll('.notif-item form').forEach(function (f) { f.remove(); });
                        headerForm.style.display = 'none';
                        updateBadge(d.count);
                    })
                    .catch(function () {});
            });
        }
    }

    // Refresh CSRF on focus
    document.addEventListener('focus', function () { csrfToken = null; }, true);

    // ── Live Clock ──
    (function () {
        var clock = document.getElementById('top-bar-clock');
        if (!clock) return;
        function tick() {
            var now = new Date();
            var days = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
            var months = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
            var d = days[now.getDay()];
            var dd = now.getDate();
            var m = months[now.getMonth()];
            var y = now.getFullYear();
            var h = String(now.getHours()).padStart(2,'0');
            var min = String(now.getMinutes()).padStart(2,'0');
            var s = String(now.getSeconds()).padStart(2,'0');
            clock.textContent = d + ' ' + dd + ' ' + m + ' ' + y + ' — ' + h + ':' + min + ':' + s;
        }
        tick();
        setInterval(tick, 1000);
    })();

    // ── Live Search (AJAX, no page reload) ──
    document.querySelectorAll('.search-bar').forEach(function (form) {
        var input = form.querySelector('input[type="search"]');
        if (!input) return;
        var timer = null;

        // Store search function on form for external access
        form._doSearch = function () {
            clearTimeout(timer);
            var q = input.value.trim();
            var params = new URLSearchParams(window.location.search);
            if (q) { params.set('q', q); } else { params.delete('q'); }

            var newUrl = window.location.pathname + '?' + params.toString();
            history.replaceState(null, '', newUrl);

            fetch(newUrl)
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');

                    var oldTable = form.closest('.card')?.querySelector('table');
                    var newTable = doc.querySelector('table');
                    if (oldTable && newTable) {
                        var oldTbody = oldTable.querySelector('tbody');
                        var newTbody = newTable.querySelector('tbody');
                        if (oldTbody && newTbody) {
                            oldTbody.innerHTML = newTbody.innerHTML;
                        }
                    }

                    var oldCount = document.querySelector('.page-count');
                    var newCount = doc.querySelector('.page-count');
                    if (oldCount && newCount) {
                        oldCount.textContent = newCount.textContent;
                    }

                    var effacer = form.querySelector('.btn-cancel');
                    if (q) {
                        if (!effacer) {
                            var a = document.createElement('a');
                            a.className = 'btn btn-cancel';
                            a.innerHTML = '<span class="material-symbols-outlined">close</span> Effacer';
                            a.href = '#';
                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                input.value = '';
                                form._doSearch();
                            });
                            form.querySelector('.inline-form')?.appendChild(a);
                        }
                    } else {
                        if (effacer) effacer.remove();
                    }
                })
                .catch(function () {});
        };

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form._doSearch(); }, 400);
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            form._doSearch();
        });

        // Intercept static "Effacer" link rendered by PHP
        var effacer = form.querySelector('.btn-cancel');
        if (effacer) {
            effacer.addEventListener('click', function (e) {
                e.preventDefault();
                input.value = '';
                form._doSearch();
            });
        }
    });

    // ── Auto-focus search input when query is present ──
    (function () {
        var input = document.querySelector('.search-bar input[type="search"]');
        if (!input) return;
        if (input.value.trim() !== '') {
            input.focus();
            input.selectionStart = input.selectionEnd = input.value.length;
        }
    })();

    // ── Import Excel Modal (2-step: upload → preview + edit → confirm) ──
    (function () {
        var uploadModal = document.querySelector('[data-modal="import-upload"]');
        var previewModal = document.querySelector('[data-modal="import-preview"]');
        var openBtns = document.querySelectorAll('[data-import-btn]');
        if (!uploadModal || !previewModal || openBtns.length === 0) return;

        var uploadForm = uploadModal.querySelector('[data-import-upload-form]');
        var uploadError = uploadModal.querySelector('[data-import-upload-error]');
        var previewBody = previewModal.querySelector('[data-import-preview-body]');
        var previewTable = previewModal.querySelector('[data-import-preview-table]');
        var previewError = previewModal.querySelector('[data-import-preview-error]');
        var importCount = previewModal.querySelector('[data-import-count]');
        var confirmBtn = previewModal.querySelector('[data-import-confirm]');
        var currentTable = '';
        var currentData = null;

        // ── CSRF helper ──
        function getCsrf() {
            var el = document.querySelector('input[name="csrf_token"], input[name="_csrf_token"]');
            return el ? el.value : '';
        }

        // ── Modal helpers ──
        function openModal(modal) {
            modal.classList.add('open');
        }
        function closeModal(modal) {
            modal.classList.remove('open');
        }

        // Close on overlay click / Escape
        function initModalClose(modal) {
            modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
                el.addEventListener('click', function () { closeModal(modal); });
            });
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal(modal);
            });
        }
        initModalClose(uploadModal);
        initModalClose(previewModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal(uploadModal);
                closeModal(previewModal);
            }
        });

        // ── Open upload modal ──
        openBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentTable = btn.getAttribute('data-import-btn') || '';
                uploadForm.querySelector('input[name="table"]').value = currentTable;
                uploadForm.reset();
                uploadError.style.display = 'none';
                openModal(uploadModal);
            });
        });

        // ── Step 1: Upload + preview ──
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(uploadForm);
            var token = getCsrf();
            if (token) fd.set('_csrf_token', token);

            var submitBtn = uploadForm.querySelector('button[type="submit"]');
            var origHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Analyse...';
            uploadError.style.display = 'none';

            fetch('api.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json.success) {
                        uploadError.textContent = json.message || 'Erreur lors de l\'analyse du fichier.';
                        uploadError.style.display = '';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origHtml;
                        return;
                    }

                    currentData = json;
                    renderPreview(json);
                    closeModal(uploadModal);
                    openModal(previewModal);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origHtml;
                })
                .catch(function () {
                    uploadError.textContent = 'Erreur réseau. Veuillez réessayer.';
                    uploadError.style.display = '';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origHtml;
                });
        });

        // ── Render preview table ──
        function renderPreview(data) {
            var headers = data.headers || [];
            var rows = data.rows || [];
            var expected = data.expected_headers || [];

            importCount.textContent = rows.length;
            previewError.style.display = 'none';

            // Build thead
            var thead = previewTable.querySelector('thead');
            thead.innerHTML = '';
            var tr = document.createElement('tr');
            headers.forEach(function (h) {
                var th = document.createElement('th');
                th.setAttribute('data-col', h);
                th.textContent = h;
                if (expected.indexOf(h) === -1) {
                    th.classList.add('col-extra');
                }
                tr.appendChild(th);
            });
            thead.appendChild(tr);

            // Build tbody with editable inputs
            var tbody = previewTable.querySelector('tbody');
            tbody.innerHTML = '';
            rows.forEach(function (row, ri) {
                var tr2 = document.createElement('tr');
                headers.forEach(function (h) {
                    var td = document.createElement('td');
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'import-edit-input';
                    input.value = row[h] !== null && row[h] !== undefined ? String(row[h]) : '';
                    input.dataset.row = String(ri);
                    input.dataset.col = h;
                    if (expected.indexOf(h) === -1) {
                        input.classList.add('col-extra');
                    }
                    td.appendChild(input);
                    tr2.appendChild(td);
                });
                tbody.appendChild(tr2);
            });

        }

        // ── Step 2: Confirm import ──
        confirmBtn.addEventListener('click', function () {
            if (!currentData) return;

            var headers = currentData.headers || [];
            var inputs = previewTable.querySelectorAll('tbody input.import-edit-input');
            var rowCount = currentData.rows.length;

            // Collect modified data
            var rows = [];
            for (var ri = 0; ri < rowCount; ri++) {
                var row = {};
                for (var ci = 0; ci < headers.length; ci++) {
                    var input = previewTable.querySelector(
                        'tbody input.import-edit-input[data-row="' + ri + '"][data-col="' + headers[ci] + '"]'
                    );
                    row[headers[ci]] = input ? input.value : '';
                }
                rows.push(row);
            }

            var fd = new FormData();
            var token = getCsrf();
            if (token) fd.set('_csrf_token', token);
            fd.set('action', 'import_confirm');
            fd.set('table', currentTable);
            fd.set('import_data', JSON.stringify(rows));

            var origHtml = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Import en cours...';

            fetch('api.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json.success) {
                        previewError.textContent = json.message || 'Erreur lors de l\'import.';
                        previewError.style.display = '';
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = origHtml;
                        return;
                    }

                    closeModal(previewModal);
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = origHtml;

                    // Reload page to show new data
                    var msg = json.message || (json.imported + ' ligne(s) importée(s).');
                    var params = new URLSearchParams(window.location.search);
                    params.set('import_msg', msg);
                    window.location.search = params.toString();
                })
                .catch(function () {
                    previewError.textContent = 'Erreur réseau lors de l\'import.';
                    previewError.style.display = '';
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = origHtml;
                });
        });
    })();
})();

