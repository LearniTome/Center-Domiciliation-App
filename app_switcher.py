import tkinter as tk
from tkinter import ttk
from Main_app import DomiciliationApp
from dashboard import DomiciliationDashboard
from ttkthemes import ThemedStyle

class AppSwitcher:
    def __init__(self, root):
        self.root = root
        self.root.title("Centre de Domiciliation")

        # Configuration du thème
        self.style = ThemedStyle(self.root)
        self.style.configure('.', font=('Segoe UI', 10))

        # Configuration initiale du thème
        self.is_dark_mode = True
        self.apply_theme()

        # Configuration des raccourcis clavier
        self.root.bind('<Control-t>', lambda e: self.toggle_theme())

        self.current_frame = None
        self.setup_gui()

    def apply_theme(self):
        # Définir les couleurs pour chaque thème
        dark_colors = {
            'bg': '#464646',
            'fg': 'white',
            'theme': 'black'
        }
        light_colors = {
            'bg': '#f0f0f0',
            'fg': 'black',
            'theme': 'arc'
        }

        # Sélectionner les couleurs en fonction du mode
        colors = dark_colors if self.is_dark_mode else light_colors

        # Appliquer le thème une seule fois
        self.style.set_theme(colors['theme'])

        # Configurer les styles de base
        self.root.configure(bg=colors['bg'])

        # Configurer tous les styles en une seule fois
        self.style.configure('.',
                           background=colors['bg'],
                           foreground=colors['fg'])

        # Mettre à jour le texte du bouton de thème
        theme_text = "☀️ Mode Clair" if self.is_dark_mode else "🌙 Mode Sombre"
        if hasattr(self, 'theme_button'):
            self.theme_button.configure(text=theme_text)

    def toggle_theme(self):
        self.is_dark_mode = not self.is_dark_mode
        self.apply_theme()

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

        # Boutons de navigation (à gauche)
        nav_buttons = [
            ("📄 Application Principale", self.show_main_app),
            ("📊 Dashboard", self.show_dashboard)
        ]

        for i, (text, command) in enumerate(nav_buttons):
            btn = ttk.Button(left_buttons,
                           text=text,
                           style='Nav.TButton',
                           command=command)
            btn.grid(row=0, column=i, padx=5)

        # Bouton de thème (à droite)
        self.theme_button = ttk.Button(right_buttons,
                                     text="🌓 Changer le thème",
                                     style='Nav.TButton',
                                     command=self.toggle_theme)
        self.theme_button.grid(row=0, column=0, padx=5)

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

    # Obtenir les dimensions de l'écran
    screen_width = root.winfo_screenwidth()
    screen_height = root.winfo_screenheight()

    # Définir la taille minimale de la fenêtre
    root.minsize(1024, 768)

    # Centrer la fenêtre
    x = (screen_width - 1200) // 2
    y = (screen_height - 800) // 2
    root.geometry(f"1200x800+{x}+{y}")

    # Mettre la fenêtre en plein écran
    import platform
    if platform.system() == "Linux":
        root.attributes('-fullscreen', True)
    else:  # Windows
        root.state('zoomed')

    app = AppSwitcher(root)
    root.mainloop()
