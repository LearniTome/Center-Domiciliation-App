import tkinter as tk
from tkinter import ttk, messagebox
from .styles import ModernTheme
import os
import pandas as pd
from pathlib import Path
import json
import logging
import traceback
from typing import Optional, Callable, Any
import datetime
from openpyxl import load_workbook
from openpyxl.utils import get_column_letter
from openpyxl import Workbook
from pathlib import Path as _Path
import pandas as _pd
import shutil

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
            try:
                self.tooltip.destroy()
            except Exception:
                pass
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
        # Start background monitor to ensure dynamically created non-ttk widgets
        # (like Combobox popdown Listbox) get themed shortly after creation.
        try:
            # _start_non_ttk_monitor is defined on this class below
            self._start_non_ttk_monitor()
        except Exception:
            logger.debug('Failed to start non-ttk monitor', exc_info=True)

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
            # Update non-ttk widget styles for existing widgets
            self._apply_non_ttk_styles()
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

    def _apply_non_ttk_styles(self):
        """Apply colors to non-ttk widgets (Listbox, Menu, Text) and update existing instances.

        This uses root.option_add to set defaults for new widgets and walks the widget tree
        to update already-created widgets so their selection colors match the theme.
        """
        try:
            # Set global defaults for new widgets
            try:
                self.root.option_add('*Listbox.background', self.colors['bg'])
                self.root.option_add('*Listbox.foreground', self.colors['fg'])
                self.root.option_add('*Listbox.selectBackground', self.colors['accent'])
                self.root.option_add('*Listbox.selectForeground', 'white')

                self.root.option_add('*Text.background', self.colors['bg'])
                self.root.option_add('*Text.foreground', self.colors['fg'])
                self.root.option_add('*Text.insertBackground', self.colors['fg'])

                self.root.option_add('*Menu.background', self.colors['bg'])
                self.root.option_add('*Menu.foreground', self.colors['fg'])
                self.root.option_add('*Menu.activeBackground', self.colors['accent'])
                self.root.option_add('*Menu.activeForeground', 'white')
            except Exception:
                pass

            # Walk existing widgets and update instances
            def _walk_update(widget):
                for child in widget.winfo_children():
                    try:
                        if isinstance(child, tk.Listbox):
                            child.configure(background=self.colors['bg'], foreground=self.colors['fg'], selectbackground=self.colors['accent'], selectforeground='white')
                        elif isinstance(child, tk.Text):
                            child.configure(background=self.colors['bg'], foreground=self.colors['fg'], insertbackground=self.colors['fg'])
                        elif isinstance(child, tk.Menu):
                            try:
                                child.configure(background=self.colors['bg'], foreground=self.colors['fg'], activebackground=self.colors['accent'], activeforeground='white')
                            except Exception:
                                pass
                    except Exception:
                        pass
                    # Recurse
                    try:
                        _walk_update(child)
                    except Exception:
                        pass

            _walk_update(self.root)
        except Exception:
            logger.debug('Failed to apply non-ttk styles', exc_info=True)

    def _start_non_ttk_monitor(self, interval_ms: int = 400):
        """Start a periodic after() loop that reapplies non-ttk styles.

        This ensures popdown widgets created later (combobox lists) are themed.
        """
        try:
            # cancel previous if any
            if hasattr(self, '_monitor_id') and self._monitor_id:
                try:
                    self.root.after_cancel(self._monitor_id)
                except Exception:
                    pass

            def _monitor():
                try:
                    self._apply_non_ttk_styles()
                except Exception:
                    pass
                try:
                    self._monitor_id = self.root.after(interval_ms, _monitor)
                except Exception:
                    pass

            # schedule first run
            self._monitor_id = self.root.after(interval_ms, _monitor)
        except Exception:
            logger.debug('Failed to start monitor', exc_info=True)

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
    def create_button(parent, text, command, style='Secondary.TButton', tooltip=None):
        btn = ttk.Button(parent, text=text, command=command, style=style, takefocus=False)
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

def ensure_excel_db(path, sheets: dict):
    """Create an Excel workbook at `path` with given sheets dict (name -> columns).

    Idempotent: if the file exists, ensure missing sheets are added with headers.
    Also attempts to set basic date column formatting where column names contain 'date'.
    """
    try:
        import openpyxl
    except Exception:
        raise RuntimeError('openpyxl is required for ensure_excel_db')

    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)

    if not path.exists():
        # create new workbook
        with pd.ExcelWriter(path, engine='openpyxl') as writer:
            for name, cols in sheets.items():
                pd.DataFrame(columns=cols).to_excel(writer, sheet_name=name, index=False)
        return

    # If exists, open and add missing sheets
    wb = openpyxl.load_workbook(path)
    modified = False
    for name, cols in sheets.items():
        if name not in wb.sheetnames:
            # create sheet with header row
            ws = wb.create_sheet(title=name)
            for c, col in enumerate(cols, start=1):
                ws.cell(row=1, column=c, value=col)
            modified = True
    if modified:
        wb.save(path)
    return


def write_records_to_db(path, societe_vals: dict, associes_list: list, contrat_vals: dict):
    """Write the provided records into the Excel workbook at `path`.

    This function is idempotent and will compute incremental integer IDs
    for Societes/Associes/Contrats based on existing rows in the workbook.
    Date-like fields are converted to datetime so Excel stores them as dates.
    """
    path = _Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)

    # import constants lazily to avoid circular imports
    from . import constants as _const

    # Helper to load existing sheet into DataFrame safely
    def _load_sheet_df(sheet_name):
        try:
            return _pd.read_excel(path, sheet_name=sheet_name, dtype=str)
        except Exception:
            return _pd.DataFrame(columns=_const.excel_sheets.get(sheet_name, []))

    # Helper next ID
    def _next_id(sheet_name, id_col):
        df = _load_sheet_df(sheet_name)
        if id_col in df.columns and not df.empty:
            try:
                nums = _pd.to_numeric(df[id_col], errors='coerce').dropna()
                if not nums.empty:
                    return int(nums.max()) + 1
            except Exception:
                pass
            return len(df) + 1
        return 1

    def _to_datetime(val):
        # Return pandas.Timestamp or None
        if val is None or (isinstance(val, str) and val.strip() == ''):
            return None
        try:
            return _pd.to_datetime(val, dayfirst=True, errors='coerce')
        except Exception:
            return None

    # Build rows aligned with headers
    soc_df = _pd.DataFrame(columns=_const.societe_headers)
    assoc_df = _pd.DataFrame(columns=_const.associe_headers)
    contrat_df = _pd.DataFrame(columns=_const.contrat_headers)

    # Societe
    if societe_vals:
        sid = _next_id('Societes', 'ID_SOCIETE')
        # initialize with None so columns can hold datetimes or numbers
        row: dict = {h: None for h in _const.societe_headers}
        row['ID_SOCIETE'] = sid
        # mapping from form keys to headers (best-effort)
        mapping = {
            'denomination': 'DEN_STE',
            'forme_juridique': 'FORME_JUR',
            'ice': 'ICE',
            'date_ice': 'DATE_ICE',
            'capital': 'CAPITAL',
            'parts_social': 'PART_SOCIAL',
            'adresse': 'STE_ADRESS',
            'tribunal': 'TRIBUNAL'
        }
        for k, h in mapping.items():
            if k in societe_vals:
                v = societe_vals.get(k)
                # try to parse dates into datetime
                if h.upper().find('DATE') >= 0:
                    dt = _to_datetime(v)
                    row[h] = dt.to_pydatetime() if dt is not None else None
                else:
                    if v is None:
                        row[h] = None
                    elif isinstance(v, bool):
                        row[h] = int(v)
                    elif isinstance(v, (int, float)):
                        row[h] = v
                    else:
                        row[h] = str(v)
        soc_df = _pd.DataFrame([row])

    # Associes
    if associes_list:
        aid = _next_id('Associes', 'ID_ASSOCIE')
        assoc_rows = []
        # Determine linked societe id
        linked_sid = soc_df['ID_SOCIETE'].iloc[0] if not soc_df.empty else ''
        for a in associes_list:
            if not isinstance(a, dict):
                continue
            r: dict = {h: None for h in _const.associe_headers}
            r['ID_ASSOCIE'] = aid
            aid += 1
            r['ID_SOCIETE'] = linked_sid
            map_a = {
                'civilite': 'CIVIL', 'prenom': 'PRENOM', 'nom': 'NOM',
                'nationalite': 'NATIONALITY', 'num_piece': 'CIN_NUM',
                'validite_piece': 'CIN_VALIDATY', 'date_naiss': 'DATE_NAISS',
                'lieu_naiss': 'LIEU_NAISS', 'adresse': 'ADRESSE',
                'telephone': 'PHONE', 'email': 'EMAIL',
                # forms historically used either 'parts' or 'num_parts'
                'parts': 'PARTS', 'num_parts': 'PARTS',
                # form uses 'capital_detenu' variable, store it in CAPITAL_DETENU
                'capital_detenu': 'CAPITAL_DETENU',
                'est_gerant': 'IS_GERANT', 'qualite': 'QUALITY'
            }
            for k, h in map_a.items():
                if k in a:
                    v = a.get(k)
                    if h.upper().find('DATE') >= 0:
                        dt = _to_datetime(v)
                        r[h] = dt.to_pydatetime() if dt is not None else None
                    else:
                        if v is None:
                            r[h] = None
                        elif isinstance(v, bool):
                            r[h] = int(v)
                        elif isinstance(v, (int, float)):
                            r[h] = v
                        else:
                            s = str(v).strip()
                            # Try numeric conversion for parts / capital
                            if h in ('PARTS', 'CAPITAL_DETENU'):
                                try:
                                    # remove spaces and parse comma/point
                                    ns = s.replace(' ', '').replace(',', '.')
                                    if '.' in ns:
                                        r[h] = float(ns)
                                    else:
                                        r[h] = int(ns)
                                except Exception:
                                    r[h] = s
                            else:
                                r[h] = s
            assoc_rows.append(r)
        if assoc_rows:
            assoc_df = _pd.DataFrame(assoc_rows)

    # Contrat
    if contrat_vals:
        cid = _next_id('Contrats', 'ID_CONTRAT')
        r: dict = {h: None for h in _const.contrat_headers}
        r['ID_CONTRAT'] = cid
        r['ID_SOCIETE'] = soc_df['ID_SOCIETE'].iloc[0] if not soc_df.empty else None
        # Map keys used by ContratForm -> canonical headers
        map_c = {
            'date_contrat': 'DATE_CONTRAT',
            # ContratForm uses 'period'
            'period': 'PERIOD_DOMCIL',
            # ContratForm uses 'prix_mensuel' and 'prix_inter'
            'prix_mensuel': 'PRIX_CONTRAT', 'prix_inter': 'PRIX_INTERMEDIARE_CONTRAT',
            'date_debut': 'DOM_DATEDEB', 'date_fin': 'DOM_DATEFIN'
        }
        for k, h in map_c.items():
            if k in contrat_vals:
                v = contrat_vals.get(k)
                if h.upper().find('DATE') >= 0:
                    dt = _to_datetime(v)
                    r[h] = dt.to_pydatetime() if dt is not None else None
                else:
                    if v is None:
                        r[h] = None
                    elif isinstance(v, (int, float)):
                        r[h] = v
                    else:
                        s = str(v).strip()
                        # Try to parse prices/numeric fields into numbers
                        if h in ('PRIX_CONTRAT', 'PRIX_INTERMEDIARE_CONTRAT'):
                            try:
                                ns = s.replace(' ', '').replace(',', '.')
                                if '.' in ns:
                                    r[h] = float(ns)
                                else:
                                    r[h] = int(ns)
                            except Exception:
                                r[h] = s
                        else:
                            r[h] = s
        contrat_df = _pd.DataFrame([r])

    # Write into workbook
    # If file exists, append; otherwise create fresh workbook
    if not path.exists():
        with _pd.ExcelWriter(path, engine='openpyxl') as writer:
            if not soc_df.empty:
                soc_df.to_excel(writer, sheet_name='Societes', index=False)
            else:
                # ensure header exists
                _pd.DataFrame(columns=_const.societe_headers).to_excel(writer, sheet_name='Societes', index=False)
            if not assoc_df.empty:
                assoc_df.to_excel(writer, sheet_name='Associes', index=False)
            else:
                _pd.DataFrame(columns=_const.associe_headers).to_excel(writer, sheet_name='Associes', index=False)
            if not contrat_df.empty:
                contrat_df.to_excel(writer, sheet_name='Contrats', index=False)
            else:
                _pd.DataFrame(columns=_const.contrat_headers).to_excel(writer, sheet_name='Contrats', index=False)
        return

    # Append to existing workbook — safer approach:
    # For each canonical sheet, read existing data, align columns to canonical headers,
    # concat the new rows, then write back replacing the sheet. This avoids column
    # shifts when the existing workbook has a different header layout.
    try:
        from . import constants as _const
        sheets_to_write = [
            ("Societes", soc_df, _const.societe_headers),
            ("Associes", assoc_df, _const.associe_headers),
            ("Contrats", contrat_df, _const.contrat_headers),
        ]

        for sheet_name, new_df, headers in sheets_to_write:
            if new_df.empty:
                # still ensure the sheet exists with correct headers
                try:
                    existing = _pd.read_excel(path, sheet_name=sheet_name, dtype=str)
                except Exception:
                    existing = _pd.DataFrame(columns=headers)
                if set(existing.columns) != set(headers):
                    # rewrite sheet with canonical headers but keep existing rows aligned if possible
                    existing_aligned = existing.reindex(columns=headers, fill_value='')
                    try:
                        with _pd.ExcelWriter(path, engine='openpyxl', mode='a', if_sheet_exists='replace') as writer:
                            existing_aligned.to_excel(writer, sheet_name=sheet_name, index=False)
                    except TypeError:
                        # pandas older versions may not support if_sheet_exists; fallback
                        wb = load_workbook(path)
                        if sheet_name in wb.sheetnames:
                            std = wb[sheet_name]
                            wb.remove(std)
                            wb.save(path)
                        with _pd.ExcelWriter(path, engine='openpyxl', mode='a') as writer:
                            existing_aligned.to_excel(writer, sheet_name=sheet_name, index=False)
                continue

            # Read existing sheet if present
            try:
                existing = _pd.read_excel(path, sheet_name=sheet_name, dtype=str)
            except Exception:
                existing = _pd.DataFrame(columns=headers)

            # Reindex both to canonical headers to avoid column shifts
            existing_aligned = existing.reindex(columns=headers, fill_value='')
            new_aligned = new_df.reindex(columns=headers, fill_value='')

            # Avoid concatenating empty or all-NA frames to prevent pandas FutureWarning
            parts = []
            try:
                if not existing_aligned.dropna(how='all').empty:
                    parts.append(existing_aligned)
            except Exception:
                # if dropna fails for any reason, fall back to using the raw frame
                if not existing_aligned.empty:
                    parts.append(existing_aligned)
            try:
                if not new_aligned.dropna(how='all').empty:
                    parts.append(new_aligned)
            except Exception:
                if not new_aligned.empty:
                    parts.append(new_aligned)

            if parts:
                combined = _pd.concat(parts, ignore_index=True)
            else:
                # both frames empty/all-NA -> produce an empty canonical DataFrame
                combined = _pd.DataFrame(columns=headers)

            # Write back replacing the sheet — use if_sheet_exists='replace' when available
            try:
                with _pd.ExcelWriter(path, engine='openpyxl', mode='a', if_sheet_exists='replace') as writer:
                    combined.to_excel(writer, sheet_name=sheet_name, index=False)
            except TypeError:
                # Fallback: remove sheet via openpyxl then append
                wb = load_workbook(path)
                if sheet_name in wb.sheetnames:
                    try:
                        std = wb[sheet_name]
                        wb.remove(std)
                        wb.save(path)
                    except Exception:
                        pass
                with _pd.ExcelWriter(path, engine='openpyxl', mode='a') as writer:
                    combined.to_excel(writer, sheet_name=sheet_name, index=False)
    except Exception:
        # In case of any failure fall back to the previous overlay append method
        try:
            with _pd.ExcelWriter(path, engine='openpyxl', mode='a', if_sheet_exists='overlay') as writer:
                if not soc_df.empty:
                    if 'Societes' in writer.book.sheetnames:
                        start = writer.book['Societes'].max_row
                        soc_df.to_excel(writer, sheet_name='Societes', index=False, header=False, startrow=start)
                    else:
                        soc_df.to_excel(writer, sheet_name='Societes', index=False)
                if not assoc_df.empty:
                    if 'Associes' in writer.book.sheetnames:
                        start = writer.book['Associes'].max_row
                        assoc_df.to_excel(writer, sheet_name='Associes', index=False, header=False, startrow=start)
                    else:
                        assoc_df.to_excel(writer, sheet_name='Associes', index=False)
                if not contrat_df.empty:
                    if 'Contrats' in writer.book.sheetnames:
                        start = writer.book['Contrats'].max_row
                        contrat_df.to_excel(writer, sheet_name='Contrats', index=False, header=False, startrow=start)
                    else:
                        contrat_df.to_excel(writer, sheet_name='Contrats', index=False)
        except Exception:
            logger.exception('Failed to append records to workbook')

    # After writing/appending, ensure date columns have a proper Excel number format
    try:
        from . import constants as _const
        wb = load_workbook(path)
        # iterate canonical sheets and apply format to columns whose header contains 'DATE'
        for sheet_name, headers in [('Societes', _const.societe_headers), ('Associes', _const.associe_headers), ('Contrats', _const.contrat_headers)]:
            if sheet_name not in wb.sheetnames:
                continue
            ws = wb[sheet_name]
            for idx, hdr in enumerate(headers, start=1):
                if not hdr:
                    continue
                if 'DATE' in hdr.upper():
                    col_letter = get_column_letter(idx)
                    for r_i in range(2, ws.max_row + 1):
                        try:
                            cell = ws[f"{col_letter}{r_i}"]
                            cell.number_format = 'DD/MM/YYYY'
                        except Exception:
                            pass
        wb.save(path)
    except Exception:
        logger.exception('Failed to apply date number formats after writing records')

    # Try to autofit column widths for all sheets to improve readability
    try:
        wb = load_workbook(path)
        for ws in wb.worksheets:
            try:
                # compute max length per column (include header row)
                for idx, col_cells in enumerate(ws.columns, start=1):
                    max_len = 0
                    col_letter = get_column_letter(idx)
                    for cell in col_cells:
                        try:
                            val = cell.value
                            if val is None:
                                l = 0
                            else:
                                l = len(str(val))
                            if l > max_len:
                                max_len = l
                        except Exception:
                            continue
                    # set width with some padding; guard minimum width
                    width = max(8, float(max_len) + 2)
                    try:
                        ws.column_dimensions[col_letter].width = width
                    except Exception:
                        pass
            except Exception:
                continue
        # Apply header style and column-specific formatting
        try:
            from openpyxl.styles import Font, Alignment, PatternFill
            header_font = Font(bold=True)
            header_fill = PatternFill(fill_type='solid', fgColor='DDDDDD')
            header_align = Alignment(horizontal='center', vertical='center')
            wrap_align = Alignment(wrap_text=True, vertical='top')

            for ws in wb.worksheets:
                try:
                    # header row styling
                    for cell in list(ws[1]):
                        try:
                            cell.font = header_font
                            cell.fill = header_fill
                            cell.alignment = header_align
                        except Exception:
                            pass
                    # per-column formatting based on header name
                    for idx in range(1, ws.max_column + 1):
                        try:
                            hdr = ws.cell(row=1, column=idx).value
                            if not hdr:
                                continue
                            h = str(hdr).upper()
                            col_letter = get_column_letter(idx)
                            # numeric columns (integers)
                            if h in ('CAPITAL', 'CAPITAL_DETENU', 'PARTS'):
                                for r in range(2, ws.max_row + 1):
                                    try:
                                        c = ws[f"{col_letter}{r}"]
                                        c.number_format = '#,##0'
                                        c.alignment = Alignment(horizontal='right', vertical='top')
                                    except Exception:
                                        pass
                            # pricing / currency columns
                            if h in ('PRIX_CONTRAT', 'PRIX_INTERMEDIARE_CONTRAT'):
                                for r in range(2, ws.max_row + 1):
                                    try:
                                        c = ws[f"{col_letter}{r}"]
                                        c.number_format = '#,##0.00'
                                        c.alignment = Alignment(horizontal='right', vertical='top')
                                    except Exception:
                                        pass
                            # phone as text
                            if h in ('PHONE',):
                                for r in range(2, ws.max_row + 1):
                                    try:
                                        c = ws[f"{col_letter}{r}"]
                                        c.number_format = '@'
                                        c.alignment = Alignment(horizontal='left', vertical='top')
                                    except Exception:
                                        pass
                            # long text fields -> wrap
                            if h in ('ADRESSE', 'STE_ADRESS', 'LIEU_NAISS'):
                                for r in range(2, ws.max_row + 1):
                                    try:
                                        c = ws[f"{col_letter}{r}"]
                                        c.alignment = wrap_align
                                    except Exception:
                                        pass
                        except Exception:
                            # if anything fails for this header/column, continue with next
                            continue
                    # freeze header row for easier navigation
                    try:
                        ws.freeze_panes = ws['A2']
                    except Exception:
                        pass
                except Exception:
                    continue
        except Exception:
            logger.exception('Failed to apply header/column formatting')

        wb.save(path)
    except Exception:
        logger.exception('Failed to autofit column widths after writing records')


def migrate_excel_workbook(path):
    """Detects sheets that look like canonical sheets but have different names
    and merges their rows into the canonical sheet, then removes the old sheet.
    """
    path = _Path(path)
    if not path.exists():
        return
    try:
        # Create a timestamped backup before modifying the workbook
        timestamp = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')
        backup_name = f"{path.stem}_backup_{timestamp}{path.suffix}"
        backup_path = path.parent / backup_name
        shutil.copy2(path, backup_path)
        logger.info(f"Created backup of workbook before migration: {backup_path}")
        # Also add the backup path to the generation report (if present)
        try:
            tmp_out = Path(__file__).resolve().parent.parent.parent / 'tmp_out'
            tmp_out.mkdir(parents=True, exist_ok=True)

            # Try to find an HTML generation report and update its embedded JSON
            updated = False
            try:
                for html in tmp_out.glob('*_Raport_Docs_generer.html'):
                    try:
                        text = html.read_text(encoding='utf-8')
                        start_tag = '<pre id="genjson">'
                        end_tag = '</pre>'
                        sidx = text.find(start_tag)
                        if sidx != -1:
                            sidx += len(start_tag)
                            eidx = text.find(end_tag, sidx)
                            if eidx != -1:
                                raw = text[sidx:eidx]
                                try:
                                    rep = json.loads(raw)
                                except Exception:
                                    rep = {}
                                rep['migration_backup'] = str(backup_path)
                                # replace the JSON block
                                new_raw = json.dumps(rep, ensure_ascii=False, indent=2)
                                new_text = text[:sidx] + new_raw + text[eidx:]
                                html.write_text(new_text, encoding='utf-8')
                                updated = True
                                break
                    except Exception:
                        continue
            except Exception:
                updated = False

            if not updated:
                # Fallback: write a named JSON report matching the HTML convention
                # so external tooling can find it more reliably. Use a safe
                # default company string if none is available.
                try:
                    import datetime as _dt
                    gen_date = _dt.date.today().isoformat()
                    gen_time = _dt.datetime.now().strftime('%H-%M-%S')
                except Exception:
                    gen_date = 'unknown_date'
                    import time as _time
                    gen_time = _time.strftime('%H-%M-%S')
                company_clean = 'UnknownCompany'
                gen_name = f"{gen_date}_{company_clean}_Raport_Docs_generer_{gen_time}.json"
                gen_report = tmp_out / gen_name
                if gen_report.exists():
                    try:
                        with gen_report.open('r', encoding='utf-8') as gf:
                            rep = json.load(gf)
                    except Exception:
                        rep = {}
                else:
                    rep = {}
                rep['migration_backup'] = str(backup_path)
                with gen_report.open('w', encoding='utf-8') as gf:
                    json.dump(rep, gf, ensure_ascii=False, indent=2)
        except Exception:
            logger.exception('Failed to update generation report with migration backup')
    except Exception:
        logger.exception('Failed to create backup before migration; continuing without backup')
    from . import constants as _const
    wb = load_workbook(path)
    changed = False
    # Build set of canonical header sets for quick matching
    canonical = {name: set([h.upper() for h in cols]) for name, cols in _const.excel_sheets.items()}
    to_remove = []
    for sheet in list(wb.sheetnames):
        if sheet in canonical:
            continue
        try:
            df = _pd.read_excel(path, sheet_name=sheet, dtype=str)
        except Exception:
            continue
        hdrs = set([c.upper() for c in df.columns])
        # find best matching canonical sheet
        for cname, cheaders in canonical.items():
            # if overlap is large relative to the legacy sheet size, consider it a match
            # this lets small legacy extracts (few columns) be merged
            overlap = len(hdrs & cheaders)
            if overlap >= max(1, int(len(hdrs) * 0.5)):
                # append df to canonical sheet
                try:
                    existing = _pd.read_excel(path, sheet_name=cname, dtype=str)
                except Exception:
                    existing = _pd.DataFrame(columns=_const.excel_sheets.get(cname, []))
                # Align legacy df to canonical headers to ensure correct column placement
                canonical_cols = _const.excel_sheets.get(cname, [])
                df_aligned = df.reindex(columns=canonical_cols, fill_value='')
                # write back by appending
                with _pd.ExcelWriter(path, engine='openpyxl', mode='a', if_sheet_exists='overlay') as writer:
                    startrow = writer.book[cname].max_row if cname in writer.book.sheetnames else 0
                    df_aligned.to_excel(writer, sheet_name=cname, index=False, header=False, startrow=startrow)
                to_remove.append(sheet)
                changed = True
                break
    if to_remove:
        # reload workbook to ensure any appended data is present before removing old sheets
        wb = load_workbook(path)
        for s in to_remove:
            try:
                std = wb[s]
                wb.remove(std)
            except Exception:
                pass
        wb.save(path)
    # Ensure Excel date columns use a readable date number format
    try:
        # Reload constants to get canonical headers
        from . import constants as _const
        wb = load_workbook(path)
        for cname, cols in _const.excel_sheets.items():
            if cname not in wb.sheetnames:
                continue
            ws = wb[cname]
            for idx, col_name in enumerate(cols, start=1):
                if not col_name:
                    continue
                if 'DATE' in col_name.upper():
                    col_letter = get_column_letter(idx)
                    # Apply number_format to all data cells in this column
                    for row in range(2, ws.max_row + 1):
                        try:
                            cell = ws[f"{col_letter}{row}"]
                            # set format regardless; Excel/openpyxl will ignore non-dates
                            cell.number_format = 'DD/MM/YYYY'
                        except Exception:
                            pass
        wb.save(path)
    except Exception:
        logger.exception('Failed to apply date number formats after migration')
    # Autofit columns for all sheets after migration adjustments
    try:
        wb = load_workbook(path)
        for ws in wb.worksheets:
            try:
                for idx, col_cells in enumerate(ws.columns, start=1):
                    max_len = 0
                    col_letter = get_column_letter(idx)
                    for cell in col_cells:
                        try:
                            val = cell.value
                            if val is None:
                                l = 0
                            else:
                                l = len(str(val))
                            if l > max_len:
                                max_len = l
                        except Exception:
                            continue
                    width = max(8, float(max_len) + 2)
                    try:
                        ws.column_dimensions[col_letter].width = width
                    except Exception:
                        pass
            except Exception:
                continue
        wb.save(path)
    except Exception:
        logger.exception('Failed to autofit column widths after migration')
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
