(function () {
    'use strict';

    // ── CSRF token cache ──
    var csrfToken = null;
    function getCsrfToken() {
        if (csrfToken) return csrfToken;
        var el = document.querySelector('input[name="csrf_token"]') || document.querySelector('input[name="_csrf_token"]');
        if (el) csrfToken = el.value;
        return csrfToken;
    }

    // ── Quick Create Modal ──
    (function () {
        function initQuickCreate(modal, form, openBtns) {
            if (!modal || !form || !openBtns || openBtns.length === 0) return;

            function open() { modal.classList.add('open'); }
            function close() { modal.classList.remove('open'); }

            openBtns.forEach(function (btn) { btn.addEventListener('click', open); });

            modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
                el.addEventListener('click', close);
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('open')) close();
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(form);
                var token = getCsrfToken();
                if (token) fd.set('_csrf_token', token);

                var submitBtn = form.querySelector('button[type="submit"]');
                var originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Creation...';

                fetch('api.php', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success && json.data) {
                            var table = document.querySelector('[data-table]');
                            if (table) {
                                var tbody = table.querySelector('tbody');
                                if (tbody) {
                                    var newRow = buildRow(json.data, table);
                                    if (newRow) {
                                        tbody.insertBefore(newRow, tbody.firstChild);
                                    }
                                }
                                form.reset();
                                close();
                                showToast('success', json.message);
                                return;
                            }
                            var dataCols = Object.keys(json.data).filter(function (k) { return k !== 'id'; });
                            if (dataCols.length > 0) {
                                var colName = dataCols[0];
                                var newVal = json.data[colName];
                                document.querySelectorAll('select[data-field-name="' + colName + '"]').forEach(function (sel) {
                                    var exists = Array.from(sel.options).some(function (o) { return o.value === newVal; });
                                    if (!exists) {
                                        var opt = document.createElement('option');
                                        opt.value = newVal;
                                        opt.textContent = newVal;
                                        sel.appendChild(opt);
                                    }
                                    sel.value = newVal;
                                });
                            }
                            form.reset();
                            close();
                            showToast('success', json.message || 'Enregistre.');
                        } else {
                            showToast('error', json.message || 'Erreur lors de la creation.');
                        }
                    })
                    .catch(function () {
                        showToast('error', 'Erreur reseau. Veuillez reessayer.');
                    })
                    .finally(function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
            });
        }

        // Single modal (list page pattern — attribute without value)
        var modal = document.querySelector('[data-modal="quick-create"]');
        var form = modal ? modal.querySelector('[data-quick-create-form]') : null;
        var openBtns = document.querySelectorAll('[data-quick-create-btn=""]');
        initQuickCreate(modal, form, openBtns);

        // Keyed modals (wizard / multi-modal pattern)
        document.querySelectorAll('[data-quick-create-btn]').forEach(function (btn) {
            var key = btn.getAttribute('data-quick-create-btn');
            if (key) {
                var kModal = document.querySelector('[data-modal="quick-create-' + key + '"]');
                var kForm = kModal ? kModal.querySelector('[data-quick-create-form]') : null;
                if (kModal && kForm) {
                    initQuickCreate(kModal, kForm, [btn]);
                }
            }
        });
    })();

    // ── Build table row from API data ──
    function buildRow(data, table) {
        var template = table.querySelector('[data-row-template]');
        if (!template) return null;
        var tr = template.content.cloneNode(true).querySelector('tr');
        if (!tr) return null;

        tr.removeAttribute('data-row-template');
        tr.setAttribute('data-id', data.id);

        var cells = tr.querySelectorAll('[data-cell]');
        cells.forEach(function (cell) {
            var key = cell.getAttribute('data-cell');
            var val = data[key] !== undefined && data[key] !== null ? data[key] : '';
            if (key === 'societe_capital' && val) {
                val = Number(val).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' DH';
            } else if (key === 'created_at' || key === 'updated_at') {
                if (val) {
                    var d = new Date(val);
                    val = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth()+1)).slice(-2) + '/' + d.getFullYear();
                }
            } else if ((key === 'societe_date_ice' || key === 'societe_date_exp_cert_neg') && val) {
                var d = new Date(val);
                val = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth()+1)).slice(-2) + '/' + d.getFullYear();
            }
            cell.textContent = val || '-';
        });

        var linkCell = tr.querySelector('[data-cell-link]');
        if (linkCell) {
            var page = linkCell.getAttribute('data-cell-link');
            var linkVal = data[linkCell.getAttribute('data-cell-value') || 'id'];
            if (linkVal) {
                var a = document.createElement('a');
                a.href = 'index.php?page=' + page + '&id=' + linkVal;
                a.style.cssText = 'color:var(--primary);text-decoration:none;font-weight:500';
                var label = data[linkCell.getAttribute('data-cell-label') || 'societe_raison_sociale'] || '';
                a.textContent = label || '#' + linkVal;
                linkCell.textContent = '';
                linkCell.appendChild(a);
            }
        }

        var actionsCell = tr.querySelector('[data-cell-actions]');
        if (actionsCell) {
            var delForm = actionsCell.querySelector('form');
            if (delForm) {
                var idInput = delForm.querySelector('input[name="id"]');
                if (idInput) idInput.value = data.id;
                var tokenInput = delForm.querySelector('input[name="_csrf_token"]');
                if (tokenInput) tokenInput.value = getCsrfToken() || '';
            }
        }

        var emptyState = document.querySelector('.table-empty');
        if (emptyState) {
            var wrapper = emptyState.closest('.table-scroll') || emptyState.parentElement;
            if (wrapper) {
                var tbl = wrapper.querySelector('table');
                if (tbl) {
                    emptyState.style.display = 'none';
                    tbl.style.display = '';
                }
            }
        }

        return tr;
    }

    // ── Inline Editing ──
    (function () {
        var tables = document.querySelectorAll('table[data-table]');
        tables.forEach(function (table) {
            table.addEventListener('dblclick', function (e) {
                var cell = e.target.closest('[data-editable]');
                if (!cell) return;
                if (cell.querySelector('input, select, textarea')) return;

                var column = cell.getAttribute('data-editable');
                var row = cell.closest('tr');
                var id = row ? row.getAttribute('data-id') : null;
                if (!id) return;

                var currentValue = cell.textContent.trim();
                if (currentValue === '-' || currentValue === '—') currentValue = '';
                var tableName = table.getAttribute('data-table');

                var isSelect = cell.hasAttribute('data-editable-options');
                var el;

                if (isSelect) {
                    el = document.createElement('select');
                    el.className = 'inline-edit-input';
                    el.style.cssText = 'width:100%;box-sizing:border-box;padding:2px 4px;border:1px solid var(--primary);border-radius:2px;background:var(--surface);color:var(--text);font:inherit';
                    try {
                        var options = JSON.parse(cell.getAttribute('data-editable-options'));
                        var blank = document.createElement('option');
                        blank.value = '';
                        blank.textContent = '—';
                        el.appendChild(blank);
                        options.forEach(function (opt) {
                            var o = document.createElement('option');
                            o.value = opt;
                            o.textContent = opt;
                            if (opt === currentValue) o.selected = true;
                            el.appendChild(o);
                        });
                    } catch (e) {
                        // fallback to text input if options parse fails
                        var fallback = document.createElement('input');
                        fallback.type = 'text';
                        fallback.value = currentValue;
                        el = fallback;
                    }
                } else {
                    el = document.createElement('input');
                    el.type = 'text';
                    el.value = currentValue;
                }

                el.className = 'inline-edit-input';
                el.style.cssText = 'width:100%;box-sizing:border-box;padding:2px 4px;border:1px solid var(--primary);border-radius:2px;background:var(--surface);color:var(--text);font:inherit';

                cell.textContent = '';
                cell.appendChild(el);
                el.focus();
                if (el.select) el.select();

                function getValue() {
                    return el.value ? el.value.trim() : '';
                }

                function save() {
                    var newValue = getValue();
                    if (newValue === currentValue) {
                        cell.textContent = currentValue || '-';
                        return;
                    }

                    var fd = new FormData();
                    var token = getCsrfToken();
                    if (token) fd.append('_csrf_token', token);
                    fd.append('action', 'inline_update');
                    fd.append('table', tableName);
                    fd.append('id', id);
                    fd.append('column', column);
                    fd.append('value', newValue);

                    fetch('api.php', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (json.success) {
                                cell.textContent = newValue || '-';
                            } else {
                                cell.textContent = currentValue || '-';
                                showToast('error', json.message || 'Erreur de mise a jour.');
                            }
                        })
                        .catch(function () {
                            cell.textContent = currentValue || '-';
                            showToast('error', 'Erreur reseau.');
                        });
                }

                function cancel() {
                    cell.textContent = currentValue || '-';
                }

                el.addEventListener('blur', save);
                el.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter') { ev.preventDefault(); el.blur(); }
                    if (ev.key === 'Escape') { ev.preventDefault(); cancel(); }
                });
            });
        });
    })();

    // ── Bulk Editing ──
    (function () {
        var tables = document.querySelectorAll('table[data-bulk]');
        if (tables.length === 0) return;

        tables.forEach(function (table) {
            var tbody = table.querySelector('tbody');
            if (!tbody) return;

            var selectAll = table.querySelector('[data-bulk-select-all]');
            var toolbar = document.querySelector('[data-bulk-toolbar]');
            var modal = document.querySelector('[data-modal="bulk-edit"]');
            var form = modal ? modal.querySelector('[data-bulk-form]') : null;

            function updateToolbar() {
                var checked = tbody.querySelectorAll('[data-bulk-checkbox]:checked');
                if (!toolbar) return;
                if (checked.length > 0) {
                    toolbar.classList.add('visible');
                    var count = toolbar.querySelector('[data-bulk-count]');
                    if (count) count.textContent = checked.length;
                } else {
                    toolbar.classList.remove('visible');
                }
            }

            tbody.addEventListener('change', function (e) {
                if (e.target.matches('[data-bulk-checkbox]')) updateToolbar();
            });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    var checked = selectAll.checked;
                    tbody.querySelectorAll('[data-bulk-checkbox]').forEach(function (cb) {
                        cb.checked = checked;
                    });
                    updateToolbar();
                });
            }

            if (modal && form) {
                var bulkBtn = toolbar ? toolbar.querySelector('[data-bulk-edit-btn]') : null;
                if (bulkBtn) {
                    bulkBtn.addEventListener('click', function () {
                        var checked = tbody.querySelectorAll('[data-bulk-checkbox]:checked');
                        var ids = [];
                        checked.forEach(function (cb) {
                            var row = cb.closest('tr');
                            if (row) ids.push(row.getAttribute('data-id'));
                        });
                        form.querySelector('[name="ids"]').value = ids.join(',');
                        modal.classList.add('open');
                    });
                }

                modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
                    el.addEventListener('click', function () { modal.classList.remove('open'); });
                });
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) modal.classList.remove('open');
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && modal.classList.contains('open')) modal.classList.remove('open');
                });

            form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var fd = new FormData(form);
                    var token = getCsrfToken();
                    if (token) fd.set('_csrf_token', token);

                    var submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Mise a jour...';

                    fetch('api.php', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (json.success) {
                                modal.classList.remove('open');
                                showToast('success', json.message);
                                window.location.reload();
                            } else {
                                showToast('error', json.message || 'Erreur de mise a jour.');
                            }
                        })
                        .catch(function () {
                            showToast('error', 'Erreur reseau.');
                        })
                        .finally(function () {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<span class="material-symbols-outlined">check</span> Appliquer';
                        });
                });
            }
        });
    })();

    // ── Toast notifications ──
    function showToast(type, message) {
        var container = document.querySelector('[data-toast-container]');
        if (!container) {
            container = document.createElement('div');
            container.setAttribute('data-toast-container', '');
            container.style.cssText = 'position:fixed;top:16px;right:16px;z-index:2000;display:flex;flex-direction:column;gap:8px;max-width:400px';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.style.cssText = 'padding:12px 16px;border-radius:6px;color:#fff;font-size:0.85rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);animation:slideIn 0.25s ease-out;display:flex;align-items:center;gap:8px';
        toast.style.background = type === 'success' ? '#00b894' : type === 'error' ? '#fc424a' : '#74b9ff';

        var icon = document.createElement('span');
        icon.className = 'material-symbols-outlined';
        icon.textContent = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info';
        icon.style.fontSize = '1.1rem';
        toast.appendChild(icon);

        var text = document.createElement('span');
        text.textContent = message;
        toast.appendChild(text);

        container.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3500);
    }

    // ── Slide-in keyframe ──
    var style = document.createElement('style');
    style.textContent = '@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(style);
})();
