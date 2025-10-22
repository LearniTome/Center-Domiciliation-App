import tkinter as tk
from tkinter import ttk
from tkcalendar import Calendar
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
        self.date_contrat_var = tk.StringVar()
        self.period_var = tk.StringVar()
        self.prix_mensuel_var = tk.StringVar()
        self.prix_inter_var = tk.StringVar()
        self.date_debut_var = tk.StringVar()
        self.date_fin_var = tk.StringVar()

    def setup_gui(self):
        """Configure l'interface utilisateur principale"""
        # Conteneur principal avec grille
        main_frame = ttk.Frame(self)
        main_frame.pack(fill="both", expand=True, padx=5, pady=5)

        # Configuration de la grille (3 colonnes, 2 lignes)
        for i in range(3):
            main_frame.grid_columnconfigure(i, weight=1, uniform="col")

        # Création des champs en 2 lignes de 3 champs
        self.create_fields_row1(main_frame)
        self.create_fields_row2(main_frame)

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

    def create_date_field_group(self, parent, label_text, variable):
        """Crée un groupe de champs pour les dates"""
        frame = ttk.LabelFrame(parent, text=label_text, padding=(5, 5, 5, 5))

        entry_frame = ttk.Frame(frame)
        entry_frame.pack(fill="x", expand=True)

        entry = ttk.Entry(entry_frame, textvariable=variable)
        entry.pack(side="left", fill="x", expand=True)

        cal_button = WidgetFactory.create_button(entry_frame, text="📅",
                      command=lambda v=variable: self.show_calendar(v), style='Secondary.TButton')
        cal_button.pack(side="right", padx=(5, 0))

        return frame

    def create_entry_field_group(self, parent, label_text, variable):
        """Crée un groupe de champs pour les entrées simples"""
        frame = ttk.LabelFrame(parent, text=label_text, padding=(5, 5, 5, 5))

        entry = ttk.Entry(frame, textvariable=variable)
        entry.pack(fill="x", expand=True)

        return frame

    def create_combo_field_group(self, parent, label_text, variable, values):
        """Crée un groupe de champs pour les combobox"""
        frame = ttk.LabelFrame(parent, text=label_text, padding=(5, 5, 5, 5))

        combo = ttk.Combobox(frame, textvariable=variable, values=values)
        combo.pack(fill="x", expand=True)

        return frame

    def show_calendar(self, var):
        """Affiche un calendrier pour sélectionner une date"""
        top = tk.Toplevel(self)
        top.title("Sélectionner une date")
        cal = Calendar(top, selectmode="day", date_pattern="dd/mm/y")
        cal.pack(padx=10, pady=10)

        def set_date():
            var.set(cal.get_date())
            top.destroy()

        WidgetFactory.create_button(top, text="OK", command=set_date, style='Secondary.TButton').pack(pady=5)

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
        self.date_contrat_var.set('')
        self.period_var.set('')
        self.prix_mensuel_var.set('')
        self.prix_inter_var.set('')
        self.date_debut_var.set('')
        self.date_fin_var.set('')
        self.values = {}

    def _cleanup(self, event=None):
        """Nettoie les ressources lors de la destruction"""
        self.unbind("<Destroy>")
