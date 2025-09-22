import tkinter as tk
from tkinter import ttk, messagebox
from src.forms.main_form import MainForm
from src.utils import WindowManager, ThemeManager, PathManager, ErrorHandler
import pandas as pd
import logging

# Configuration du logging
logging.basicConfig(
    filename='app.log',
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class MainApp(tk.Tk):
    def __init__(self):
        super().__init__()

        # Configuration de la fenêtre principale
        WindowManager.setup_window(self, "Genérateurs Docs Juridiques")

        # Initialisation du thème
        self.theme_manager = ThemeManager(self)
        self.style = self.theme_manager.style

        # Dictionnaire pour stocker toutes les valeurs
        self.values = {}

        # Création de l'interface
        self.setup_gui()

    def setup_gui(self):
        """Configure l'interface utilisateur principale"""
        # Create main container
        self.main_container = ttk.Frame(self)
        self.main_container.pack(fill='both', expand=True)

        # Create the main form
        self.main_form = MainForm(self.main_container, self.values)
        self.main_form.pack(fill='both', expand=True, padx=10, pady=10)

        # Create control buttons
        self.setup_buttons()

    def setup_buttons(self):
        """Configure les boutons de contrôle"""
        buttons_frame = ttk.Frame(self)
        buttons_frame.pack(pady=15, side=tk.BOTTOM, fill=tk.X, padx=20)

        # Espace flexible au milieu
        buttons_frame.grid_columnconfigure(2, weight=1)

        # Boutons de génération (gauche)
        ttk.Button(buttons_frame,
                  text="📄 Documents Word",
                  command=self.generate_docs).grid(row=0, column=0, padx=5)

        ttk.Button(buttons_frame,
                  text="📑 Word et PDF",
                  command=self.generate_pdf).grid(row=0, column=1, padx=5)

        # Boutons de contrôle (droite)
        ttk.Button(buttons_frame,
                  text="🆕 Nouvelle",
                  command=self.clear_form).grid(row=0, column=3, padx=5)

        ttk.Button(buttons_frame,
                  text="💾 Sauvegarder",
                  command=self.save_to_db).grid(row=0, column=4, padx=5)

        ttk.Button(buttons_frame,
                  text="❌ Quitter",
                  command=self.quit).grid(row=0, column=5, padx=5)

    def collect_values(self):
        """Collecte toutes les valeurs des formulaires"""
        # Get values from the main form
        self.values = self.main_form.get_values()

    def generate_docs(self):
        """Génère les documents Word"""
        try:
            self.collect_values()
            # Code de génération des documents Word...
            logger.info("Documents Word générés avec succès")
        except Exception as e:
            logger.error(f"Erreur lors de la génération des documents Word: {e}")

    def generate_pdf(self):
        """Génère les documents Word et PDF"""
        try:
            self.collect_values()
            # Code de génération des documents PDF...
            logger.info("Documents PDF générés avec succès")
        except Exception as e:
            logger.error(f"Erreur lors de la génération des PDF: {e}")

    def save_to_db(self):
        """Sauvegarde les données dans la base"""
        try:
            self.collect_values()

            db_path = PathManager.get_database_path("DataBase_domiciliation.xlsx")

            # Préparer les données pour chaque feuille
            # Get all values from the main form after collection
            societe_data = {k: [v] for k, v in self.values.get('societe', {}).items()}
            contrat_data = {k: [v] for k, v in self.values.get('contrat', {}).items()}
            associes_data = self.values.get('associes', [])

            # Créer des DataFrames
            df_societe = pd.DataFrame(societe_data)
            df_associes = pd.DataFrame(associes_data)
            df_contrat = pd.DataFrame(contrat_data)

            # Utiliser ExcelWriter pour écrire sur plusieurs feuilles
            with pd.ExcelWriter(db_path, engine='openpyxl', mode='a', if_sheet_exists='overlay') as writer:
                # Charger les feuilles existantes pour ne pas les écraser
                if "Societe" in writer.book.sheetnames:
                    startrow = writer.book["Societe"].max_row
                    df_societe.to_excel(writer, sheet_name='Societe', index=False, header=False, startrow=startrow)
                else:
                    df_societe.to_excel(writer, sheet_name='Societe', index=False)

                if "Associes" in writer.book.sheetnames:
                    startrow = writer.book["Associes"].max_row
                    # Ajouter l'ID de la société pour lier les données
                    df_associes['societe_id'] = societe_data.get('denomination_sociale', [''])[0]
                    df_associes.to_excel(writer, sheet_name='Associes', index=False, header=False, startrow=startrow)
                else:
                    df_associes['societe_id'] = societe_data.get('denomination_sociale', [''])[0]
                    df_associes.to_excel(writer, sheet_name='Associes', index=False)

                if "Contrat" in writer.book.sheetnames:
                    startrow = writer.book["Contrat"].max_row
                    df_contrat.to_excel(writer, sheet_name='Contrat', index=False, header=False, startrow=startrow)
                else:
                    df_contrat.to_excel(writer, sheet_name='Contrat', index=False)

            messagebox.showinfo("Succès", "Données sauvegardées avec succès dans le fichier Excel.")
            logger.info("Données sauvegardées avec succès")

        except Exception as e:
            ErrorHandler.handle_error(e, "Erreur lors de la sauvegarde des données.")

    def clear_form(self):
        """Réinitialise tous les formulaires"""
        try:
            self.values.clear()
            self.main_form.reset()
            logger.info("Formulaires réinitialisés")
            messagebox.showinfo("Formulaire vidé", "Tous les champs ont été réinitialisés.")
        except Exception as e:
            ErrorHandler.handle_error(e, "Erreur lors de la réinitialisation du formulaire.")

if __name__ == "__main__":
    try:
        app = MainApp()
        app.mainloop()
    except Exception as e:
        logging.critical(f"Failed to start application: {e}", exc_info=True)
        tk.Tk().withdraw()  # Hide root tkinter window
        messagebox.showerror("Application Error", f"A critical error occurred and the application cannot start.\n\nDetails: {e}\n\nCheck app.log for more information.")
