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
    """ThemeManager simplifié : délègue tout à ModernTheme dans `src.utils.styles`.

    Ceci évite la duplication des styles dans plusieurs fichiers et garde
    un seul endroit (`ModernTheme`) responsable des apparences.
    """
    def __init__(self, root):
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

        # Use the centralized ModernTheme from src.utils.styles
        self.theme = ModernTheme(root, mode=mode)
        self.style = self.theme.style
        self.colors = self.theme.colors
        # Apply background to root and existing canvases to keep tk widgets in sync
        try:
            self._apply_root_background()
            self._update_canvas_backgrounds()
        except Exception:
            logger.debug('Failed to update canvas/background on init', exc_info=True)

    def set_theme(self, mode: str):
        if mode not in ('light', 'dark'):
            return
        # Recreate centralized theme
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
        # Update root and existing canvases so widgets created before theme change update
        try:
            self._apply_root_background()
            self._update_canvas_backgrounds()
        except Exception:
            logger.debug('Failed to update canvas/background after set_theme', exc_info=True)

    def toggle_theme(self):
        new_mode = 'dark' if self.theme.mode == 'light' else 'light'
        self.set_theme(new_mode)

    def _apply_root_background(self):
        """Configure the top-level root background to the current theme bg color."""
        try:
            if hasattr(self.root, 'configure'):
                # Some platforms expect 'background' or 'bg'
                try:
                    self.root.configure(background=self.colors['bg'])
                except Exception:
                    try:
                        self.root.configure(bg=self.colors['bg'])
                    except Exception:
                        pass
        except Exception:
            logger.debug('Failed to apply root background', exc_info=True)

    def _update_canvas_backgrounds(self):
        """Recursively find tk.Canvas widgets under root and update their background.

        This ensures Canvas widgets (which are not ttk and do not follow ttk styles)
        reflect the current theme background when the theme is changed at runtime.
        """
        try:
            # recursive walk
            def _walk(widget):
                for child in widget.winfo_children():
                    # tk.Canvas class is available as tk.Canvas
                    if isinstance(child, tk.Canvas):
                        try:
                            child.configure(background=self.colors['bg'])
                        except Exception:
                            try:
                                child.configure(bg=self.colors['bg'])
                            except Exception:
                                pass
                    # recurse
                    try:
                        _walk(child)
                    except Exception:
                        pass

            _walk(self.root)
        except Exception:
            logger.debug('Failed to update canvas backgrounds', exc_info=True)

    def apply_widget_styles(self, widget):
        """Applique un style cohérent en utilisant les noms définis dans ModernTheme.

        Cette méthode remplace les styles locaux et force l'utilisation du
        fichier unique `src/utils/styles.py` comme source de vérité.
        """
        try:
            if isinstance(widget, ttk.Entry):
                widget.configure(style='TEntry')
            elif isinstance(widget, ttk.Combobox):
                widget.configure(style='TCombobox')
            elif isinstance(widget, ttk.Label):
                widget.configure(style='FieldLabel.TLabel')
            elif isinstance(widget, ttk.LabelFrame):
                # Use Section frame style for labeled sections
                widget.configure(style='Section.TFrame')
            elif isinstance(widget, ttk.Frame):
                widget.configure(style='TFrame')
            elif isinstance(widget, ttk.Checkbutton):
                # fall back to default checkbutton style
                widget.configure(style='TCheckbutton')
        except Exception:
            # Defensive: ignore failures to avoid breaking UI creation
            logger.debug('apply_widget_styles: failed to configure widget style', exc_info=True)
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

    @staticmethod
    def center_window(win):
        """Center a Toplevel or root window on the screen or on its parent."""
        try:
            win.update_idletasks()
            # If window has a parent (transient), center on parent
            parent = getattr(win, 'master', None)
            if parent and parent.winfo_ismapped():
                pw = parent.winfo_width()
                ph = parent.winfo_height()
                px = parent.winfo_rootx()
                py = parent.winfo_rooty()
                ww = win.winfo_width()
                wh = win.winfo_height()
                x = px + max(0, (pw - ww) // 2)
                y = py + max(0, (ph - wh) // 2)
            else:
                screen_w = win.winfo_screenwidth()
                screen_h = win.winfo_screenheight()
                ww = win.winfo_width()
                wh = win.winfo_height()
                x = (screen_w - ww) // 2
                y = (screen_h - wh) // 2
            win.geometry(f"+{x}+{y}")
        except Exception:
            pass

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
