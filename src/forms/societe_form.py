import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import Calendar
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
        """Initialise toutes les variables du formulaire"""
        # Variables pour les champs de texte
        self.den_ste_var = tk.StringVar(value="")
        self.forme_jur_var = tk.StringVar(value="")
        self.ice_var = tk.StringVar(value="")
        self.date_ice_var = tk.StringVar(value="")
        self.capital_var = tk.StringVar(value="")
        self.parts_social_var = tk.StringVar(value="")
        self.ste_adress_var = tk.StringVar(value="")
        self.tribunal_var = tk.StringVar(value="")

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
        frame = ttk.LabelFrame(parent, text="Identification", padding=(5, 5))
        frame.grid(row=0, column=0, padx=5, pady=5, sticky="nsew")
        frame.grid_columnconfigure(1, weight=1)

        # Dénomination Société
        self.create_combo_field(frame, "Dénomination", self.den_ste_var, DenSte, 0)

        # ICE et Date ICE
        ice_frame = ttk.Frame(frame)
        ice_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=5)
        ice_frame.grid_columnconfigure(1, weight=1)
        ice_frame.grid_columnconfigure(3, weight=1)

        ttk.Label(ice_frame, text="ICE:").grid(row=0, column=0, padx=(0, 5))
        ttk.Entry(ice_frame, textvariable=self.ice_var).grid(row=0, column=1, sticky="ew", padx=5)
        ttk.Label(ice_frame, text="Date:").grid(row=0, column=2, padx=5)
        date_frame = ttk.Frame(ice_frame)
        date_frame.grid(row=0, column=3, sticky="ew")
        ttk.Entry(date_frame, textvariable=self.date_ice_var).pack(side="left", fill="x", expand=True)
        ttk.Button(date_frame, text="📅", width=3,
                  command=lambda: self.show_calendar(self.date_ice_var)).pack(side="right", padx=(5, 0))

    def create_legal_section(self, parent):
        """Crée la section légale"""
        frame = ttk.LabelFrame(parent, text="Informations Légales", padding=(5, 5))
        frame.grid(row=0, column=1, padx=5, pady=5, sticky="nsew")
        frame.grid_columnconfigure(1, weight=1)

        # Forme Juridique
        self.create_combo_field(frame, "Forme Juridique", self.forme_jur_var, Formjur, 0)

        # Capital et Parts
        capital_frame = ttk.Frame(frame)
        capital_frame.grid(row=1, column=0, columnspan=2, sticky="ew", pady=5)
        capital_frame.grid_columnconfigure(1, weight=1)
        capital_frame.grid_columnconfigure(3, weight=1)

        ttk.Label(capital_frame, text="Capital:").grid(row=0, column=0, padx=(0, 5))
        ttk.Entry(capital_frame, textvariable=self.capital_var).grid(row=0, column=1, sticky="ew", padx=5)
        ttk.Label(capital_frame, text="Parts:").grid(row=0, column=2, padx=5)
        ttk.Entry(capital_frame, textvariable=self.parts_social_var).grid(row=0, column=3, sticky="ew")

    def create_address_section(self, parent):
        """Crée la section adresse"""
        frame = ttk.LabelFrame(parent, text="Localisation", padding=(5, 5))
        frame.grid(row=1, column=0, columnspan=2, padx=5, pady=5, sticky="ew")
        frame.grid_columnconfigure(1, weight=1)

        # Adresse et Tribunal
        self.create_combo_field(frame, "Adresse", self.ste_adress_var, SteAdresse, 0)
        self.create_combo_field(frame, "Tribunal", self.tribunal_var, Tribunnaux, 1)

    def create_activities_section(self, parent):
        """Crée la section des activités"""
        frame = ttk.LabelFrame(parent, text="Activités", padding=(5, 5))
        frame.grid(row=2, column=0, columnspan=2, padx=5, pady=5, sticky="ew")
        frame.grid_columnconfigure(1, weight=1)

        # Zone des activités
        self.activities_container = ttk.Frame(frame)
        self.activities_container.pack(fill="x", expand=True)

        def add_activity():
            if len(self.activites_vars) >= 6:
                messagebox.showwarning("Limite atteinte",
                                     "Maximum 6 activités autorisées.")
                return

            var = tk.StringVar()
            self.activites_vars.append(var)

            activity_frame = ttk.Frame(self.activities_container)
            activity_frame.pack(fill="x", pady=2)

            combo = ttk.Combobox(activity_frame, textvariable=var,
                               values=Activities, width=50)
            combo.pack(side="left", fill="x", expand=True)

            ttk.Button(activity_frame, text="❌", width=3,
                      command=lambda f=activity_frame, v=var: self.remove_activity(f, v)).pack(
                          side="right", padx=(5, 0))

        ttk.Label(frame, text="Ajoutez jusqu'à 6 activités principales").pack(pady=(0, 5))
        ttk.Button(frame, text="➕ Ajouter une activité",
                  command=add_activity).pack(pady=(0, 5))

    def create_combo_field(self, parent, label, variable, values, row):
        """Crée un champ combo avec label"""
        ttk.Label(parent, text=label + ":").grid(row=row, column=0,
                                                sticky="e", padx=(0, 5), pady=3)

        combo = ttk.Combobox(parent, textvariable=variable, values=values)
        combo.set("")  # Valeur par défaut vide

        combo.grid(row=row, column=1, sticky="ew", pady=3)
        if hasattr(self, "combos"):
            self.combos.append(combo)
        return combo

    def show_calendar(self, var):
        """Affiche un calendrier pour sélectionner une date"""
        top = tk.Toplevel(self)
        top.title("Sélectionner une date")
        cal = Calendar(top, selectmode="day", date_pattern="dd/mm/y")
        cal.pack(padx=10, pady=10)

        def set_date():
            var.set(cal.get_date())
            top.destroy()

        ttk.Button(top, text="OK", command=set_date).pack(pady=5)

    def remove_activity(self, frame, var):
        """Supprime une activité"""
        self.activites_vars.remove(var)
        frame.destroy()

    def get_values(self):
        """Récupère toutes les valeurs du formulaire"""
        return {
            'denomination_sociale': self.den_ste_var.get(),
            'forme_juridique': self.forme_jur_var.get(),
            'ice': self.ice_var.get(),
            'date_ice': self.date_ice_var.get(),
            'capital': self.capital_var.get(),
            'parts_social': self.parts_social_var.get(),
            'adresse': self.ste_adress_var.get(),
            'tribunal': self.tribunal_var.get(),
            'activites': [var.get() for var in self.activites_vars if var.get()]
        }

    def set_values(self, values):
        """Définit les valeurs du formulaire"""
        if not values:
            self.reset()
            return

        self.den_ste_var.set(values.get('denomination_sociale', ''))
        self.forme_jur_var.set(values.get('forme_juridique', ''))
        self.ice_var.set(values.get('ice', ''))
        self.date_ice_var.set(values.get('date_ice', ''))
        self.capital_var.set(values.get('capital', ''))
        self.parts_social_var.set(values.get('parts_social', ''))
        self.ste_adress_var.set(values.get('adresse', ''))
        self.tribunal_var.set(values.get('tribunal', ''))

    def reset(self):
        """Réinitialise complètement le formulaire"""
        # Réinitialiser toutes les variables de texte
        for var in [self.den_ste_var, self.forme_jur_var, self.ice_var,
                   self.date_ice_var, self.capital_var, self.parts_social_var,
                   self.ste_adress_var, self.tribunal_var]:
            var.set("")

        # Réinitialiser les combobox
        for combo in self.combos:
            combo.set("")

        # Réinitialiser les activités
        if hasattr(self, 'activities_container'):
            # Supprimer tous les widgets d'activités
            for child in self.activities_container.winfo_children():
                child.destroy()
            # Vider la liste des variables d'activités
            self.activites_vars.clear()

        # Réinitialiser le dictionnaire de valeurs
        self.values = {}

        # Force la mise à jour de l'affichage
        self.update_idletasks()
        self.event_generate("<<Reset>>")
