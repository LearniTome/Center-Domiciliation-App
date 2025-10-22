import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import Calendar, DateEntry
from ..utils.constants import DenSte, Formjur, Capital, PartsSocial, SteAdresse, Tribunnaux, Activities
from ..utils.utils import ThemeManager, WidgetFactory, ToolTip

class SocieteForm(ttk.Frame):
    def __init__(self, parent, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = {}

        # Initialiser le gestionnaire de thème
        self.theme_manager = ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Liste pour stocker les références aux combobox
        self.combos = []

        # Variables pour stocker les valeurs des champs
        self.initialize_variables()

        # Création du formulaire
        self.setup_gui()

        # Définir les valeurs initiales si fournies
        if values_dict:
            self.set_values(values_dict)

    def initialize_variables(self):
        """Initialise toutes les variables du formulaire avec des valeurs par défaut raisonnables"""
        # Variables pour les champs de texte — valeurs par défaut prises depuis les constantes
        from ..utils.constants import DenSte, Formjur, Capital, PartsSocial, SteAdresse, Tribunnaux

        self.den_ste_var = tk.StringVar(value=DenSte[0] if DenSte else "")
        self.forme_jur_var = tk.StringVar(value=Formjur[0] if Formjur else "")
        self.ice_var = tk.StringVar(value="")
        # default date to today's date in dd/mm/yyyy
        import datetime
        today = datetime.date.today().strftime('%d/%m/%Y')
        self.date_ice_var = tk.StringVar(value=today)
        self.capital_var = tk.StringVar(value=Capital[0] if Capital else "")
        self.parts_social_var = tk.StringVar(value=PartsSocial[0] if PartsSocial else "")
        self.ste_adress_var = tk.StringVar(value=SteAdresse[0] if SteAdresse else "")
        self.tribunal_var = tk.StringVar(value=Tribunnaux[0] if Tribunnaux else "")

        # Liste pour stocker les variables des activités
        self.activites_vars = []

    def setup_gui(self):
        """Configure l'interface utilisateur principale"""
        main_frame = ttk.Frame(self, padding=(10, 5))
        main_frame.pack(fill="both", expand=True)

        # Configuration de la grille (2 colonnes)
        main_frame.grid_columnconfigure(0, weight=1, uniform="col")
        main_frame.grid_columnconfigure(1, weight=1, uniform="col")

        # Création des sections
        self.create_identification_section(main_frame)
        self.create_legal_section(main_frame)
        self.create_address_section(main_frame)
        self.create_activities_section(main_frame)

    def create_identification_section(self, parent):
        """Crée la section d'identification"""
        frame = ttk.LabelFrame(parent, text="Identification", padding=(10, 5))
        frame.grid(row=0, column=0, padx=5, pady=5, sticky="nsew")

        # Container principal avec grid
        grid = ttk.Frame(frame)
        grid.pack(fill="x", padx=5, pady=5)
        grid.columnconfigure(1, weight=1)

        # Dénomination
        ttk.Label(grid, text="Dénomination:", anchor="e", width=15).grid(
            row=0, column=0, padx=(0, 5), pady=2)
        combo = ttk.Combobox(grid, textvariable=self.den_ste_var,
                           values=DenSte, width=30)
        combo.grid(row=0, column=1, sticky="ew", pady=2)
        self.combos.append(combo)

        # Sous-frame pour ICE et Date
        ice_frame = ttk.Frame(grid)
        ice_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=2)
        ice_frame.columnconfigure(1, weight=1)
        ice_frame.columnconfigure(3, weight=1)

        # ICE
        ttk.Label(ice_frame, text="ICE:", anchor="e", width=12).grid(
            row=0, column=0, padx=(0, 5))
        ttk.Entry(ice_frame, textvariable=self.ice_var).grid(
            row=0, column=1, sticky="ew", padx=(0, 15))

        # Date ICE
        ttk.Label(ice_frame, text="Date:", anchor="e", width=8).grid(
            row=0, column=2, padx=(0, 5))
        date_container = ttk.Frame(ice_frame)
        date_container.grid(row=0, column=3, sticky="ew")

        DateEntry(date_container, textvariable=self.date_ice_var,
                 date_pattern='dd/mm/yyyy', width=12).pack(side="left", fill="x", expand=True)

    def create_legal_section(self, parent):
        """Crée la section légale"""
        frame = ttk.LabelFrame(parent, text="Informations Légales", padding=(10, 5))
        frame.grid(row=0, column=1, padx=5, pady=5, sticky="nsew")

        # Container principal avec grid
        grid = ttk.Frame(frame)
        grid.pack(fill="x", padx=5, pady=5)
        grid.columnconfigure(1, weight=1)

        # Forme Juridique
        ttk.Label(grid, text="Forme Juridique:", anchor="e", width=15).grid(
            row=0, column=0, padx=(0, 5), pady=2)
        combo = ttk.Combobox(grid, textvariable=self.forme_jur_var,
                           values=Formjur, width=30)
        combo.grid(row=0, column=1, sticky="ew", pady=2)
        self.combos.append(combo)

        # Sous-frame pour Capital et Parts
        capital_frame = ttk.Frame(grid)
        capital_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=2)
        capital_frame.columnconfigure(1, weight=1)
        capital_frame.columnconfigure(3, weight=1)

        # Capital
        ttk.Label(capital_frame, text="Capital:", anchor="e", width=12).grid(
            row=0, column=0, padx=(0, 5))
        ttk.Entry(capital_frame, textvariable=self.capital_var).grid(
            row=0, column=1, sticky="ew", padx=(0, 15))

        # Parts
        ttk.Label(capital_frame, text="Parts:", anchor="e", width=8).grid(
            row=0, column=2, padx=(0, 5))
        ttk.Entry(capital_frame, textvariable=self.parts_social_var).grid(
            row=0, column=3, sticky="ew")

    def create_address_section(self, parent):
        """Crée la section adresse"""
        frame = ttk.LabelFrame(parent, text="Localisation", padding=(10, 5))
        frame.grid(row=1, column=0, columnspan=2, padx=5, pady=5, sticky="ew")

        # Container principal avec grid
        grid = ttk.Frame(frame)
        grid.pack(fill="x", padx=5, pady=5)
        grid.columnconfigure(1, weight=1)

        # Adresse
        ttk.Label(grid, text="Adresse:", anchor="e", width=15).grid(
            row=0, column=0, padx=(0, 5), pady=2)
        combo = ttk.Combobox(grid, textvariable=self.ste_adress_var,
                           values=SteAdresse, width=50)
        combo.grid(row=0, column=1, sticky="ew", pady=2)
        self.combos.append(combo)

        # Tribunal
        ttk.Label(grid, text="Tribunal:", anchor="e", width=15).grid(
            row=1, column=0, padx=(0, 5), pady=2)
        combo = ttk.Combobox(grid, textvariable=self.tribunal_var,
                           values=Tribunnaux, width=50)
        combo.grid(row=1, column=1, sticky="ew", pady=2)
        self.combos.append(combo)

    def create_activities_section(self, parent):
        """Crée la section des activités"""
        frame = ttk.LabelFrame(parent, text="Activités", padding=(10, 5))
        frame.grid(row=2, column=0, columnspan=2, padx=5, pady=5, sticky="ew")

        # Container pour les activités
        content_frame = ttk.Frame(frame)
        content_frame.pack(fill="x", padx=5, pady=5)
        content_frame.columnconfigure(0, weight=1)

        # Label d'information
        info_label = ttk.Label(content_frame,
                             text="Ajoutez jusqu'à 6 activités principales",
                             anchor="center")
        info_label.pack(pady=(0, 5))

        # Container pour les activités
        self.activities_container = ttk.Frame(content_frame)
        self.activities_container.pack(fill="x", expand=True)

        # Bouton d'ajout
        add_btn = WidgetFactory.create_button(content_frame,
                   text="➕ Ajouter une activité",
                   command=self.add_activity,
                   style='Secondary.TButton')
        add_btn.pack(pady=(5, 0))

    def add_activity(self):
        """Ajoute une nouvelle activité"""
        if len(self.activites_vars) >= 6:
            messagebox.showwarning("Limite atteinte",
                                 "Maximum 6 activités autorisées.")
            return

        var = tk.StringVar()
        self.activites_vars.append(var)

        # Frame pour l'activité
        activity_frame = ttk.Frame(self.activities_container)
        activity_frame.pack(fill="x", pady=2)
        activity_frame.columnconfigure(0, weight=1)

        # Combobox pour l'activité
        combo = ttk.Combobox(activity_frame,
                           textvariable=var,
                           values=Activities)
        combo.pack(side="left", fill="x", expand=True, padx=(0, 5))

        # Bouton de suppression
        remove_btn = WidgetFactory.create_button(activity_frame,
                      text="❌",
                      command=lambda f=activity_frame, v=var: self.remove_activity(f, v),
                      style='Danger.TButton')
        # keep small width via padding instead of width to keep uniform height
        remove_btn.pack(side="right")

    def remove_activity(self, frame, var):
        """Supprime une activité"""
        self.activites_vars.remove(var)
        frame.destroy()

    def get_values(self):
        """Récupère toutes les valeurs du formulaire"""
        return {
            'denomination': self.den_ste_var.get(),
            'forme_juridique': self.forme_jur_var.get(),
            'ice': self.ice_var.get(),
            'date_ice': self.date_ice_var.get(),
            'capital': self.capital_var.get(),
            'parts_social': self.parts_social_var.get(),
            'adresse': self.ste_adress_var.get(),
            'tribunal': self.tribunal_var.get(),
            'activites': [var.get() for var in self.activites_vars]
        }

    def set_values(self, values_dict):
        """Définit les valeurs du formulaire"""
        if not values_dict:
            return

        # Mise à jour des champs simples
        self.den_ste_var.set(values_dict.get('denomination', ''))
        self.forme_jur_var.set(values_dict.get('forme_juridique', ''))
        self.ice_var.set(values_dict.get('ice', ''))
        self.date_ice_var.set(values_dict.get('date_ice', ''))
        self.capital_var.set(values_dict.get('capital', ''))
        self.parts_social_var.set(values_dict.get('parts_social', ''))
        self.ste_adress_var.set(values_dict.get('adresse', ''))
        self.tribunal_var.set(values_dict.get('tribunal', ''))

        # Mise à jour des activités
        activites = values_dict.get('activites', [])
        for activite in activites:
            if len(self.activites_vars) < 6:  # Vérifier la limite
                self.add_activity()
                self.activites_vars[-1].set(activite)
