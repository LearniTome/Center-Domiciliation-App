import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import DateEntry
from ..utils.utils import ThemeManager

class AssocieForm(ttk.Frame):
    def __init__(self, parent, theme_manager: ThemeManager):
        super().__init__(parent)
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
        self.add_first_associe()

        # Cleanup on destroy
        self.bind("<Destroy>", self._cleanup)

    def create_associe_vars(self):
        """Crée et retourne les variables pour un nouvel associé"""
        return {
            'nom': tk.StringVar(),
            'prenom': tk.StringVar(),
            'date_naiss': tk.StringVar(),
            'lieu_naiss': tk.StringVar(),
            'nationalite': tk.StringVar(),
            'type_piece': tk.StringVar(),
            'num_piece': tk.StringVar(),
            'validite_piece': tk.StringVar(),
            'adresse': tk.StringVar(),
            'pays_residence': tk.StringVar(),
            'capital_detenu': tk.StringVar(),
            'num_parts': tk.StringVar()
        }

    def create_associe_fields(self, parent, index):
        """Crée les champs pour un associé"""
        vars_dict = self.create_associe_vars()

        frame = ttk.LabelFrame(parent, text=f"👤 Associé {index + 1}")
        frame.pack(fill="x", padx=5, pady=5, expand=True)

        # Configure frame grid weights
        frame.grid_columnconfigure(0, weight=1)

        # Identification section
        id_frame = ttk.Frame(frame)
        id_frame.pack(fill="both", padx=5, pady=5, expand=True)

        # Row 1
        row1 = ttk.Frame(id_frame)
        row1.pack(fill="x", pady=2)
        ttk.Label(row1, text="Nom:").pack(side="left", padx=5)
        ttk.Entry(row1, textvariable=vars_dict['nom']).pack(side="left", padx=5, fill="x", expand=True)
        ttk.Label(row1, text="Prénom:").pack(side="left", padx=5)
        ttk.Entry(row1, textvariable=vars_dict['prenom']).pack(side="left", padx=5, fill="x", expand=True)

        # Row 2
        row2 = ttk.Frame(id_frame)
        row2.pack(fill="x", pady=2)
        ttk.Label(row2, text="Date de naissance:").pack(side="left", padx=5)
        date_frame = ttk.Frame(row2)
        date_frame.pack(side="left", padx=5, fill="x", expand=True)
        DateEntry(date_frame, textvariable=vars_dict['date_naiss'], date_pattern='dd/mm/yyyy').pack(fill="x")

        ttk.Label(row2, text="Lieu de naissance:").pack(side="left", padx=5)
        ttk.Entry(row2, textvariable=vars_dict['lieu_naiss']).pack(side="left", padx=5, fill="x", expand=True)

        # Row 3
        row3 = ttk.Frame(id_frame)
        row3.pack(fill="x", pady=2)
        ttk.Label(row3, text="Nationalité:").pack(side="left", padx=5)
        ttk.Entry(row3, textvariable=vars_dict['nationalite']).pack(side="left", padx=5, fill="x", expand=True)

        # Document section
        doc_frame = ttk.Frame(frame)
        doc_frame.pack(fill="x", padx=5, pady=5)

        # Row 4
        row4 = ttk.Frame(doc_frame)
        row4.pack(fill="x", pady=2)
        ttk.Label(row4, text="Type de pièce:").pack(side="left", padx=5)
        ttk.Entry(row4, textvariable=vars_dict['type_piece']).pack(side="left", padx=5, fill="x", expand=True)
        ttk.Label(row4, text="Numéro:").pack(side="left", padx=5)
        ttk.Entry(row4, textvariable=vars_dict['num_piece']).pack(side="left", padx=5, fill="x", expand=True)

        # Row 5
        row5 = ttk.Frame(doc_frame)
        row5.pack(fill="x", pady=2)
        ttk.Label(row5, text="Validité:").pack(side="left", padx=5)
        date_frame = ttk.Frame(row5)
        date_frame.pack(side="left", padx=5, fill="x", expand=True)
        DateEntry(date_frame, textvariable=vars_dict['validite_piece'], date_pattern='dd/mm/yyyy').pack(fill="x")

        # Address section
        addr_frame = ttk.Frame(frame)
        addr_frame.pack(fill="x", padx=5, pady=5)

        # Row 6
        row6 = ttk.Frame(addr_frame)
        row6.pack(fill="x", pady=2)
        ttk.Label(row6, text="Adresse:").pack(side="left", padx=5)
        ttk.Entry(row6, textvariable=vars_dict['adresse']).pack(side="left", padx=5, fill="x", expand=True)

        # Row 7
        row7 = ttk.Frame(addr_frame)
        row7.pack(fill="x", pady=2)
        ttk.Label(row7, text="Pays de résidence:").pack(side="left", padx=5)
        ttk.Entry(row7, textvariable=vars_dict['pays_residence']).pack(side="left", padx=5, fill="x", expand=True)

        # Capital section
        cap_frame = ttk.Frame(frame)
        cap_frame.pack(fill="x", padx=5, pady=5)

        # Row 8
        row8 = ttk.Frame(cap_frame)
        row8.pack(fill="x", pady=2)
        ttk.Label(row8, text="Capital détenu (%):").pack(side="left", padx=5)
        ttk.Entry(row8, textvariable=vars_dict['capital_detenu']).pack(side="left", padx=5, fill="x", expand=True)
        ttk.Label(row8, text="Nombre de parts:").pack(side="left", padx=5)
        ttk.Entry(row8, textvariable=vars_dict['num_parts']).pack(side="left", padx=5, fill="x", expand=True)

        # Remove button
        remove_btn = ttk.Button(
            frame,
            text="❌ Supprimer",
            style='Danger.TButton',
            command=lambda: self.remove_associe(frame, vars_dict)
        )
        remove_btn.pack(side="right", padx=5, pady=5)

        return vars_dict

    def setup_scrollable_container(self):
        """Configure le conteneur scrollable pour les associés"""
        # Create canvas and scrollbar
        canvas_frame = ttk.Frame(self.main_container)
        canvas_frame.grid(row=0, column=0, sticky="nsew")
        canvas_frame.grid_columnconfigure(0, weight=1)
        canvas_frame.grid_rowconfigure(0, weight=1)

        self.canvas = tk.Canvas(canvas_frame, background=self.theme_manager.colors['bg'])
        self.canvas.grid(row=0, column=0, sticky="nsew")

        scrollbar = ttk.Scrollbar(canvas_frame, orient="vertical", command=self.canvas.yview)
        scrollbar.grid(row=0, column=1, sticky="ns")

        # Configure canvas
        self.associes_frame = ttk.Frame(self.canvas)
        self.associes_frame.grid_columnconfigure(0, weight=1)
        self.associes_frame.grid_rowconfigure(0, weight=1)
        self.canvas.configure(yscrollcommand=scrollbar.set)

        # Configure canvas size
        self.canvas.configure(width=800)  # Set a minimum width

        # Create the canvas window
        self.canvas_window = self.canvas.create_window((0, 0), window=self.associes_frame, anchor="nw")

        # Configure canvas resizing
        def on_configure(event):
            self.canvas.configure(scrollregion=self.canvas.bbox("all"))
            # Ajuster la largeur du contenu à celle du canvas
            self.canvas.itemconfig(self.canvas_window, width=self.canvas.winfo_width())

        self.associes_frame.bind("<Configure>", on_configure)
        self.canvas.bind("<Configure>", lambda e: self.canvas.itemconfig(
            self.canvas_window, width=e.width))

        # Mouse wheel scrolling
        def on_mousewheel(event):
            self.canvas.yview_scroll(int(-1 * (event.delta / 120)), "units")

        self.canvas.bind_all("<MouseWheel>", on_mousewheel)

    def setup_control_buttons(self):
        """Configure les boutons de contrôle"""
        buttons_frame = ttk.Frame(self.main_container)
        buttons_frame.grid(row=1, column=0, sticky="ew", pady=5, padx=5)
        buttons_frame.grid_columnconfigure(1, weight=1)  # Pour pousser le bouton à droite

        add_button = ttk.Button(
            buttons_frame,
            text="➕ Ajouter un associé",
            style='Action.TButton',
            command=self.add_associe
        )
        add_button.grid(row=0, column=1, sticky="e")

    def add_first_associe(self):
        """Ajoute le premier associé"""
        self.add_associe()

    def add_associe(self):
        """Ajoute un nouvel associé"""
        if len(self.associe_vars) >= 10:
            messagebox.showwarning(
                "Limite atteinte",
                "Vous ne pouvez pas ajouter plus de 10 associés."
            )
            return

        vars_dict = self.create_associe_fields(self.associes_frame, len(self.associe_vars))
        self.associe_vars.append(vars_dict)

    def remove_associe(self, frame, vars_dict):
        """Supprime un associé"""
        if messagebox.askyesno("Confirmation",
                             "Voulez-vous vraiment supprimer cet associé ?"):
            self.associe_vars.remove(vars_dict)
            frame.destroy()
            self.update_associes_numbers()

    def update_associes_numbers(self):
        """Met à jour la numérotation des associés"""
        for i, frame in enumerate(self.associes_frame.winfo_children(), 1):
            if isinstance(frame, ttk.LabelFrame):
                frame.configure(text=f"👤 Associé {i}")

    def get_values(self):
        """Retourne les valeurs des champs pour tous les associés"""
        values_list = []
        for vars_dict in self.associe_vars:
            associe_values = {}
            for key, var in vars_dict.items():
                associe_values[key] = var.get()
            values_list.append(associe_values)
        return values_list

    def set_values(self, values_list):
        """Définit les valeurs des champs pour tous les associés"""
        # Supprimer tous les associés existants
        for frame in self.associes_frame.winfo_children():
            frame.destroy()
        self.associe_vars.clear()

        # Pas de valeurs à définir
        if not values_list:
            self.add_first_associe()
            return

        # Ajouter et remplir les nouveaux associés
        for values in values_list:
            vars_dict = self.create_associe_fields(self.associes_frame, len(self.associe_vars))
            for key, value in values.items():
                if key in vars_dict:
                    vars_dict[key].set(value if value is not None else "")
            self.associe_vars.append(vars_dict)

    def reset(self):
        """Réinitialise complètement le formulaire"""
        # Supprimer tous les widgets associés existants
        for frame in self.associes_frame.winfo_children():
            frame.destroy()
        # Vider la liste des variables
        self.associe_vars.clear()
        # Ajouter un nouvel associé vide
        self.add_first_associe()
        # Forcer la mise à jour de l'affichage
        self.canvas.yview_moveto(0)

    def _cleanup(self, event=None):
        """Nettoie les événements lors de la destruction du widget"""
        try:
            self.unbind_all("<MouseWheel>")
            self.unbind("<Destroy>")
        except Exception:
            pass  # Ignorer les erreurs de nettoyage
