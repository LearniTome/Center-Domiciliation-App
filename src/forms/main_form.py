import tkinter as tk
from tkinter import ttk, messagebox
from .societe_form import SocieteForm
from .associe_form import AssocieForm
from .contrat_form import ContratForm
from ..utils.utils import ThemeManager, WidgetFactory

class MainForm(ttk.Frame):
    def __init__(self, parent, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = values_dict or {}

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
        self.forms_container.grid_columnconfigure(0, weight=1)

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

        # Grid layout for better control
        self.canvas.grid(row=0, column=0, sticky="nsew")
        scrollbar.grid(row=0, column=1, sticky="ns")

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
        header_frame.grid(row=row, column=column, columnspan=columnspan,
                         sticky="ew", pady=(0, 10), padx=5)

        label = ttk.Label(header_frame,
                         text=f"{icon} {text}",
                         style='SectionHeader.TLabel')
        label.pack(fill="x", expand=True)
        return header_frame

    def create_societe_page(self):
        header = self.create_section_header(self.forms_container, "Informations Société", "📝", 0, 0)
        page = ttk.Frame(self.forms_container, style='Section.TFrame')
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        self.societe_form = SocieteForm(page, self.values.get('societe', {}))
        self.societe_form.pack(fill="both", expand=True, padx=10, pady=10)
        self.pages.append(('societe', page, self.societe_form))

    def create_associe_page(self):
        header = self.create_section_header(self.forms_container, "Informations Associés", "👥", 0, 0)
        page = ttk.Frame(self.forms_container, style='Section.TFrame')
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        # AssocieForm expects a ThemeManager instance
        self.associe_form = AssocieForm(page, self.theme_manager)
        self.associe_form.pack(fill="both", expand=True, padx=10, pady=10)
        self.pages.append(('associes', page, self.associe_form))

    def create_contrat_page(self):
        header = self.create_section_header(self.forms_container, "Informations Contrat", "📋", 0, 0)
        page = ttk.Frame(self.forms_container, style='Section.TFrame')
        page.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))
        self.contrat_form = ContratForm(page, self.values.get('contrat', {}))
        self.contrat_form.pack(fill="both", expand=True, padx=10, pady=10)
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
        nav_frame = ttk.Frame(self.forms_container)
        nav_frame.grid(row=99, column=0, sticky="ew", padx=20, pady=15)
        nav_frame.grid_columnconfigure(0, weight=1)

        # Previous
        self.prev_btn = WidgetFactory.create_button(
            nav_frame, text="◀ Précédent", command=self.prev_page)
        self.prev_btn.grid(row=0, column=0, sticky="w", padx=5)

        # Next
        self.next_btn = WidgetFactory.create_button(
            nav_frame, text="Suivant ▶", command=self.next_page)
        self.next_btn.grid(row=0, column=2, sticky="e", padx=5)

        # Save
        self.save_btn = WidgetFactory.create_button(
            nav_frame, text="💾 Sauvegarder", command=self.save_current)
        self.save_btn.grid(row=0, column=3, sticky="e", padx=5)

        # Finish
        self.finish_btn = WidgetFactory.create_button(
            nav_frame, text="🏁 Terminer", command=self.finish)
        self.finish_btn.grid(row=0, column=4, sticky="e", padx=5)

        self.update_nav_buttons()

    def show_dashboard(self):
        """Switch to dashboard view"""
        from .dashboard_view import DashboardView
        dashboard = DashboardView(self.winfo_toplevel())  # Window is now modal by default

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
                self.values[key] = form.get_values()
                messagebox.showinfo("Sauvegarde", f"Section '{key}' sauvegardée.")
            except Exception as e:
                messagebox.showerror("Erreur", f"Impossible de sauvegarder la section: {e}")

    def finish(self):
        """Save all pages and emit a finished event."""
        # Save current and gather all values
        self.save_current()
        all_values = self.get_values()
        # TODO: hook this into actual persistence (Excel/db/doc generation)
        messagebox.showinfo("Terminé", "Toutes les sections ont été sauvegardées.")
        # Emit a virtual event so outer code can handle finalization if needed
        try:
            self.event_generate('<<FormsFinished>>')
        except Exception:
            pass

    def update_nav_buttons(self):
        # Disable Prev on first page, Next on last
        if hasattr(self, 'prev_btn'):
            self.prev_btn.configure(state=('disabled' if self.current_page == 0 else 'normal'))
        if hasattr(self, 'next_btn'):
            self.next_btn.configure(state=('disabled' if self.current_page == len(self.pages) - 1 else 'normal'))
