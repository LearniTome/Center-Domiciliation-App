import tkinter as tk
from tkinter import ttk
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
        """Configure les différentes sections du formulaire"""
        # Configure grid weights for form sections
        self.forms_container.grid_columnconfigure(0, weight=1)  # Société
        self.forms_container.grid_columnconfigure(1, weight=1)  # Associés

        # Create sections
        self.create_societe_section(self.forms_container)
        self.create_associe_section(self.forms_container)
        self.create_contrat_section(self.forms_container)

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

    def create_societe_section(self, parent):
        """Crée la section Informations Société"""
        # Header
        self.create_section_header(parent, "Informations Société", "📝", 0, 0)

        # Form container with proper styling
        societe_frame = ttk.Frame(parent, style='Section.TFrame')
        societe_frame.grid(row=1, column=0, sticky="nsew", padx=5, pady=(0, 10))

        # Create societe form
        self.societe_form = SocieteForm(societe_frame, self.values)
        self.societe_form.pack(fill="both", expand=True, padx=10, pady=10)

    def create_associe_section(self, parent):
        """Crée la section Informations Associés"""
        # Header
        self.create_section_header(parent, "Informations Associés", "👥", 0, 1)

        # Form container
        associe_frame = ttk.Frame(parent, style='Section.TFrame')
        associe_frame.grid(row=1, column=1, sticky="nsew", padx=5, pady=(0, 10))

        # Create associe form
        self.associe_form = AssocieForm(associe_frame, self.theme_manager)
        self.associe_form.pack(fill="both", expand=True, padx=10, pady=10)

    def create_contrat_section(self, parent):
        """Crée la section Informations Contrat"""
        # Header
        self.create_section_header(parent, "Informations Contrat", "📋", 2, 0, 2)

        # Form container
        contrat_frame = ttk.Frame(parent, style='Section.TFrame')
        contrat_frame.grid(row=3, column=0, columnspan=2, sticky="nsew",
                          padx=5, pady=(0, 10))

        # Create contrat form with reduced height
        self.contrat_form = ContratForm(contrat_frame, self.values)
        self.contrat_form.pack(fill="both", expand=True, padx=10, pady=10)

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
        """Setup navigation buttons"""
        nav_frame = ttk.Frame(self.forms_container)
        nav_frame.pack(fill="x", padx=20, pady=15)

        # Dashboard button
        dashboard_btn = WidgetFactory.create_button(
            nav_frame,
            text="📊 Tableau de bord",
            command=self.show_dashboard,
            tooltip="Voir le tableau de bord"
        )
        dashboard_btn.pack(side="left", padx=5)

    def show_dashboard(self):
        """Switch to dashboard view"""
        from .dashboard_view import DashboardView
        dashboard = DashboardView(self.winfo_toplevel())  # Window is now modal by default

    def get_values(self):
        """Get values from all forms"""
        self.values = {
            'societe': self.societe_form.get_values(),
            'associes': self.associe_form.get_values(),  # AssocieForm.get_values() retourne déjà une liste
            'contrat': self.contrat_form.get_values()
        }
        return self.values

    def set_values(self, values):
        """Set values for all forms"""
        self.values = values
        if values:
            if 'societe' in values:
                self.societe_form.set_values(values['societe'])
            if 'associes' in values:
                self.associe_form.set_values(values['associes'])
            if 'contrat' in values:
                self.contrat_form.set_values(values['contrat'])

    def reset(self):
        """Reset all forms to their default state"""
        self.values = {}
        # Réinitialiser chaque formulaire individuellement
        self.societe_form.set_values({})
        self.associe_form.set_values([])  # Liste vide pour les associés
        self.contrat_form.set_values({})
