import warnings
import tkinter as tk
from tkinter import ttk, messagebox

# Suppress known SyntaxWarning from tkcalendar about an invalid escape sequence in its font string
# This is non-fatal; the package still works. We filter the specific message to avoid noisy output.
warnings.filterwarnings(
    "ignore",
    message=r"invalid escape sequence '\\ '",
    category=SyntaxWarning,
    module=r"tkcalendar\..*",
)
from src.forms.main_form import MainForm
from src.utils import WindowManager, ThemeManager, PathManager, ErrorHandler
import pandas as pd
import logging
from tkinter import filedialog, simpledialog
from typing import Optional
import threading
import time
from src.utils.doc_generator import render_templates
from pathlib import Path

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
        ttk.Button(
            buttons_frame,
            text="Générer (Word)",
            command=self.generate_docs,
        ).grid(row=0, column=0, padx=5)

        ttk.Button(
            buttons_frame,
            text="Générer (Word & PDF)",
            command=self.generate_pdf,
        ).grid(row=0, column=1, padx=5)

        # Dashboard button (left)
        ttk.Button(
            buttons_frame,
            text="📊 Tableau de bord",
            command=self.main_form.show_dashboard,
        ).grid(row=0, column=2, padx=5)

        # Boutons de contrôle (droite)
        ttk.Button(
            buttons_frame,
            text="🆕 Nouvelle",
            command=self.clear_form,
        ).grid(row=0, column=3, padx=5)

        ttk.Button(
            buttons_frame,
            text="💾 Sauvegarder",
            command=self.save_to_db,
        ).grid(row=0, column=4, padx=5)

        ttk.Button(
            buttons_frame,
            text="❌ Quitter",
            command=self.quit,
        ).grid(row=0, column=5, padx=5)

    def collect_values(self):
        """Collecte toutes les valeurs des formulaires"""
        # Get values from the main form
        self.values = self.main_form.get_values()

    def generate_docs(self):
        """Génère les documents Word"""
        try:
            self.collect_values()
            # Provide immediate user feedback while generation logic is implemented
            logger.info("Démarrage de la génération Word")
            messagebox.showinfo("Génération Word", "La génération des documents Word a démarré.\nVérifiez le journal pour la progression.")
            # Example: show a small summary of collected values (non-sensitive)
            summary = f"Société: {self.values.get('societe', {}).get('denomination', '')}\nAssociés: {len(self.values.get('associes', []))}"
            logger.debug(f"Valeurs collectées pour génération Word: {self.values}")
            messagebox.showinfo("Résumé", summary)
            logger.info("Documents Word - action user notified")
            # Let user choose which templates to generate
            templates = self.choose_templates()
            if templates is None:
                return
            if not templates:
                messagebox.showinfo("Aucun modèle", "Aucun modèle sélectionné. Annulation.")
                return

            out_dir = filedialog.askdirectory(title="Choisir le dossier de sortie")
            if not out_dir:
                return
            to_pdf = messagebox.askyesno("PDF", "Voulez-vous également générer des PDF ?")
            # Start generation with modal progress, passing selected templates
            self.start_generation(out_dir, to_pdf, templates_list=templates)
        except Exception as e:
            logger.error(f"Erreur lors de la génération des documents Word: {e}")

    def generate_pdf(self):
        """Génère les documents Word et PDF"""
        try:
            self.collect_values()
            logger.info("Démarrage de la génération Word et PDF")
            messagebox.showinfo("Génération Word+PDF", "La génération Word + PDF a démarré.\nVérifiez le journal pour la progression.")
            summary = f"Société: {self.values.get('societe', {}).get('denomination', '')}\nAssociés: {len(self.values.get('associes', []))}"
            logger.debug(f"Valeurs collectées pour génération PDF: {self.values}")
            messagebox.showinfo("Résumé", summary)
            logger.info("Documents Word+PDF - action user notified")
            templates = self.choose_templates()
            if templates is None:
                return
            if not templates:
                messagebox.showinfo("Aucun modèle", "Aucun modèle sélectionné. Annulation.")
                return
            out_dir = filedialog.askdirectory(title="Choisir le dossier de sortie")
            if not out_dir:
                return
            # Force PDF generation
            self.start_generation(out_dir, True, templates_list=templates)
        except Exception as e:
            logger.error(f"Erreur lors de la génération des PDF: {e}")

    def start_generation(self, out_dir: str, to_pdf: bool, templates_list: Optional[list] = None):
        """Start generation on a background thread and show modal progress."""
        # Create modal progress window
        progress_win = tk.Toplevel(self)
        progress_win.title("Génération en cours")
        progress_win.transient(self)
        progress_win.grab_set()
        ttk.Label(progress_win, text="Génération en cours, veuillez patienter...").pack(padx=20, pady=(10, 5))
        pb = ttk.Progressbar(progress_win, mode='indeterminate', length=300)
        pb.pack(padx=20, pady=(0, 10))
        pb.start(10)

        def worker():
            try:
                # call generator (pass templates_list if provided)
                report = render_templates(self.values, str(PathManager.MODELS_DIR), out_dir, to_pdf=to_pdf, templates_list=templates_list)
                # Save a short summary to log
                logging.info('Generation finished, %d templates processed', len(report))
                # Show final message in main thread
                self.after(10, lambda: messagebox.showinfo('Génération terminée', f"Génération terminée. Fichiers enregistrés dans {out_dir}"))
            except Exception as e:
                logging.exception('Generation failed: %s', e)
                self.after(10, lambda: ErrorHandler.handle_error(e, 'Erreur pendant la génération'))
            finally:
                # stop progress and destroy modal
                self.after(10, lambda: (pb.stop(), progress_win.grab_release(), progress_win.destroy()))

        t = threading.Thread(target=worker, daemon=True)
        t.start()

    def choose_templates(self):
        """Open a modal dialog to let the user pick which .docx templates in Models/ to generate.

        Returns a list of absolute file paths (strings), an empty list if none selected,
        or None if the user cancelled the dialog.
        """
        models_dir = Path(PathManager.MODELS_DIR)
        templates = list(models_dir.glob('*.docx'))

        dlg = tk.Toplevel(self)
        dlg.title('Sélection des modèles')
        dlg.transient(self)
        dlg.grab_set()

        frame = ttk.Frame(dlg)
        frame.pack(fill='both', expand=True, padx=10, pady=10)

        vars_map = {}
        row = 0
        if not templates:
            ttk.Label(frame, text='Aucun modèle .docx trouvé dans le dossier Models/').grid(row=0, column=0)
        else:
            for tpl in templates:
                v = tk.BooleanVar(value=True)
                chk = ttk.Checkbutton(frame, text=tpl.name, variable=v)
                chk.grid(row=row, column=0, sticky='w', pady=2)
                vars_map[tpl] = v
                row += 1

        btn_frame = ttk.Frame(dlg)
        btn_frame.pack(fill='x', pady=(5, 10), padx=10)
        selected = {'value': None}

        def on_ok():
            chosen = [str(p.resolve()) for p, bv in vars_map.items() if bv.get()]
            selected['value'] = chosen
            dlg.grab_release()
            dlg.destroy()

        def on_cancel():
            selected['value'] = None
            dlg.grab_release()
            dlg.destroy()

        ttk.Button(btn_frame, text='OK', command=on_ok).pack(side='right', padx=5)
        ttk.Button(btn_frame, text='Annuler', command=on_cancel).pack(side='right')

        self.wait_window(dlg)
        return selected['value']

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
