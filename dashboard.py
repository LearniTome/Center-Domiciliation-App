import tkinter as tk
from tkinter import ttk
import pandas as pd
import os
from utils import ThemeManager, WidgetFactory
from tkinter import ttk, messagebox
import pandas as pd
import os
from pathlib import Path
import openpyxl
from Main_app import societe_headers, associe_headers, contrat_headers, DomiciliationApp
from utils import ThemeManager, WidgetFactory, PathManager

# Combine all headers for the dashboard view
# We keep all headers including IDs as they're needed for data operations
excel_headers = societe_headers + associe_headers[2:] + contrat_headers[2:]  # Skip ID fields from associe and contrat

# Headers to display in the treeview (excluding technical IDs)
display_headers = [h for h in excel_headers if not h.startswith('ID_')]

class DomiciliationDashboard:
    def __init__(self, root):
        self.root = root

        # Determine if we're working with a root window or a frame
        if isinstance(root, tk.Tk):
            self.root.title("Dashboard Domiciliation")
            self.root.geometry("1200x800")
            window = root
        else:
            window = root.winfo_toplevel()

        # Configuration du thème et des styles
        self.theme_manager = ThemeManager(window)
        self.style = self.theme_manager.style

        # Configuration initiale
        self.setup_styles()
        self.setup_column_configs()

        # Création de la barre de statut
        self.status_bar = ttk.Label(self.root, text="Prêt", relief=tk.SUNKEN, anchor=tk.W)
        self.status_bar.pack(side=tk.BOTTOM, fill=tk.X)

        # Charger les données
        self.load_data()
        # Créer l'interface
        self.setup_gui()

    def setup_styles(self):
        """Configure les styles personnalisés pour le dashboard"""
        # Style du Treeview
        self.style.configure('Custom.Treeview',
                           rowheight=30,
                           font=('Segoe UI', 9))
        self.style.configure('Custom.Treeview.Heading',
                           font=('Segoe UI', 9, 'bold'))

        # Style des boutons de contrôle
        self.style.configure('Control.TButton',
                           padding=(10, 5),
                           font=('Segoe UI', 9))

        # Style des filtres
        self.style.configure('Filter.TFrame',
                           padding=10)

        # Style de la barre d'outils
        self.style.configure('Toolbar.TFrame',
                           padding=5)

        # Style des labels d'info
        self.style.configure('Info.TLabel',
                           font=('Segoe UI', 9),
                           padding=(5, 2))
        """Configure les styles pour les widgets"""
        # Style pour les Treeviews
        self.style.configure("Treeview",
                           rowheight=25,
                           fieldbackground=self.theme_manager.colors['bg'])
        # Style pour les en-têtes avec texte en gras et centré
        self.style.configure("Treeview.Heading",
                           background="#4a90e2",
                           foreground="white",
                           relief="flat",
                           font=('TkDefaultFont', 9, 'bold'),  # Ajout du style gras
                           justify='center')  # Centrage du texte
        self.style.map("Treeview.Heading",
                      background=[("active", "#2171cd")])

        # Style pour les boutons
        self.style.configure("Action.TButton",
                           padding=6,
                           relief="flat",
                           background="#4a90e2",
                           foreground="white")
        self.style.map("Action.TButton",
                      background=[("active", "#2171cd")])

    def setup_column_configs(self):
        """Configure les colonnes et leurs propriétés"""
        # Configuration des largeurs de colonnes
        self.column_widths = {
            # Colonnes de la société
            "DEN_STE": 200,        # Plus large pour les noms de société
            "FORME_JUR": 120,      # Largeur standard
            "ICE": 100,            # Plus étroit pour les numéros
            "CAPITAL": 100,        # Plus étroit pour les montants
            "PART_SOCIAL": 100,    # Plus étroit pour les montants
            "STE_ADRESS": 200,     # Plus large pour les adresses
            "DATE_ICE": 100,       # Plus étroit pour les dates
            "TRIBUNAL": 150,       # Largeur standard

            # Colonnes des associés
            "CIVIL": 80,           # Plus étroit
            "PRENOM": 120,         # Largeur standard
            "NOM": 120,            # Largeur standard
            "NATIONALITY": 120,    # Largeur standard
            "CIN_NUM": 100,        # Plus étroit pour les numéros
            "CIN_VALIDATY": 100,   # Plus étroit pour les dates
            "DATE_NAISS": 100,     # Plus étroit pour les dates
            "LIEU_NAISS": 150,     # Largeur standard
            "ADRESSE": 200,        # Plus large pour les adresses
            "PHONE": 120,          # Largeur standard
            "EMAIL": 150,          # Plus large pour les emails
            "PARTS": 80,           # Plus étroit
            "IS_GERANT": 80,       # Plus étroit
            "QUALITY": 120,        # Largeur standard

            # Colonnes du contrat
            "PERIOD_DOMCIL": 100,  # Plus étroit
            "PRIX_CONTRAT": 100,   # Plus étroit pour les montants
            "PRIX_INTERMEDIARE_CONTRAT": 120,  # Largeur standard
            "DOM_DATEDEB": 100,    # Plus étroit pour les dates
            "DOM_DATEFIN": 100,    # Plus étroit pour les dates
        }

        # Noms d'affichage pour les colonnes
        self.column_names = {
            # Colonnes de la société
            "DEN_STE": "Société",
            "FORME_JUR": "Forme Juridique",
            "ICE": "ICE",
            "CAPITAL": "Capital",
            "PART_SOCIAL": "Part Social",
            "STE_ADRESS": "Adresse Société",
            "DATE_ICE": "Date ICE",
            "TRIBUNAL": "Tribunal",

            # Colonnes des associés
            "CIVIL": "Civilité",
            "PRENOM": "Prénom",
            "NOM": "Nom",
            "NATIONALITY": "Nationalité",
            "CIN_NUM": "CIN",
            "CIN_VALIDATY": "Validité CIN",
            "DATE_NAISS": "Date Naissance",
            "LIEU_NAISS": "Lieu Naissance",
            "ADRESSE": "Adresse",
            "PHONE": "Téléphone",
            "EMAIL": "Email",
            "PARTS": "Parts",
            "IS_GERANT": "Est Gérant",
            "QUALITY": "Qualité",

            # Colonnes du contrat
            "PERIOD_DOMCIL": "Période",
            "PRIX_CONTRAT": "Prix Contrat",
            "PRIX_INTERMEDIARE_CONTRAT": "Prix Intermédiaire",
            "DOM_DATEDEB": "Date Début",
            "DOM_DATEFIN": "Date Fin"
        }

    def save_data(self, excel_file_path=None):
        """Sauvegarde les données dans le fichier Excel."""
        if excel_file_path is None:
            excel_file_path = Path(__file__).parent / 'databases' / 'DataBase_domiciliation.xlsx'
        self.df.to_excel(excel_file_path, sheet_name="DataBaseDom", index=False)



    def load_data(self):
        """Charge les données depuis le fichier Excel ou crée un nouveau fichier si nécessaire."""
        try:
            PathManager.ensure_directories()
            excel_file_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

            if not excel_file_path.exists():
                self.df = pd.DataFrame(columns=excel_headers)
                self.save_data(excel_file_path)
                return

            self.df = pd.read_excel(excel_file_path, sheet_name="DataBaseDom", engine='openpyxl')

            for col in excel_headers:
                if col not in self.df.columns:
                    self.df[col] = ""  # Ajout silencieux des colonnes manquantes

            # Réorganiser les colonnes dans le même ordre que excel_headers
            self.df = self.df[excel_headers]

        except Exception as e:
            messagebox.showerror("Erreur", f"Erreur lors du chargement des données: {str(e)}")
            self.df = pd.DataFrame(columns=excel_headers)

    def setup_gui(self):
        # Frame principal pour la barre d'outils
        toolbar_frame = ttk.Frame(self.root)
        toolbar_frame.pack(fill="x", padx=10, pady=5)

        # Frame de recherche (à gauche)
        search_frame = ttk.LabelFrame(toolbar_frame, text="Recherche avancée", padding="10")
        search_frame.pack(side="left", fill="x", expand=True)

        # Zone de recherche avec style amélioré
        search_container = ttk.Frame(search_frame)
        search_container.pack(fill="x", expand=True)

        ttk.Label(search_container, text="🔍").pack(side="left", padx=5)
        self.search_var = tk.StringVar()
        self.search_entry = ttk.Entry(search_container, textvariable=self.search_var, width=40)
        self.search_entry.pack(side="left", padx=5, fill="x", expand=True)
        self.search_entry.bind('<KeyRelease>', self.search_records)

        # Menu déroulant pour le filtre de recherche
        self.filter_var = tk.StringVar(value="Tout")
        filter_menu = ttk.OptionMenu(search_container, self.filter_var,
                                     "Tout","Société", "Gérant", "Contrat")
        filter_menu.pack(side="left", padx=5)

        # Frame des boutons (à droite)
        button_frame = ttk.Frame(toolbar_frame)
        button_frame.pack(side="right", padx=10)

        # Boutons avec style amélioré
        ttk.Button(button_frame, text="➕ Ajouter",
                  style="Action.TButton", command=self.add_society).pack(side="left", padx=5)
        ttk.Button(button_frame, text="✏️ Modifier",
                  style="Action.TButton", command=self.edit_society).pack(side="left", padx=5)
        ttk.Button(button_frame, text="🗑️ Supprimer",
                  style="Action.TButton", command=self.delete_society).pack(side="left", padx=5)
        ttk.Button(button_frame, text="🔄 Actualiser",
                  style="Action.TButton", command=self.refresh_data).pack(side="left", padx=5)

        # Treeview pour afficher les données
        self.setup_treeview()

    def create_treeview(self, parent, columns, title):
        """Crée un treeview avec scrollbars"""
        # Frame pour contenir le treeview
        frame = ttk.LabelFrame(parent, text=title, padding="2")
        frame.pack(fill="both", padx=2, pady=1)  # Réduit les marges

        # Frame interne pour le treeview et les scrollbars
        tree_frame = ttk.Frame(frame)
        tree_frame.pack(fill="both", expand=True)

        # Style pour les lignes de société (en gras)
        self.style.configure("societe.Treeview", font=('TkDefaultFont', 9, 'bold'))

        # Définir une hauteur fixe pour le treeview
        height = 8 if title == "Sociétés" else 6  # Hauteur augmentée pour Associés et Contrats

        # Scrollbars
        y_scrollbar = ttk.Scrollbar(tree_frame)
        y_scrollbar.pack(side="right", fill="y")

        x_scrollbar = ttk.Scrollbar(tree_frame, orient="horizontal")
        x_scrollbar.pack(side="bottom", fill="x")

        # Création du treeview avec hauteur fixe
        tree = ttk.Treeview(tree_frame, columns=columns, show="headings",
                           yscrollcommand=y_scrollbar.set,
                           xscrollcommand=x_scrollbar.set,
                           height=height)  # Définir la hauteur en nombre de lignes

        # Configuration des scrollbars
        y_scrollbar.config(command=tree.yview)
        x_scrollbar.config(command=tree.xview)

        # Configuration des colonnes avec centrage
        for col in columns:
            tree.heading(col, text=self.column_names[col],
                        command=lambda c=col: self.sort_treeview(tree, c))
            tree.column(col, width=self.column_widths[col], minwidth=50, anchor='center')

        tree.pack(fill="both", expand=True)
        return tree

    def setup_treeview(self):
        # Créer un canvas avec scrollbar pour contenir tous les treeviews
        canvas = tk.Canvas(self.root)
        scrollbar = ttk.Scrollbar(self.root, orient="vertical", command=canvas.yview)

        # Frame principal qui contiendra tous les treeviews
        main_frame = ttk.Frame(canvas)

        # Configurer le canvas
        canvas.configure(yscrollcommand=scrollbar.set)

        # Pack le canvas et la scrollbar
        scrollbar.pack(side="right", fill="y")
        canvas.pack(side="left", fill="both", expand=True)

        # Créer une fenêtre dans le canvas avec le main_frame
        canvas.create_window((0, 0), window=main_frame, anchor="nw", width=canvas.winfo_width())

        # Créer les trois panneaux verticaux avec des poids différents
        societe_frame = ttk.Frame(main_frame)
        societe_frame.pack(side="top", fill="both", expand=True, pady=1)

        associe_frame = ttk.Frame(main_frame)
        associe_frame.pack(side="top", fill="both", expand=True, pady=1)

        contrat_frame = ttk.Frame(main_frame)
        contrat_frame.pack(side="top", fill="both", expand=True, pady=1)

        # Fonction pour mettre à jour la région de défilement
        def _on_frame_configure(event=None):
            canvas.configure(scrollregion=canvas.bbox("all"))

        # Lier la configuration du frame
        main_frame.bind("<Configure>", _on_frame_configure)

        # Lier le redimensionnement de la fenêtre
        def _on_canvas_configure(event):
            canvas.itemconfig(canvas.find_withtag("all")[0], width=event.width)

        canvas.bind("<Configure>", _on_canvas_configure)

        # Configurer le défilement avec la molette de la souris
        def _on_mousewheel(event):
            try:
                canvas.yview_scroll(int(-1*(event.delta/120)), "units")
            except Exception as e:
                print(f"Erreur de défilement: {str(e)}")  # Pour le débogage
                pass

        # Lier l'événement de la molette uniquement au canvas et à ses enfants
        canvas.bind("<MouseWheel>", _on_mousewheel)
        main_frame.bind("<MouseWheel>", _on_mousewheel)
        # Assurer que le défilement fonctionne aussi sur les widgets enfants
        for frame in [societe_frame, associe_frame, contrat_frame]:
            frame.bind("<MouseWheel>", _on_mousewheel)
            for child in frame.winfo_children():
                child.bind("<MouseWheel>", _on_mousewheel)

        # Unbind events when destroying the widget
        def _on_destroy(event):
            try:
                canvas.unbind_all("<MouseWheel>")
            except:
                pass

        canvas.bind("<Destroy>", _on_destroy)

        # Définir les colonnes pour chaque treeview

        # Définir les colonnes pour chaque treeview (en excluant les IDs)
        societe_columns = [
            "DEN_STE", "FORME_JUR", "ICE", "CAPITAL", "PART_SOCIAL",
            "STE_ADRESS", "DATE_ICE", "TRIBUNAL"
        ]

        associe_columns = [
            "DEN_STE",  # Ajout du nom de la société
            "CIVIL", "PRENOM", "NOM", "NATIONALITY", "CIN_NUM", "CIN_VALIDATY",
            "DATE_NAISS", "LIEU_NAISS", "ADRESSE", "PHONE", "EMAIL",
            "PARTS", "IS_GERANT", "QUALITY"
        ]

        contrat_columns = [
            "DEN_STE",  # Ajout du nom de la société
            "PERIOD_DOMCIL", "PRIX_CONTRAT", "PRIX_INTERMEDIARE_CONTRAT",
            "DOM_DATEDEB", "DOM_DATEFIN"
        ]

        # Créer les trois treeviews
        self.societe_tree = self.create_treeview(societe_frame, societe_columns, "Sociétés")
        self.associe_tree = self.create_treeview(associe_frame, associe_columns, "Associés")
        self.contrat_tree = self.create_treeview(contrat_frame, contrat_columns, "Contrats")

        # Appeler populate_treeviews après la création
        self.populate_treeviews()

        # Configuration des colonnes spécifiques à chaque vue
        column_widths = {
            # Colonnes de la société
            "ID_SOCIETE": 100,     # ID technique
            "DEN_STE": 200,        # Plus large pour les noms de société
            "FORME_JUR": 120,      # Largeur standard
            "ICE": 100,            # Plus étroit pour les numéros
            "CAPITAL": 100,        # Plus étroit pour les montants
            "PART_SOCIAL": 100,    # Plus étroit pour les montants
            "STE_ADRESS": 200,     # Plus large pour les adresses
            "DATE_ICE": 100,       # Plus étroit pour les dates
            "TRIBUNAL": 150,       # Largeur standard

            # Colonnes des associés
            "CIVIL": 80,           # Plus étroit
            "PRENOM": 120,         # Largeur standard
            "NOM": 120,            # Largeur standard
            "NATIONALITY": 120,    # Largeur standard
            "CIN_NUM": 100,        # Plus étroit pour les numéros
            "CIN_VALIDATY": 100,   # Plus étroit pour les dates
            "DATE_NAISS": 100,     # Plus étroit pour les dates
            "LIEU_NAISS": 150,     # Largeur standard
            "ADRESSE": 200,        # Plus large pour les adresses
            "PHONE": 120,          # Largeur standard
            "EMAIL": 150,          # Plus large pour les emails
            "PARTS": 80,           # Plus étroit
            "IS_GERANT": 80,       # Plus étroit
            "QUALITY": 120,        # Largeur standard

            # Colonnes du contrat
            "PERIOD_DOMCIL": 100,  # Plus étroit
            "PRIX_CONTRAT": 100,   # Plus étroit pour les montants
            "PRIX_INTERMEDIARE_CONTRAT": 120,  # Largeur standard
            "DOM_DATEDEB": 100,    # Plus étroit pour les dates
            "DOM_DATEFIN": 100,    # Plus étroit pour les dates
        }

        # Noms d'affichage pour toutes les colonnes
        column_names = {
            # Colonnes de la société
            "ID_SOCIETE": "ID Société",
            "DEN_STE": "Société",
            "FORME_JUR": "Forme Juridique",
            "ICE": "ICE",
            "CAPITAL": "Capital",
            "PART_SOCIAL": "Part Social",
            "STE_ADRESS": "Adresse Société",
            "DATE_ICE": "Date ICE",
            "TRIBUNAL": "Tribunal",

            # Colonnes des associés
            "CIVIL": "Civilité",
            "PRENOM": "Prénom",
            "NOM": "Nom",
            "NATIONALITY": "Nationalité",
            "CIN_NUM": "CIN",
            "CIN_VALIDATY": "Validité CIN",
            "DATE_NAISS": "Date Naissance",
            "LIEU_NAISS": "Lieu Naissance",
            "ADRESSE": "Adresse",
            "PHONE": "Téléphone",
            "EMAIL": "Email",
            "PARTS": "Parts",
            "IS_GERANT": "Est Gérant",
            "QUALITY": "Qualité",

            # Colonnes du contrat
            "PERIOD_DOMCIL": "Période",
            "PRIX_CONTRAT": "Prix Contrat",
            "PRIX_INTERMEDIARE_CONTRAT": "Prix Intermédiaire",
            "DOM_DATEDEB": "Date Début",
            "DOM_DATEFIN": "Date Fin"
        }

        # Removed redundant code - treeview configuration is now handled in create_treeview
        self.populate_treeviews()

    def populate_treeviews(self):
        """Remplit les trois treeviews avec les données correspondantes"""
        # Vérifier si le DataFrame est vide
        if self.df.empty:
            return

        # Effacer les données existantes
        self.clear_all_treeviews()

        # Remplir les valeurs NaN avec des chaînes vides
        df_display = self.df.fillna("")

        # Convertir toutes les valeurs en chaînes
        for col in df_display.columns:
            df_display[col] = df_display[col].astype(str).str.strip()

        # Grouper les données par société en utilisant DEN_STE au lieu de ID_SOCIETE
        for societe_name in df_display['DEN_STE'].unique():
            # Données de la société
            societe_data = df_display[df_display['DEN_STE'] == societe_name]
            if not societe_data.empty:
                self.populate_societe_tree(societe_data.iloc[0])
                self.populate_associe_tree(societe_data)
                self.populate_contrat_tree(societe_data)

        # Mettre à jour le statut avec le nombre total de sociétés
        total_societes = len(df_display['DEN_STE'].unique())
        self.status_bar.config(text=f"Total des sociétés : {total_societes}")

    def populate_societe_tree(self, societe_row):
        """Remplit le treeview des sociétés"""
        values = []
        for col in self.societe_tree['columns']:
            values.append(str(societe_row.get(col, "")))

        # Créer un ID unique pour chaque entrée en utilisant DEN_STE
        iid = f"soc_{societe_row['DEN_STE']}"

        # Vérifier si cette société existe déjà dans le treeview
        existing_items = self.societe_tree.get_children()
        exists = False
        for item in existing_items:
            if self.societe_tree.item(item)['values'][0] == societe_row['DEN_STE']:
                exists = True
                break

        # Insérer seulement si la société n'existe pas déjà
        if not exists:
            self.societe_tree.insert("", "end", values=values, iid=iid)

    def populate_associe_tree(self, societe_data):
        """Remplit le treeview des associés"""
        if not societe_data.empty:
            societe_name = societe_data.iloc[0]['DEN_STE']
            # Créer un parent node pour la société avec un ID unique basé sur le nom
            societe_iid = f"soc_assoc_{societe_name}"

            # Vérifier si ce nœud de société existe déjà
            existing_items = self.associe_tree.get_children()
            if societe_iid not in existing_items:
                self.associe_tree.insert("", "end", values=[societe_name] + ["" for _ in range(len(self.associe_tree['columns'])-1)],
                                       iid=societe_iid, tags=('societe',))

            # Ajouter les associés sous le noeud de la société
            for idx, row in societe_data.iterrows():
                values = [societe_name]  # Ajouter le nom de la société comme première colonne
                for col in self.associe_tree['columns'][1:]:  # Skip first column which is DEN_STE
                    values.append(str(row.get(col, "")))
                # Créer un ID unique pour chaque associé
                iid = f"assoc_{societe_name}_{idx}"
                self.associe_tree.insert(societe_iid, "end", values=values, iid=iid)  # Insérer sous le parent

    def populate_contrat_tree(self, societe_data):
        """Remplit le treeview des contrats"""
        if not societe_data.empty:
            societe_name = societe_data.iloc[0]['DEN_STE']
            # Créer un parent node pour la société
            societe_iid = f"soc_cont_{societe_data.iloc[0]['ID_SOCIETE']}"

        # Vérifier si ce nœud de société existe déjà
        existing_items = self.contrat_tree.get_children()
        if societe_iid not in existing_items:
            self.contrat_tree.insert("", "end", values=[societe_name] + ["" for _ in range(len(self.contrat_tree['columns'])-1)],
                                   iid=societe_iid, tags=('societe',))

        # Ajouter les contrats sous le noeud de la société
        for idx, row in societe_data.iterrows():
            values = [societe_name]  # Ajouter le nom de la société comme première colonne
            for col in self.contrat_tree['columns'][1:]:
                values.append(str(row.get(col, "")))
            # Créer un ID unique pour chaque contrat
            iid = f"cont_{societe_name}_{idx}"
            self.contrat_tree.insert(societe_iid, "end", values=values, iid=iid)

        return

    def clear_all_treeviews(self):
       #Efface les données de tous les treeviews"""
       for tree in [self.societe_tree, self.associe_tree, self.contrat_tree]:
          for item in tree.get_children():
            tree.delete(item)

    def sort_treeview(self, tree, col):
        """Trie un treeview spécifique selon une colonne"""
        # Récupérer toutes les lignes
        items = [(tree.set(item, col), item) for item in tree.get_children("")]

        # Trier les lignes
        items.sort()

        # Réorganiser les lignes dans le treeview
        for index, (val, item) in enumerate(items):
            tree.move(item, "", index)

        return

    def search_records(self, event=None):
        search_term = self.search_var.get().lower()
        search_filter = self.filter_var.get()

        # Mettre à jour la barre de statut
        self.status_bar.config(text="Recherche en cours...")
        self.root.update()

        # Effacer les résultats précédents
        self.clear_all_treeviews()

                # Si le terme de recherche est vide, afficher toutes les données
        if not search_term:
            self.populate_treeviews()
            self.status_bar.config(text="Prêt")
            return

        # Définir les colonnes à rechercher selon le filtre
        if search_filter == "Société":
            search_columns = ["DEN_STE", "FORME_JUR", "ICE", "CAPITAL", "STE_ADRESS"]
            trees_to_search = [self.societe_tree]
        elif search_filter == "Associés":
            search_columns = ["CIVIL", "PRENOM", "NOM", "CIN_NUM", "PHONE", "EMAIL"]
            trees_to_search = [self.associe_tree]
        elif search_filter == "Contrat":
            search_columns = ["PERIOD_DOMCIL", "DOM_DATEDEB", "DOM_DATEFIN", "PRIX_CONTRAT"]
            trees_to_search = [self.contrat_tree]
        else:
            # Pour "Tout", rechercher dans toutes les colonnes
            trees_to_search = [self.societe_tree, self.associe_tree, self.contrat_tree]
            search_columns = self.df.columns

        # Rechercher dans les colonnes sélectionnées
        mask = self.df[search_columns].astype(str).apply(
            lambda x: x.str.contains(search_term, case=False)).any(axis=1)
        filtered_df = self.df[mask]

        # Mettre à jour la barre de statut avec le nombre de résultats
        self.status_bar.config(text=f"{len(filtered_df)} résultat(s) trouvé(s)")

        # Afficher les résultats filtrés
        for societe_id in filtered_df['ID_SOCIETE'].unique():
            societe_data = filtered_df[filtered_df['ID_SOCIETE'] == societe_id]
            if not societe_data.empty:
                if self.societe_tree in trees_to_search:
                    self.populate_societe_tree(societe_data.iloc[0])
                if self.associe_tree in trees_to_search:
                    self.populate_associe_tree(societe_data)
                if self.contrat_tree in trees_to_search:
                    self.populate_contrat_tree(societe_data)

    def refresh_data(self):
        self.load_data()
        self.populate_treeviews()
        messagebox.showinfo("Succès", "Données actualisées avec succès!")

    def add_society(self):
        from Main_app import DomiciliationApp
        from utils import WindowManager

        # Trouver la fenêtre principale
        main_window = self.root.winfo_toplevel()

        # Masquer la fenêtre principale
        main_window.withdraw()

        # Créer une nouvelle fenêtre pour l'ajout
        add_window = tk.Toplevel(main_window)
        WindowManager.setup_window(add_window, "Ajouter une nouvelle société")

        # Créer une instance de DomiciliationApp dans la nouvelle fenêtre
        app = DomiciliationApp(add_window)

        # Ajouter un bouton personnalisé pour sauvegarder et fermer
        def save_and_close():
            app.save_to_database()  # Sauvegarder dans la base de données
            self.refresh_data()     # Actualiser le tableau
            add_window.destroy()    # Fermer la fenêtre
            main_window.deiconify()   # Réafficher la fenêtre principale

        def on_closing():
            add_window.destroy()    # Fermer la fenêtre
            main_window.deiconify()   # Réafficher la fenêtre principale

        WidgetFactory.create_button(
            app.buttons_frame,
            text="💾 Sauvegarder et Fermer",
            command=save_and_close,
            style='Secondary.TButton',
            tooltip="Sauvegarder les modifications et fermer la fenêtre"
        ).grid(row=0, column=6, padx=5)

        # Configurer le gestionnaire de fermeture de fenêtre
        add_window.protocol("WM_DELETE_WINDOW", on_closing)

    def edit_society(self):
        from Main_app import DomiciliationApp
        from utils import WindowManager

        # Obtenir l'élément sélectionné
        selected_item = self.societe_tree.selection()
        if not selected_item:
            messagebox.showwarning("Attention", "Veuillez sélectionner une société à modifier.")
            return

        # Obtenir les données de la société sélectionnée
        item = self.societe_tree.item(selected_item[0])
        den_ste = item['values'][0]  # Nom de la société

        # Trouver la fenêtre principale
        main_window = self.root.winfo_toplevel()

        # Masquer la fenêtre principale
        main_window.withdraw()

        # Ouvrir une nouvelle fenêtre pour la modification
        edit_window = tk.Toplevel(main_window)
        WindowManager.setup_window(edit_window, f"Modifier {den_ste}")

        # Créer une instance de DomiciliationApp dans la nouvelle fenêtre
        app = DomiciliationApp(edit_window)

        # Récupérer les données existantes
        societe_data = self.df[self.df['DEN_STE'] == den_ste].iloc[0]

        # Remplir tous les champs disponibles avec les données existantes
        field_mappings = {
            'DEN_STE': 'den_ste_var',
            'FORME_JUR': 'forme_jur_var',
            'ICE': 'ice_var',
            'CAPITAL': 'capital_var',
            'PART_SOCIAL': 'part_social_var',
            'NATIONALITY': 'nationality_var',
            'STE_ADRESS': 'ste_adress_var',
            'DATE_ICE': 'date_ice_var',
            'CIVIL': 'civil_var',
            'PRENOM': 'prenom_var',
            'NOM': 'nom_var',
            'DATE_NAISS': 'date_naiss_var',
            'LIEU_NAISS': 'lieu_naiss_var',
            'GERANT_ADRESS': 'gerant_adress_var',
            'CIN_NUM': 'cin_num_var',
            'CIN_VALIDATY': 'cin_validaty_var',
            'GERANT_QUALITY': 'gerant_quality_var',
            'GERANT_PHONE': 'gerant_phone_var',
            'GERANT_EMAIL': 'gerant_email_var',
            'TRIBUNAL': 'tribunal_var',
            'PERIOD_DOMCIL': 'period_var',
            'DOM_DATEDEB': 'date_debut_var',
            'DOM_DATEFIN': 'date_fin_var',
            'DATE_CONTRAT': 'date_contrat_var',
            'PRIX_CONTRAT': 'prix_contrat_var',
            'PRIX_INTERMEDIARE_CONTRAT': 'prix_intermediaire_var',
            'INTERMEDIARE': 'intermediaire_var'
        }

        # Mise à jour de tous les champs disponibles
        for excel_col, var_name in field_mappings.items():
            if hasattr(app, var_name) and excel_col in societe_data:
                try:
                    getattr(app, var_name).set(societe_data[excel_col])
                except Exception as e:
                    print(f"Erreur lors de la mise à jour du champ {excel_col}: {str(e)}")

        # Ajouter les boutons personnalisés pour la sauvegarde et la fermeture
        def save_and_close():
            try:
                # Supprimer l'ancienne entrée de la société
                self.df = self.df[self.df['DEN_STE'].str.strip() != str(den_ste).strip()]

                # Collecter et sauvegarder les nouvelles valeurs
                app.collect_values()  # S'assurer que toutes les valeurs sont collectées

                # Créer une nouvelle ligne avec les valeurs mises à jour
                new_row = {}
                for excel_col, var_name in field_mappings.items():
                    if hasattr(app, var_name):
                        new_row[excel_col] = getattr(app, var_name).get()

                # Ajouter la nouvelle ligne au DataFrame
                self.df = pd.concat([self.df, pd.DataFrame([new_row])], ignore_index=True)

                # Sauvegarder dans la base de données
                self.save_data()

                # Actualiser l'affichage
                self.refresh_data()

                messagebox.showinfo("Succès", "Modifications enregistrées avec succès!")
                edit_window.destroy()
                main_window.deiconify()

            except Exception as e:
                messagebox.showerror("Erreur", f"Erreur lors de la sauvegarde : {str(e)}")

        def just_close():
            if messagebox.askyesno("Confirmation", "Êtes-vous sûr de vouloir fermer sans sauvegarder les modifications ?"):
                edit_window.destroy()
                main_window.deiconify()

        # Bouton de sauvegarde
        WidgetFactory.create_button(
            app.buttons_frame,
            text="💾 Sauvegarder et Fermer",
            command=save_and_close,
            style='Secondary.TButton',
            tooltip="Sauvegarder les modifications et fermer la fenêtre"
        ).grid(row=0, column=6, padx=5)

        # Bouton pour fermer sans sauvegarder
        WidgetFactory.create_button(
            app.buttons_frame,
            text="❌ Fermer sans sauvegarder",
            command=just_close,
            style='Secondary.TButton',
            tooltip="Fermer sans sauvegarder les modifications"
        ).grid(row=0, column=7, padx=5)

        # Configurer le gestionnaire de fermeture de fenêtre
        edit_window.protocol("WM_DELETE_WINDOW", just_close)

    def delete_society(self):
        selected_item = self.societe_tree.selection()
        if not selected_item:
            messagebox.showwarning("Attention", "Veuillez sélectionner une société à supprimer.")
            return

        if messagebox.askyesno("Confirmation", "Êtes-vous sûr de vouloir supprimer cette société?"):
            # Récupérer l'ID de la société à partir de l'iid sélectionné
            societe_iid = selected_item[0]
            societe_id = societe_iid.split('_')[1]  # Extrait l'ID de la société du format "soc_ID"

            # Supprimer toutes les lignes correspondant à cette société
            self.df = self.df[self.df['ID_SOCIETE'] != societe_id]

            # Réinitialiser les index du DataFrame
            self.df = self.df.reset_index(drop=True)

            # Sauvegarder dans Excel
            try:
                excel_file_path = Path(__file__).parent / 'databases' / 'DataBase_domiciliation.xlsx'

                if len(self.df) == 0:  # Si le DataFrame est vide
                    # Créer un nouveau fichier vide
                    self.save_data(excel_file_path)
                else:
                    # Sauvegarder les données
                    with pd.ExcelWriter(excel_file_path, engine='openpyxl') as writer:
                        self.df.to_excel(writer, sheet_name="DataBaseDom", index=False)

                messagebox.showinfo("Succès", "Société supprimée avec succès!")
                self.refresh_data()
            except Exception as e:
                messagebox.showerror("Erreur", f"Erreur lors de la suppression: {str(e)}")

if __name__ == "__main__":
    root = tk.Tk()
    app = DomiciliationDashboard(root)
    root.mainloop()
