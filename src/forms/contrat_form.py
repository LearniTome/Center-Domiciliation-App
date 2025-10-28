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
        from ..utils.constants import Nbmois

        today = datetime.date.today().strftime('%d/%m/%Y')

        # Defaults: today's date for contract and start, default period to 12 months if available
        self.date_contrat_var = tk.StringVar(value=today)
        self.period_var = tk.StringVar(value=(Nbmois[1] if len(Nbmois) > 1 else (Nbmois[0] if Nbmois else '')))
        self.prix_mensuel_var = tk.StringVar(value='')
        self.prix_inter_var = tk.StringVar(value='')
        self.date_debut_var = tk.StringVar(value=today)
        self.date_fin_var = tk.StringVar(value='')
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

    def setup_gui(self):
        """Configure l'interface utilisateur principale"""
        # Conteneur principal avec grille
        main_frame = ttk.Frame(self)
        main_frame.pack(fill="both", expand=True, padx=5, pady=5)

        # Create two logical groups: Dates & Periods, and Prices
        # Dates group on top, Prices group below
        dates_group = ttk.LabelFrame(main_frame, text="Dates & Durée", padding=(8, 6))
        dates_group.pack(fill="x", padx=5, pady=(0, 8))
        dates_group.grid_columnconfigure(0, weight=1)
        dates_group.grid_columnconfigure(1, weight=1)

        prices_group = ttk.LabelFrame(main_frame, text="Tarifs", padding=(8, 6))
        prices_group.pack(fill="x", padx=5, pady=(0, 8))
        prices_group.grid_columnconfigure(0, weight=1)
        prices_group.grid_columnconfigure(1, weight=1)

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

        # Prices group: Prix mensuel | Prix intermédiaire
        prix_frame = self.create_entry_field_group(prices_group, "Prix mensuel", self.prix_mensuel_var)
        prix_frame.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

        prix_inter_frame = self.create_entry_field_group(prices_group, "Prix intermédiaire", self.prix_inter_var)
        prix_inter_frame.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

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

    def create_entry_field_group(self, parent, label_text, variable):
        """Crée un groupe de champs pour les entrées simples (label + entry)"""
        frame = ttk.Frame(parent)
        ttk.Label(frame, text=label_text + ':', anchor='w').grid(row=0, column=0, sticky='w')
        ttk.Entry(frame, textvariable=variable).grid(row=1, column=0, sticky='ew', pady=(2, 0))
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
            'date_fin': self.date_fin_var.get()
        }

    def _update_date_fin(self):
        """Calcule et met à jour `date_fin` à partir de `date_debut` + `period` mois.

        Expects dates in dd/mm/YYYY and period as number of months (string like '12' or '06').
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
            new_date = datetime.date(new_year, new_month, new_day)
            self.date_fin_var.set(new_date.strftime('%d/%m/%Y'))
        except Exception:
            # if parsing fails silently ignore
            return

    def set_values(self, values):
        """Définit les valeurs du formulaire"""
        if values:
            self.date_contrat_var.set(values.get('date_contrat', ''))
            self.period_var.set(values.get('period', ''))
            self.prix_mensuel_var.set(values.get('prix_mensuel', ''))
            self.prix_inter_var.set(values.get('prix_inter', ''))
            self.date_debut_var.set(values.get('date_debut', ''))
            self.date_fin_var.set(values.get('date_fin', ''))
        else:
            self.reset()

    def reset(self):
        """Réinitialise complètement le formulaire"""
        import datetime
        from ..utils.constants import Nbmois
        today = datetime.date.today().strftime('%d/%m/%Y')

        self.date_contrat_var.set(today)
        self.period_var.set((Nbmois[1] if len(Nbmois) > 1 else (Nbmois[0] if Nbmois else '')))
        self.prix_mensuel_var.set('')
        self.prix_inter_var.set('')
        self.date_debut_var.set(today)
        self.date_fin_var.set('')
        self.values = {}

    def _cleanup(self, event=None):
        """Nettoie les ressources lors de la destruction"""
        self.unbind("<Destroy>")
