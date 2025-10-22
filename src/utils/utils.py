import tkinter as tk
from tkinter import ttk, messagebox
from .styles import ModernTheme
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
        # Read persisted preference if available
        self.root = root
        self.pref_path = Path(__file__).resolve().parent.parent.parent / 'config' / 'preferences.json'
        mode = 'dark'
        try:
            if self.pref_path.exists():
                with self.pref_path.open('r', encoding='utf-8') as f:
                    prefs = json.load(f)
                    mode = prefs.get('theme', 'dark')
        except Exception:
            mode = 'dark'

        self.theme = ModernTheme(root, mode=mode)
        self.style = self.theme.style
        self.colors = self.theme.colors

    def set_theme(self, mode: str):
        """Set theme mode ('light' or 'dark') and persist preference."""
        if mode not in ('light', 'dark'):
            return
        # Recreate theme with new mode
        self.theme = ModernTheme(self.root, mode=mode)
        self.style = self.theme.style
        self.colors = self.theme.colors
        # Persist
        try:
            prefs = {'theme': mode}
            self.pref_path.parent.mkdir(parents=True, exist_ok=True)
            with self.pref_path.open('w', encoding='utf-8') as f:
                json.dump(prefs, f, ensure_ascii=False, indent=2)
        except Exception:
            logger.exception('Failed to persist theme preference')

    def toggle_theme(self):
        new_mode = 'dark' if self.theme.mode == 'light' else 'light'
        self.set_theme(new_mode)

    def setup_colors(self):
        # Configuration des couleurs modernes
        self.colors = {
            'bg': '#1e1e1e',  # Fond sombre
            'fg': '#ffffff',   # Texte clair
            'accent': '#2171cd',
            'accent_light': '#4a90e2',
            'error': '#dc3545',
            'success': '#28a745',
            'warning': '#ffc107',
            'info': '#17a2b8',
            'border': '#3e3e3e',  # Bordure plus subtile
            'hover': '#2a2a2a',   # Effet hover plus subtil
            'disabled': '#6c757d',
            'label_fg': '#cccccc', # Labels légèrement plus clairs
            'input_bg': '#2d2d2d', # Fond des champs de saisie
            'input_border': '#3e3e3e',
            'section_bg': '#252526', # Fond des sections
            'section_header_bg': '#323233', # Fond des en-têtes de section
            'section_header_fg': '#ffffff', # Texte des en-têtes de section
            'input_fg': '#ffffff',
            'button_bg': '#323233',
            'button_fg': '#ffffff',
            'entry_bg': '#2d2d2d',
            'entry_fg': '#ffffff',
            'section_border': '#3e3e3e' # Bordure des sections
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
            background=self.colors['section_bg'],
            foreground=self.colors['fg'],
            borderwidth=1,
            relief='solid',
            bordercolor=self.colors['section_border'],
            padding=15)

        # Style pour les titres de section
        self.style.configure('Section.TLabelFrame.Label',
            font=('Segoe UI', 10, 'bold'),
            foreground=self.colors['section_header_fg'],
            background=self.colors['section_header_bg'],
            padding=(10, 5))

        # Style pour les sous-sections
        self.style.configure('SubSection.TLabelFrame',
            background=self.colors['section_bg'],
            foreground=self.colors['fg'],
            borderwidth=1,
            relief='solid',
            bordercolor=self.colors['section_border'],
            padding=10)

        # Style pour les titres de sous-sections
        self.style.configure('SubSection.TLabelFrame.Label',
            font=('Segoe UI', 9, 'bold'),
            foreground=self.colors['section_header_fg'],
            background=self.colors['section_bg'],
            padding=(8, 4))

        # Style spécial pour les sections d'information
        self.style.configure('Info.TLabelFrame',
            background=self.colors['section_bg'],
            foreground=self.colors['fg'],
            borderwidth=1,
            relief='solid',
            bordercolor=self.colors['info'],
            padding=15)

        # Style pour les titres des sections d'information
        self.style.configure('Info.TLabelFrame.Label',
            font=('Segoe UI', 10, 'bold'),
            foreground=self.colors['info'],
            background=self.colors['section_header_bg'],
            padding=(10, 5))

    def apply_widget_styles(self, widget):
        """Applique automatiquement le style approprié à un widget"""
        if isinstance(widget, ttk.Entry):
            widget.configure(style='App.TEntry')
        elif isinstance(widget, ttk.Combobox):
            widget.configure(style='App.TCombobox')
        elif isinstance(widget, ttk.Label):
            # Détermine le style approprié selon le contexte
            parent_name = widget.winfo_parent().lower() if widget.winfo_parent() else ''
            if 'field' in parent_name:
                widget.configure(style='Field.TLabel')
            elif 'info' in parent_name:
                widget.configure(style='Info.TLabel')
            else:
                widget.configure(style='FieldLabel.TLabel')
        elif isinstance(widget, ttk.LabelFrame):
            # Détermine le style de section approprié
            widget_name = widget.winfo_name().lower()
            if 'info' in widget_name:
                widget.configure(style='Info.TLabelFrame')
            elif 'sub' in widget_name:
                widget.configure(style='SubSection.TLabelFrame')
            else:
                widget.configure(style='Section.TLabelFrame')
        elif isinstance(widget, ttk.Frame):
            # Détermine le style de frame approprié
            parent_name = widget.winfo_parent().lower() if widget.winfo_parent() else ''
            if 'field' in parent_name:
                widget.configure(style='Field.TFrame')
            elif 'info' in parent_name:
                widget.configure(style='Info.TFrame')
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
    BASE_DIR = Path(__file__).resolve().parent.parent.parent
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
