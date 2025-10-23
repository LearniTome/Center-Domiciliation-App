import tkinter as tk
from tkinter import ttk, messagebox
import pandas as pd
from pathlib import Path
import logging
from ..utils.constants import (
    societe_headers, associe_headers, contrat_headers,
    DenSte, Civility, Formjur, Capital, PartsSocial
)
from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler

# Configuration du logging
logger = logging.getLogger(__name__)

class DashboardView(tk.Toplevel):
    def __init__(self, parent):
        super().__init__(parent)
        self.parent = parent
        self.title("Tableau de Bord - Centre de Domiciliation")
        self.geometry("1200x700")
        self.minsize(800, 600)

        # Make window modal
        self.transient(parent)
        self.grab_set()

        # Nettoyage lors de la destruction
        self.bind("<Destroy>", self._cleanup)

        # Configuration du thème
        self.theme_manager = ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Définition des en-têtes pour le tableau de bord
        self.setup_headers()

        # Configuration initiale
        self.setup_styles()
        self.setup_layout()

        # Chargement des données
        self.load_data()

    def setup_headers(self):
        """Configure les en-têtes pour l'affichage"""
        # En-têtes combinées pour la vue dashboard
        self.excel_headers = (
            societe_headers +
            [h for h in associe_headers if h not in ['ID_ASSOCIE', 'ID_SOCIETE']] +
            [h for h in contrat_headers if h not in ['ID_CONTRAT', 'ID_SOCIETE']]
        )

        # En-têtes pour l'affichage (sans les IDs techniques)
        self.display_headers = [h for h in self.excel_headers
                              if not h.startswith('ID_')]

    def setup_styles(self):
        """Configure les styles pour le dashboard"""
        # Style des tableaux
        self.style.configure(
            'Dashboard.Treeview',
            rowheight=25,
            fieldbackground=self.theme_manager.colors['bg']
        )
        self.style.configure(
            'Dashboard.Treeview.Heading',
            background="#4a90e2",
            foreground="white",
            relief="flat",
            font=('Segoe UI', 9, 'bold')
        )
        self.style.map(
            'Dashboard.Treeview.Heading',
            background=[("active", "#2171cd")]
        )

        # Style des contrôles
        self.style.configure(
            'Dashboard.TButton',
            padding=6,
            relief="flat",
            background="#4a90e2",
            foreground="white"
        )

        # Style des filtres
        self.style.configure(
            'Dashboard.TFrame',
            padding=10
        )

    def setup_layout(self):
        """Configure la disposition des éléments"""
        # Barre d'outils
        self.create_toolbar()

        # Zone principale avec les tableaux
        self.create_main_view()

        # Barre de statut
        self.create_status_bar()

    def create_toolbar(self):
        """Crée la barre d'outils avec recherche et filtres"""
        toolbar = ttk.Frame(self, style='Dashboard.TFrame')
        toolbar.pack(fill="x", padx=10, pady=5)

        # Zone de recherche
        search_frame = ttk.LabelFrame(toolbar, text="Recherche", padding=10)
        search_frame.pack(side="left", fill="x", expand=True)

        # Champ de recherche avec icône
        search_container = ttk.Frame(search_frame)
        search_container.pack(fill="x")

        ttk.Label(search_container, text="🔍").pack(side="left")
        self.search_var = tk.StringVar()
        self.search_entry = ttk.Entry(search_container,
                                    textvariable=self.search_var,
                                    width=40)
        self.search_entry.pack(side="left", padx=5, fill="x", expand=True)
        self.search_entry.bind('<KeyRelease>', self.search_records)

        # Menu de filtre
        self.filter_var = tk.StringVar(value="Tout")
        filter_menu = ttk.OptionMenu(
            search_container,
            self.filter_var,
            "Tout",
            "Société", "Associés", "Contrat",
            command=self.search_records
        )
        filter_menu.pack(side="left", padx=5)

        # Boutons d'actions
        button_frame = ttk.Frame(toolbar)
        button_frame.pack(side="right")

        self.create_action_buttons(button_frame)

    def create_action_buttons(self, parent):
        """Crée les boutons d'action"""
        buttons = [
            ("➕ Ajouter", self.add_record, "Ajouter un nouvel enregistrement"),
            ("✏️ Modifier", self.edit_record, "Modifier l'enregistrement sélectionné"),
            ("🗑️ Supprimer", self.delete_record, "Supprimer l'enregistrement sélectionné"),
            ("🔄 Actualiser", self.refresh_data, "Actualiser les données")
        ]

        for text, command, tooltip in buttons:
            WidgetFactory.create_button(
                parent,
                text=text,
                command=command,
                style='Dashboard.TButton',
                tooltip=tooltip
            ).pack(side="left", padx=5)

    def create_main_view(self):
        """Crée la vue principale avec les tableaux"""
        # Frame principal avec scrollbar
        main_frame = ttk.Frame(self)
        main_frame.pack(fill="both", expand=True, padx=10, pady=5)

        # Canvas pour le scrolling
        canvas = tk.Canvas(main_frame)
        scrollbar = ttk.Scrollbar(main_frame, orient="vertical",
                                command=canvas.yview)
        scrollable_frame = ttk.Frame(canvas)

        scrollable_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(scrollregion=canvas.bbox("all"))
        )

        canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        canvas.configure(yscrollcommand=scrollbar.set)

        # Gestion du défilement avec la molette
        def _on_mousewheel(event):
            try:
                canvas.yview_scroll(int(-1*(event.delta/120)), "units")
            except Exception as e:
                pass

        # Lier l'événement de la molette
        canvas.bind_all("<MouseWheel>", _on_mousewheel)

        # Gestion du redimensionnement
        def _on_canvas_configure(event):
            canvas.itemconfig(
                canvas.find_withtag("all")[0],
                width=event.width
            )
        canvas.bind("<Configure>", _on_canvas_configure)

        # Tableaux
        self.create_data_tables(scrollable_frame)

        # Placement des éléments
        scrollbar.pack(side="right", fill="y")
        canvas.pack(side="left", fill="both", expand=True)

    def create_data_tables(self, parent):
        """Crée les tableaux de données"""
        # Table des sociétés
        self.societe_tree = self.create_table_section(
            parent, "Sociétés", self.get_societe_columns()
        )

        # Table des associés
        self.associe_tree = self.create_table_section(
            parent, "Associés", self.get_associe_columns()
        )

        # Table des contrats
        self.contrat_tree = self.create_table_section(
            parent, "Contrats", self.get_contrat_columns()
        )

    def create_table_section(self, parent, title, columns):
        """Crée une section de tableau avec titre"""
        frame = ttk.LabelFrame(parent, text=title)
        frame.pack(fill="x", padx=5, pady=5)

        # Création du tableau
        tree = ttk.Treeview(
            frame,
            columns=columns,
            show="headings",
            style='Dashboard.Treeview'
        )

        # Configuration des colonnes
        for col in columns:
            tree.heading(col, text=col, command=lambda c=col: self.sort_table(tree, c))
            width = 150 if "ADRESS" in col else 100
            tree.column(col, width=width, minwidth=50)

        # Scrollbars
        y_scroll = ttk.Scrollbar(frame, orient="vertical", command=tree.yview)
        x_scroll = ttk.Scrollbar(frame, orient="horizontal", command=tree.xview)
        tree.configure(yscrollcommand=y_scroll.set, xscrollcommand=x_scroll.set)

        # Placement des éléments
        y_scroll.pack(side="right", fill="y")
        x_scroll.pack(side="bottom", fill="x")
        tree.pack(fill="both", expand=True)

        return tree

    def create_status_bar(self):
        """Crée la barre de statut"""
        self.status_bar = ttk.Label(
            self,
            text="Prêt",
            relief=tk.SUNKEN,
            anchor=tk.W
        )
        self.status_bar.pack(side=tk.BOTTOM, fill=tk.X)

    def get_societe_columns(self):
        """Retourne les colonnes pour le tableau des sociétés"""
        return [col for col in societe_headers if not col.startswith('ID_')]

    def get_associe_columns(self):
        """Retourne les colonnes pour le tableau des associés"""
        cols = ['DEN_STE']  # Ajout du nom de la société
        cols.extend([col for col in associe_headers[2:]])
        return cols

    def get_contrat_columns(self):
        """Retourne les colonnes pour le tableau des contrats"""
        cols = ['DEN_STE']  # Ajout du nom de la société
        cols.extend([col for col in contrat_headers[2:]])
        return cols

    def load_data(self):
        """Charge les données depuis la base de données"""
        try:
            # Assurer que les répertoires existent
            PathManager.ensure_directories()

            # Chemin du fichier de base de données
            excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

            if not excel_path.exists():
                self.df = pd.DataFrame(columns=self.excel_headers)
                self.save_data(excel_path)
                logger.info("Nouvelle base de données créée")
                return

            # Charger les données
            self.df = pd.read_excel(excel_path, sheet_name="DataBaseDom")
            logger.info(f"Données chargées: {len(self.df)} enregistrements")

            # Mettre à jour l'affichage
            self.refresh_display()

        except Exception as e:
            ErrorHandler.handle_error(
                e,
                "Erreur lors du chargement des données",
                callback=self.create_empty_database
            )

    def save_data(self, path=None):
        """Sauvegarde les données dans le fichier Excel"""
        if path is None:
            path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

        try:
            self.df.to_excel(path, sheet_name="DataBaseDom", index=False)
            logger.info("Données sauvegardées avec succès")
        except Exception as e:
            ErrorHandler.handle_error(e, "Erreur lors de la sauvegarde des données")

    def refresh_display(self):
        """Actualise l'affichage des données"""
        # Effacer les tableaux
        for tree in [self.societe_tree, self.associe_tree, self.contrat_tree]:
            for item in tree.get_children():
                tree.delete(item)

        if self.df.empty:
            self.status_bar.config(text="Aucune donnée à afficher")
            return

        # Remplir les tableaux
        self.populate_societe_tree()
        self.populate_associe_tree()
        self.populate_contrat_tree()

        # Mettre à jour le statut
        self.status_bar.config(
            text=f"Total: {len(self.df)} enregistrements"
        )

    def populate_societe_tree(self):
        """Remplit le tableau des sociétés"""
        for _, row in self.df.drop_duplicates(subset=['DEN_STE']).iterrows():
            values = [str(row.get(col, "")) for col in self.get_societe_columns()]
            self.societe_tree.insert("", "end", values=values)

    def populate_associe_tree(self):
        """Remplit le tableau des associés"""
        for den_ste, group in self.df.groupby('DEN_STE'):
            # Créer le nœud parent pour la société
            parent = self.associe_tree.insert(
                "", "end",
                values=[den_ste] + ["" for _ in range(len(self.get_associe_columns())-1)],
                tags=('society',)
            )

            # Ajouter les associés
            for _, row in group.iterrows():
                values = [den_ste]
                values.extend(str(row.get(col, ""))
                            for col in self.get_associe_columns()[1:])
                self.associe_tree.insert(parent, "end", values=values)

    def populate_contrat_tree(self):
        """Remplit le tableau des contrats"""
        for den_ste, group in self.df.groupby('DEN_STE'):
            # Créer le nœud parent pour la société
            parent = self.contrat_tree.insert(
                "", "end",
                values=[den_ste] + ["" for _ in range(len(self.get_contrat_columns())-1)],
                tags=('society',)
            )

            # Ajouter les contrats
            for _, row in group.iterrows():
                values = [den_ste]
                values.extend(str(row.get(col, ""))
                            for col in self.get_contrat_columns()[1:])
                self.contrat_tree.insert(parent, "end", values=values)

    def sort_table(self, tree, col):
        """Trie un tableau selon une colonne"""
        # Récupérer les données
        data = [
            (tree.set(item, col), item)
            for item in tree.get_children('')
        ]

        # Trier
        data.sort()

        # Réorganiser
        for idx, (_, item) in enumerate(data):
            tree.move(item, '', idx)

    def search_records(self, event=None):
        """Recherche dans les enregistrements"""
        search_term = self.search_var.get().lower()
        search_filter = self.filter_var.get()

        # Mettre à jour le statut
        self.status_bar.config(text="Recherche en cours...")
        self.update()

        # Si vide, afficher tout
        if not search_term:
            self.refresh_display()
            return

        # Filtrer les données
        if search_filter == "Société":
            cols = self.get_societe_columns()
        elif search_filter == "Associés":
            cols = self.get_associe_columns()
        elif search_filter == "Contrat":
            cols = self.get_contrat_columns()
        else:
            cols = self.excel_headers

        # Appliquer le filtre
        mask = self.df[cols].astype(str).apply(
            lambda x: x.str.contains(search_term, case=False)
        ).any(axis=1)

        filtered_df = self.df[mask]

        # Sauvegarder temporairement
        original_df = self.df
        self.df = filtered_df

        # Actualiser l'affichage
        self.refresh_display()

        # Restaurer
        self.df = original_df

    def refresh_data(self):
        """Actualise les données"""
        self.load_data()
        self.refresh_display()
        messagebox.showinfo("Succès", "Données actualisées")

    def add_record(self):
        """Ajoute un nouvel enregistrement"""
        from .societe_form import SocieteForm
        from .associe_form import AssocieForm
        from .contrat_form import ContratForm

        # Créer une nouvelle fenêtre
        dialog = tk.Toplevel(self)
        dialog.title("Ajouter un enregistrement")
        dialog.geometry("800x600")

        # Créer un notebook pour les onglets
        notebook = ttk.Notebook(dialog)
        notebook.pack(fill="both", expand=True, padx=10, pady=5)

        # Créer les onglets avec les formulaires
        societe_frame = ttk.Frame(notebook)
        associe_frame = ttk.Frame(notebook)
        contrat_frame = ttk.Frame(notebook)

        notebook.add(societe_frame, text="📋 Société")
        notebook.add(associe_frame, text="👥 Associés")
        notebook.add(contrat_frame, text="📄 Contrat")

        # Créer les formulaires
        societe_form = SocieteForm(societe_frame, values_dict={})
        societe_form.pack(fill="both", expand=True)

        associe_form = AssocieForm(associe_frame, values_dict={})
        associe_form.pack(fill="both", expand=True)

        contrat_form = ContratForm(contrat_frame, values_dict={})
        contrat_form.pack(fill="both", expand=True)

        # Boutons de contrôle
        btn_frame = ttk.Frame(dialog)
        btn_frame.pack(fill="x", padx=10, pady=5)

        def save_and_close():
            try:
                # Collecter les données de tous les formulaires
                societe_vals = societe_form.get_values() or {}
                associes_vals = associe_form.get_values() or []
                contrat_vals = contrat_form.get_values() or {}

                # Build a single row where each section is stored under its key.
                # This avoids calling dict.update() with a list (which would iterate keys).
                all_values = {
                    'societe': societe_vals,
                    'associes': associes_vals,
                    'contrat': contrat_vals
                }

                # Ajouter à la base de données: create a one-row DataFrame and concat
                new_row_df = pd.DataFrame([all_values])
                self.df = pd.concat([self.df, new_row_df], ignore_index=True)
                self.save_data()
                self.refresh_display()

                messagebox.showinfo("Succès", "Enregistrement ajouté avec succès!")
                dialog.destroy()
            except Exception as e:
                ErrorHandler.handle_error(e, "Erreur lors de l'ajout de l'enregistrement")

        def cancel():
            if messagebox.askyesno("Confirmation", "Voulez-vous vraiment annuler ?"):
                dialog.destroy()

        # Boutons
        WidgetFactory.create_button(
            btn_frame,
            text="💾 Enregistrer",
            command=save_and_close,
            style="Dashboard.TButton",
            tooltip="Enregistrer et fermer"
        ).pack(side="right", padx=5)

        WidgetFactory.create_button(
            btn_frame,
            text="❌ Annuler",
            command=cancel,
            style="Dashboard.TButton",
            tooltip="Annuler et fermer"
        ).pack(side="right", padx=5)

    def edit_record(self):
        """Modifie l'enregistrement sélectionné"""
        # Vérifier qu'une société est sélectionnée
        selected = self.societe_tree.selection()
        if not selected:
            messagebox.showwarning(
                "Sélection requise",
                "Veuillez sélectionner une société à modifier."
            )
            return

        # Obtenir les données de la société sélectionnée
        item = self.societe_tree.item(selected[0])
        den_ste = item['values'][0]

        # Récupérer toutes les données de cette société
        societe_data = self.df[self.df['DEN_STE'] == den_ste].iloc[0].to_dict()

        # Créer le même type de dialogue que pour l'ajout
        self.add_record()  # Réutiliser la même structure
        # TODO: Ajouter le code pour pré-remplir les formulaires

    def delete_record(self):
        """Supprime l'enregistrement sélectionné"""
        selected = self.societe_tree.selection()
        if not selected:
            messagebox.showwarning(
                "Sélection requise",
                "Veuillez sélectionner une société à supprimer."
            )
            return

        # Obtenir les données de la société sélectionnée
        item = self.societe_tree.item(selected[0])
        den_ste = item['values'][0]

        if messagebox.askyesno(
            "Confirmation",
            f"Voulez-vous vraiment supprimer la société {den_ste} ?"
        ):
            # Supprimer toutes les entrées liées à cette société
            self.df = self.df[self.df['DEN_STE'] != den_ste]
            self.save_data()
            self.refresh_display()
            messagebox.showinfo("Succès", "Société supprimée avec succès!")

    def create_empty_database(self):
        """Crée une base de données vide"""
        self.df = pd.DataFrame(columns=self.excel_headers)
        try:
            self.save_data()
            logger.info("Base de données vide créée")
            messagebox.showinfo(
                "Information",
                "Une nouvelle base de données vide a été créée"
            )
        except Exception as e:
            ErrorHandler.handle_error(
                e,
                "Erreur lors de la création de la base de données vide"
            )

    def _cleanup(self, event=None):
        """Nettoie les événements lors de la destruction du widget"""
        try:
            self.unbind_all("<MouseWheel>")
            self.unbind("<Destroy>")
        except Exception:
            pass  # Ignorer les erreurs de nettoyage
