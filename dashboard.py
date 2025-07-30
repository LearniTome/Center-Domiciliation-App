import tkinter as tk
from tkinter import ttk, messagebox
import pandas as pd
import os
from pathlib import Path
import openpyxl
from Main_app import excel_headers, DomiciliationApp
from utils import ThemeManager, WidgetFactory, PathManager

class DomiciliationDashboard:
    def __init__(self, root):
        self.root = root
        if isinstance(root, tk.Tk):
            self.root.title("Dashboard Domiciliation")
            self.root.geometry("1200x800")

            # Configuration du thème
            self.theme_manager = ThemeManager(self.root)
            self.style = self.theme_manager.style

            # Configurer les styles personnalisés
            self.style.configure("Treeview",
                               rowheight=25,
                               fieldbackground=self.theme_manager.colors['bg'])
            self.style.configure("Treeview.Heading",
                               background="#4a90e2",
                               foreground="white",
                               relief="flat")
            self.style.map("Treeview.Heading",
                          background=[("active", "#2171cd")])

            # Configurer le style des boutons
            self.style.configure("Action.TButton",
                               padding=6,
                               relief="flat",
                               background="#4a90e2",
                               foreground="white")
            self.style.map("Action.TButton",
                          background=[("active", "#2171cd")])

        # Création de la barre de statut
        self.status_bar = ttk.Label(self.root, text="Prêt", relief=tk.SUNKEN, anchor=tk.W)
        self.status_bar.pack(side=tk.BOTTOM, fill=tk.X)

        # Charger les données
        self.load_data()
        # Créer l'interface
        self.setup_gui()



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
                    print(f"Ajout de la colonne manquante: {col}")
                    self.df[col] = ""

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
        filter_menu = ttk.OptionMenu(search_container, self.filter_var, "Tout",
                                   "Société", "Gérant", "Contrat")
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

    def setup_treeview(self):
        # Frame pour le treeview avec scrollbar
        tree_frame = ttk.Frame(self.root)
        tree_frame.pack(fill="both", expand=True, padx=10, pady=5)

        # Scrollbars
        y_scrollbar = ttk.Scrollbar(tree_frame)
        y_scrollbar.pack(side="right", fill="y")

        x_scrollbar = ttk.Scrollbar(tree_frame, orient="horizontal")
        x_scrollbar.pack(side="bottom", fill="x")

        # Utiliser toutes les colonnes de excel_headers
        columns = excel_headers

        self.tree = ttk.Treeview(tree_frame, columns=columns, show="headings",
                                yscrollcommand=y_scrollbar.set,
                                xscrollcommand=x_scrollbar.set)

        # Configuration des scrollbars
        y_scrollbar.config(command=self.tree.yview)
        x_scrollbar.config(command=self.tree.xview)

        # Configuration des largeurs de colonnes avec des valeurs par défaut
        column_widths = {
            "DEN_STE": 200,        # Plus large pour les noms de société
            "FORME_JUR": 120,      # Largeur standard
            "ICE": 100,            # Plus étroit pour les numéros
            "CAPITAL": 100,        # Plus étroit pour les montants
            "PART_SOCIAL": 100,    # Plus étroit pour les montants
            "NATIONALITY": 120,    # Largeur standard
            "STE_ADRESS": 200,     # Plus large pour les adresses
            "DATE_ICE": 100,       # Plus étroit pour les dates
            "CIVIL": 80,           # Plus étroit
            "PRENOM": 120,         # Largeur standard
            "NOM": 120,            # Largeur standard
            "DATE_NAISS": 100,     # Plus étroit pour les dates
            "LIEU_NAISS": 150,     # Largeur standard
            "GERANT_ADRESS": 200,  # Plus large pour les adresses
            "CIN_NUM": 100,        # Plus étroit pour les numéros
            "CIN_VALIDATY": 100,   # Plus étroit pour les dates
            "GERANT_QUALITY": 120, # Largeur standard
            "GERANT_PHONE": 120,   # Largeur standard
            "GERANT_EMAIL": 150,   # Plus large pour les emails
            "TRIBUNAL": 150,       # Largeur standard
            "PERIOD_DOMCIL": 100,  # Plus étroit
            "DOM_DATEDEB": 100,    # Plus étroit pour les dates
            "DOM_DATEFIN": 100,    # Plus étroit pour les dates
            "DATE_CONTRAT": 100,   # Plus étroit pour les dates
            "PRIX_CONTRAT": 100,   # Plus étroit pour les montants
            "PRIX_INTERMEDIARE_CONTRAT": 120,  # Largeur standard
            "INTERMEDIARE": 150    # Largeur standard
        }

        # Noms d'affichage pour toutes les colonnes
        column_names = {
            "DEN_STE": "Société",
            "FORME_JUR": "Forme Juridique",
            "ICE": "ICE",
            "CAPITAL": "Capital",
            "PART_SOCIAL": "Part Social",
            "NATIONALITY": "Nationalité",
            "STE_ADRESS": "Adresse Société",
            "DATE_ICE": "Date ICE",
            "CIVIL": "Civilité",
            "PRENOM": "Prénom",
            "NOM": "Nom",
            "DATE_NAISS": "Date Naissance",
            "LIEU_NAISS": "Lieu Naissance",
            "GERANT_ADRESS": "Adresse Gérant",
            "CIN_NUM": "CIN",
            "CIN_VALIDATY": "Validité CIN",
            "GERANT_QUALITY": "Qualité Gérant",
            "GERANT_PHONE": "Téléphone",
            "GERANT_EMAIL": "Email",
            "TRIBUNAL": "Tribunal",
            "PERIOD_DOMCIL": "Période",
            "DOM_DATEDEB": "Date Début",
            "DOM_DATEFIN": "Date Fin",
            "DATE_CONTRAT": "Date Contrat",
            "PRIX_CONTRAT": "Prix Contrat",
            "PRIX_INTERMEDIARE_CONTRAT": "Prix Intermédiaire",
            "INTERMEDIARE": "Intermédiaire"
        }

        for col in columns:
            self.tree.heading(col, text=column_names[col],
                            command=lambda c=col: self.sort_treeview(c))
            self.tree.column(col, width=column_widths[col], minwidth=50)

        self.tree.pack(fill="both", expand=True)
        self.populate_treeview()

    def populate_treeview(self):
        # Effacer les données existantes
        for item in self.tree.get_children():
            self.tree.delete(item)

        # Vérifier si le DataFrame est vide
        if self.df.empty:
            return

        # Remplir les valeurs NaN avec des chaînes vides
        df_display = self.df.fillna("")

        # Convertir toutes les valeurs en chaînes
        for col in df_display.columns:
            df_display[col] = df_display[col].astype(str).str.strip()

        # S'assurer que nous avons toutes les colonnes dans le bon ordre
        for col in excel_headers:
            if col not in df_display.columns:
                df_display[col] = ""
        df_display = df_display[excel_headers]

        # Ajouter chaque ligne au Treeview avec toutes les colonnes
        for index, row in df_display.iterrows():
            try:
                values = []
                for col in excel_headers:  # Utiliser excel_headers au lieu de self.tree["columns"]
                    values.append(str(row[col]))
                self.tree.insert("", "end", values=values)
            except Exception as e:
                print(f"Erreur lors de l'ajout de la ligne {index}: {str(e)}")
                print(f"Valeurs : {values}")
                print(f"Colonnes disponibles : {row.index.tolist()}")

    def sort_treeview(self, col):
        # Trier le DataFrame
        self.df = self.df.sort_values(by=[col])
        self.populate_treeview()

    def search_records(self, event=None):
        search_term = self.search_var.get().lower()
        search_filter = self.filter_var.get()

        # Mettre à jour la barre de statut
        self.status_bar.config(text="Recherche en cours...")
        self.root.update()

        # Effacer les résultats précédents
        for item in self.tree.get_children():
            self.tree.delete(item)

        # Si le terme de recherche est vide, afficher toutes les données
        if not search_term:
            self.populate_treeview()
            self.status_bar.config(text="Prêt")
            return

        # Définir les colonnes à rechercher selon le filtre
        if search_filter == "Société":
            columns = ["DEN_STE", "FORME_JUR", "ICE", "CAPITAL", "STE_ADRESS"]
        elif search_filter == "Gérant":
            columns = ["CIVIL", "PRENOM", "NOM", "CIN_NUM", "GERANT_QUALITY", "GERANT_PHONE", "GERANT_EMAIL"]
        elif search_filter == "Contrat":
            columns = ["PERIOD_DOMCIL", "DOM_DATEDEB", "DOM_DATEFIN", "DATE_CONTRAT", "PRIX_CONTRAT"]
        else:
            columns = self.df.columns

        # Rechercher dans les colonnes sélectionnées
        mask = self.df[columns].astype(str).apply(
            lambda x: x.str.contains(search_term, case=False)).any(axis=1)
        filtered_df = self.df[mask]

        # Mettre à jour la barre de statut avec le nombre de résultats
        self.status_bar.config(text=f"{len(filtered_df)} résultat(s) trouvé(s)")

        # Afficher les résultats filtrés
        for index, row in filtered_df.iterrows():
            try:
                values = []
                for col in self.tree["columns"]:
                    val = row.get(col, "")
                    if pd.isna(val):
                        val = ""
                    values.append(str(val))
                self.tree.insert("", "end", values=values)
            except Exception as e:
                print(f"Erreur lors de l'ajout de la ligne {index}: {str(e)}")

    def refresh_data(self):
        self.load_data()
        self.populate_treeview()
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
        selected_item = self.tree.selection()
        if not selected_item:
            messagebox.showwarning("Attention", "Veuillez sélectionner une société à modifier.")
            return

        # Obtenir les données de la société sélectionnée
        item = self.tree.item(selected_item[0])
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
        selected_item = self.tree.selection()
        if not selected_item:
            messagebox.showwarning("Attention", "Veuillez sélectionner une société à supprimer.")
            return

        if messagebox.askyesno("Confirmation", "Êtes-vous sûr de vouloir supprimer cette société?"):
            # Récupérer l'index de l'élément dans le Treeview
            tree_index = self.tree.index(selected_item[0])

            # Supprimer la ligne correspondante dans le DataFrame
            self.df = self.df.drop(self.df.index[tree_index])

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
