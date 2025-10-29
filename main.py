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
from src.forms.dashboard_view import DashboardFrame
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

        # Create the dashboard embedded frame (replaces the previous main form)
        self.dashboard = DashboardFrame(self.main_container, self.values)
        self.dashboard.pack(fill='both', expand=True, padx=10, pady=10)

        # Create control buttons
        self.setup_buttons()

    def setup_buttons(self):
        """Configure les boutons de contrôle"""
        buttons_frame = ttk.Frame(self)
        buttons_frame.pack(pady=15, side=tk.BOTTOM, fill=tk.X, padx=20)

        # Single horizontal row to host all main buttons so they remain on one line
        row = ttk.Frame(buttons_frame)
        row.pack(fill='x')

        # Left-side primary buttons (Configuration, New, Generate)
        # Configuration button (opens dashboard configuration dialog)
        try:
            cfg_btn = WidgetFactory.create_button(row, text="⚙ Configuration", command=self.dashboard.open_configuration)
            cfg_btn.pack(side='left', padx=6)
            # attach to dashboard for state updates
            self.dashboard.config_btn = cfg_btn
        except Exception:
            pass

        # New record (opens an add dialog implemented in dashboard)
        try:
            WidgetFactory.create_button(row, text="🆕 Nouvelle", command=self.dashboard.add_record).pack(side='left', padx=6)
        except Exception:
            pass

        # Single generation button (modern style)
        gen_btn = WidgetFactory.create_button(
            row,
            text="Générer les documents",
            command=self.generate_documents,
            style='Secondary.TButton'
        )
        gen_btn.pack(side='left', padx=6)

        # (Theme toggle removed) — keep toolbar focused and simple. Theme is
        # still managed programmatically via ThemeManager and the
        # configuration dialog.

        # Flexible spacer to push the remaining controls to the right
        spacer = ttk.Frame(row)
        spacer.pack(side='left', expand=True, fill='x')

        # Right-side control buttons (packed in reverse so visual order is left->right)
        try:
            # Pack Quitter first (will appear at the far right)
            WidgetFactory.create_button(row, text="❌ Quitter", command=self.confirm_quit).pack(side='right', padx=6)

            # Clear form data
            WidgetFactory.create_button(row, text="🧹 Réinitialiser", command=self.clear_form).pack(side='right', padx=6)
        except Exception:
            pass

    def collect_values(self):
        """Collecte toutes les valeurs des formulaires"""
        # Get values from the main form
        # If dashboard exists use it, otherwise fallback to main_form
        if getattr(self, 'dashboard', None) is not None:
            try:
                self.values = self.dashboard.get_values()
                return
            except Exception:
                pass
        if getattr(self, 'main_form', None) is not None:
            self.values = self.main_form.get_values()

    def confirm_quit(self):
        """Ask user to confirm quitting the application."""
        try:
            ans = messagebox.askyesno('Quitter', 'Voulez-vous quitter l\'application ?')
            if ans:
                self.quit()
        except Exception:
            # fallback: quit without confirmation if dialog fails
            try:
                self.quit()
            except Exception:
                pass

    def generate_docs(self):
        # Deprecated: replaced by generate_documents
        return self.generate_documents()

    def generate_pdf(self):
        # Deprecated: replaced by generate_documents
        return self.generate_documents()

    def generate_documents(self):
        """Unified document generation flow: user chooses templates and formats (Word/PDF/Both)."""
        try:
            self.collect_values()
            # Ask the user whether they want to save before generating.
            # Yes -> save then generate; No -> generate without saving; Cancel -> abort.
            try:
                choice = messagebox.askyesnocancel(
                    'Sauvegarder avant génération',
                    'Voulez-vous sauvegarder les données dans la base avant de générer les documents ?\n\nOui = sauvegarder puis générer\nNon = générer sans sauvegarder\nAnnuler = annuler la génération'
                )
            except Exception:
                # If messagebox fails, default to saving to be safe
                choice = True

            if choice is None:
                # user cancelled
                return

            if choice:
                # User chose to save before generation
                try:
                    db_path = self.save_to_db()
                except Exception as _err:
                    logger.exception('Erreur lors de la sauvegarde avant génération: %s', _err)
                    messagebox.showwarning('Sauvegarde échouée', "La sauvegarde a échoué. La génération a été annulée.")
                    return
                if not db_path:
                    # save_to_db returns None on failure / cancel — stop the generation flow
                    messagebox.showwarning('Sauvegarde manquante', 'La sauvegarde a échoué ou a été annulée. La génération a été annulée.')
                    return
            else:
                # User chose NOT to save; proceed without saving
                try:
                    proceed = messagebox.askyesno(
                        'Générer sans sauvegarder',
                        'Vous avez choisi de ne pas sauvegarder. Confirmez-vous la génération sans enregistrer les données ?'
                    )
                except Exception:
                    proceed = True
                if not proceed:
                    return
            # Choose templates
            templates = self.choose_templates_with_format()
            if templates is None:
                return
            tpl_paths, to_pdf = templates
            if not tpl_paths:
                messagebox.showinfo("Aucun modèle", "Aucun modèle sélectionné. Annulation.")
                return
            out_dir = filedialog.askdirectory(title="Choisir le dossier de sortie")
            if not out_dir:
                return

            # Create modal progress window with counts and lists
            progress_win = tk.Toplevel(self)
            progress_win.title("Génération en cours")
            progress_win.transient(self)
            progress_win.grab_set()
            # center the progress window
            from src.utils.utils import WindowManager
            WindowManager.center_window(progress_win)
            progress_frame = ttk.Frame(progress_win, padding=12)
            progress_frame.pack(fill='both', expand=True)

            ttk.Label(progress_frame, text="Génération des documents", font=('Segoe UI', 12, 'bold')).pack(anchor='w')
            counts_label = ttk.Label(progress_frame, text="0 / 0")
            counts_label.pack(anchor='w', pady=(6, 0))

            pb = ttk.Progressbar(progress_frame, orient='horizontal', length=400, mode='determinate')
            pb.pack(pady=(6, 6))

            status_text = tk.Text(progress_frame, height=8, width=80, state='disabled')
            status_text.pack(fill='both', expand=True)

            # progress callback updates the UI from the worker thread via after()
            def progress_cb(processed, total, template_name, entry):
                def _update():
                    counts_label.configure(text=f"{processed} / {total}")
                    pb['maximum'] = total
                    pb['value'] = processed
                    status_text.configure(state='normal')
                    status_text.insert('end', f"[{entry.get('status')}] {template_name} - {entry.get('error') or ''}\n")
                    status_text.see('end')
                    status_text.configure(state='disabled')
                self.after(1, _update)

            def worker():
                try:
                    report = render_templates(
                        self.values,
                        str(PathManager.MODELS_DIR),
                        out_dir,
                        to_pdf=to_pdf,
                        templates_list=tpl_paths,
                        progress_callback=progress_cb,
                    )

                    def _show_done(rep):
                        # Try to determine the actual generated folder from report entries
                        try:
                            import os
                            from pathlib import Path as _P
                            paths = [str(_P(e.get('out_docx')).parent) for e in rep if e.get('out_docx')]
                            folder = os.path.commonpath(paths) if paths else out_dir
                        except Exception:
                            folder = out_dir
                        self.after(10, lambda: messagebox.showinfo('Génération terminée', f"Génération terminée. {len(rep)} modèles traités. Fichiers enregistrés dans {folder}"))

                    self.after(10, lambda r=report: _show_done(r))

                except Exception as e:
                    logging.exception('Generation failed: %s', e)
                    self.after(10, lambda: ErrorHandler.handle_error(e, 'Erreur pendant la génération'))

                finally:
                    self.after(10, lambda: (progress_win.grab_release(), progress_win.destroy()))

            t = threading.Thread(target=worker, daemon=True)
            t.start()

        except Exception as e:
            logger.exception('Erreur pendant la génération unifiée: %s', e)

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

    def choose_templates_with_format(self):
        """Let the user pick templates and whether to generate Word, PDF, or both.

        Returns a tuple (list_of_paths, to_pdf_bool) or None if cancelled.
        """
        models_dir = Path(PathManager.MODELS_DIR)
        templates = list(models_dir.glob('*.docx'))

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
            ttk.Label(frame, text='Aucun modèle .docx trouvé dans le dossier Models/').grid(row=0, column=0)
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
            # Reset embedded dashboard forms if present
            if getattr(self, 'dashboard', None) is not None:
                try:
                    self.dashboard.set_values({})
                except Exception:
                    pass
            elif getattr(self, 'main_form', None) is not None:
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
