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

        # Single generation button (modern style)
        # Use the unified configuration button style for all main buttons
        gen_btn = WidgetFactory.create_button(
            buttons_frame,
            text="Générer les documents",
            command=self.generate_documents,
            style='Secondary.TButton'
        )
        gen_btn.grid(row=0, column=0, padx=5)

        # Dashboard button (left)
        WidgetFactory.create_button(
            buttons_frame,
            text="📊 Tableau de bord",
            command=self.main_form.show_dashboard
        ).grid(row=0, column=2, padx=5)

        # Theme toggle
        def _toggle_theme():
            try:
                current = self.theme_manager.theme.mode
                self.theme_manager.toggle_theme()
                new = self.theme_manager.theme.mode
                # update button text to reflect new mode
                theme_btn.configure(text=('🌙' if new == 'dark' else '☀️'))
            except Exception:
                pass

        theme_btn = WidgetFactory.create_button(buttons_frame, text=('🌙' if self.theme_manager.theme.mode == 'dark' else '☀️'), command=_toggle_theme)
        theme_btn.grid(row=0, column=6, padx=5)

        # Boutons de contrôle (droite)
        # Keep only non-redundant toolbar actions here. The main form already
        # provides a Save button in its footer/navigation, so we avoid creating
        # a duplicate "Sauvegarder" button in the global toolbar.
        WidgetFactory.create_button(buttons_frame, text="🆕 Nouvelle", command=self.clear_form).grid(row=0, column=3, padx=5)
        WidgetFactory.create_button(buttons_frame, text="❌ Quitter", command=self.quit).grid(row=0, column=5, padx=5)

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

    def generate_documents(self):
        """Unified document generation flow: user chooses templates and formats (Word/PDF/Both)."""
        try:
            self.collect_values()
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
                    report = render_templates(self.values, str(PathManager.MODELS_DIR), out_dir, to_pdf=to_pdf, templates_list=tpl_paths, progress_callback=progress_cb)
                    self.after(10, lambda: messagebox.showinfo('Génération terminée', f"Génération terminée. {len(report)} modèles traités. Fichiers enregistrés dans {out_dir}"))
                except Exception as e:
                    logging.exception('Generation failed: %s', e)
                    self.after(10, lambda: ErrorHandler.handle_error(e, 'Erreur pendant la génération'))
                finally:
                    self.after(10, lambda: (progress_win.grab_release(), progress_win.destroy()))

            def worker_wrapper():
                # detect if user selected the project's tmp_out directory and request cleanup
                try:
                    cleanup_tmp_flag = Path(out_dir).name == 'tmp_out'
                except Exception:
                    cleanup_tmp_flag = False
                # run worker with cleanup flag captured via closure
                try:
                    report = render_templates(self.values, str(PathManager.MODELS_DIR), out_dir, to_pdf=to_pdf, templates_list=tpl_paths, progress_callback=progress_cb, cleanup_tmp=cleanup_tmp_flag)
                    self.after(10, lambda: messagebox.showinfo('Génération terminée', f"Génération terminée. {len(report)} modèles traités. Fichiers enregistrés dans {out_dir}"))
                except Exception as e:
                    logging.exception('Generation failed: %s', e)
                    self.after(10, lambda: ErrorHandler.handle_error(e, 'Erreur pendant la génération'))
                finally:
                    self.after(10, lambda: (progress_win.grab_release(), progress_win.destroy()))

            t = threading.Thread(target=worker_wrapper, daemon=True)
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
            from src.utils.utils import ensure_excel_db
            ensure_excel_db(db_path, _const.excel_sheets)

            # Prepare raw values from forms
            societe_vals = self.values.get('societe', {}) or {}
            contrat_vals = self.values.get('contrat', {}) or {}
            associes_list = self.values.get('associes', []) or []

            # Helper: determine next integer ID in a sheet
            def _next_id(sheet_name, id_col):
                try:
                    existing = pd.read_excel(db_path, sheet_name=sheet_name)
                    if id_col in existing.columns and not existing.empty:
                        # Try numeric conversion
                        try:
                            nums = pd.to_numeric(existing[id_col], errors='coerce').dropna()
                            if not nums.empty:
                                return int(nums.max()) + 1
                        except Exception:
                            pass
                        return len(existing) + 1
                except Exception:
                    pass
                return 1

            def _to_cell(v):
                if v is None:
                    return ''
                # Keep simple types as strings for Excel cells
                try:
                    return str(v)
                except Exception:
                    return ''

            # Map form keys to canonical excel headers
            societe_map = {
                'denomination': 'DEN_STE',
                'forme_juridique': 'FORME_JUR',
                'ice': 'ICE',
                'date_ice': 'DATE_ICE',
                'capital': 'CAPITAL',
                'parts_social': 'PART_SOCIAL',
                'adresse': 'STE_ADRESS',
                'tribunal': 'TRIBUNAL'
            }

            associe_map = {
                'civilite': 'CIVIL',
                'prenom': 'PRENOM',
                'nom': 'NOM',
                'nationalite': 'NATIONALITY',
                'num_piece': 'CIN_NUM',
                'validite_piece': 'CIN_VALIDATY',
                'date_naiss': 'DATE_NAISS',
                'lieu_naiss': 'LIEU_NAISS',
                'adresse': 'ADRESSE',
                'telephone': 'PHONE',
                'email': 'EMAIL',
                'parts': 'PARTS',
                'est_gerant': 'IS_GERANT',
                'qualite': 'QUALITY'
            }

            contrat_map = {
                'date_contrat': 'DATE_CONTRAT',
                'period_domcil': 'PERIOD_DOMCIL',
                'prix_contrat': 'PRIX_CONTRAT',
                'prix_intermediare': 'PRIX_INTERMEDIARE_CONTRAT',
                'dom_datedeb': 'DOM_DATEDEB',
                'dom_datefin': 'DOM_DATEFIN'
            }

            # Build Societe DataFrame aligned with headers
            df_societe = pd.DataFrame(columns=_const.societe_headers)
            if societe_vals:
                soc_id = _next_id('Societes', 'ID_SOCIETE')
                row = {h: '' for h in _const.societe_headers}
                row['ID_SOCIETE'] = _to_cell(soc_id)
                for k, v in societe_map.items():
                    if k in societe_vals:
                        row[v] = _to_cell(societe_vals.get(k, ''))
                df_societe = pd.DataFrame([row])

            # Build Associes DataFrame aligned with headers
            df_associes = pd.DataFrame(columns=_const.associe_headers)
            if associes_list:
                next_assoc_id = _next_id('Associes', 'ID_ASSOCIE')
                assoc_rows = []
                # Determine societe id for linking
                soc_id_for_assoc = df_societe['ID_SOCIETE'].iloc[0] if not df_societe.empty else (societe_vals.get('id') or '')
                try:
                    soc_id_for_assoc = int(soc_id_for_assoc)
                except Exception:
                    pass
                for a in associes_list:
                    if isinstance(a, dict):
                        r = {h: '' for h in _const.associe_headers}
                        r['ID_ASSOCIE'] = _to_cell(next_assoc_id)
                        next_assoc_id += 1
                        r['ID_SOCIETE'] = _to_cell(soc_id_for_assoc)
                        for k, v in associe_map.items():
                            if k in a:
                                val = a.get(k, '')
                                if isinstance(val, bool):
                                    val = int(val)
                                r[v] = _to_cell(val)
                        assoc_rows.append(r)
                if assoc_rows:
                    df_associes = pd.DataFrame(assoc_rows)

            # Build Contrat DataFrame aligned with headers
            df_contrat = pd.DataFrame(columns=_const.contrat_headers)
            if contrat_vals:
                next_contrat_id = _next_id('Contrats', 'ID_CONTRAT')
                r = {h: '' for h in _const.contrat_headers}
                r['ID_CONTRAT'] = _to_cell(next_contrat_id)
                r['ID_SOCIETE'] = _to_cell(df_societe['ID_SOCIETE'].iloc[0] if not df_societe.empty else '')
                for k, v in contrat_map.items():
                    if k in contrat_vals:
                        r[v] = _to_cell(contrat_vals.get(k, ''))
                df_contrat = pd.DataFrame([r])

            # Use ExcelWriter to write multiple sheets; create file if missing
            # Append to existing workbook using the canonical sheet names from constants
            with pd.ExcelWriter(db_path, engine='openpyxl', mode='a', if_sheet_exists='overlay') as writer:
                # Societes
                if not df_societe.empty:
                    if 'Societes' in writer.book.sheetnames:
                        startrow = writer.book['Societes'].max_row
                        df_societe.to_excel(writer, sheet_name='Societes', index=False, header=False, startrow=startrow)
                    else:
                        df_societe.to_excel(writer, sheet_name='Societes', index=False)

                # Associes
                if not df_associes.empty:
                    if 'Associes' in writer.book.sheetnames:
                        startrow = writer.book['Associes'].max_row
                        df_associes.to_excel(writer, sheet_name='Associes', index=False, header=False, startrow=startrow)
                    else:
                        df_associes.to_excel(writer, sheet_name='Associes', index=False)

                # Contrats
                if not df_contrat.empty:
                    if 'Contrats' in writer.book.sheetnames:
                        startrow = writer.book['Contrats'].max_row
                        df_contrat.to_excel(writer, sheet_name='Contrats', index=False, header=False, startrow=startrow)
                    else:
                        df_contrat.to_excel(writer, sheet_name='Contrats', index=False)

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
