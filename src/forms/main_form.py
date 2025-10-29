import tkinter as tk
from tkinter import ttk, messagebox
from typing import Optional
from .societe_form import SocieteForm
from .associe_form import AssocieForm
from .contrat_form import ContratForm
from ..utils.utils import ThemeManager, WidgetFactory, WindowManager, PathManager, ensure_excel_db
from ..utils import constants as _const
from pathlib import Path

class MainForm(ttk.Frame):
    def __init__(self, parent, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = values_dict or {}

        # Initialize navigation button attributes with explicit types so
        # linters/type checkers know these exist and accept Button assignments.
        # They are attached by the outer application toolbar (main.py)
        # to keep a single unified control row.
        self.prev_btn: Optional[ttk.Button] = None
        self.next_btn: Optional[ttk.Button] = None
        self.save_btn: Optional[ttk.Button] = None
        self.finish_btn: Optional[ttk.Button] = None
        self.config_btn: Optional[ttk.Button] = None

        # Allow this main frame to expand inside its parent
        try:
            self.pack(fill="both", expand=True)
        except Exception:
            pass

        # Initialize theme manager
        self.theme_manager = ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Create scrollable container
        self.setup_scrollable_container()

        # Create forms
        self.setup_forms()

    def setup_scrollable_container(self):
        """Configure the scrollable container for all forms"""
        # Create main container
        container = ttk.Frame(self)
        container.pack(fill="both", expand=True)
        container.grid_columnconfigure(0, weight=1)
        container.grid_rowconfigure(0, weight=1)

        # Create canvas and scrollbar
        self.canvas = tk.Canvas(container, background=self.theme_manager.colors['bg'])
        scrollbar = ttk.Scrollbar(container, orient="vertical", command=self.canvas.yview)
        self.canvas.configure(yscrollcommand=scrollbar.set)

        # Create main container for forms
        self.forms_container = ttk.Frame(self.canvas)
        # Make sure forms container will expand within the canvas window
        self.forms_container.grid_columnconfigure(0, weight=1)
        # Allow multiple rows to stretch if necessary
        # Pages are placed at row=1 below any header rows; set row 0..2 to stretch
        for r in range(0, 3):
            self.forms_container.grid_rowconfigure(r, weight=1)

        # Create the canvas window with auto-width
        self.canvas_window = self.canvas.create_window(
            (0, 0), window=self.forms_container, anchor="nw"
        )

        # Configure scroll region when forms are updated
        def _on_frame_configure(event):
            self.canvas.configure(scrollregion=self.canvas.bbox("all"))

        def _on_canvas_configure(event):
            # Update the width to expand to the canvas width
            self.canvas.itemconfig(self.canvas_window, width=event.width)

        self.forms_container.bind("<Configure>", _on_frame_configure)
        self.canvas.bind("<Configure>", _on_canvas_configure)

        # Configure mouse wheel scrolling
        def _on_mousewheel(event):
            if event.state & 0x4: # Check if Control key is pressed
                # Zoom with Ctrl + Mouse wheel
                return
            self.canvas.yview_scroll(-1 * (event.delta // 120), "units")

        self.canvas.bind_all("<MouseWheel>", _on_mousewheel)

        # Grid layout for better control — make canvas expand
        container.grid_rowconfigure(0, weight=1)
        container.grid_columnconfigure(0, weight=1)
        self.canvas.grid(row=0, column=0, sticky="nsew")
        scrollbar.grid(row=0, column=1, sticky="ns")

        # Footer container for navigation (fixed, not inside the scroll area)
        # Placed directly inside this frame so it stays visible when content scrolls
        # Use Card.TFrame style if available so footer is visually separated
        try:
            self.footer_container = ttk.Frame(self, style='Card.TFrame')
        except Exception:
            self.footer_container = ttk.Frame(self)
        # Keep footer fixed height and add padding so content doesn't overlap
        self.footer_container.pack(fill="x", padx=10, pady=(8, 10))
        # Internal container to host navigation widgets consistently
        self.footer_inner = ttk.Frame(self.footer_container)
        self.footer_inner.pack(fill="x")

    def setup_forms(self):
        """Create one page per logical section and prepare navigation.

        Previously the form showed all sections on a single screen. We now
        create one frame (page) per section and provide Next/Previous
        navigation as well as Save and Finish actions.
        """
        # Container for pages
        self.pages = []
        self.current_page = 0

        # Create pages
        self.create_societe_page()
        self.create_associe_page()
        self.create_contrat_page()

        # Show first page
        self.show_page(0)

        # Setup navigation controls
        self.setup_navigation()

    def create_section_header(self, parent, text, icon, row, column, columnspan=1):
        """Crée un en-tête de section stylisé"""
        header_frame = ttk.Frame(parent, style='Section.TFrame')
        # Ensure the header frame expands horizontally within its parent
        try:
            header_frame.grid(row=row, column=column, columnspan=columnspan,
                              sticky="ew", pady=(0, 10), padx=5)
            header_frame.grid_columnconfigure(0, weight=1)
        except Exception:
            # Fallback to pack if grid placement isn't supported
            header_frame.pack(fill="x", pady=(0, 10), padx=5)

        label = ttk.Label(
            header_frame,
            text=f"{icon} {text}",
            style='SectionHeader.TLabel'
        )
        label.pack(fill="x", expand=True, padx=6, pady=4)

        return header_frame

    def create_societe_page(self):
        # Create a dedicated page container and put the header inside it so
        # the header shows/hides together with the page.
        # Use a plain frame for the page so the header's styled frame provides
        # the visual section border/strip; avoids nested Section.TFrame borders
        page = ttk.Frame(self.forms_container)
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        # Make the page expand so its inner form can stretch
        page.grid_rowconfigure(0, weight=0)
        page.grid_rowconfigure(1, weight=1)
        page.grid_columnconfigure(0, weight=1)
        header = self.create_section_header(page, "Informations de la Société", "📝", 0, 0)
        self.societe_form = SocieteForm(page, self.values.get('societe', {}))
        self.societe_form.grid(row=1, column=0, sticky="nsew", padx=10, pady=10)
        self.pages.append(('societe', page, self.societe_form))

    def create_associe_page(self):
        page = ttk.Frame(self.forms_container)
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        # Expand the page so the associe list/canvas can grow
        page.grid_rowconfigure(0, weight=0)
        page.grid_rowconfigure(1, weight=1)
        page.grid_columnconfigure(0, weight=1)
        header = self.create_section_header(page, "Informations des Associés", "👥", 0, 0)
        # AssocieForm expects a ThemeManager instance
        self.associe_form = AssocieForm(page, self.theme_manager)
        self.associe_form.grid(row=1, column=0, sticky="nsew", padx=10, pady=10)
        # Defensive: remove any accidental scrollbar widgets that might have been
        # created inside the page by older code or third-party widgets. We want
        # a single scrollbar (the one on the main form canvas) to control
        # vertical scrolling for the whole page.
        for child in list(page.winfo_children()):
            try:
                if isinstance(child, ttk.Scrollbar):
                    child.destroy()
            except Exception:
                pass
        # Ensure exactly one initial associé form is present
        try:
            # add one initial associé if none exist yet
            if len(self.associe_form.associe_vars) == 0:
                self.associe_form.add_associe()
        except Exception:
            # conservative: ignore errors here to avoid breaking startup
            pass
        self.pages.append(('associes', page, self.associe_form))

    def create_contrat_page(self):
        page = ttk.Frame(self.forms_container)
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        # Expand the page so its inner form can stretch
        page.grid_rowconfigure(0, weight=0)
        page.grid_rowconfigure(1, weight=1)
        page.grid_columnconfigure(0, weight=1)
        header = self.create_section_header(page, "Informations du Contrat", "📋", 0, 0)
        self.contrat_form = ContratForm(page, self.values.get('contrat', {}))
        self.contrat_form.grid(row=1, column=0, sticky="nsew", padx=10, pady=10)
        self.pages.append(('contrat', page, self.contrat_form))

    def create_collapsible_section(self, title, form_creator):
        """Create a collapsible section with the given title and form"""
        section = ttk.Frame(self.forms_container)
        section.pack(fill="x", padx=20, pady=10)

        # Header with title and toggle button
        header = ttk.Frame(section)
        header.pack(fill="x", pady=5)

        title_label = ttk.Label(
            header,
            text=title,
            font=('Segoe UI', 12, 'bold'),
            foreground=self.theme_manager.colors['accent']
        )
        title_label.pack(side="left")

        # Container for the form
        content_frame = ttk.Frame(section)
        content_frame.pack(fill="x", pady=5)

        # Create the form
        form = form_creator(content_frame)
        form.pack(fill="x", expand=True)

        # Toggle button
        def toggle_section():
            if content_frame.winfo_viewable():
                content_frame.pack_forget()
                toggle_btn.configure(text="🔽")
            else:
                content_frame.pack(fill="x", pady=5)
                toggle_btn.configure(text="🔼")

        toggle_btn = WidgetFactory.create_button(
            header,
            text="🔼",
            command=toggle_section,
            tooltip="Afficher/Masquer la section"
        )
        toggle_btn.pack(side="right")

        return section

    def setup_navigation(self):
        """Create navigation controls for the wizard pages.

        Adds Previous, Next, Save and Finish buttons. Previous/Next navigate
        between created pages. Save stores the current page's values into
        `self.values`. Finish will save then emit an event `<<FormsFinished>>`.
        """
        # Navigation buttons are created at the app-level toolbar (in main.py)
        # to keep a single, unified control row. Here we only ensure nav button
        # states are initialized and keyboard shortcuts are bound.
        try:
            # Ensure attributes exist so update_nav_buttons can safely introspect
            if not hasattr(self, 'prev_btn'):
                self.prev_btn = None
            if not hasattr(self, 'next_btn'):
                self.next_btn = None
            if not hasattr(self, 'save_btn'):
                self.save_btn = None
            if not hasattr(self, 'finish_btn'):
                self.finish_btn = None
        except Exception:
            pass

        # Initialize nav button states if possible
        try:
            self.update_nav_buttons()
        except Exception:
            pass

        # Keyboard shortcuts on the top-level window
        try:
            top = self.winfo_toplevel()
            top.bind('<Left>', lambda e: self.prev_page())
            top.bind('<Right>', lambda e: self.next_page())
            top.bind('<Control-s>', lambda e: self.save_current())
        except Exception:
            pass

    def show_dashboard(self):
        """Switch to dashboard view"""
        from .dashboard_view import DashboardView
        dashboard = DashboardView(self.winfo_toplevel())  # Window is now modal by default

    def open_configuration(self):
        """Open a simple configuration dialog to change theme and other prefs."""
        try:
            top = tk.Toplevel(self.winfo_toplevel())
            top.transient(self.winfo_toplevel())
            top.title('Configuration')
            top.resizable(False, False)
            # modal
            try:
                top.grab_set()
            except Exception:
                pass

            inner = ttk.Frame(top, padding=12)
            inner.pack(fill='both', expand=True)

            # Theme selection
            ttk.Label(inner, text='Thème', style='Header.TLabel').pack(anchor='w', pady=(0, 6))
            theme_var = tk.StringVar(value=getattr(self.theme_manager.theme, 'mode', 'dark'))

            rb_dark = ttk.Radiobutton(inner, text='Sombre', variable=theme_var, value='dark')
            rb_light = ttk.Radiobutton(inner, text='Clair', variable=theme_var, value='light')
            rb_dark.pack(anchor='w')
            rb_light.pack(anchor='w')

            # Actions
            actions = ttk.Frame(inner)
            actions.pack(fill='x', pady=(12, 0))

            def _save():
                try:
                    chosen = theme_var.get()
                    self.theme_manager.set_theme(chosen)
                    messagebox.showinfo('Configuration', 'Préférences enregistrées.')
                    try:
                        top.destroy()
                    except Exception:
                        pass
                except Exception as e:
                    messagebox.showerror('Erreur', f"Impossible d'enregistrer: {e}")

            save_btn = WidgetFactory.create_button(actions, text='Enregistrer', command=_save, style='Action.TButton')
            save_btn.pack(side='right', padx=4)

            close_btn = WidgetFactory.create_button(actions, text='Fermer', command=lambda: top.destroy())
            close_btn.pack(side='right')

            # Center the dialog
            try:
                WindowManager.center_window(top)
            except Exception:
                pass
        except Exception:
            messagebox.showerror('Erreur', "Impossible d'ouvrir la configuration")

    def get_values(self):
        """Get values from all forms"""
        # Ensure latest current page is saved before returning
        try:
            # call get_values on each form present
            values = {}
            for key, _, form in self.pages:
                if hasattr(form, 'get_values'):
                    values[key] = form.get_values()
            self.values = values
        except Exception:
            # fallback to existing structure if any form is missing
            self.values = {
                'societe': getattr(self, 'societe_form', None) and self.societe_form.get_values(),
                'associes': getattr(self, 'associe_form', None) and self.associe_form.get_values(),
                'contrat': getattr(self, 'contrat_form', None) and self.contrat_form.get_values()
            }
        return self.values

    def set_values(self, values):
        """Set values for all forms"""
        self.values = values
        if values:
            for key, _, form in self.pages:
                if key in values and hasattr(form, 'set_values'):
                    form.set_values(values[key])

    def reset(self):
        """Reset all forms to their default state"""
        self.values = {}
        # Réinitialiser chaque formulaire individuellement
        for key, _, form in self.pages:
            if hasattr(form, 'set_values'):
                # default empty structure
                default = [] if key == 'associes' else {}
                form.set_values(default)

    # --- Navigation helpers ---
    def show_page(self, index: int):
        """Show the page at `index` and hide others."""
        if index < 0 or index >= len(self.pages):
            return
        self.current_page = index
        for i, (_key, frame, _form) in enumerate(self.pages):
            if i == index:
                frame.tkraise()
                frame.grid()
            else:
                frame.grid_remove()
        # After showing the requested page, allow the page/form to perform
        # any 'on-show' updates. This is useful for forms that need to
        # recompute derived fields (for example, the Contrat form that
        # recalculates 'Date Fin' from 'Date Début' + 'Période'). We look for
        # a public hook `recalculate_date_fin` first, then fall back to the
        # private `_update_date_fin` to preserve backward compatibility.
        try:
            key, frame, form = self.pages[index]
            hook = getattr(form, 'recalculate_date_fin', None) or getattr(form, '_update_date_fin', None)
            if callable(hook):
                try:
                    hook()
                except Exception:
                    # Non-fatal: ignore form-level errors to avoid breaking navigation
                    pass
        except Exception:
            # Conservative: ignore any problems with calling the hook
            pass

        self.update_nav_buttons()

    def next_page(self):
        if self.current_page < len(self.pages) - 1:
            self.show_page(self.current_page + 1)

    def prev_page(self):
        if self.current_page > 0:
            self.show_page(self.current_page - 1)

    def save_current(self):
        """Save current page values into `self.values`."""
        key, _frame, form = self.pages[self.current_page]
        if hasattr(form, 'get_values'):
            try:
                vals = form.get_values()

                # If we're saving the societe page, check for existing company name and forbid duplicates
                if key == 'societe':
                    try:
                        from ..utils.utils import societe_exists
                        name = vals.get('denomination') or vals.get('DEN_STE')
                        if name and societe_exists(name):
                            messagebox.showerror('Société existante', f"La société '{name}' existe déjà dans la base. Enregistrement interdit pour éviter les doublons.")
                            return
                    except Exception:
                        # If the check fails, log but allow save to proceed (conservative)
                        logger = __import__('logging').getLogger(__name__)
                        logger.exception('Failed to run societe_exists check')

                self.values[key] = vals
                messagebox.showinfo("Sauvegarde", f"Section '{key}' sauvegardée.")
            except Exception as e:
                messagebox.showerror("Erreur", f"Impossible de sauvegarder la section: {e}")

    def finish(self):
        """Save all pages and emit a finished event."""
        # Gather all values from forms (do this silently to avoid per-section popups)
        try:
            # get_values calls each form.get_values() and updates self.values
            all_values = self.get_values()
        except Exception:
            # Fallback: attempt to collect each form's values without showing dialogs
            try:
                values = {}
                for key, _, form in self.pages:
                    try:
                        values[key] = form.get_values() if hasattr(form, 'get_values') else {}
                    except Exception:
                        values[key] = {}
                self.values = values
                all_values = values
            except Exception:
                all_values = self.values
        # Ensure the Excel database exists and has expected sheets
        # Build the DB path explicitly to avoid static-analysis warnings about
        # PathManager.get_database_path. Use the centralized DB filename from
        # constants so the path is consistent.
        try:
            db_path = Path(PathManager.DATABASE_DIR) / _const.DB_FILENAME
        except Exception:
            db_path = Path.cwd() / _const.DB_FILENAME

        try:
            # Create the Excel DB using centralized headers from constants; ensure idempotent creation
            ensure_excel_db(db_path, _const.excel_sheets)
        except Exception as e:
            # non-fatal: log and show an error to the user
            try:
                from ..utils.utils import ErrorHandler
                ErrorHandler.handle_error(e, 'Erreur lors de la création de la base de données', show_dialog=True)
            except Exception:
                messagebox.showerror('Erreur', f"Impossible de créer la base de données: {e}")
            return

        # Now attempt to persist the collected values to the workbook by calling
        # the top-level application's save_to_db. This will collect values from
        # the forms and write them to the Excel file.
        try:
            top = self.winfo_toplevel()
            save_fn = getattr(top, 'save_to_db', None)
            saved_db = None
            if callable(save_fn):
                # save_to_db now returns the DB path on success (or None on failure)
                try:
                    saved_db = save_fn()
                except Exception:
                    # If the top-level save raised, surface a user-friendly message
                    saved_db = None
        except PermissionError as pe:
            # Common on Windows when the file is open in Excel
            messagebox.showerror('Erreur lors de la sauvegarde des données', 'Le fichier Excel est ouvert dans une autre application. Fermez Excel et réessayez.')
            return
        except Exception as e:
            try:
                from ..utils.utils import ErrorHandler
                ErrorHandler.handle_error(e, 'Erreur lors de la sauvegarde des données', show_dialog=True)
            except Exception:
                messagebox.showerror('Erreur', f"Erreur lors de la sauvegarde: {e}")
            return

        # If save was aborted or failed (saved_db is None), do not show success message
        if saved_db is None:
            # save_to_db already reports errors to the user where appropriate
            return

        # Present a single, clear success message (include the DB path when available)
        try:
            messagebox.showinfo("Sauvegarde réussie", f"Toutes les sections ont été sauvegardées dans le fichier Excel :\n{saved_db}")
        except Exception:
            # If messagebox fails for any reason, log and continue
            try:
                logger = __import__('logging').getLogger(__name__)
                logger.info('Sauvegarde terminée (message box failed to show)')
            except Exception:
                pass

        # Emit a virtual event so outer code can handle finalization if needed
        try:
            self.event_generate('<<FormsFinished>>')
        except Exception:
            pass

    def update_nav_buttons(self):
        # Disable Prev on first page, Next on last
        # Use explicit None checks so static analyzers (Pylance) know the
        # attribute is not None before calling widget methods.
        _prev = getattr(self, 'prev_btn', None)
        if _prev is not None:
            _prev.configure(state=('disabled' if self.current_page == 0 else 'normal'))

        _next = getattr(self, 'next_btn', None)
        if _next is not None:
            _next.configure(state=('disabled' if self.current_page == len(self.pages) - 1 else 'normal'))
