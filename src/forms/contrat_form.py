import tkinter as tk
from tkinter import ttk
from tkcalendar import DateEntry
from typing import Optional
from decimal import Decimal, InvalidOperation
from ..utils.constants import Nbmois
from ..utils.utils import ThemeManager

class ContratForm(ttk.Frame):
    def __init__(self, parent, theme_manager: Optional[ThemeManager] = None, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = values_dict or {}

        # Backward compatibility: ContratForm(parent, values_dict)
        if isinstance(theme_manager, dict) and values_dict is None:
            values_dict = theme_manager
            theme_manager = None
        self.values = values_dict or {}

        # Nettoyage lors de la destruction
        self.bind("<Destroy>", self._cleanup)

        # Initialiser le gestionnaire de thème
        self.theme_manager = theme_manager or ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style
        self._numeric_entry_bindings = []

        # Initialisation des variables
        self.initialize_variables()

        # Création du formulaire
        self.setup_gui()
        self._apply_numeric_display_format()

    def initialize_variables(self):
        """Initialise les variables du formulaire"""
        import datetime
        from ..utils.constants import Nbmois, TypeRenouvellement, TypeContratDomiciliation, Collaborateurs
        from ..utils.defaults_manager import get_defaults_manager

        today = datetime.date.today().strftime('%d/%m/%Y')
        
        # Get custom defaults
        defaults_mgr = get_defaults_manager()
        default_period = defaults_mgr.get_default('contrat', 'NbMois') or (Nbmois[1] if len(Nbmois) > 1 else (Nbmois[0] if Nbmois else ''))
        default_type_contrat = defaults_mgr.get_default('contrat', 'TypeContratDomiciliation') or (
            TypeContratDomiciliation[0] if TypeContratDomiciliation else ''
        )
        self.collaborateurs = list(Collaborateurs or [])
        default_collaborateur_code = defaults_mgr.get_default('contrat', 'CollaborateurCode') or 'CLTD'
        default_collaborateur_nom = defaults_mgr.get_default('contrat', 'CollaborateurNom') or ''
        default_collaborateur_selection = self._selection_from_collaborateur_code(default_collaborateur_code)
        default_type_renouvellement = defaults_mgr.get_default('contrat', 'TypeRenouvellement') or self._default_renewal_period(TypeRenouvellement)
        default_tva = defaults_mgr.get_default('contrat', 'Tva') or '20'
        default_dh_ht = defaults_mgr.get_default('contrat', 'DhHt') or '83.3333'
        default_tva_renouvellement = defaults_mgr.get_default('contrat', 'TvaRenouvellement') or '20'
        default_dh_ht_renouvellement = defaults_mgr.get_default('contrat', 'DhHtRenouvellement') or '166.667'

        # Defaults: today's date for contract and start, default period from config
        self.date_contrat_var = tk.StringVar(value=today)
        self.period_var = tk.StringVar(value=default_period)
        self.type_contrat_domiciliation_var = tk.StringVar(value=default_type_contrat)
        self.type_contrat_domiciliation_autre_var = tk.StringVar(value='')
        self.collaborateur_selection_var = tk.StringVar(value=default_collaborateur_selection)
        self.collaborateur_nom_var = tk.StringVar(value=default_collaborateur_nom)
        self.prix_mensuel_var = tk.StringVar(value='')
        self.prix_inter_var = tk.StringVar(value='')
        self.date_debut_var = tk.StringVar(value=today)
        self.date_fin_var = tk.StringVar(value='')
        self.tva_var = tk.StringVar(value=default_tva)
        self.dh_ht_var = tk.StringVar(value=default_dh_ht)
        self.montant_ht_var = tk.StringVar(value='')
        
        # Pack de Démarrage variables
        self.pack_demarrage_montant_var = tk.StringVar(value='')
        self.pack_demarrage_loyer_var = tk.StringVar(value='')
        
        # Renouvellement variables
        self.type_renouvellement_var = tk.StringVar(value=default_type_renouvellement)
        self.tva_renouvellement_var = tk.StringVar(value=default_tva_renouvellement)
        self.dh_ht_renouvellement_var = tk.StringVar(value=default_dh_ht_renouvellement)
        self.montant_ht_renouvellement_var = tk.StringVar(value='')
        self.loyer_renouvellement_mensuel_var = tk.StringVar(value='')
        self.loyer_renouvellement_annuel_var = tk.StringVar(value='')
        
        # When period or start date change, update end date automatically
        try:
            self.period_var.trace_add('write', lambda *a: self._update_date_fin())
        except Exception:
            # older Tkinter versions
            self.period_var.trace('w', lambda *a: self._update_date_fin())
        try:
            self.date_debut_var.trace_add('write', lambda *a: self._update_date_fin())
        except Exception:
            self.date_debut_var.trace('w', lambda *a: self._update_date_fin())
        try:
            self.tva_var.trace_add('write', lambda *a: self._update_loyer_calculations())
            self.dh_ht_var.trace_add('write', lambda *a: self._update_loyer_calculations())
            self.period_var.trace_add('write', lambda *a: self._update_loyer_calculations())
            self.type_renouvellement_var.trace_add('write', lambda *a: self._update_loyer_calculations())
            self.tva_renouvellement_var.trace_add('write', lambda *a: self._update_loyer_calculations())
            self.dh_ht_renouvellement_var.trace_add('write', lambda *a: self._update_loyer_calculations())
        except Exception:
            self.tva_var.trace('w', lambda *a: self._update_loyer_calculations())
            self.dh_ht_var.trace('w', lambda *a: self._update_loyer_calculations())
            self.period_var.trace('w', lambda *a: self._update_loyer_calculations())
            self.type_renouvellement_var.trace('w', lambda *a: self._update_loyer_calculations())
            self.tva_renouvellement_var.trace('w', lambda *a: self._update_loyer_calculations())
            self.dh_ht_renouvellement_var.trace('w', lambda *a: self._update_loyer_calculations())

    def setup_gui(self):
        """Configure une mise en page compacte sans sous-blocs internes."""
        from ..utils.constants import TypeRenouvellement, TypeContratDomiciliation

        main_frame = ttk.Frame(self)
        main_frame.pack(fill="both", expand=True, padx=10, pady=6)

        fields = ttk.Frame(main_frame)
        fields.pack(fill="x")
        for col in range(6):
            fields.columnconfigure(col, weight=1, uniform="contrat_cols")

        def _place(row: int, col: int, widget: ttk.Frame, span: int = 1, pad: int = 8):
            widget.grid(
                row=row,
                column=col,
                columnspan=span,
                sticky="ew",
                padx=(0, pad) if (col + span - 1) < 5 else (0, 0),
                pady=(0, 5),
            )

        # Ligne 1: infos contrat (6 colonnes)
        _place(0, 0, self.create_date_field_group(fields, "Date Contrat", self.date_contrat_var))
        _place(0, 1, self.create_combo_field_group(fields, "Période de Contrat", self.period_var, Nbmois, bind_update=True))
        _place(0, 2, self.create_date_field_group(fields, "Date Début", self.date_debut_var, bind_update=True))
        _place(0, 3, self.create_date_field_group(fields, "Date Fin", self.date_fin_var))
        _place(0, 4, self.create_combo_field_group(
            fields,
            "Type contrat domiciliation",
            self.type_contrat_domiciliation_var,
            TypeContratDomiciliation
        ))
        _place(0, 5, self.create_entry_field_group(fields, "Autre (à préciser)", self.type_contrat_domiciliation_autre_var))

        # Ligne 2: période initiale (6 colonnes)
        _place(1, 0, self.create_entry_field_group(fields, "Initial - Loyer Mensuel HT (DH)", self.dh_ht_var))
        _place(1, 1, self.create_entry_field_group(fields, "Initial - Taux TVA (%)", self.tva_var))
        _place(1, 2, self.create_entry_field_group(fields, "Initial - Loyer Mensuel TTC (DH)", self.prix_mensuel_var, readonly=True))
        _place(1, 3, self.create_entry_field_group(fields, "Initial - Montant Loyer total HT", self.montant_ht_var, readonly=True))
        _place(1, 4, self.create_entry_field_group(fields, "Initial - Montant Loyer total TTC", self.pack_demarrage_montant_var, readonly=True))
        _place(1, 5, self.create_entry_field_group(fields, "Initial - Frais intermédiation", self.prix_inter_var))

        # Ligne 3: renouvellement (6 colonnes)
        _place(2, 0, self.create_entry_field_group(fields, "Renouv. - Loyer Mensuel HT (DH)", self.dh_ht_renouvellement_var))
        _place(2, 1, self.create_entry_field_group(fields, "Renouv. - Taux TVA (%)", self.tva_renouvellement_var))
        _place(2, 2, self.create_entry_field_group(fields, "Renouv. - Loyer Mensuel TTC (DH)", self.loyer_renouvellement_mensuel_var, readonly=True))
        _place(2, 3, self.create_entry_field_group(fields, "Renouv. - Montant Loyer total HT", self.montant_ht_renouvellement_var, readonly=True))
        _place(2, 4, self.create_entry_field_group(fields, "Renouv. - Montant Loyer total TTC", self.loyer_renouvellement_annuel_var, readonly=True))
        _place(2, 5, self.create_combo_field_group(fields, "Renouv. - Période", self.type_renouvellement_var, TypeRenouvellement))

        # Ligne 4: collaborateur (nomenclature dossier domiciliation)
        _place(
            3,
            0,
            self.create_combo_field_group(
                fields,
                "Collaborateur (code)",
                self.collaborateur_selection_var,
                self.collaborateurs,
            ),
        )
        _place(3, 1, self.create_entry_field_group(fields, "Nom collaborateur", self.collaborateur_nom_var))
        self._update_loyer_calculations()

    def _selection_from_collaborateur_code(self, code: str) -> str:
        c = str(code or '').strip().upper()
        if not c:
            c = 'CLTD'
        for option in getattr(self, 'collaborateurs', []):
            if str(option).strip().upper().startswith(f"{c} "):
                return option
        # fallback for initialization before collaborators list exists
        from ..utils.constants import Collaborateurs
        for option in list(Collaborateurs or []):
            if str(option).strip().upper().startswith(f"{c} "):
                return option
        return (list(Collaborateurs or ['CLTD -- Client Direct'])[0])

    def _code_from_collaborateur_selection(self, selection: str) -> str:
        raw = str(selection or '').strip().upper()
        if not raw:
            return 'CLTD'
        import re
        match = re.match(r'^([A-Z0-9]+)', raw)
        if not match:
            return 'CLTD'
        code = match.group(1)
        allowed = {'EXP', 'AGR', 'IND', 'COAG', 'COIND', 'COEXP', 'CLTD'}
        return code if code in allowed else 'CLTD'

    def create_date_field_group(self, parent, label_text, variable, bind_update: bool = False):
        """Crée un groupe de champs pour les dates.

        Use a plain frame with a label above the widget (no LabelFrame border) to avoid
        the double-border appearance.
        """
        frame = ttk.Frame(parent)

        # Label on top, widget below (matches AssocieForm style)
        ttk.Label(frame, text=label_text + ':', anchor='w').grid(row=0, column=0, sticky='w')
        date_widget = DateEntry(frame, textvariable=variable, date_pattern='dd/mm/yyyy', width=12)
        date_widget.grid(row=1, column=0, sticky='ew', pady=(2, 0))
        if bind_update:
            try:
                date_widget.bind('<<DateEntrySelected>>', lambda e: self._update_date_fin())
            except Exception:
                pass
        frame.grid_columnconfigure(0, weight=1)

        return frame

    def create_entry_field_group(self, parent, label_text, variable, readonly: bool = False):
        """Crée un groupe de champs pour les entrées simples (label + entry)"""
        frame = ttk.Frame(parent)
        ttk.Label(frame, text=label_text + ':', anchor='w').grid(row=0, column=0, sticky='w')
        state = 'readonly' if readonly else 'normal'
        entry = ttk.Entry(frame, textvariable=variable, state=state)
        entry.grid(row=1, column=0, sticky='ew', pady=(2, 0))
        decimals = self._get_numeric_decimals(variable)
        if not readonly and decimals is not None:
            self._bind_numeric_entry(entry, variable, decimals)
        frame.grid_columnconfigure(0, weight=1)
        return frame

    def create_combo_field_group(self, parent, label_text, variable, values, bind_update: bool = False):
        """Crée un groupe de champs pour les combobox (label + combobox)"""
        frame = ttk.Frame(parent)
        ttk.Label(frame, text=label_text + ':', anchor='w').grid(row=0, column=0, sticky='w')
        cb = ttk.Combobox(frame, textvariable=variable, values=values)
        cb.grid(row=1, column=0, sticky='ew', pady=(2, 0))
        if bind_update:
            try:
                cb.bind('<<ComboboxSelected>>', lambda e: self._update_date_fin())
            except Exception:
                pass
        frame.grid_columnconfigure(0, weight=1)
        return frame

    def show_calendar(self, var):
        """Affiche un calendrier pour sélectionner une date"""
        # removed: replaced by inline DateEntry widgets
        return

    def get_values(self):
        """Récupère toutes les valeurs du formulaire"""
        self._apply_numeric_display_format()
        return {
            'date_contrat': self.date_contrat_var.get(),
            'period': self.period_var.get(),
            'type_contrat_domiciliation': self.type_contrat_domiciliation_var.get(),
            'type_contrat_domiciliation_autre': self.type_contrat_domiciliation_autre_var.get(),
            'collaborateur': self.collaborateur_selection_var.get(),
            'collaborateur_code': self._code_from_collaborateur_selection(self.collaborateur_selection_var.get()),
            'collaborateur_nom': self.collaborateur_nom_var.get().strip(),
            'prix_mensuel': self.prix_mensuel_var.get(),
            'prix_inter': self.prix_inter_var.get(),
            'date_debut': self.date_debut_var.get(),
            'date_fin': self.date_fin_var.get(),
            'tva': self.tva_var.get(),
            'dh_ht': self.dh_ht_var.get(),
            'montant_ht': self.montant_ht_var.get(),
            'pack_demarrage_montant': self.pack_demarrage_montant_var.get(),
            'pack_demarrage_loyer': self.pack_demarrage_loyer_var.get(),
            'type_renouvellement': self.type_renouvellement_var.get(),
            'tva_renouvellement': self.tva_renouvellement_var.get(),
            'dh_ht_renouvellement': self.dh_ht_renouvellement_var.get(),
            'montant_ht_renouvellement': self.montant_ht_renouvellement_var.get(),
            'loyer_renouvellement_mensuel': self.loyer_renouvellement_mensuel_var.get(),
            'loyer_renouvellement_annuel': self.loyer_renouvellement_annuel_var.get()
        }

    def _update_date_fin(self):
        """Calcule et met à jour `date_fin` à partir de `date_debut` + `period` mois.

        Expects dates in dd/mm/YYYY and period as number of months (string like '12' or '06').
        Règle métier: la date de fin = veille de la date anniversaire.
        """
        import datetime
        from calendar import monthrange

        start = self.date_debut_var.get()
        period = self.period_var.get()

        if not start or not period:
            return

        # parse period as int (handle leading zeros)
        try:
            months = int(period)
        except Exception:
            return

        try:
            day, month, year = [int(x) for x in start.split('/')]
            # Compute target month/year
            total_month = month - 1 + months
            new_year = year + total_month // 12
            new_month = total_month % 12 + 1
            # clamp day to last day of target month
            last_day = monthrange(new_year, new_month)[1]
            new_day = min(day, last_day)
            anniversary = datetime.date(new_year, new_month, new_day)
            new_date = anniversary - datetime.timedelta(days=1)
            self.date_fin_var.set(new_date.strftime('%d/%m/%Y'))
        except Exception:
            # if parsing fails silently ignore
            return

    def _parse_decimal(self, value):
        if value is None:
            return None
        s = str(value).strip()
        if not s:
            return None
        s = s.replace(' ', '').replace(',', '.')
        try:
            return float(s)
        except Exception:
            return None

    def _get_numeric_decimals(self, variable):
        if variable is self.tva_var or variable is self.tva_renouvellement_var:
            return 2
        if (
            variable is self.dh_ht_var
            or variable is self.prix_inter_var
            or variable is self.dh_ht_renouvellement_var
        ):
            return 4
        return None

    def _format_numeric_text(self, value, decimals: int = 4) -> str:
        text = str(value or '').strip()
        if not text:
            return ''
        normalized = text.replace('\xa0', ' ').replace(' ', '')
        if ',' in normalized and '.' in normalized:
            normalized = normalized.replace(',', '')
        elif ',' in normalized:
            normalized = normalized.replace(',', '.')
        try:
            number = Decimal(normalized)
        except (InvalidOperation, ValueError):
            return text

        if decimals <= 0:
            return f"{int(round(float(number))):,}".replace(',', ' ')

        rendered = f"{float(number):,.{decimals}f}"
        integer_part, fractional_part = rendered.split('.')
        integer_part = integer_part.replace(',', ' ')
        fractional_part = fractional_part.rstrip('0')
        if not fractional_part:
            return integer_part
        return f"{integer_part},{fractional_part}"

    def _bind_numeric_entry(self, entry: ttk.Entry, variable, decimals: int):
        entry.bind(
            '<FocusOut>',
            lambda _event, var=variable, places=decimals: var.set(self._format_numeric_text(var.get(), places)),
            add='+',
        )
        entry.bind(
            '<Return>',
            lambda _event, var=variable, places=decimals: var.set(self._format_numeric_text(var.get(), places)),
            add='+',
        )
        self._numeric_entry_bindings.append((entry, variable, decimals))

    def _apply_numeric_display_format(self):
        for _entry, variable, decimals in self._numeric_entry_bindings:
            try:
                variable.set(self._format_numeric_text(variable.get(), decimals))
            except Exception:
                pass

    def _format_number(self, value):
        try:
            if value is None:
                return ''
            # French format: space as thousand separator and comma decimals.
            return f"{float(value):,.2f}".replace(",", " ").replace(".", ",")
        except Exception:
            return ''

    def _extract_period_months(self):
        import re
        p = str(self.period_var.get() or '').strip()
        if not p:
            return None
        m = re.search(r'\d+', p)
        if not m:
            return None
        try:
            return int(m.group(0))
        except Exception:
            return None

    def _extract_renewal_months(self):
        import re
        mode = str(self.type_renouvellement_var.get() or '').strip().lower()
        if not mode:
            return None
        mapping = {
            'mensuel': 1,
            'trimestriel': 3,
            'annuel': 12,
        }
        if mode in mapping:
            return mapping[mode]
        m = re.search(r'\d+', mode)
        if not m:
            return None
        try:
            years = int(m.group(0))
            return years * 12
        except Exception:
            return None

    @staticmethod
    def _default_renewal_period(options) -> str:
        """Return annual renewal label when available, else first option."""
        if not options:
            return ''
        for option in options:
            txt = str(option or '').strip().lower()
            if 'annuel' in txt:
                return str(option)
        return str(options[0])

    def _update_loyer_calculations(self):
        # Calcul période initiale
        initial_dh_ht = self._parse_decimal(self.dh_ht_var.get())
        initial_tva_pct = self._parse_decimal(self.tva_var.get())
        initial_months = self._extract_period_months()

        if initial_dh_ht is None:
            self.montant_ht_var.set('')
            self.pack_demarrage_montant_var.set('')
            self.pack_demarrage_loyer_var.set('')
            self.prix_mensuel_var.set('')
        else:
            if initial_tva_pct is None:
                initial_tva_pct = 0.0
            initial_multiplier = 1.0 + (initial_tva_pct / 100.0)
            initial_loyer_mensuel_ttc = initial_dh_ht * initial_multiplier
            initial_montant_ht = initial_dh_ht * initial_months if initial_months is not None else None
            initial_montant_ttc = initial_loyer_mensuel_ttc * initial_months if initial_months is not None else None

            self.montant_ht_var.set(self._format_number(initial_montant_ht))
            self.pack_demarrage_montant_var.set(self._format_number(initial_montant_ttc))
            self.pack_demarrage_loyer_var.set(self._format_number(initial_loyer_mensuel_ttc))
            self.prix_mensuel_var.set(self._format_number(initial_loyer_mensuel_ttc))

        # Calcul renouvellement (séparé de la période initiale)
        renewal_dh_ht = self._parse_decimal(self.dh_ht_renouvellement_var.get())
        renewal_tva_pct = self._parse_decimal(self.tva_renouvellement_var.get())
        renewal_months = self._extract_renewal_months()

        if renewal_dh_ht is None:
            self.montant_ht_renouvellement_var.set('')
            self.loyer_renouvellement_mensuel_var.set('')
            self.loyer_renouvellement_annuel_var.set('')
        else:
            if renewal_tva_pct is None:
                renewal_tva_pct = 0.0
            renewal_multiplier = 1.0 + (renewal_tva_pct / 100.0)
            renewal_loyer_mensuel_ttc = renewal_dh_ht * renewal_multiplier
            renewal_loyer_annuel_ttc = renewal_loyer_mensuel_ttc * 12.0
            renewal_montant_ht = renewal_dh_ht * renewal_months if renewal_months is not None else None

            self.montant_ht_renouvellement_var.set(self._format_number(renewal_montant_ht))
            self.loyer_renouvellement_mensuel_var.set(self._format_number(renewal_loyer_mensuel_ttc))
            self.loyer_renouvellement_annuel_var.set(self._format_number(renewal_loyer_annuel_ttc))

    def set_values(self, values):
        """Définit les valeurs du formulaire"""
        if values:
            self.date_contrat_var.set(values.get('date_contrat', ''))
            self.period_var.set(values.get('period', ''))
            self.type_contrat_domiciliation_var.set(values.get('type_contrat_domiciliation', ''))
            self.type_contrat_domiciliation_autre_var.set(values.get('type_contrat_domiciliation_autre', ''))
            selected_collaborateur = values.get('collaborateur', values.get('collaborateur_selection', ''))
            if selected_collaborateur:
                self.collaborateur_selection_var.set(str(selected_collaborateur))
            else:
                self.collaborateur_selection_var.set(
                    self._selection_from_collaborateur_code(
                        values.get('collaborateur_code', values.get('CollaborateurCode', 'CLTD'))
                    )
                )
            self.collaborateur_nom_var.set(values.get('collaborateur_nom', values.get('CollaborateurNom', '')))
            self.prix_mensuel_var.set(values.get('prix_mensuel', ''))
            self.prix_inter_var.set(values.get('prix_inter', ''))
            self.date_debut_var.set(values.get('date_debut', ''))
            self.date_fin_var.set(values.get('date_fin', ''))
            self.tva_var.set(values.get('tva', '20'))
            self.dh_ht_var.set(values.get('dh_ht', ''))
            self.montant_ht_var.set(values.get('montant_ht', ''))
            self.pack_demarrage_montant_var.set(values.get('pack_demarrage_montant', ''))
            self.pack_demarrage_loyer_var.set(values.get('pack_demarrage_loyer', ''))
            from ..utils.constants import TypeRenouvellement
            self.type_renouvellement_var.set(
                values.get('type_renouvellement', '') or self._default_renewal_period(TypeRenouvellement)
            )
            self.tva_renouvellement_var.set(values.get('tva_renouvellement', values.get('tva', '20')))
            self.dh_ht_renouvellement_var.set(values.get('dh_ht_renouvellement', values.get('dh_ht', '')))
            self.montant_ht_renouvellement_var.set(values.get('montant_ht_renouvellement', ''))
            self.loyer_renouvellement_mensuel_var.set(values.get('loyer_renouvellement_mensuel', ''))
            self.loyer_renouvellement_annuel_var.set(values.get('loyer_renouvellement_annuel', ''))
            self._update_loyer_calculations()
            self._apply_numeric_display_format()
        else:
            self.reset()

    def reset(self):
        """Réinitialise complètement le formulaire"""
        import datetime
        from ..utils.constants import Nbmois, TypeRenouvellement, TypeContratDomiciliation
        from ..utils.defaults_manager import get_defaults_manager
        today = datetime.date.today().strftime('%d/%m/%Y')
        defaults_mgr = get_defaults_manager()

        self.date_contrat_var.set(today)
        self.period_var.set((Nbmois[1] if len(Nbmois) > 1 else (Nbmois[0] if Nbmois else '')))
        self.type_contrat_domiciliation_var.set(TypeContratDomiciliation[0] if TypeContratDomiciliation else '')
        self.type_contrat_domiciliation_autre_var.set('')
        self.collaborateur_selection_var.set(
            self._selection_from_collaborateur_code(defaults_mgr.get_default('contrat', 'CollaborateurCode') or 'CLTD')
        )
        self.collaborateur_nom_var.set(defaults_mgr.get_default('contrat', 'CollaborateurNom') or '')
        self.prix_mensuel_var.set('')
        self.prix_inter_var.set('')
        self.date_debut_var.set(today)
        self.date_fin_var.set('')
        self.tva_var.set('20')
        self.dh_ht_var.set('83.3333')
        self.montant_ht_var.set('')
        self.pack_demarrage_montant_var.set('')
        self.pack_demarrage_loyer_var.set('')
        self.type_renouvellement_var.set(self._default_renewal_period(TypeRenouvellement))
        self.tva_renouvellement_var.set('20')
        self.dh_ht_renouvellement_var.set('166.667')
        self.montant_ht_renouvellement_var.set('')
        self.loyer_renouvellement_mensuel_var.set('')
        self.loyer_renouvellement_annuel_var.set('')
        self._update_loyer_calculations()
        self._apply_numeric_display_format()
        self.values = {}

    def _cleanup(self, event=None):
        """Nettoie les ressources lors de la destruction"""
        self.unbind("<Destroy>")
