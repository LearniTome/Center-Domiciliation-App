import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from typing import Optional
import logging
import os
import subprocess
import sys
from contextlib import nullcontext
from .societe_form import SocieteForm
from .associe_form import AssocieForm
from .contrat_form import ContratForm
from ..utils.utils import ThemeManager, WidgetFactory, WindowManager, PathManager, ensure_excel_db
from ..utils import constants as _const
from pathlib import Path

logger = logging.getLogger(__name__)

class MainForm(ttk.Frame):
    def __init__(self, parent, values_dict=None, startup_profiler=None):
        super().__init__(parent)
        self.parent = parent
        self.values = values_dict or {}
        self.startup_profiler = startup_profiler

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
        with self._profile_scope("MainForm.setup_scrollable_container"):
            self.setup_scrollable_container()

        # Create forms
        with self._profile_scope("MainForm.setup_forms"):
            self.setup_forms()

    def _profile_scope(self, name: str):
        if self.startup_profiler is None:
            return nullcontext()
        try:
            return self.startup_profiler.span(name)
        except Exception:
            return nullcontext()

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
        with self._profile_scope("MainForm.create_societe_page"):
            self.create_societe_page()
        with self._profile_scope("MainForm.create_associe_page"):
            self.create_associe_page()
        with self._profile_scope("MainForm.create_contrat_page"):
            self.create_contrat_page()

        # Show first page
        with self._profile_scope("MainForm.show_page.initial"):
            self.show_page(0)

        # Setup navigation controls
        with self._profile_scope("MainForm.setup_navigation"):
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
        with self._profile_scope("SocieteForm.__init__"):
            self.societe_form = SocieteForm(page, self.theme_manager, self.values.get('societe', {}))
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
        with self._profile_scope("AssocieForm.__init__"):
            self.associe_form = AssocieForm(
                page,
                self.theme_manager,
                get_societe_totals=self._get_societe_totals_for_associes,
            )
        self.associe_form.grid(row=1, column=0, sticky="nsew", padx=10, pady=10)
        try:
            self.associe_form.bind_societe_totals_vars(
                getattr(self.societe_form, 'capital_var', None),
                getattr(self.societe_form, 'parts_social_var', None),
            )
        except Exception:
            pass
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
                with self._profile_scope("AssocieForm.add_initial_associe"):
                    self.associe_form.add_associe()
        except Exception:
            # conservative: ignore errors here to avoid breaking startup
            pass
        self.pages.append(('associes', page, self.associe_form))

    def _get_societe_totals_for_associes(self):
        """Return company capital/parts totals for associate distribution."""
        try:
            if hasattr(self, 'societe_form') and self.societe_form is not None:
                cap = self.societe_form.capital_var.get() if hasattr(self.societe_form, 'capital_var') else ''
                parts = self.societe_form.parts_social_var.get() if hasattr(self.societe_form, 'parts_social_var') else ''
                return cap, parts
        except Exception:
            pass
        return '', ''

    def create_contrat_page(self):
        page = ttk.Frame(self.forms_container)
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        # Expand the page so its inner form can stretch
        page.grid_rowconfigure(0, weight=0)
        page.grid_rowconfigure(1, weight=1)
        page.grid_columnconfigure(0, weight=1)
        header = self.create_section_header(page, "Informations du Contrat", "📋", 0, 0)
        with self._profile_scope("ContratForm.__init__"):
            self.contrat_form = ContratForm(page, self.theme_manager, self.values.get('contrat', {}))
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
            style='Secondary.TButton',
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
        """Open a lightweight configuration launcher with two actions."""
        try:
            top = tk.Toplevel(self.winfo_toplevel())
            top.transient(self.winfo_toplevel())
            top.title("Configuration")
            top.geometry("420x220")
            top.resizable(False, False)

            try:
                top.grab_set()
            except Exception:
                pass

            main_frame = ttk.Frame(top, padding=14)
            main_frame.pack(fill="both", expand=True)

            ttk.Label(
                main_frame,
                text="⚙ Configuration",
                font=("Segoe UI", 12, "bold"),
            ).pack(anchor="w", pady=(0, 8))
            ttk.Label(
                main_frame,
                text="Choisissez le module à ouvrir :",
            ).pack(anchor="w", pady=(0, 10))

            actions = ttk.Frame(main_frame)
            actions.pack(fill="x", expand=True)

            def _open_defaults():
                try:
                    top.destroy()
                except Exception:
                    pass
                self._open_defaults_dialog()

            def _open_analyzer():
                try:
                    top.destroy()
                except Exception:
                    pass
                self._open_template_values_analyzer_dialog()

            WidgetFactory.create_button(
                actions,
                text="⚙ Valeurs par défaut",
                command=_open_defaults,
                style="Success.TButton",
            ).pack(fill="x", pady=(0, 8))

            WidgetFactory.create_button(
                actions,
                text="📊 Analyse des valeurs templates",
                command=_open_analyzer,
                style="Refresh.TButton",
            ).pack(fill="x", pady=(0, 8))

            buttons = ttk.Frame(main_frame)
            buttons.pack(fill="x")
            WidgetFactory.create_button(
                buttons,
                text="❌ Fermer",
                command=top.destroy,
                style="Close.TButton",
            ).pack(side="right")

            WindowManager.center_window(top)
        except Exception as e:
            logger.exception("Erreur lors de l'ouverture de la configuration")
            messagebox.showerror("Erreur", f"Impossible d'ouvrir la configuration: {e}")

    def _open_defaults_dialog(self):
        """Open configuration dialog to manage default values for the entire application."""
        try:
            from ..utils.defaults_manager import get_defaults_manager
            from ..utils import constants
            
            top = tk.Toplevel(self.winfo_toplevel())
            top.transient(self.winfo_toplevel())
            top.title('Configuration - Valeurs par défaut')
            top.geometry('700x550')
            top.resizable(True, True)
            
            # Apply theme to the dialog window
            theme_manager = self.theme_manager
            bg_color = theme_manager.colors.get('bg', '#2b2b2b')
            fg_color = theme_manager.colors.get('fg', '#ffffff')
            top.configure(bg=bg_color)
            
            # Make modal
            try:
                top.grab_set()
            except Exception:
                pass

            # Get defaults manager
            defaults_mgr = get_defaults_manager()
            current_defaults = defaults_mgr.get_all_defaults()

            # Main container with notebook (tabs)
            main_frame = ttk.Frame(top)
            main_frame.pack(fill='both', expand=True, padx=10, pady=10)

            # Title
            title_label = ttk.Label(main_frame, text='⚙ Gestion des valeurs par défaut', 
                                   font=('Segoe UI', 12, 'bold'))
            title_label.pack(anchor='w', pady=(0, 10))

            # Create notebook for tabs
            notebook = ttk.Notebook(main_frame)
            notebook.pack(fill='both', expand=True, pady=(0, 10))

            # Dictionary to hold all entry widgets for saving
            entry_vars = {}

            # Create a tab for each section (societe, associe, contrat)
            sections_config = {
                'societe': {
                    'label': '🏢 Entreprise',
                    'fields': [
                        ('DenSte', 'Dénomination sociale', constants.DenSte),
                        ('FormJur', 'Forme juridique', constants.Formjur),
                        ('Capital', 'Capital', constants.Capital),
                        ('PartsSocial', 'Parts sociales', constants.PartsSocial),
                        ('ValeurNominale', 'Valeur nominale', ['100']),
                        ('SteAdresse', 'Adresse', constants.SteAdresse),
                        ('Tribunal', 'Tribunal', constants.Tribunnaux),
                    ]
                },
                'associe': {
                    'label': '👤 Associé',
                    'fields': [
                        ('Civility', 'Civilité', constants.Civility),
                        ('Nationality', 'Nationalité', constants.Nationalite),
                        ('Quality', 'Qualité', constants.QualityGerant),
                    ]
                },
                'contrat': {
                    'label': '📋 Contrat',
                    'fields': [
                        ('NbMois', 'Période (mois)', constants.Nbmois),
                    ]
                }
            }

            for section_key, section_info in sections_config.items():
                # Create frame for this tab
                tab_frame = ttk.Frame(notebook)
                notebook.add(tab_frame, text=section_info['label'])

                # Create scrollable area
                canvas = tk.Canvas(tab_frame, highlightthickness=0, bg=bg_color)
                scrollbar = ttk.Scrollbar(tab_frame, orient='vertical', command=canvas.yview)
                scrollable_frame = ttk.Frame(canvas)

                scrollable_frame.bind(
                    "<Configure>",
                    lambda _event, c=canvas: c.configure(scrollregion=c.bbox("all"))
                )

                canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
                canvas.configure(yscrollcommand=scrollbar.set)

                canvas.pack(side='left', fill='both', expand=True)
                scrollbar.pack(side='right', fill='y')

                # Add fields for this section
                entry_vars[section_key] = {}
                
                for field_key, field_label, field_options in section_info['fields']:
                    field_frame = ttk.Frame(scrollable_frame)
                    field_frame.pack(fill='x', pady=8, padx=5)

                    ttk.Label(field_frame, text=f'{field_label}:', width=20, anchor='w').pack(side='left', padx=(0, 10))
                    
                    # Get current value
                    current_value = current_defaults.get(section_key, {}).get(field_key, '')
                    
                    # Create StringVar for this field
                    var = tk.StringVar(value=str(current_value))
                    entry_vars[section_key][field_key] = var
                    
                    # Create combobox with options (if available) or entry widget
                    if field_options and len(field_options) > 0:
                        combo = ttk.Combobox(field_frame, textvariable=var, values=field_options, 
                                           width=45, state='readonly')
                        combo.pack(side='left', fill='x', expand=True)
                    else:
                        # Fallback to entry if no options
                        entry = ttk.Entry(field_frame, textvariable=var, width=45)
                        entry.pack(side='left', fill='x', expand=True)

            # Button frame at bottom
            button_frame = ttk.Frame(main_frame)
            button_frame.pack(fill='x', pady=(10, 0))

            def _save():
                try:
                    # Collect all values from tabs
                    new_defaults = {}
                    for section_key, fields in entry_vars.items():
                        new_defaults[section_key] = {}
                        for field_key, var in fields.items():
                            new_defaults[section_key][field_key] = var.get()
                    
                    # Save to defaults manager
                    defaults_mgr.set_all_defaults(new_defaults)
                    
                    try:
                        self._on_defaults_changed()
                    except Exception:
                        pass
                    if hasattr(self.parent, '_on_defaults_changed'):
                        self.parent._on_defaults_changed()
                    
                    messagebox.showinfo('Configuration', 'Valeurs par défaut enregistrées avec succès.')
                    try:
                        top.destroy()
                    except Exception:
                        pass
                except Exception as e:
                    messagebox.showerror('Erreur', f"Impossible d'enregistrer: {e}")

            def _reset():
                """Reset to initial defaults."""
                if messagebox.askyesno('Confirmation', 'Réinitialiser tous les défauts?\n\nCette action ne peut pas être annulée.'):
                    try:
                        defaults_mgr.reset_to_initial()
                        # Reload values
                        current_defaults = defaults_mgr.get_all_defaults()
                        for section_key, fields in entry_vars.items():
                            for field_key, var in fields.items():
                                new_val = current_defaults.get(section_key, {}).get(field_key, '')
                                var.set(str(new_val))
                        
                        try:
                            self._on_defaults_changed()
                        except Exception:
                            pass
                        if hasattr(self.parent, '_on_defaults_changed'):
                            self.parent._on_defaults_changed()
                        
                        messagebox.showinfo('Configuration', 'Défauts réinitialisés aux valeurs initiales.')
                    except Exception as e:
                        messagebox.showerror('Erreur', f"Impossible de réinitialiser: {e}")

            # Buttons
            save_btn = WidgetFactory.create_button(button_frame, text='💾 Enregistrer', 
                                                 command=_save, style='Success.TButton')
            save_btn.pack(side='right', padx=4)

            reset_btn = WidgetFactory.create_button(button_frame, text='🔄 Réinitialiser', 
                                                   command=_reset, style='Secondary.TButton')
            reset_btn.pack(side='right', padx=4)

            close_btn = WidgetFactory.create_button(button_frame, text='❌ Fermer', 
                                                  command=lambda: top.destroy(), style='Close.TButton')
            close_btn.pack(side='right', padx=4)

            # Center the dialog
            try:
                from ..utils.utils import WindowManager
                WindowManager.center_window(top)
            except Exception:
                pass
        except Exception as e:
            logger.exception('Erreur lors de l\'ouverture de la configuration')
            messagebox.showerror('Erreur', f"Impossible d'ouvrir la configuration: {e}")

    def _open_template_values_analyzer_dialog(self):
        """Open template variable analyzer with global and detailed views."""
        try:
            from ..utils.doc_generator import get_expected_context_keys
            from ..utils.template_value_analyzer import (
                analyze_templates,
                export_analysis_rows,
                filter_analysis_rows,
            )

            top = tk.Toplevel(self.winfo_toplevel())
            top.transient(self.winfo_toplevel())
            top.title("Configuration - Analyse des valeurs templates")
            top.geometry("1200x760")
            top.resizable(True, True)

            try:
                top.grab_set()
            except Exception:
                pass

            main_frame = ttk.Frame(top, padding=10)
            main_frame.pack(fill="both", expand=True)
            main_frame.columnconfigure(0, weight=1)
            main_frame.rowconfigure(3, weight=1)

            ttk.Label(
                main_frame,
                text="📊 Analyse des valeurs templates",
                font=("Segoe UI", 12, "bold"),
            ).grid(row=0, column=0, sticky="w", pady=(0, 8))

            filter_frame = ttk.Frame(main_frame)
            filter_frame.grid(row=1, column=0, sticky="ew", pady=(0, 8))
            for idx in range(8):
                filter_frame.columnconfigure(idx, weight=1 if idx % 2 else 0)
            filter_frame.columnconfigure(8, weight=0)

            search_var = tk.StringVar()
            template_var = tk.StringVar(value="Tous")
            section_var = tk.StringVar(value="Tous")
            coverage_var = tk.StringVar(value="Tous")
            status_var = tk.StringVar(value="")
            errors_var = tk.StringVar(value="")

            ttk.Label(filter_frame, text="Recherche:").grid(row=0, column=0, sticky="w", padx=(0, 6))
            search_entry = ttk.Entry(filter_frame, textvariable=search_var)
            search_entry.grid(row=0, column=1, sticky="ew", padx=(0, 10))

            ttk.Label(filter_frame, text="Template:").grid(row=0, column=2, sticky="w", padx=(0, 6))
            template_combo = ttk.Combobox(filter_frame, textvariable=template_var, state="readonly", values=["Tous"])
            template_combo.grid(row=0, column=3, sticky="ew", padx=(0, 10))

            ttk.Label(filter_frame, text="Section:").grid(row=0, column=4, sticky="w", padx=(0, 6))
            section_combo = ttk.Combobox(
                filter_frame,
                textvariable=section_var,
                state="readonly",
                values=["Tous", "societe", "associe", "contrat", "autre"],
            )
            section_combo.grid(row=0, column=5, sticky="ew", padx=(0, 10))

            ttk.Label(filter_frame, text="Couverture:").grid(row=0, column=6, sticky="w", padx=(0, 6))
            coverage_combo = ttk.Combobox(
                filter_frame,
                textvariable=coverage_var,
                state="readonly",
                values=["Tous", "couvert", "non couvert"],
            )
            coverage_combo.grid(row=0, column=7, sticky="ew", padx=(0, 10))

            actions = ttk.Frame(filter_frame)
            actions.grid(row=0, column=8, sticky="e")

            notebook = ttk.Notebook(main_frame)
            notebook.grid(row=3, column=0, sticky="nsew", pady=(0, 8))
            global_tab = ttk.Frame(notebook)
            detail_tab = ttk.Frame(notebook)
            notebook.add(global_tab, text="Vue globale")
            notebook.add(detail_tab, text="Vue détaillée")

            for frame in (global_tab, detail_tab):
                frame.columnconfigure(0, weight=1)
                frame.rowconfigure(0, weight=1)

            global_cols = ("variable", "occurrences", "templates_count", "section", "coverage")
            detail_cols = ("template", "variable", "occurrences", "section", "coverage")

            def _create_tree(parent, columns, headings):
                container = ttk.Frame(parent)
                container.grid(row=0, column=0, sticky="nsew")
                container.columnconfigure(0, weight=1)
                container.rowconfigure(0, weight=1)

                tree = ttk.Treeview(container, columns=columns, show="headings")
                y_scroll = ttk.Scrollbar(container, orient="vertical", command=tree.yview)
                x_scroll = ttk.Scrollbar(container, orient="horizontal", command=tree.xview)
                tree.configure(yscrollcommand=y_scroll.set, xscrollcommand=x_scroll.set)

                tree.grid(row=0, column=0, sticky="nsew")
                y_scroll.grid(row=0, column=1, sticky="ns")
                x_scroll.grid(row=1, column=0, sticky="ew")

                sort_state = {col: False for col in columns}

                def _sort_by(col_name):
                    reverse = sort_state.get(col_name, False)
                    sort_state[col_name] = not reverse
                    rows = [(tree.set(item_id, col_name), item_id) for item_id in tree.get_children("")]

                    def _coerce(value):
                        txt = str(value).replace(" ", "").replace(",", ".")
                        try:
                            return float(txt)
                        except Exception:
                            return str(value).lower()

                    rows.sort(key=lambda item: _coerce(item[0]), reverse=reverse)
                    for idx, (_, item_id) in enumerate(rows):
                        tree.move(item_id, "", idx)

                for col_name, heading in zip(columns, headings):
                    tree.heading(col_name, text=heading, command=lambda c=col_name: _sort_by(c))
                    tree.column(col_name, width=220 if col_name in ("variable", "template") else 130, anchor="w")
                return tree

            global_tree = _create_tree(
                global_tab,
                global_cols,
                ("Variable", "Occurrences", "Nb templates", "Section", "Couverture"),
            )
            detail_tree = _create_tree(
                detail_tab,
                detail_cols,
                ("Template", "Variable", "Occurrences", "Section", "Couverture"),
            )

            kpi_frame = ttk.Frame(main_frame)
            kpi_frame.grid(row=2, column=0, sticky="ew", pady=(0, 8))
            for idx in range(5):
                kpi_frame.columnconfigure(idx, weight=1)

            total_templates_var = tk.StringVar(value="Templates: 0")
            total_occurrences_var = tk.StringVar(value="Occurrences: 0")
            total_distinct_var = tk.StringVar(value="Variables distinctes: 0")
            total_covered_var = tk.StringVar(value="Couvertes: 0")
            total_uncovered_var = tk.StringVar(value="Non couvertes: 0")

            ttk.Label(kpi_frame, textvariable=total_templates_var).grid(row=0, column=0, sticky="w")
            ttk.Label(kpi_frame, textvariable=total_occurrences_var).grid(row=0, column=1, sticky="w")
            ttk.Label(kpi_frame, textvariable=total_distinct_var).grid(row=0, column=2, sticky="w")
            ttk.Label(kpi_frame, textvariable=total_covered_var).grid(row=0, column=3, sticky="w")
            ttk.Label(kpi_frame, textvariable=total_uncovered_var).grid(row=0, column=4, sticky="w")

            footer = ttk.Frame(main_frame)
            footer.grid(row=4, column=0, sticky="ew")
            footer.columnconfigure(0, weight=1)
            ttk.Label(footer, textvariable=status_var).grid(row=0, column=0, sticky="w")
            ttk.Label(footer, textvariable=errors_var, foreground="#c5865d").grid(row=1, column=0, sticky="w")

            analysis_data = {"variables": [], "details": [], "summary": {}, "templates": [], "errors": []}
            current_global_rows = []
            current_detail_rows = []
            detail_item_map = {}

            def _populate_tree(tree, rows, columns, item_map=None):
                for item_id in tree.get_children(""):
                    tree.delete(item_id)
                if item_map is not None:
                    item_map.clear()
                for row in rows:
                    item_id = tree.insert("", "end", values=[row.get(col, "") for col in columns])
                    if item_map is not None:
                        item_map[item_id] = row

            def _apply_filters(_event=None):
                nonlocal current_global_rows, current_detail_rows
                global_rows = filter_analysis_rows(
                    analysis_data.get("variables", []),
                    search_text=search_var.get(),
                    template_name=template_var.get(),
                    section=section_var.get(),
                    coverage=coverage_var.get(),
                )
                detail_rows = filter_analysis_rows(
                    analysis_data.get("details", []),
                    search_text=search_var.get(),
                    template_name=template_var.get(),
                    section=section_var.get(),
                    coverage=coverage_var.get(),
                )
                current_global_rows = global_rows
                current_detail_rows = detail_rows
                _populate_tree(global_tree, current_global_rows, global_cols)
                _populate_tree(detail_tree, current_detail_rows, detail_cols, item_map=detail_item_map)
                status_var.set(
                    f"Global: {len(global_rows)} / {len(analysis_data.get('variables', []))} | "
                    f"Détail: {len(detail_rows)} / {len(analysis_data.get('details', []))}"
                )

            def _export_current_view(default_ext: str):
                if notebook.index(notebook.select()) == 0:
                    rows = current_global_rows
                    title = "vue_globale"
                    export_columns = (
                        "variable",
                        "occurrences",
                        "templates_count",
                        "section",
                        "coverage",
                        "templates",
                    )
                else:
                    rows = current_detail_rows
                    title = "vue_detaillee"
                    export_columns = (
                        "template",
                        "template_path",
                        "variable",
                        "occurrences",
                        "section",
                        "coverage",
                    )

                if not rows:
                    messagebox.showwarning("Export", "Aucune ligne à exporter pour la vue courante.")
                    return

                filename = filedialog.asksaveasfilename(
                    title="Exporter l'analyse",
                    defaultextension=default_ext,
                    filetypes=[
                        ("Fichier Excel", "*.xlsx"),
                        ("Fichier CSV", "*.csv"),
                    ],
                    initialfile=f"analyse_templates_{title}{default_ext}",
                )
                if not filename:
                    return
                try:
                    export_analysis_rows(rows, filename, columns=export_columns)
                    messagebox.showinfo("Export", f"Export terminé:\n{filename}")
                except Exception as exc:
                    logger.exception("Erreur lors de l'export de l'analyse templates")
                    messagebox.showerror("Erreur", f"Impossible d'exporter: {exc}")

            def _open_selected_template():
                selected = detail_tree.selection()
                if not selected:
                    messagebox.showwarning("Ouvrir template", "Sélectionnez d'abord une ligne dans la vue détaillée.")
                    return
                row = detail_item_map.get(selected[0]) or {}
                template_path_value = row.get("template_path")
                template_name = row.get("template")

                candidate = Path(template_path_value) if template_path_value else None
                if not candidate or not candidate.exists():
                    if template_name:
                        matches = sorted(PathManager.MODELS_DIR.rglob(str(template_name)))
                        if not matches:
                            messagebox.showerror(
                                "Ouvrir template",
                                f"Template introuvable pour '{template_name}'.",
                            )
                            return
                        candidate = matches[0]

                try:
                    self._open_path_in_system(candidate)
                except Exception as exc:
                    logger.exception("Erreur lors de l'ouverture du template")
                    messagebox.showerror("Erreur", f"Impossible d'ouvrir le template: {exc}")

            def _refresh_analysis():
                try:
                    data = analyze_templates(
                        PathManager.MODELS_DIR,
                        context_keys=get_expected_context_keys(),
                    )
                    analysis_data.update(data)

                    summary = data.get("summary", {})
                    total_templates_var.set(f"Templates: {summary.get('total_templates', 0)}")
                    total_occurrences_var.set(f"Occurrences: {summary.get('total_variable_occurrences', 0)}")
                    total_distinct_var.set(f"Variables distinctes: {summary.get('total_distinct_variables', 0)}")
                    total_covered_var.set(f"Couvertes: {summary.get('covered_variables', 0)}")
                    total_uncovered_var.set(f"Non couvertes: {summary.get('uncovered_variables', 0)}")

                    template_values = ["Tous"] + sorted(set(data.get("templates", [])))
                    template_combo.configure(values=template_values)
                    if template_var.get() not in template_values:
                        template_var.set("Tous")

                    if section_var.get() not in ("Tous", "societe", "associe", "contrat", "autre"):
                        section_var.set("Tous")
                    if coverage_var.get() not in ("Tous", "couvert", "non couvert"):
                        coverage_var.set("Tous")

                    error_count = len(data.get("errors", []))
                    if error_count:
                        errors_var.set(f"⚠ {error_count} template(s) non analysé(s). Voir logs.")
                    else:
                        errors_var.set("")

                    _apply_filters()
                except Exception as exc:
                    logger.exception("Erreur lors de l'actualisation de l'analyse templates")
                    messagebox.showerror("Erreur", f"Impossible d'analyser les templates: {exc}")

            WidgetFactory.create_button(
                actions,
                text="🔄 Actualiser",
                command=_refresh_analysis,
                style="Refresh.TButton",
            ).pack(side="left", padx=(0, 6))
            WidgetFactory.create_button(
                actions,
                text="📤 Export CSV",
                command=lambda: _export_current_view(".csv"),
                style="Secondary.TButton",
            ).pack(side="left", padx=(0, 6))
            WidgetFactory.create_button(
                actions,
                text="📗 Export Excel",
                command=lambda: _export_current_view(".xlsx"),
                style="Secondary.TButton",
            ).pack(side="left", padx=(0, 6))
            WidgetFactory.create_button(
                actions,
                text="📂 Ouvrir template",
                command=_open_selected_template,
                style="View.TButton",
            ).pack(side="left", padx=(0, 6))
            WidgetFactory.create_button(
                actions,
                text="❌ Fermer",
                command=top.destroy,
                style="Close.TButton",
            ).pack(side="left")

            search_entry.bind("<KeyRelease>", _apply_filters)
            template_var.trace_add("write", lambda *_args: _apply_filters())
            section_var.trace_add("write", lambda *_args: _apply_filters())
            coverage_var.trace_add("write", lambda *_args: _apply_filters())

            _refresh_analysis()
            WindowManager.center_window(top)
        except Exception as e:
            logger.exception("Erreur lors de l'ouverture de l'analyse des valeurs templates")
            messagebox.showerror("Erreur", f"Impossible d'ouvrir l'analyse: {e}")

    @staticmethod
    def _open_path_in_system(path: Path):
        """Open a file/folder with the system default application."""
        p = Path(path)
        if not p.exists():
            raise FileNotFoundError(f"Chemin introuvable: {p}")

        if os.name == "nt":
            os.startfile(str(p))  # type: ignore[attr-defined]
            return
        if sys.platform == "darwin":
            subprocess.run(["open", str(p)], check=False)
            return
        subprocess.run(["xdg-open", str(p)], check=False)

    def _on_defaults_changed(self):
        """Recharger les formulaires quand les défauts changent."""
        try:
            # Recharger les valeurs par défaut dans les formulaires existants
            if hasattr(self, 'societe_form') and self.societe_form:
                self.societe_form.initialize_variables()
            
            if hasattr(self, 'associe_form') and self.associe_form:
                # Pour AssocieForm, on réinitialise les vars pour les nouveaux associés
                # Les associés existants gardent leurs valeurs
                pass
            
            if hasattr(self, 'contrat_form') and self.contrat_form:
                self.contrat_form.initialize_variables()
            
            logger.info("Formulaires rechargés avec les nouvelles valeurs par défaut")
        except Exception as e:
            logger.exception('Erreur lors du rechargement des formulaires après changement de défauts')

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

                if key == 'associes' and hasattr(form, 'validate_for_submit'):
                    valid, _errors = form.validate_for_submit(show_dialog=True)
                    if not valid:
                        return

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
            # Initialize reference sheets with default data from constants
            from ..utils.utils import initialize_reference_sheets
            initialize_reference_sheets(db_path)
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

    def handle_dashboard_action(self, action: str, payload: dict | None):
        """Handle actions coming from the DashboardView.

        Supported actions:
        - 'add'    : clear forms and show the societe page for a new entry
        - 'edit'   : prefill forms with the provided payload (company row dict) and show the societe page
        - 'delete' : remove the company (and related rows) from the Excel DB after confirmation
        - other    : ignored
        """
        try:
            # Bring main window to front
            top = self.winfo_toplevel()
            try:
                top.deiconify()
                top.lift()
                top.focus_force()
            except Exception:
                pass

            if action == 'add':
                # Reset forms to default/new state and show first page
                self.reset()
                self.show_page(0)
                return

            if action == 'edit':
                if not payload:
                    messagebox.showwarning('Modifier', 'Aucune donnée fournie pour modification.')
                    return

                # Map canonical DB fields back to form keys (reverse of write_records_to_db mapping)
                soc_map = {
                    'DEN_STE': 'denomination', 'FORME_JUR': 'forme_juridique', 'ICE': 'ice',
                    'DATE_ICE': 'date_ice', 'DATE_EXP_CERT_NEG': 'date_expiration_certificat_negatif',
                    'CAPITAL': 'capital', 'PART_SOCIAL': 'parts_social', 'VALEUR_NOMINALE': 'valeur_nominale',
                    'STE_ADRESS': 'adresse', 'TRIBUNAL': 'tribunal'
                }
                soc_vals = {}
                for k, v in (payload.items() if isinstance(payload, dict) else []):
                    if k in soc_map:
                        soc_vals[soc_map[k]] = v

                # Attempt to also load associes and contrats from the workbook if possible
                associes_list = []
                contrat_vals = {}
                try:
                    import pandas as _pd
                    db_path = Path(PathManager.DATABASE_DIR) / _const.DB_FILENAME
                    if db_path.exists():
                        try:
                            assoc_df = _pd.read_excel(db_path, sheet_name='Associes', dtype=str).fillna('')
                        except Exception:
                            assoc_df = _pd.DataFrame()
                        try:
                            contrat_df = _pd.read_excel(db_path, sheet_name='Contrats', dtype=str).fillna('')
                        except Exception:
                            contrat_df = _pd.DataFrame()

                        # Prefer matching by ID_SOCIETE when available
                        sid = None
                        if isinstance(payload, dict):
                            sid = payload.get('ID_SOCIETE') or payload.get('ID_SOCIETE')
                        if sid and not assoc_df.empty and 'ID_SOCIETE' in assoc_df.columns:
                            matches = assoc_df[assoc_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip()]
                        else:
                            den = (payload.get('DEN_STE') or '').strip() if isinstance(payload, dict) else ''
                            if not assoc_df.empty and 'DEN_STE' in assoc_df.columns:
                                matches = assoc_df[assoc_df['DEN_STE'].astype(str).str.strip().str.lower() == den.lower()]
                            else:
                                matches = _pd.DataFrame()

                        if not matches.empty:
                            # inverse mapping from canonical DB headers to AssocieForm keys
                            inverse_assoc_map = {
                                'CIVIL': 'civilite', 'PRENOM': 'prenom', 'NOM': 'nom',
                                'PARTS': 'num_parts', 'DATE_NAISS': 'date_naiss', 'LIEU_NAISS': 'lieu_naiss',
                                'NATIONALITY': 'nationalite', 'CIN_NUM': 'num_piece', 'CIN_VALIDATY': 'validite_piece',
                                'ADRESSE': 'adresse', 'PHONE': 'telephone', 'EMAIL': 'email',
                                'IS_GERANT': 'est_gerant', 'QUALITY': 'qualite', 'CAPITAL_DETENU': 'capital_detenu',
                                'PART_PERCENT': 'percentage'
                            }
                            for _, ar in matches.iterrows():
                                ad = {}
                                for col in ar.index:
                                    if col in inverse_assoc_map:
                                        ad[inverse_assoc_map[col]] = ar.get(col)
                                associes_list.append(ad)

                        # take first contrat row if present
                        if not contrat_df.empty:
                            if sid and 'ID_SOCIETE' in contrat_df.columns:
                                cands = contrat_df[contrat_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip()]
                            else:
                                den = (payload.get('DEN_STE') or '').strip() if isinstance(payload, dict) else ''
                                if not contrat_df.empty and 'DEN_STE' in contrat_df.columns:
                                    cands = contrat_df[contrat_df['DEN_STE'].astype(str).str.strip().str.lower() == den.lower()]
                                else:
                                    cands = _pd.DataFrame()
                            if not cands.empty:
                                crow = cands.iloc[0]
                                inverse_contrat_map = {
                                    'DATE_CONTRAT': 'date_contrat',
                                    'DUREE_CONTRAT_MOIS': 'period',
                                    'TYPE_CONTRAT_DOMICILIATION': 'type_contrat_domiciliation',
                                    'TYPE_CONTRAT_DOMICILIATION_AUTRE': 'type_contrat_domiciliation_autre',
                                    'LOYER_MENSUEL_TTC': 'prix_mensuel',
                                    'FRAIS_INTERMEDIAIRE_CONTRAT': 'prix_inter',
                                    'DATE_DEBUT_CONTRAT': 'date_debut',
                                    'DATE_FIN_CONTRAT': 'date_fin',
                                    'TAUX_TVA_POURCENT': 'tva',
                                    'LOYER_MENSUEL_HT': 'dh_ht',
                                    'MONTANT_TOTAL_HT_CONTRAT': 'montant_ht',
                                    'MONTANT_PACK_DEMARRAGE_TTC': 'pack_demarrage_montant',
                                    'LOYER_MENSUEL_PACK_DEMARRAGE_TTC': 'pack_demarrage_loyer',
                                    'TYPE_RENOUVELLEMENT': 'type_renouvellement',
                                    'TAUX_TVA_RENOUVELLEMENT_POURCENT': 'tva_renouvellement',
                                    'LOYER_MENSUEL_HT_RENOUVELLEMENT': 'dh_ht_renouvellement',
                                    'MONTANT_TOTAL_HT_RENOUVELLEMENT': 'montant_ht_renouvellement',
                                    'LOYER_MENSUEL_RENOUVELLEMENT_TTC': 'loyer_renouvellement_mensuel',
                                    'LOYER_ANNUEL_RENOUVELLEMENT_TTC': 'loyer_renouvellement_annuel',
                                    # legacy columns fallback
                                    'PERIOD_DOMCIL': 'period',
                                    'PRIX_CONTRAT': 'prix_mensuel',
                                    'PRIX_INTERMEDIARE_CONTRAT': 'prix_inter',
                                    'DOM_DATEDEB': 'date_debut',
                                    'DOM_DATEFIN': 'date_fin',
                                    'PACK_DEMARRAGE_MONTANT_TTC': 'pack_demarrage_montant',
                                    'PACK_DEMARRAGE_LOYER_MENSUEL_TTC': 'pack_demarrage_loyer',
                                    'LOYER_RENOUVELLEMENT_MENSUEL_TTC': 'loyer_renouvellement_mensuel',
                                    'LOYER_RENOUVELLEMENT_ANNUEL_TTC': 'loyer_renouvellement_annuel',
                                }
                                for col in crow.index:
                                    if col in inverse_contrat_map:
                                        contrat_vals[inverse_contrat_map[col]] = crow.get(col)
                except Exception:
                    # ignore data load errors; fallback to partial prefill
                    pass

                # Apply values to forms and show societe page for editing
                values = {'societe': soc_vals or {}, 'associes': associes_list, 'contrat': contrat_vals}
                self.set_values(values)
                self.show_page(0)
                return

            if action == 'delete':
                if not payload:
                    messagebox.showwarning('Supprimer', 'Aucune société sélectionnée pour suppression.')
                    return
                den = payload.get('DEN_STE') or ''
                sid = payload.get('ID_SOCIETE') if isinstance(payload, dict) else None
                if not messagebox.askyesno('Confirmation', f"Voulez-vous vraiment supprimer la société '{den}' ?"):
                    return

                # Remove rows from the Excel workbook
                try:
                    import pandas as _pd
                    db_path = Path(PathManager.DATABASE_DIR) / _const.DB_FILENAME
                    if not db_path.exists():
                        messagebox.showerror('Erreur', 'Fichier de base de données introuvable.')
                        return

                    # Read canonical sheets
                    try:
                        soc_df = _pd.read_excel(db_path, sheet_name='Societes', dtype=str).fillna('')
                    except Exception:
                        soc_df = _pd.DataFrame()
                    try:
                        assoc_df = _pd.read_excel(db_path, sheet_name='Associes', dtype=str).fillna('')
                    except Exception:
                        assoc_df = _pd.DataFrame()
                    try:
                        contrat_df = _pd.read_excel(db_path, sheet_name='Contrats', dtype=str).fillna('')
                    except Exception:
                        contrat_df = _pd.DataFrame()

                    if sid:
                        if not soc_df.empty and 'ID_SOCIETE' in soc_df.columns:
                            soc_df = soc_df[~(soc_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip())]
                        if not assoc_df.empty and 'ID_SOCIETE' in assoc_df.columns:
                            assoc_df = assoc_df[~(assoc_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip())]
                        if not contrat_df.empty and 'ID_SOCIETE' in contrat_df.columns:
                            contrat_df = contrat_df[~(contrat_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip())]
                    else:
                        # fallback to DEN_STE match
                        if not soc_df.empty and 'DEN_STE' in soc_df.columns:
                            soc_df = soc_df[~(soc_df['DEN_STE'].astype(str).str.strip().str.lower() == den.strip().lower())]
                        if not assoc_df.empty and 'DEN_STE' in assoc_df.columns:
                            assoc_df = assoc_df[~(assoc_df['DEN_STE'].astype(str).str.strip().str.lower() == den.strip().lower())]
                        if not contrat_df.empty and 'DEN_STE' in contrat_df.columns:
                            contrat_df = contrat_df[~(contrat_df['DEN_STE'].astype(str).str.strip().str.lower() == den.strip().lower())]

                    # Write back sheets replacing them
                    try:
                        with _pd.ExcelWriter(db_path, engine='openpyxl', mode='a', if_sheet_exists='replace') as writer:
                            soc_df.to_excel(writer, sheet_name='Societes', index=False)
                            assoc_df.to_excel(writer, sheet_name='Associes', index=False)
                            contrat_df.to_excel(writer, sheet_name='Contrats', index=False)
                    except TypeError:
                        # pandas older version fallback
                        from openpyxl import load_workbook
                        wb = load_workbook(db_path)
                        for sname, df in (('Societes', soc_df), ('Associes', assoc_df), ('Contrats', contrat_df)):
                            if sname in wb.sheetnames:
                                try:
                                    std = wb[sname]
                                    wb.remove(std)
                                except Exception:
                                    pass
                        wb.save(db_path)
                        with _pd.ExcelWriter(db_path, engine='openpyxl', mode='a') as writer:
                            soc_df.to_excel(writer, sheet_name='Societes', index=False)
                            assoc_df.to_excel(writer, sheet_name='Associes', index=False)
                            contrat_df.to_excel(writer, sheet_name='Contrats', index=False)

                    messagebox.showinfo('Succès', f"Société '{den}' supprimée avec succès.")
                    return
                except PermissionError:
                    messagebox.showerror('Erreur', 'Le fichier Excel est ouvert dans une autre application. Fermez Excel et réessayez.')
                    return
                except Exception as e:
                    try:
                        from ..utils.utils import ErrorHandler
                        ErrorHandler.handle_error(e, 'Erreur lors de la suppression')
                    except Exception:
                        messagebox.showerror('Erreur', f'Impossible de supprimer: {e}')
                    return

        except Exception as e:
            try:
                logger = __import__('logging').getLogger(__name__)
                logger.exception('handle_dashboard_action failed: %s', e)
            except Exception:
                pass
