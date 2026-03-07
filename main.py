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
from src.utils.utils import WidgetFactory
import pandas as pd
import logging
from tkinter import filedialog, simpledialog
from typing import Optional
import threading
import time
from src.utils.doc_generator import render_templates
from pathlib import Path
from src.utils import constants as _const
from src.forms.generation_selector import show_generation_selector

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

        # Single horizontal row to host all main buttons so they remain on one line
        row = ttk.Frame(buttons_frame)
        row.pack(fill='x')

        # Left-side primary buttons (Configuration, Dashboard, Generate)
        # Configuration button (opens MainForm configuration dialog)
        try:
            cfg_btn = WidgetFactory.create_button(row, text="⚙ Configuration", command=self.main_form.open_configuration, style='Refresh.TButton')
            cfg_btn.pack(side='left', padx=6)
            # attach to main_form for state updates
            self.main_form.config_btn = cfg_btn
        except Exception:
            pass

        # Dashboard button
        WidgetFactory.create_button(
            row,
            text="📊 Tableau de bord",
            command=self.main_form.show_dashboard,
            style='View.TButton'
        ).pack(side='left', padx=6)

        # Single generation button (modern style - SUCCESS GREEN)
        gen_btn = WidgetFactory.create_button(
            row,
            text="Générer les documents",
            command=self.generate_documents,
            style='Success.TButton'
        )
        gen_btn.pack(side='left', padx=6, pady=3)

        # (Theme toggle removed) — keep toolbar focused and simple. Theme is
        # still managed programmatically via ThemeManager and the
        # configuration dialog.

        # Flexible spacer to push the remaining controls to the right
        spacer = ttk.Frame(row)
        spacer.pack(side='left', expand=True, fill='x')

        # Right-side control buttons (packed in reverse so visual order is left->right)
        try:
            # Pack Quitter first (will appear at the far right) - CANCEL RED
            WidgetFactory.create_button(row, text="❌ Quitter", command=self.quit, style='Cancel.TButton').pack(side='right', padx=6, pady=3)

            # Suivant
            _btn = WidgetFactory.create_button(row, text="Suivant ▶", command=self.main_form.next_page, style='Secondary.TButton')
            _btn.pack(side='right', padx=6, pady=3)
            self.main_form.next_btn = _btn

            # Précédent
            _btn = WidgetFactory.create_button(row, text="◀ Précédent", command=self.main_form.prev_page, style='Secondary.TButton')
            _btn.pack(side='right', padx=6, pady=3)
            self.main_form.prev_btn = _btn

            # Terminer - SUCCESS GREEN
            _btn = WidgetFactory.create_button(row, text="🏁 Terminer", command=self.main_form.finish, style='Success.TButton')
            _btn.pack(side='right', padx=6, pady=3)
            self.main_form.finish_btn = _btn

            # Sauvegarder - SUCCESS GREEN
            _btn = WidgetFactory.create_button(row, text="💾 Sauvegarder", command=self.main_form.save_current, style='Success.TButton')
            _btn.pack(side='right', padx=6, pady=3)
            self.main_form.save_btn = _btn

            # Nouvelle (will appear left-most among the right cluster) - SECONDARY
            WidgetFactory.create_button(row, text="🆕 Nouvelle", command=self.clear_form, style='Copy.TButton').pack(side='right', padx=6, pady=3)
        except Exception:
            # If main_form isn't ready for some reason, ignore and continue
            pass

    def collect_values(self):
        """Collecte toutes les valeurs des formulaires"""
        # Get values from the main form
        self.values = self.main_form.get_values()

    def generate_docs(self):
        # Deprecated: replaced by generate_documents
        return self.generate_documents()

    def generate_pdf(self):
        # Deprecated: replaced by generate_documents
        return self.generate_documents()

    def _ask_yes_no_cancel(self, title, message):
        """Custom Yes/No/Cancel dialog with dark mode and automatic geometry.

        Returns: True (Yes), False (No), None (Cancel)
        """
        dlg = tk.Toplevel(self)
        dlg.title(title)
        dlg.transient(self)
        dlg.grab_set()
        dlg.configure(bg='#2b2b2b')
        from src.utils.utils import WindowManager

        # Set a reasonable initial size that will be adjusted
        dlg.geometry('450x100')

        # Main content frame (for proper centering)
        content_frame = ttk.Frame(dlg)
        content_frame.pack(fill='both', expand=True, padx=20, pady=15)

        # Title with icon - CENTERED
        title_frame = ttk.Frame(content_frame)
        title_frame.pack(fill='x', expand=False, pady=(0, 10))

        ttk.Label(title_frame, text="❓", font=('Segoe UI', 16)).pack(side='top', pady=(0, 6))
        msg_label = ttk.Label(title_frame, text=message, font=('Segoe UI', 11), wraplength=380, justify='center')
        msg_label.pack(fill='both', expand=False)

        # Buttons frame - CENTERED at bottom
        btn_frame = ttk.Frame(dlg)
        btn_frame.pack(fill='x', padx=20, pady=(10, 15))

        result = None

        def on_yes():
            nonlocal result
            result = True
            dlg.grab_release()
            dlg.destroy()

        def on_no():
            nonlocal result
            result = False
            dlg.grab_release()
            dlg.destroy()

        def on_cancel():
            nonlocal result
            result = None
            dlg.grab_release()
            dlg.destroy()

        WidgetFactory.create_button(btn_frame, text='Oui', command=on_yes, style='Confirm.TButton').pack(side='left', padx=5, expand=True, fill='x')
        WidgetFactory.create_button(btn_frame, text='Non', command=on_no, style='Close.TButton').pack(side='left', padx=5, expand=True, fill='x')
        WidgetFactory.create_button(btn_frame, text='Annuler', command=on_cancel, style='Close.TButton').pack(side='left', padx=5, expand=True, fill='x')

        # Force widget update to calculate actual sizes
        dlg.update_idletasks()

        # Calculate required width and height
        reqwidth = dlg.winfo_reqwidth()
        reqheight = dlg.winfo_reqheight()

        # Add padding and ensure minimum sizes
        final_width = max(reqwidth + 40, 450)
        final_height = max(reqheight + 30, 200)

        dlg.geometry(f'{final_width}x{final_height}')
        WindowManager.center_window(dlg)

        self.wait_window(dlg)
        return result

    def _ask_yes_no(self, title, message):
        """Custom Yes/No dialog with dark mode and automatic geometry.

        Returns: True (Yes), False (No)
        """
        dlg = tk.Toplevel(self)
        dlg.title(title)
        dlg.transient(self)
        dlg.grab_set()
        dlg.configure(bg='#2b2b2b')

        # Set a reasonable initial size that will be adjusted
        dlg.geometry('400x100')
        from src.utils.utils import WindowManager

        # Main content frame (for proper centering)
        content_frame = ttk.Frame(dlg)
        content_frame.pack(fill='both', expand=True, padx=20, pady=15)

        # Title with icon - CENTERED
        title_frame = ttk.Frame(content_frame)
        title_frame.pack(fill='x', expand=False, pady=(0, 10))

        ttk.Label(title_frame, text="❓", font=('Segoe UI', 16)).pack(side='top', pady=(0, 6))
        msg_label = ttk.Label(title_frame, text=message, font=('Segoe UI', 11), wraplength=350, justify='center')
        msg_label.pack(fill='both', expand=False)

        # Buttons frame - CENTERED at bottom
        btn_frame = ttk.Frame(dlg)
        btn_frame.pack(fill='x', padx=20, pady=(10, 15))

        result = False

        def on_yes():
            nonlocal result
            result = True
            dlg.grab_release()
            dlg.destroy()

        def on_no():
            nonlocal result
            result = False
            dlg.grab_release()
            dlg.destroy()

        WidgetFactory.create_button(btn_frame, text='Non', command=on_no, style='Close.TButton').pack(side='left', padx=5, expand=True, fill='x')
        WidgetFactory.create_button(btn_frame, text='Oui', command=on_yes, style='Confirm.TButton').pack(side='left', padx=5, expand=True, fill='x')

        # Force widget update to calculate actual sizes
        dlg.update_idletasks()

        # Calculate required width and height
        reqwidth = dlg.winfo_reqwidth()
        reqheight = dlg.winfo_reqheight()

        # Add padding and ensure minimum sizes
        final_width = max(reqwidth + 40, 400)
        final_height = max(reqheight + 30, 180)

        dlg.geometry(f'{final_width}x{final_height}')
        WindowManager.center_window(dlg)

        self.wait_window(dlg)
        return result

    def generate_documents(self):
        """Open generation selector directly (all choices now live inside selector)."""
        try:
            self.collect_values()

            # Show generation selector - pass values and format for integrated generation
            # The selector now handles template selection, output format and optional save.
            selector_result = show_generation_selector(
                self,
                self.values,
                output_format='word',
                save_callback=self.save_to_db,
            )
            # The selector handles generation internally, no need to do anything here

        except Exception as e:
            logger.exception('Erreur pendant la génération: %s', e)

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

    def _ask_output_format(self):
        """Ask user whether to generate Word or Word & PDF with automatic geometry.

        Returns: 'word' or 'both', or None if cancelled.
        """
        dlg = tk.Toplevel(self)
        dlg.title('Choisir le format de sortie')
        dlg.transient(self)
        dlg.grab_set()

        # Set a reasonable initial size that will be adjusted
        dlg.geometry('420x100')
        from src.utils.utils import WindowManager

        # Apply dark mode to dialog
        dlg.configure(bg='#2b2b2b')

        # Main content frame (for proper centering)
        content_frame = ttk.Frame(dlg)
        content_frame.pack(fill='both', expand=True, padx=20, pady=15)

        # Title - CENTERED
        ttk.Label(content_frame, text="Quel format voulez-vous générer ?", font=('Segoe UI', 12, 'bold')).pack(anchor='center', pady=(0, 15))

        fmt_var = tk.StringVar(value='word')

        # Radio buttons frame
        radio_frame = ttk.Frame(content_frame)
        radio_frame.pack(fill='x', expand=False, pady=8)

        ttk.Radiobutton(radio_frame, text='📄 Word uniquement', variable=fmt_var, value='word').pack(anchor='w', pady=4)
        ttk.Radiobutton(radio_frame, text='📊 Word & PDF', variable=fmt_var, value='both').pack(anchor='w', pady=4)

        result = None

        def on_ok():
            nonlocal result
            result = fmt_var.get()
            dlg.grab_release()
            dlg.destroy()

        def on_cancel():
            nonlocal result
            result = None
            dlg.grab_release()
            dlg.destroy()

        # Button frame at bottom
        btn_frame = ttk.Frame(dlg)
        btn_frame.pack(fill='x', padx=20, pady=(10, 15))

        WidgetFactory.create_button(btn_frame, text='Annuler', command=on_cancel, style='Close.TButton').pack(side='left', padx=5, expand=True, fill='x')
        WidgetFactory.create_button(btn_frame, text='OK', command=on_ok, style='Confirm.TButton').pack(side='left', padx=5, expand=True, fill='x')

        # Force widget update to calculate actual sizes
        dlg.update_idletasks()

        # Calculate required width and height
        reqwidth = dlg.winfo_reqwidth()
        reqheight = dlg.winfo_reqheight()

        # Add padding and ensure minimum sizes
        final_width = max(reqwidth + 40, 420)
        final_height = max(reqheight + 30, 200)

        dlg.geometry(f'{final_width}x{final_height}')
        WindowManager.center_window(dlg)

        self.wait_window(dlg)
        return result

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
        from src.utils.utils import WindowManager
        WindowManager.center_window(dlg)

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

        selected_value = None

        def on_ok():
            nonlocal selected_value
            chosen = [str(p.resolve()) for p, bv in vars_map.items() if bv.get()]
            selected_value = chosen
            dlg.grab_release()
            dlg.destroy()

        def on_cancel():
            nonlocal selected_value
            selected_value = None
            dlg.grab_release()
            dlg.destroy()

        from src.utils.utils import WidgetFactory as _WF
        _WF.create_button(btn_frame, text='OK', command=on_ok, style='Secondary.TButton').pack(side='right', padx=5)
        _WF.create_button(btn_frame, text='Annuler', command=on_cancel, style='Secondary.TButton').pack(side='right')

        self.wait_window(dlg)
        return selected_value

    def choose_templates_with_format(self, generation_type=None, creation_type=None):
        """Let the user pick templates and whether to generate Word, PDF, or both.

        Args:
            generation_type: 'creation' or 'domiciliation' to filter templates (optional)
            creation_type: 'SARL' or 'SARL_AU' for creation documents (optional)

        Returns a tuple (list_of_paths, to_pdf_bool) or None if cancelled.
        """
        models_dir = Path(PathManager.MODELS_DIR)
        all_templates = list(models_dir.glob('*.docx'))

        # Filter templates based on generation type
        if generation_type == 'creation':
            # Filter for creation documents (SARL or SARL_AU)
            if creation_type == 'SARL':
                # Include templates with SARL in the name but not SARL_AU
                templates = [t for t in all_templates if 'SARL' in t.name and 'SARL_AU' not in t.name]
            elif creation_type == 'SARL_AU':
                # Include templates with SARL_AU in the name
                templates = [t for t in all_templates if 'SARL_AU' in t.name]
            else:
                # If creation_type not specified, include all SARL templates
                templates = [t for t in all_templates if 'SARL' in t.name]
        elif generation_type == 'domiciliation':
            # Filter for domiciliation documents
            templates = [t for t in all_templates if 'domiciliation' in t.name.lower() or 'domicil' in t.name.lower()]
        else:
            # No filter, show all templates
            templates = all_templates

        dlg = tk.Toplevel(self)
        dlg.title('Sélection des modèles et du format')
        dlg.transient(self)
        dlg.grab_set()
        from src.utils.utils import WindowManager
        WindowManager.center_window(dlg)

        frame = ttk.Frame(dlg, padding=10)
        frame.pack(fill='both', expand=True)

        vars_map = {}
        row = 0
        if not templates:
            ttk.Label(frame, text='Aucun modèle .docx trouvé correspondant au type sélectionné.').grid(row=0, column=0)
        else:
            for tpl in templates:
                v = tk.BooleanVar(value=True)
                chk = ttk.Checkbutton(frame, text=tpl.name, variable=v)
                chk.grid(row=row, column=0, sticky='w', pady=2)
                vars_map[tpl] = v
                row += 1

        # Format options
        fmt_frame = ttk.Frame(frame)
        fmt_frame.grid(row=0, column=1, rowspan=max(1, row), padx=(10, 0), sticky='n')
        fmt_var = tk.StringVar(value='word')
        ttk.Radiobutton(fmt_frame, text='Word uniquement', variable=fmt_var, value='word').pack(anchor='w')
        ttk.Radiobutton(fmt_frame, text='PDF uniquement', variable=fmt_var, value='pdf').pack(anchor='w')
        ttk.Radiobutton(fmt_frame, text='Word & PDF', variable=fmt_var, value='both').pack(anchor='w')

        result = {'paths': [], 'format': 'word'}

        def on_ok():
            chosen = [str(p.resolve()) for p, bv in vars_map.items() if bv.get()]
            result['paths'] = chosen
            result['format'] = fmt_var.get()
            dlg.grab_release()
            dlg.destroy()

        def on_cancel():
            result['paths'] = None
            result['format'] = None
            dlg.grab_release()
            dlg.destroy()

        btn_frame = ttk.Frame(dlg)
        btn_frame.pack(fill='x', pady=(8, 6))
        from src.utils.utils import WidgetFactory as _WF
        _WF.create_button(btn_frame, text='OK', command=on_ok, style='Secondary.TButton').pack(side='right', padx=5)
        _WF.create_button(btn_frame, text='Annuler', command=on_cancel, style='Secondary.TButton').pack(side='right')

        self.wait_window(dlg)
        if result['paths'] is None:
            return None
        to_pdf = result['format'] in ('pdf', 'both')
        return result['paths'], to_pdf

    def save_to_db(self):
        """Sauvegarde les données dans la base"""
        try:
            self.collect_values()

            # Use centralized DB filename from constants
            db_path = PathManager.DATABASE_DIR / _const.DB_FILENAME

            # Ensure workbook and sheets exist
            from src.utils.utils import ensure_excel_db, write_records_to_db, migrate_excel_workbook, societe_exists
            ensure_excel_db(db_path, _const.excel_sheets)

            # Run migration to reconcile older/misnamed sheets into canonical ones
            try:
                migrate_excel_workbook(db_path)
            except Exception:
                # Migration is best-effort; don't block saving if it fails
                logger.exception('Migration of legacy sheets failed')

            # Prepare raw values from forms
            societe_vals = self.values.get('societe', {}) or {}
            contrat_vals = self.values.get('contrat', {}) or {}
            associes_list = self.values.get('associes', []) or []

            # If a company name is provided, check for duplicates in the DB and *forbid* saving
            try:
                name = societe_vals.get('denomination') or societe_vals.get('DEN_STE')
                if name and societe_exists(name, db_path):
                    # Do not allow duplicate société names in the DB
                    messagebox.showerror('Société existante', f"La société '{name}' existe déjà dans la base. Enregistrement interdit pour éviter les doublons.")
                    return None
            except Exception:
                # Defensive: on any failure of the check, log and continue with save
                logger.exception('Failed to perform duplicate societe check')

            # Delegate the heavy lifting to the utility that handles IDs and date conversion
            write_records_to_db(db_path, societe_vals, associes_list, contrat_vals)
            # Do not show a modal message here — let the caller (finish or other
            # UI action) present a single, consolidated message to the user.
            logger.info("Données sauvegardées avec succès dans %s", db_path)
            return db_path

        except Exception as e:
            ErrorHandler.handle_error(e, "Erreur lors de la sauvegarde des données.")
            return None

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
