import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import DateEntry
from typing import Optional
from src.utils.utils import ThemeManager

class AssocieForm(ttk.Frame):
    def __init__(self, parent, theme_manager: Optional[ThemeManager] = None, values_dict=None):
        """AssocieForm supports two calling conventions for backward compatibility:

        - AssocieForm(parent, theme_manager)
        - AssocieForm(parent, values_dict={})  # legacy dashboard usage

        We detect the types and adapt accordingly.
        """
        super().__init__(parent)

        # Backwards-compatible handling: if the second positional arg is a dict or list,
        # it's actually the legacy `values_dict`.
        if isinstance(theme_manager, (dict, list)) and values_dict is None:
            values_dict = theme_manager
            theme_manager = None

        # Ensure we have a ThemeManager instance
        if theme_manager is None:
            try:
                theme_manager = ThemeManager(self.winfo_toplevel())
            except Exception:
                # Fallback: try constructing with self; if not available keep None
                try:
                    theme_manager = ThemeManager(self)
                except Exception:
                    theme_manager = None

        self.theme_manager = theme_manager
        self.associe_vars = []

        # Configure weights to expand properly
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(0, weight=1)

        # Configure main container
        self.main_container = ttk.Frame(self)
        self.main_container.grid(row=0, column=0, sticky="nsew")
        self.main_container.grid_columnconfigure(0, weight=1)
        self.main_container.grid_rowconfigure(0, weight=1)
        # Setup UI components
        self.setup_scrollable_container()
        self.setup_control_buttons()
        # Note: do NOT auto-add an initial associé here. The parent MainForm
        # will control how many associés are created initially to avoid
        # duplications when embedded in different contexts.

        # Cleanup on destroy
        self.bind("<Destroy>", self._cleanup)

        # If legacy values_dict was provided, populate fields
        if values_dict is not None:
            # If it's a list of associés, set them all
            if isinstance(values_dict, list):
                if len(values_dict) == 0:
                    # create a single empty associe to allow data entry
                    self.add_associe()
                else:
                    self.set_values(values_dict)
            elif isinstance(values_dict, dict):
                # create one associe and populate
                self.add_associe()
                if len(values_dict) > 0:
                    self.set_values([values_dict])

    def create_associe_vars(self):
        """Crée et retourne les variables pour un nouvel associé"""
        return {
            'civilite': tk.StringVar(),
            'nom': tk.StringVar(),
            'prenom': tk.StringVar(),
            'parts': tk.StringVar(),
            'date_naiss': tk.StringVar(),
            'lieu_naiss': tk.StringVar(),
            'nationalite': tk.StringVar(),
            'num_piece': tk.StringVar(),
            'validite_piece': tk.StringVar(),
            'adresse': tk.StringVar(),
            'telephone': tk.StringVar(),
            'email': tk.StringVar(),
            'est_gerant': tk.BooleanVar(),
            'qualite': tk.StringVar(),
            'capital_detenu': tk.StringVar(),
            'num_parts': tk.StringVar()
        }

    def create_associe_fields(self, parent, index):
        """Crée les champs pour un associé"""
        vars_dict = self.create_associe_vars()

        # Frame principal de l'associé
        frame = ttk.LabelFrame(parent, text=f"👤 Associé {index + 1}")
        frame.pack(fill="x", padx=5, pady=5, expand=True)

        # Conteneur principal à deux colonnes
        main_grid = ttk.Frame(frame)
        main_grid.pack(fill="x", padx=5, pady=5, expand=True)
        main_grid.columnconfigure(0, weight=1)
        main_grid.columnconfigure(1, weight=1)

        # === COLONNE GAUCHE ===
        left_column = ttk.Frame(main_grid)
        left_column.grid(row=0, column=0, sticky="nsew", padx=5)

        # Section Informations de base
        self.create_basic_info_section(left_column, vars_dict)

        # Section Naissance
        self.create_birth_section(left_column, vars_dict)

        # Section Statut de Gérant
        self.create_manager_section(left_column, vars_dict)

        # === COLONNE DROITE ===
        right_column = ttk.Frame(main_grid)
        right_column.grid(row=0, column=1, sticky="nsew", padx=5)

        # Section Identité
        self.create_identity_section(right_column, vars_dict)

        # Section Contact
        self.create_contact_section(right_column, vars_dict)

        # Section Capital
        self.create_capital_section(right_column, vars_dict)

        # Bouton Supprimer
        remove_btn = ttk.Button(
            frame,
            text="❌ Supprimer",
            style='Danger.TButton',
            command=lambda: self.remove_associe(frame, vars_dict)
        )
        remove_btn.pack(side="right", padx=5, pady=5)

        self.associe_vars.append(vars_dict)
        return frame

    def create_basic_info_section(self, parent, vars_dict):
        """Crée la section Informations de base"""
        info_frame = ttk.LabelFrame(parent, text="📝 Informations de base")
        info_frame.pack(fill="x", pady=5, expand=True)

        grid = ttk.Frame(info_frame)
        grid.pack(fill="x", padx=5, pady=5, expand=True)
        grid.columnconfigure(1, weight=1)
        grid.columnconfigure(3, weight=1)

        # Ligne 1: Civilité et Prénom
        ttk.Label(grid, text="Civilité:", anchor="e", width=12).grid(row=0, column=0, padx=(0, 5), pady=2)
        civilite_cb = ttk.Combobox(grid, textvariable=vars_dict['civilite'],
                                values=["M.", "Mme", "Mlle"], width=8)
        civilite_cb.grid(row=0, column=1, sticky="w", padx=(0, 15), pady=2)

        ttk.Label(grid, text="Prénom:", anchor="e", width=12).grid(row=0, column=2, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['prenom']).grid(row=0, column=3, sticky="ew", pady=2)

        # Ligne 2: Nom et Parts
        ttk.Label(grid, text="Nom:", anchor="e", width=12).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['nom']).grid(row=1, column=1, sticky="ew", padx=(0, 15), pady=2)

        ttk.Label(grid, text="Parts (%):", anchor="e", width=12).grid(row=1, column=2, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['parts']).grid(row=1, column=3, sticky="ew", pady=2)

    def create_birth_section(self, parent, vars_dict):
        """Crée la section Naissance"""
        birth_frame = ttk.LabelFrame(parent, text="👶 Naissance")
        birth_frame.pack(fill="x", pady=5, expand=True)

        grid = ttk.Frame(birth_frame)
        grid.pack(fill="x", padx=5, pady=5, expand=True)
        grid.columnconfigure(1, weight=1)

        # Date de naissance
        ttk.Label(grid, text="Date de naissance:", anchor="e", width=15).grid(row=0, column=0, padx=(0, 5), pady=2)
        DateEntry(grid, textvariable=vars_dict['date_naiss'],
                date_pattern='dd/mm/yyyy', width=12).grid(row=0, column=1, sticky="w", pady=2)

        # Lieu de naissance
        ttk.Label(grid, text="Lieu de naissance:", anchor="e", width=15).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['lieu_naiss']).grid(row=1, column=1, sticky="ew", pady=2)

    def create_manager_section(self, parent, vars_dict):
        """Crée la section Statut de Gérant"""
        manager_frame = ttk.LabelFrame(parent, text="👔 Statut de Gérant")
        manager_frame.pack(fill="x", pady=5, expand=True)

        grid = ttk.Frame(manager_frame)
        grid.pack(fill="x", padx=5, pady=5, expand=True)
        grid.columnconfigure(1, weight=1)

        # Checkbox Est Gérant
        ttk.Checkbutton(grid, text="Est Gérant", variable=vars_dict['est_gerant']).grid(
            row=0, column=0, columnspan=2, sticky="w", pady=2)

        # Qualité
        ttk.Label(grid, text="Qualité:", anchor="e", width=12).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['qualite']).grid(row=1, column=1, sticky="ew", pady=2)

    def create_identity_section(self, parent, vars_dict):
        """Crée la section Identité"""
        identity_frame = ttk.LabelFrame(parent, text="🆔 Identité")
        identity_frame.pack(fill="x", pady=5, expand=True)

        grid = ttk.Frame(identity_frame)
        grid.pack(fill="x", padx=5, pady=5, expand=True)
        grid.columnconfigure(1, weight=1)

        # Nationalité
        ttk.Label(grid, text="Nationalité:", anchor="e", width=12).grid(row=0, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['nationalite']).grid(row=0, column=1, sticky="ew", pady=2)

        # N° CIN
        ttk.Label(grid, text="N° CIN:", anchor="e", width=12).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['num_piece']).grid(row=1, column=1, sticky="ew", pady=2)

        # Validité CIN
        ttk.Label(grid, text="Validité CIN:", anchor="e", width=12).grid(row=2, column=0, padx=(0, 5), pady=2)
        date_container = ttk.Frame(grid)
        date_container.grid(row=2, column=1, sticky="ew", pady=2)
        date_container.columnconfigure(0, weight=1)

        DateEntry(date_container, textvariable=vars_dict['validite_piece'],
                date_pattern='dd/mm/yyyy', width=12).grid(row=0, column=0, sticky="w")

    def create_contact_section(self, parent, vars_dict):
        """Crée la section Contact"""
        contact_frame = ttk.LabelFrame(parent, text="📞 Contact")
        contact_frame.pack(fill="x", pady=5, expand=True)

        grid = ttk.Frame(contact_frame)
        grid.pack(fill="x", padx=5, pady=5, expand=True)
        grid.columnconfigure(1, weight=1)

        # Adresse
        ttk.Label(grid, text="Adresse:", anchor="e", width=12).grid(row=0, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['adresse']).grid(row=0, column=1, sticky="ew", pady=2)

        # Téléphone
        ttk.Label(grid, text="Téléphone:", anchor="e", width=12).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['telephone']).grid(row=1, column=1, sticky="ew", pady=2)

        # Email
        ttk.Label(grid, text="Email:", anchor="e", width=12).grid(row=2, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['email']).grid(row=2, column=1, sticky="ew", pady=2)

    def create_capital_section(self, parent, vars_dict):
        """Crée la section Capital"""
        capital_frame = ttk.LabelFrame(parent, text="💰 Capital")
        capital_frame.pack(fill="x", pady=5, expand=True)

        grid = ttk.Frame(capital_frame)
        grid.pack(fill="x", padx=5, pady=5, expand=True)
        grid.columnconfigure(1, weight=1)
        grid.columnconfigure(3, weight=1)

        # Capital détenu et Nombre de parts
        ttk.Label(grid, text="Capital détenu (%):", anchor="e", width=15).grid(row=0, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['capital_detenu']).grid(row=0, column=1, sticky="ew", padx=(0, 15), pady=2)

        ttk.Label(grid, text="Nombre de parts:", anchor="e", width=15).grid(row=0, column=2, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['num_parts']).grid(row=0, column=3, sticky="ew", pady=2)

    def setup_scrollable_container(self):
        """Configure le conteneur scrollable pour les associés"""
        # Use a simple frame inside the main container. The outer MainForm
        # canvas (the page container) will handle scrolling for the whole page.
        canvas_frame = ttk.Frame(self.main_container)
        canvas_frame.grid(row=0, column=0, sticky="nsew")
        canvas_frame.grid_columnconfigure(0, weight=1)
        canvas_frame.grid_rowconfigure(0, weight=1)

        # The associes_frame holds the individual associé LabelFrames.
        # It is a plain Frame (no inner canvas/scrollbar) so the outer
        # MainForm canvas controls scrolling for the whole page.
        self.associes_frame = ttk.Frame(canvas_frame)
        self.associes_frame.grid(row=0, column=0, sticky="nsew")
        self.associes_frame.grid_columnconfigure(0, weight=1)

    def setup_control_buttons(self):
        """Configure les boutons de contrôle"""
        buttons_frame = ttk.Frame(self.main_container)
        buttons_frame.grid(row=1, column=0, sticky="ew", pady=5, padx=5)
        buttons_frame.grid_columnconfigure(1, weight=1)

        add_button = ttk.Button(
            buttons_frame,
            text="➕ Ajouter un associé",
            style='Action.TButton',
            command=self.add_associe
        )
        add_button.grid(row=0, column=1, sticky="e")

    def add_first_associe(self):
        """Ajoute le premier associé"""
        # kept for backward compatibility but left intentionally empty
        return

    def add_associe(self):
        """Ajoute un nouvel associé"""
        if len(self.associe_vars) >= 10:
            messagebox.showwarning(
                "Limite atteinte",
                "Vous ne pouvez pas ajouter plus de 10 associés."
            )
            return

        self.create_associe_fields(self.associes_frame, len(self.associe_vars))

    def get_values(self):
        """Retourne la liste des associés sous forme de dictionnaires."""
        results = []
        for vars_dict in self.associe_vars:
            item = {}
            for k, v in vars_dict.items():
                try:
                    # BooleanVar -> bool
                    if isinstance(v, tk.BooleanVar):
                        item[k] = bool(v.get())
                    else:
                        item[k] = v.get()
                except Exception:
                    item[k] = None
            results.append(item)
        return results

    def set_values(self, associes_list):
        """Remplit le formulaire des associés avec une liste de dicts.

        Each element of associes_list should be a dict mapping the field names
        to values. This will clear existing entries and recreate them.
        """
        # Clear existing UI
        for child in list(self.associes_frame.winfo_children()):
            child.destroy()
        self.associe_vars = []

        for assoc in associes_list:
            self.create_associe_fields(self.associes_frame, len(self.associe_vars))
            # Populate the latest vars dict
            vars_dict = self.associe_vars[-1]
            for k, val in assoc.items():
                if k in vars_dict:
                    try:
                        if isinstance(vars_dict[k], tk.BooleanVar):
                            vars_dict[k].set(bool(val))
                        else:
                            vars_dict[k].set(val)
                    except Exception:
                        pass

    def remove_associe(self, frame, vars_dict):
        """Supprime un associé"""
        if messagebox.askyesno("Confirmation",
                             "Voulez-vous vraiment supprimer cet associé ?"):
            self.associe_vars.remove(vars_dict)
            frame.destroy()
            self.update_associes_numbers()

    def update_associes_numbers(self):
        """Met à jour les numéros des associés après une suppression"""
        for i, frame in enumerate(self.associes_frame.winfo_children()):
            if isinstance(frame, ttk.LabelFrame):
                frame.configure(text=f"👤 Associé {i + 1}")

    def _cleanup(self, event):
        """Nettoyage lors de la destruction"""
        try:
            if hasattr(self, 'canvas'):
                self.canvas.unbind_all("<MouseWheel>")
        except Exception:
            pass
