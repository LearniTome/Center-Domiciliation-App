import tkinter as tk
from tkinter import ttk, messagebox
from ttkthemes import ThemedStyle
import os
from pathlib import Path
import json
import logging
import traceback
from typing import Optional, Callable, Any

# Configuration du logging
logging.basicConfig(
    filename='app.log',
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class ErrorHandler:
    """Gestionnaire d'erreurs centralisé pour l'application"""
    @staticmethod
    def handle_error(error: Exception,
                    message: str = "Une erreur s'est produite",
                    show_dialog: bool = True,
                    callback: Optional[Callable] = None) -> None:
        """
        Gère une erreur de manière centralisée
        Args:
            error: L'exception à gérer
            message: Message à afficher à l'utilisateur
            show_dialog: Si True, affiche une boîte de dialogue
            callback: Fonction à appeler après le traitement de l'erreur
        """
        # Log l'erreur
        logger.error(f"{message}: {str(error)}\n{traceback.format_exc()}")

        # Affiche la boîte de dialogue si demandé
        if show_dialog:
            messagebox.showerror("Erreur", f"{message}\n\nDétails: {str(error)}")

        # Exécute le callback si fourni
        if callback:
            try:
                callback()
            except Exception as e:
                logger.error(f"Erreur dans le callback: {str(e)}")

class ToolTip:
    def __init__(self, widget, text):
        self.widget = widget
        self.text = text
        self.tooltip = None
        self.widget.bind("<Enter>", self.enter)
        self.widget.bind("<Leave>", self.leave)

    def enter(self, event=None):
        x, y, _, _ = self.widget.bbox("insert")
        x += self.widget.winfo_rootx() + 25
        y += self.widget.winfo_rooty() + 20

        self.tooltip = tk.Toplevel(self.widget)
        self.tooltip.wm_overrideredirect(True)
        self.tooltip.wm_geometry(f"+{x}+{y}")

        label = ttk.Label(self.tooltip, text=self.text, justify=tk.LEFT,
                         background="#ffffe0", relief=tk.SOLID, borderwidth=1)
        label.pack(padx=1)

    def leave(self, event=None):
        if self.tooltip:
            self.tooltip.destroy()
            self.tooltip = None

class ThemeManager:
    def __init__(self, root):
        self.root = root
        self.style = ThemedStyle(root)
        self.setup_colors()
        self.apply_theme()

    def setup_colors(self):
        # Configuration du thème sombre uniquement
        self.colors = {
            'bg': '#2E2E2E',
            'fg': '#FFFFFF',
            'accent': '#4A90E2',
            'error': '#E74C3C',
            'success': '#2ECC71',
            'warning': '#F39C12',
            'input_bg': '#3E3E3E',
            'input_fg': '#FFFFFF',
            'label_fg': '#CCCCCC',
            'border': '#555555',
            'theme': 'black',
            'button_bg': '#555555',
            'button_fg': 'white',
            'entry_bg': '#666666',
            'entry_fg': 'white'
        }

    def apply_theme(self):
        self.style.set_theme('black')  # Toujours utiliser le thème sombre

        # Style de base pour l'application
        self.style.configure('.',
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            font=('Segoe UI', 10))

        # Styles pour les widgets de base
        self.setup_frame_styles()
        self.setup_input_styles()
        self.setup_button_styles()
        self.setup_section_styles()

    def setup_frame_styles(self):
        # Style de base pour les frames
        self.style.configure('App.TFrame',
            background=self.colors['bg'])

        # Style pour les cartes
        self.style.configure('Card.TFrame',
            background=self.colors['bg'],
            borderwidth=1,
            relief='solid')

        # Style pour les séparateurs
        self.style.configure('Separator.TFrame',
            background=self.colors['accent'],
            height=1)

    def setup_input_styles(self):
        # Style commun pour les champs de saisie
        input_style = {
            'fieldbackground': self.colors['input_bg'],
            'foreground': self.colors['input_fg'],
            'borderwidth': 1,
            'relief': 'solid',
            'padding': 5
        }

        # Entry
        self.style.configure('App.TEntry',
            **input_style,
            font=('Segoe UI', 9))

        # Combobox
        self.style.configure('App.TCombobox',
            **input_style,
            font=('Segoe UI', 9),
            arrowsize=12)

        # Labels
        self.style.configure('FieldLabel.TLabel',
            background=self.colors['bg'],
            foreground=self.colors['label_fg'],
            font=('Segoe UI', 9),
            padding=(5, 2))

        # Style des labels de champs
        self.style.configure('Field.TLabel',
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            font=('Segoe UI', 9),
            padding=(0, 5))

        # Cases à cocher
        self.style.configure('App.TCheckbutton',
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            padding=5)

        # Style pour les conteneurs de champs
        self.style.configure('Field.TFrame',
            background=self.colors['bg'],
            padding=(5, 2))

    def setup_button_styles(self):
        # Style commun pour tous les boutons
        button_base = {
            'padding': (15, 8),
            'relief': 'groove',
            'font': ('Segoe UI', 10, 'bold')
        }

        # Boutons de navigation
        self.style.configure('Nav.TButton',
            **button_base,
            width=18,
            background=self.colors['button_bg'])

        # Boutons d'action
        self.style.configure('Action.TButton',
            **button_base,
            width=22,
            background=self.colors['accent'])

        # Boutons secondaires
        self.style.configure('Secondary.TButton',
            **button_base,
            width=16,
            background=self.colors['bg'])

        # Boutons de danger
        self.style.configure('Danger.TButton',
            **button_base,
            background=self.colors['error'],
            foreground=self.colors['fg'])

        # Boutons de calendrier
        self.style.configure('Calendar.TButton',
            padding=2,
            width=3)

    def setup_section_styles(self):
        # Style de base pour les sections
        self.style.configure('Section.TLabelFrame',
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            borderwidth=1,
            relief='solid',
            padding=10)

        # Style pour les titres de section
        self.style.configure('Section.TLabelFrame.Label',
            font=('Segoe UI', 9, 'bold'),
            foreground=self.colors['accent'],
            background=self.colors['bg'],
            padding=(5, 2))

    def apply_widget_styles(self, widget):
        """Applique automatiquement le style approprié à un widget"""
        if isinstance(widget, ttk.Entry):
            widget.configure(style='App.TEntry')
        elif isinstance(widget, ttk.Combobox):
            widget.configure(style='App.TCombobox')
        elif isinstance(widget, ttk.Label):
            # Détermine si le label est un label de champ ou un label général
            if widget.winfo_parent() and 'field' in widget.winfo_parent().lower():
                widget.configure(style='Field.TLabel')
            else:
                widget.configure(style='FieldLabel.TLabel')
        elif isinstance(widget, ttk.LabelFrame):
            widget.configure(style='Section.TLabelFrame')
        elif isinstance(widget, ttk.Frame):
            # Détermine si le frame est un conteneur de champ
            if widget.winfo_parent() and 'field' in widget.winfo_parent().lower():
                widget.configure(style='Field.TFrame')
            else:
                widget.configure(style='App.TFrame')
        elif isinstance(widget, ttk.Checkbutton):
            widget.configure(style='App.TCheckbutton')
        return widget

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

# Fonction utilitaire pour appliquer les styles
def apply_style(widget, theme_manager):
    """
    Applique le style approprié à un widget en utilisant le ThemeManager fourni.

    Args:
        widget: Le widget auquel appliquer le style
        theme_manager: L'instance de ThemeManager à utiliser
    """
    return theme_manager.apply_widget_styles(widget)

class PathManager:
    """Gestionnaire centralisé des chemins de fichiers de l'application"""
    BASE_DIR = Path(__file__).parent
    MODELS_DIR = BASE_DIR / "Models"
    DATABASE_DIR = BASE_DIR / "databases"
    CONFIG_DIR = BASE_DIR / "config"
    ALLOWED_EXTENSIONS = {
        'models': ['.docx', '.doc'],
        'database': ['.xlsx', '.xls'],
        'config': ['.json']
    }

    @classmethod
    def ensure_directories(cls) -> None:
        """Crée les répertoires nécessaires s'ils n'existent pas"""
        try:
            for dir_path in [cls.MODELS_DIR, cls.DATABASE_DIR, cls.CONFIG_DIR]:
                dir_path.mkdir(parents=True, exist_ok=True)
        except Exception as e:
            ErrorHandler.handle_error(e, "Erreur lors de la création des répertoires")

    @classmethod
    def validate_file(cls, filepath: Path, file_type: str) -> bool:
        """
        Valide un fichier selon son type
        Args:
            filepath: Chemin du fichier
            file_type: Type de fichier ('models', 'database', 'config')
        Returns:
            bool: True si le fichier est valide
        """
        if not filepath.exists():
            logger.warning(f"Fichier non trouvé: {filepath}")
            return False

        if not filepath.suffix.lower() in cls.ALLOWED_EXTENSIONS.get(file_type, []):
            logger.warning(f"Extension non autorisée: {filepath}")
            return False

        return True

    @classmethod
    def get_model_path(cls, filename: str) -> Path:
        """Retourne le chemin d'un fichier modèle"""
        path = cls.MODELS_DIR / filename
        if not cls.validate_file(path, 'models'):
            raise FileNotFoundError(f"Modèle invalide ou non trouvé: {filename}")
        return path

    @classmethod
    def get_database_path(cls, filename: str) -> Path:
        """Retourne le chemin d'un fichier de base de données"""
        path = cls.DATABASE_DIR / filename
        if not cls.validate_file(path, 'database'):
            raise FileNotFoundError(f"Base de données invalide ou non trouvée: {filename}")
        return path
