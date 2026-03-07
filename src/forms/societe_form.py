import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import DateEntry
from typing import Optional
from ..utils.constants import DenSte, Formjur, Capital, PartsSocial, SteAdresse, Tribunnaux
from ..utils.utils import ThemeManager, WidgetFactory

class SocieteForm(ttk.Frame):
    def __init__(self, parent, theme_manager: Optional[ThemeManager] = None, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = {}

        # Backward compatibility: SocieteForm(parent, values_dict)
        if isinstance(theme_manager, dict) and values_dict is None:
            values_dict = theme_manager
            theme_manager = None

        # Initialiser le gestionnaire de thème
        self.theme_manager = theme_manager or ThemeManager(self.winfo_toplevel())
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
        from ..utils.utils import get_reference_data
        from ..utils.defaults_manager import get_defaults_manager

        # Get defaults manager
        defaults_mgr = get_defaults_manager()
        
        # Get custom defaults or fall back to constants
        default_den_ste = defaults_mgr.get_default('societe', 'DenSte') or (DenSte[0] if DenSte else "")
        default_form_jur = defaults_mgr.get_default('societe', 'FormJur') or (Formjur[0] if Formjur else "")
        default_capital = defaults_mgr.get_default('societe', 'Capital') or (Capital[0] if Capital else "")
        default_parts_social = defaults_mgr.get_default('societe', 'PartsSocial') or (PartsSocial[0] if PartsSocial else "")
        default_valeur_nominale = defaults_mgr.get_default('societe', 'ValeurNominale') or "100"
        default_ste_adresse = defaults_mgr.get_default('societe', 'SteAdresse') or (SteAdresse[0] if SteAdresse else "")
        default_tribunal = defaults_mgr.get_default('societe', 'Tribunal') or (Tribunnaux[0] if Tribunnaux else "")

        self.den_ste_var = tk.StringVar(value=default_den_ste)
        self.forme_jur_var = tk.StringVar(value=default_form_jur)
        self.ice_var = tk.StringVar(value="")
        # default date to today's date in dd/mm/yyyy
        import datetime
        today = datetime.date.today().strftime('%d/%m/%Y')
        self.date_ice_var = tk.StringVar(value=today)
        self.date_expiration_certificat_negatif_var = tk.StringVar(value='')
        self.capital_var = tk.StringVar(value=default_capital)
        self.parts_social_var = tk.StringVar(value=default_parts_social)
        self.valeur_nominale_var = tk.StringVar(value=default_valeur_nominale)

        # Load reference data from database
        self.ste_adresses = get_reference_data('SteAdresses')
        self.tribunaux = get_reference_data('Tribunaux')

        self.ste_adress_var = tk.StringVar(value=default_ste_adresse)
        self.tribunal_var = tk.StringVar(value=default_tribunal)

        # Liste pour stocker les variables des activités
        self.activites_vars = []

    def setup_gui(self):
        """Configure une mise en page compacte sans sous-blocs internes."""
        main_frame = ttk.Frame(self, padding=(10, 5))
        main_frame.pack(fill="both", expand=True)

        fields = ttk.Frame(main_frame)
        fields.pack(fill="x", padx=5, pady=(2, 4))
        for col in range(6):
            fields.columnconfigure(col, weight=1, uniform="societe_cols")

        def _cell(row: int, col: int, label_text: str, span: int = 1, pad: int = 8) -> ttk.Frame:
            cell = ttk.Frame(fields)
            cell.grid(
                row=row,
                column=col,
                columnspan=span,
                sticky="ew",
                padx=(0, pad) if (col + span - 1) < 5 else (0, 0),
                pady=(0, 4),
            )
            cell.columnconfigure(0, weight=1)
            ttk.Label(cell, text=label_text, anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
            return cell

        # Ligne 1: 6 colonnes (Tribunal juste après Forme Juridique)
        den_cell = _cell(0, 0, "Dénomination:")
        den_combo = ttk.Combobox(den_cell, textvariable=self.den_ste_var, values=DenSte)
        den_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(den_combo)

        form_cell = _cell(0, 1, "Forme Juridique:")
        form_combo = ttk.Combobox(form_cell, textvariable=self.forme_jur_var, values=Formjur)
        form_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(form_combo)

        tribunal_cell = _cell(0, 2, "Tribunal:")
        tribunal_combo = ttk.Combobox(tribunal_cell, textvariable=self.tribunal_var, values=self.tribunaux)
        tribunal_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(tribunal_combo)

        ice_cell = _cell(0, 3, "ICE:")
        ttk.Entry(ice_cell, textvariable=self.ice_var).grid(row=1, column=0, sticky="ew")

        date_cell = _cell(0, 4, "Date certificat négatif:")
        DateEntry(
            date_cell,
            textvariable=self.date_ice_var,
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="ew")

        date_exp_cell = _cell(0, 5, "Date expiration certificat négatif:")
        DateEntry(
            date_exp_cell,
            textvariable=self.date_expiration_certificat_negatif_var,
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="ew")

        # Ligne 2: Adresse en dernier (2 colonnes)
        capital_cell = _cell(1, 0, "Capital:")
        ttk.Entry(capital_cell, textvariable=self.capital_var).grid(row=1, column=0, sticky="ew")

        parts_cell = _cell(1, 1, "Parts:")
        ttk.Entry(parts_cell, textvariable=self.parts_social_var).grid(row=1, column=0, sticky="ew")

        valeur_nominale_cell = _cell(1, 2, "Valeur nominale (DH):")
        ttk.Entry(valeur_nominale_cell, textvariable=self.valeur_nominale_var).grid(row=1, column=0, sticky="ew")

        adresse_cell = _cell(1, 3, "Adresse:", span=2)
        adresse_combo = ttk.Combobox(adresse_cell, textvariable=self.ste_adress_var, values=self.ste_adresses)
        adresse_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(adresse_combo)

        # Ligne 3+: activités
        self.create_activities_section(main_frame)

    def create_activities_section(self, parent):
        """Crée une section activités compacte sans bloc interne."""
        from ..utils.utils import get_reference_data

        section = ttk.Frame(parent)
        section.pack(fill="x", padx=5, pady=(6, 2))

        header = ttk.Frame(section)
        header.pack(fill="x", pady=(0, 4))
        ttk.Label(header, text="Activités:", anchor="w").pack(side="left")
        ttk.Label(header, text="Ajoutez jusqu'à 6 activités principales", anchor="w").pack(side="left", padx=(10, 0))

        # Load activities from reference sheet
        self.activities_list = get_reference_data('Activites')

        # Bouton d'ajout
        add_btn = WidgetFactory.create_button(header,
                   text="➕ Ajouter une activité",
                   command=self.add_activity,
                   style='Success.TButton')
        add_btn.pack(side="right")

        # Container pour les activités
        self.activities_container = ttk.Frame(section)
        self.activities_container.pack(fill="x", expand=True, pady=(2, 0))

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

        # Combobox pour l'activité — use data loaded from reference sheet
        combo = ttk.Combobox(activity_frame,
                           textvariable=var,
                           values=self.activities_list)
        combo.pack(side="left", fill="x", expand=True, padx=(0, 5))

        # Bouton de suppression
        remove_btn = WidgetFactory.create_button(activity_frame,
                      text="❌",
                      command=lambda f=activity_frame, v=var: self.remove_activity(f, v),
                      style='Cancel.TButton')
        # keep small width via padding instead of width to keep uniform height
        remove_btn.pack(side="right", pady=3)

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
            'date_certificat_negatif': self.date_ice_var.get(),
            'date_expiration_certificat_negatif': self.date_expiration_certificat_negatif_var.get(),
            'capital': self.capital_var.get(),
            'parts_social': self.parts_social_var.get(),
            'valeur_nominale': self.valeur_nominale_var.get(),
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
        self.date_ice_var.set(values_dict.get('date_ice', values_dict.get('date_certificat_negatif', '')))
        self.date_expiration_certificat_negatif_var.set(
            values_dict.get('date_expiration_certificat_negatif', values_dict.get('date_exp_certificat_negatif', ''))
        )
        self.capital_var.set(values_dict.get('capital', ''))
        self.parts_social_var.set(values_dict.get('parts_social', ''))
        self.valeur_nominale_var.set(values_dict.get('valeur_nominale', values_dict.get('valeur_nominal', '')))
        self.ste_adress_var.set(values_dict.get('adresse', ''))
        self.tribunal_var.set(values_dict.get('tribunal', ''))

        # Mise à jour des activités
        activites = values_dict.get('activites', [])
        for activite in activites:
            if len(self.activites_vars) < 6:  # Vérifier la limite
                self.add_activity()
                self.activites_vars[-1].set(activite)
