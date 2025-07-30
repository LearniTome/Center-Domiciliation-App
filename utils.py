import tkinter as tk
from tkinter import ttk, messagebox
from ttkthemes import ThemedStyle
import os
from pathlib import Path
import json
import logging

class ThemeManager:
    def __init__(self, root):
        self.root = root
        self.style = ThemedStyle(root)
        self.load_preferences()
        self.apply_theme()

    def load_preferences(self):
        try:
            with open('config/preferences.json', 'r') as f:
                prefs = json.load(f)
                self.is_dark_mode = prefs.get('theme') == 'dark'
        except:
            self.is_dark_mode = True

    def save_preferences(self):
        os.makedirs('config', exist_ok=True)
        with open('config/preferences.json', 'w') as f:
            json.dump({
                'theme': 'dark' if self.is_dark_mode else 'light'
            }, f)

    def apply_theme(self):
        dark_colors = {
            'bg': '#464646',
            'fg': 'white',
            'theme': 'black',
            'button_bg': '#555555',
            'button_fg': 'white',
            'entry_bg': '#666666',
            'entry_fg': 'white'
        }
        light_colors = {
            'bg': '#f0f0f0',
            'fg': 'black',
            'theme': 'arc',
            'button_bg': '#e0e0e0',
            'button_fg': 'black',
            'entry_bg': 'white',
            'entry_fg': 'black'
        }

        colors = dark_colors if self.is_dark_mode else light_colors
        self.style.set_theme(colors['theme'])

        # Configurer les styles communs
        self.style.configure('.',
                           background=colors['bg'],
                           foreground=colors['fg'],
                           font=('Segoe UI', 10))

        # Styles communs pour les boutons
        common_style = {
            'padding': (15, 8),
            'relief': 'groove',
            'font': ('Segoe UI', 10, 'bold')
        }

        # Styles spécifiques pour les boutons de navigation
        self.style.configure('Nav.TButton',
                           padding=common_style['padding'],
                           relief=common_style['relief'],
                           font=common_style['font'],
                           width=18,
                           background=colors['button_bg'])

        # Style pour les boutons d'action principaux
        self.style.configure('Action.TButton',
                           padding=common_style['padding'],
                           relief=common_style['relief'],
                           font=common_style['font'],
                           width=22)

        # Style pour les boutons secondaires
        self.style.configure('Secondary.TButton',
                           padding=common_style['padding'],
                           relief=common_style['relief'],
                           font=common_style['font'],
                           width=16)

        self.colors = colors
        return colors

    def toggle_theme(self):
        self.is_dark_mode = not self.is_dark_mode
        self.apply_theme()
        self.save_preferences()

class WidgetFactory:
    @staticmethod
    def create_button(parent, text, command, style='Nav.TButton', tooltip=None):
        btn = ttk.Button(parent, text=text, command=command, style=style)
        if tooltip:
            WidgetFactory.create_tooltip(btn, tooltip)
        return btn

    @staticmethod
    def create_tooltip(widget, text):
        def show_tooltip(event):
            tooltip = tk.Toplevel()
            tooltip.wm_overrideredirect(True)
            tooltip.wm_geometry(f"+{event.x_root+10}+{event.y_root+10}")

            label = ttk.Label(tooltip, text=text, justify='left',
                            relief='solid', borderwidth=1)
            label.pack()

            def hide_tooltip():
                tooltip.destroy()

            widget.tooltip = tooltip
            widget.bind('<Leave>', lambda e: hide_tooltip())
            tooltip.bind('<Leave>', lambda e: hide_tooltip())

        widget.bind('<Enter>', show_tooltip)

class WindowManager:
    @staticmethod
    def setup_window(root, title=""):
        # Obtenir les dimensions de l'écran
        screen_width = root.winfo_screenwidth()
        screen_height = root.winfo_screenheight()

        # Définir la taille minimale
        root.minsize(1024, 768)

        # Centrer la fenêtre
        x = (screen_width - 1200) // 2
        y = (screen_height - 800) // 2
        root.geometry(f"1200x800+{x}+{y}")

        # Configurer le titre
        if title:
            root.title(title)

        # Configurer le plein écran selon le système
        import platform
        if platform.system() == "Linux":
            root.attributes('-fullscreen', True)
        else:  # Windows
            root.state('zoomed')

        # Configurer la touche Échap pour quitter le plein écran
        root.bind('<Escape>', lambda e: root.state('normal'))

class PathManager:
    BASE_DIR = Path(__file__).parent
    MODELS_DIR = BASE_DIR / "Models"
    DATABASE_DIR = BASE_DIR / "databases"
    CONFIG_DIR = BASE_DIR / "config"

    @classmethod
    def ensure_directories(cls):
        for dir_path in [cls.MODELS_DIR, cls.DATABASE_DIR, cls.CONFIG_DIR]:
            dir_path.mkdir(parents=True, exist_ok=True)

    @classmethod
    def get_model_path(cls, filename):
        return cls.MODELS_DIR / filename

    @classmethod
    def get_database_path(cls, filename):
        return cls.DATABASE_DIR / filename
