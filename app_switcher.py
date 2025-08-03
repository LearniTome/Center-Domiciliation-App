import warnings
warnings.filterwarnings('ignore', message='pkg_resources is deprecated as an API.*')

import tkinter as tk
from tkinter import ttk
from Main_app import DomiciliationApp
from dashboard import DomiciliationDashboard
from utils import ThemeManager, WidgetFactory, WindowManager

class AppSwitcher:
    def __init__(self, root):
        self.root = root
        self.root.title("Centre de Domiciliation")

        # Configuration du thème et des styles
        self.theme_manager = ThemeManager(self.root)
        self.style = self.theme_manager.style
        self.setup_styles()

        # Configuration des raccourcis clavier globaux
        self.setup_keyboard_shortcuts()

        self.current_frame = None
        self.setup_gui()

    def setup_styles(self):
        """Configure les styles personnalisés pour le switcher"""
        # Style de la barre de navigation
        self.style.configure('Nav.TFrame',
                           padding=10)

        # Style des boutons de navigation
        self.style.configure('Nav.TButton',
                           padding=(15, 8),
                           font=('Segoe UI', 10))

    def setup_keyboard_shortcuts(self):
        """Configure les raccourcis clavier globaux"""
        self.root.bind('<Control-q>', lambda e: self.root.quit())

    def setup_gui(self):
        # Configuration des poids pour le redimensionnement
        self.root.grid_columnconfigure(0, weight=1)
        self.root.grid_rowconfigure(1, weight=1)

        # Frame pour les boutons de navigation avec padding
        self.nav_frame = ttk.Frame(self.root, style='Nav.TFrame', padding="10")
        self.nav_frame.grid(row=0, column=0, sticky="ew")
        self.nav_frame.grid_columnconfigure(1, weight=1)  # Espace flexible au milieu

        # Frame pour les boutons de gauche
        left_buttons = ttk.Frame(self.nav_frame, style='Nav.TFrame')
        left_buttons.grid(row=0, column=0, sticky="w")

        # Frame pour les boutons de droite
        right_buttons = ttk.Frame(self.nav_frame, style='Nav.TFrame')
        right_buttons.grid(row=0, column=2, sticky="e")

        # Style personnalisé pour les boutons de navigation
        self.style.configure('Nav.TButton',
                           padding=(20, 10),
                           font=('Segoe UI', 10, 'bold'))

        # Boutons de navigation
        nav_buttons = [
            ("📄 Principale", self.show_main_app),
            ("📊 Dashboard", self.show_dashboard)
        ]

        for i, (text, command) in enumerate(nav_buttons):
            btn = WidgetFactory.create_button(
                left_buttons,
                text=text,
                command=command,
                tooltip=f"Ouvrir {text.split()[1]}"
            )
            btn.grid(row=0, column=i, padx=5)

        # Frame principal pour le contenu avec padding
        self.content_frame = ttk.Frame(self.root, style='Content.TFrame', padding="20")
        self.content_frame.grid(row=1, column=0, sticky="nsew", padx=10, pady=10)

        # Configuration du redimensionnement du contenu
        self.content_frame.grid_columnconfigure(0, weight=1)
        self.content_frame.grid_rowconfigure(0, weight=1)

        # Afficher l'application principale par défaut
        self.show_main_app()

    def clear_content(self):
        if self.current_frame:
            self.current_frame.destroy()

    def show_main_app(self):
        self.clear_content()
        self.current_frame = ttk.Frame(self.content_frame, style='Content.TFrame', padding="10")
        self.current_frame.grid(row=0, column=0, sticky="nsew")
        self.current_frame.grid_columnconfigure(0, weight=1)
        self.current_frame.grid_rowconfigure(0, weight=1)
        app = DomiciliationApp(self.current_frame)

    def show_dashboard(self):
        self.clear_content()
        self.current_frame = ttk.Frame(self.content_frame, style='Content.TFrame', padding="10")
        self.current_frame.grid(row=0, column=0, sticky="nsew")
        self.current_frame.grid_columnconfigure(0, weight=1)
        self.current_frame.grid_rowconfigure(0, weight=1)
        dashboard = DomiciliationDashboard(self.current_frame)

if __name__ == "__main__":
    root = tk.Tk()
    WindowManager.setup_window(root, "Centre de Domiciliation")
    app = AppSwitcher(root)
    root.mainloop()
