import tkinter as tk
from tkinter import ttk
from tkcalendar import DateEntry
from ..utils.constants import Nbmois
from ..utils.utils import ThemeManager, WidgetFactory

class ContratForm(ttk.Frame):
    def __init__(self, parent, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = values_dict or {}

        # Nettoyage lors de la destruction
        self.bind("<Destroy>", self._cleanup)

        # Initialiser le gestionnaire de thème
        self.theme_manager = ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Initialisation des variables
        self.initialize_variables()

        # Création du formulaire
        self.setup_gui()

    def initialize_variables(self):
        """Initialise les variables du formulaire"""
        import datetime
        from ..utils.constants import Nbmois, TypeRenouvellement
        from ..utils.defaults_manager import get_defaults_manager

        today = datetime.date.today().strftime('%d/%m/%Y')
        
        # Get custom defaults
        defaults_mgr = get_defaults_manager()
        default_period = defaults_mgr.get_default('contrat', 'NbMois') or (Nbmois[1] if len(Nbmois) > 1 else (Nbmois[0] if Nbmois else ''))

        # Defaults: today's date for contract and start, default period from config
        self.date_contrat_var = tk.StringVar(value=today)
        self.period_var = tk.StringVar(value=default_period)
        self.prix_mensuel_var = tk.StringVar(value='')
        self.prix_inter_var = tk.StringVar(value='')
        self.date_debut_var = tk.StringVar(value=today)
        self.date_fin_var = tk.StringVar(value='')
        self.tva_var = tk.StringVar(value='20')
        self.dh_ht_var = tk.StringVar(value='83.3333')
        self.montant_ht_var = tk.StringVar(value='')
        
        # Pack de Démarrage variables
        self.pack_demarrage_montant_var = tk.StringVar(value='')
        self.pack_demarrage_loyer_var = tk.StringVar(value='')
        
        # Renouvellement variables
        self.type_renouvellement_var = tk.StringVar(value=TypeRenouvellement[0] if TypeRenouvellement else '')
        self.tva_renouvellement_var = tk.StringVar(value='20')
        self.dh_ht_renouvellement_var = tk.StringVar(value='166.667')
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
        """Configure l'interface utilisateur principale"""
        # Conteneur principal avec grille
        main_frame = ttk.Frame(self)
        main_frame.pack(fill="both", expand=True, padx=5, pady=5)

        # Bloc 1: Informations contractuelles
        dates_group = ttk.LabelFrame(main_frame, text="Infos Contrat", padding=(8, 6))
        dates_group.pack(fill="x", padx=5, pady=(0, 8))
        dates_group.grid_columnconfigure(0, weight=1)
        dates_group.grid_columnconfigure(1, weight=1)

        # Bloc 2: Conditions financières (2 colonnes)
        finance_frame = ttk.Frame(main_frame)
        finance_frame.pack(fill="x", padx=5, pady=(0, 8))
        finance_frame.grid_columnconfigure(0, weight=1)
        finance_frame.grid_columnconfigure(1, weight=1)

        # Colonne gauche: période initiale
        pack_group = ttk.LabelFrame(finance_frame, text="Initiale", padding=(8, 6))
        pack_group.grid(row=0, column=0, padx=(0, 4), pady=0, sticky="nsew")
        pack_group.grid_columnconfigure(0, weight=1)
        pack_group.grid_columnconfigure(1, weight=1)

        # Colonne droite: renouvellement
        renewal_group = ttk.LabelFrame(finance_frame, text="Renouvellement", padding=(8, 6))
        renewal_group.grid(row=0, column=1, padx=(4, 0), pady=0, sticky="nsew")
        renewal_group.grid_columnconfigure(0, weight=1)
        renewal_group.grid_columnconfigure(1, weight=1)

        # Dates & Periods (2 columns)
        # Date Contrat | Période
        date_frame = self.create_date_field_group(dates_group, "Date Contrat", self.date_contrat_var)
        date_frame.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

        period_frame = self.create_combo_field_group(dates_group, "Période de Contrat", self.period_var, Nbmois, bind_update=True)
        period_frame.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

        # Date Début | Date Fin
        date_debut_frame = self.create_date_field_group(dates_group, "Date Début", self.date_debut_var, bind_update=True)
        date_debut_frame.grid(row=1, column=0, padx=5, pady=5, sticky="ew")

        date_fin_frame = self.create_date_field_group(dates_group, "Date Fin", self.date_fin_var)
        date_fin_frame.grid(row=1, column=1, padx=5, pady=5, sticky="ew")

        # Période initiale: champs financiers principaux
        dh_ht_frame = self.create_entry_field_group(pack_group, "Loyer mensuel HT (DH)", self.dh_ht_var)
        dh_ht_frame.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

        tva_frame = self.create_entry_field_group(pack_group, "Taux TVA (%)", self.tva_var)
        tva_frame.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

        prix_frame = self.create_entry_field_group(pack_group, "Loyer mensuel TTC (DH)", self.prix_mensuel_var, readonly=True)
        prix_frame.grid(row=1, column=0, padx=5, pady=5, sticky="ew")

        montant_ht_frame = self.create_entry_field_group(pack_group, "Montant total HT (période)", self.montant_ht_var, readonly=True)
        montant_ht_frame.grid(row=1, column=1, padx=5, pady=5, sticky="ew")

        # Période initiale: total TTC + frais d'intermédiation
        pack_montant_frame = self.create_entry_field_group(pack_group, "Montant total TTC (période) (DH)", self.pack_demarrage_montant_var, readonly=True)
        pack_montant_frame.grid(row=2, column=1, padx=5, pady=5, sticky="ew")

        prix_inter_frame = self.create_entry_field_group(pack_group, "Frais d'intermédiation (DH)", self.prix_inter_var)
        prix_inter_frame.grid(row=2, column=0, padx=5, pady=5, sticky="ew")

        # Renouvellement: variables financières propres et calcul séparé
        from ..utils.constants import TypeRenouvellement
        renewal_ht_frame = self.create_entry_field_group(renewal_group, "Loyer mensuel HT (DH)", self.dh_ht_renouvellement_var)
        renewal_ht_frame.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

        renewal_tva_frame = self.create_entry_field_group(renewal_group, "Taux TVA (%)", self.tva_renouvellement_var)
        renewal_tva_frame.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

        renewal_loyer_frame = self.create_entry_field_group(renewal_group, "Loyer mensuel TTC (DH)", self.loyer_renouvellement_mensuel_var, readonly=True)
        renewal_loyer_frame.grid(row=1, column=0, padx=5, pady=5, sticky="ew")

        renewal_type_frame = self.create_combo_field_group(renewal_group, "Période de renouvellement", self.type_renouvellement_var, TypeRenouvellement)
        renewal_type_frame.grid(row=1, column=1, padx=5, pady=5, sticky="ew")

        renewal_montant_ht_frame = self.create_entry_field_group(renewal_group, "Montant total HT (période)", self.montant_ht_renouvellement_var, readonly=True)
        renewal_montant_ht_frame.grid(row=2, column=0, padx=5, pady=5, sticky="ew")

        # Loyer annuel
        renewal_annuel_frame = self.create_entry_field_group(renewal_group, "Loyer annuel (DH TTC)", self.loyer_renouvellement_annuel_var, readonly=True)
        renewal_annuel_frame.grid(row=2, column=1, padx=5, pady=5, sticky="ew")
        self._update_loyer_calculations()

    def create_fields_row1(self, parent):
        """Crée la première ligne de champs"""
        # Date Contrat
        date_frame = self.create_date_field_group(parent, "Date Contrat",
                                                self.date_contrat_var)
        date_frame.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

        # Période de Contrat
        period_frame = self.create_combo_field_group(parent, "Période de Contrat",
                                                   self.period_var, Nbmois)
        period_frame.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

        # Prix mensuel
        prix_frame = self.create_entry_field_group(parent, "Prix mensuel",
                                                 self.prix_mensuel_var)
        prix_frame.grid(row=0, column=2, padx=5, pady=5, sticky="ew")

    def create_fields_row2(self, parent):
        """Crée la deuxième ligne de champs"""
        # Prix intermédiaire
        prix_inter_frame = self.create_entry_field_group(parent, "Prix intermédiaire",
                                                       self.prix_inter_var)
        prix_inter_frame.grid(row=1, column=0, padx=5, pady=5, sticky="ew")

        # Date Début
        date_debut_frame = self.create_date_field_group(parent, "Date Début",
                                                      self.date_debut_var)
        date_debut_frame.grid(row=1, column=1, padx=5, pady=5, sticky="ew")

        # Date Fin
        date_fin_frame = self.create_date_field_group(parent, "Date Fin",
                                                    self.date_fin_var)
        date_fin_frame.grid(row=1, column=2, padx=5, pady=5, sticky="ew")

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
        ttk.Entry(frame, textvariable=variable, state=state).grid(row=1, column=0, sticky='ew', pady=(2, 0))
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
        return {
            'date_contrat': self.date_contrat_var.get(),
            'period': self.period_var.get(),
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
            self.prix_mensuel_var.set(values.get('prix_mensuel', ''))
            self.prix_inter_var.set(values.get('prix_inter', ''))
            self.date_debut_var.set(values.get('date_debut', ''))
            self.date_fin_var.set(values.get('date_fin', ''))
            self.tva_var.set(values.get('tva', '20'))
            self.dh_ht_var.set(values.get('dh_ht', ''))
            self.montant_ht_var.set(values.get('montant_ht', ''))
            self.pack_demarrage_montant_var.set(values.get('pack_demarrage_montant', ''))
            self.pack_demarrage_loyer_var.set(values.get('pack_demarrage_loyer', ''))
            self.type_renouvellement_var.set(values.get('type_renouvellement', ''))
            self.tva_renouvellement_var.set(values.get('tva_renouvellement', values.get('tva', '20')))
            self.dh_ht_renouvellement_var.set(values.get('dh_ht_renouvellement', values.get('dh_ht', '')))
            self.montant_ht_renouvellement_var.set(values.get('montant_ht_renouvellement', ''))
            self.loyer_renouvellement_mensuel_var.set(values.get('loyer_renouvellement_mensuel', ''))
            self.loyer_renouvellement_annuel_var.set(values.get('loyer_renouvellement_annuel', ''))
            self._update_loyer_calculations()
        else:
            self.reset()

    def reset(self):
        """Réinitialise complètement le formulaire"""
        import datetime
        from ..utils.constants import Nbmois, TypeRenouvellement
        today = datetime.date.today().strftime('%d/%m/%Y')

        self.date_contrat_var.set(today)
        self.period_var.set((Nbmois[1] if len(Nbmois) > 1 else (Nbmois[0] if Nbmois else '')))
        self.prix_mensuel_var.set('')
        self.prix_inter_var.set('')
        self.date_debut_var.set(today)
        self.date_fin_var.set('')
        self.tva_var.set('20')
        self.dh_ht_var.set('83.3333')
        self.montant_ht_var.set('')
        self.pack_demarrage_montant_var.set('')
        self.pack_demarrage_loyer_var.set('')
        self.type_renouvellement_var.set(TypeRenouvellement[0] if TypeRenouvellement else '')
        self.tva_renouvellement_var.set('20')
        self.dh_ht_renouvellement_var.set('166.667')
        self.montant_ht_renouvellement_var.set('')
        self.loyer_renouvellement_mensuel_var.set('')
        self.loyer_renouvellement_annuel_var.set('')
        self._update_loyer_calculations()
        self.values = {}

    def _cleanup(self, event=None):
        """Nettoie les ressources lors de la destruction"""
        self.unbind("<Destroy>")
