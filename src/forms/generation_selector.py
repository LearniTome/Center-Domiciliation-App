"""
Generation Selector Dialog - Choose document generation type (Creation vs Domiciliation)
with automatic template selection and upload custom templates.
"""

import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from pathlib import Path
from typing import Optional, List, Callable
import shutil
import logging
import threading
import re

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler, WindowManager
from ..utils.doc_generator import render_templates

logger = logging.getLogger(__name__)

# Template mappings for different document types
DOMICILIATION_TEMPLATES = ['attest', 'contrat', 'domiciliation']
LEGAL_FORM_OPTIONS = ['SARL-AU', 'SARL', 'SA', 'Personne Physique', 'Association', 'Fondation']
LEGAL_FORM_TO_FOLDER = {
    'SARL-AU': 'SARL AU',
    'SARL': 'SARL',
    'SA': 'SA',
    'Personne Physique': 'Personne Physique',
    'Association': 'Association',
    'Fondation': 'Fondation',
}
LEGAL_FORM_PREFIXES = {
    'SARL AU': 'SARL-AU',
    'SARL': 'SARL',
    'SA': 'SA',
    'Personne Physique': 'PP',
    'Association': 'ASSO',
    'Fondation': 'FOND',
}


def normalize_legal_form_selection(raw_value: Optional[str]) -> str:
    """Normalize legal-form labels from forms/DB into selector options."""
    raw = str(raw_value or "").strip()
    if not raw:
        return LEGAL_FORM_OPTIONS[0]

    compact = re.sub(r"[\s\-_]+", " ", raw).strip().upper()
    mapping = {
        "SARL AU": "SARL-AU",
        "SARLAU": "SARL-AU",
        "SARL": "SARL",
        "SA": "SA",
        "PERSONNE PHYSIQUE": "Personne Physique",
        "PP": "Personne Physique",
        "ASSOCIATION": "Association",
        "FONDATION": "Fondation",
    }
    return mapping.get(compact, LEGAL_FORM_OPTIONS[0])


def compute_selection_feedback(
    legal_form: str,
    generation_type: str,
    selected_count: int,
    available_count: Optional[int] = None,
) -> dict:
    """Compute summary text and readiness state for generation selection."""
    form_label = (legal_form or '').strip() or '—'
    generation_map = {
        'creation': 'Création',
        'domiciliation': 'Domiciliation',
    }
    type_label = generation_map.get((generation_type or '').strip().lower(), '—')
    count = max(0, int(selected_count or 0))
    available = count if available_count is None else max(0, int(available_count or 0))

    missing = []
    if form_label == '—':
        missing.append('sélectionner une forme juridique')
    if type_label == '—':
        missing.append('choisir un type de génération')
    if available <= 0:
        missing.append('aucun modèle disponible pour cette sélection')
    elif count <= 0:
        missing.append('cocher au moins un modèle')

    summary = f"Résumé sélection  |  Forme: {form_label}  |  Type: {type_label}  |  Nb modèles: {count}"
    if missing:
        tooltip = "Pour continuer: " + ", ".join(missing) + "."
    else:
        tooltip = "Prêt à générer."

    return {
        'summary': summary,
        'is_ready': len(missing) == 0,
        'tooltip': tooltip,
    }


def template_matches_generation_type(template_name: str, generation_type: str) -> bool:
    """Return True when the template should be pre-selected for the chosen flow."""
    name = (template_name or '').strip().lower()
    generation = (generation_type or '').strip().lower()
    is_domiciliation = any(keyword in name for keyword in DOMICILIATION_TEMPLATES)

    if generation == 'domiciliation':
        return is_domiciliation
    if generation == 'creation':
        return True
    return False


class GenerationSelectorDialog(tk.Toplevel):
    """Modal dialog to select generation type and manage templates."""

    def __init__(
        self,
        parent,
        values: Optional[dict] = None,
        output_format: str = 'word',
        save_callback: Optional[Callable[[], Optional[Path]]] = None,
    ):
        super().__init__(parent)
        self.parent = parent
        self.values = values or {}
        self.output_format = output_format  # 'word', 'pdf', or 'both'
        self.save_callback = save_callback
        self._initial_center_done = False
        self._one_shot_recenter_done = False
        self._proceed_tooltip_window = None
        self._proceed_tooltip_text = ""
        self._last_uploaded_template_name: Optional[str] = None
        self.output_format_var = tk.StringVar(value='word')
        self.save_before_var = tk.BooleanVar(value=True)
        self.initial_legal_form = self._extract_initial_legal_form()

        # Backward-compat normalization.
        normalized = (self.output_format or '').strip().lower()
        if normalized in ('docx', 'word'):
            self.output_format_var.set('word')
        elif normalized in ('pdf',):
            self.output_format_var.set('pdf')
        elif normalized in ('both',):
            self.output_format_var.set('both')
        else:
            self.output_format_var.set('word')

        self.title("📄 Sélectionner les documents à générer")
        # Dynamic initial geometry: use available screen height, keep vertical expansion comfortable.
        try:
            sw = self.winfo_screenwidth()
            sh = self.winfo_screenheight()
            initial_w = min(1320, max(1080, sw - 80))
            initial_h = min(900, max(760, sh - 120))
            self.geometry(f"{initial_w}x{initial_h}")
        except Exception:
            self.geometry("1260x840")
        self.resizable(True, True)
        self.minsize(1080, 700)

        # Apply the SAME theme as the parent window (not a new one)
        # This ensures the dialog inherits the parent's style configuration
        if isinstance(parent, tk.Tk):
            # Parent is the main window - use its theme
            self.theme_manager = parent.theme_manager if hasattr(parent, 'theme_manager') else ThemeManager(self)
        else:
            # Parent is another window - use its theme
            self.theme_manager = ThemeManager(self)
        
        # Configure dialog background to match theme
        try:
            bg_color = '#2b2b2b' if self.theme_manager.is_dark_mode else '#f0f0f0'
            self.configure(bg=bg_color)
        except Exception:
            pass

        # Make modal BEFORE centering
        try:
            self.transient(parent)
            self.grab_set()
        except Exception:
            pass

        # Results
        self.generation_type: Optional[str] = None
        self.creation_type: Optional[str] = None
        self.selected_templates: List[Path] = []
        self.result = None

        # Setup UI
        self._setup_ui()
        # First center pass after widgets are laid out (uses final requested size).
        self.after_idle(self._center_initial_position)
        # One-shot recenter at first mapping/configure to absorb DPI/window chrome offset.
        self.bind("<Map>", self._one_shot_recenter_on_first_show, add="+")
        self.bind("<Configure>", self._one_shot_recenter_on_first_show, add="+")

    def _extract_initial_legal_form(self) -> str:
        """Infer initial legal form from collected values when available."""
        societe = self.values.get("societe", {}) if isinstance(self.values, dict) else {}
        candidates = []
        if isinstance(societe, dict):
            candidates.extend(
                [
                    societe.get("forme_juridique"),
                    societe.get("FormJur"),
                    societe.get("FORME_JUR"),
                ]
            )
        if isinstance(self.values, dict):
            candidates.extend(
                [
                    self.values.get("legal_form"),
                    self.values.get("forme_juridique"),
                    self.values.get("FormJur"),
                ]
            )
        for value in candidates:
            if str(value or "").strip():
                return normalize_legal_form_selection(str(value))
        return LEGAL_FORM_OPTIONS[0]

    def _center_initial_position(self):
        """Center once after UI construction to get stable initial placement."""
        if self._initial_center_done:
            return
        self._initial_center_done = True
        try:
            self._center_on_screen("initial")
            self.focus_set()
        except Exception:
            pass

    def _one_shot_recenter_on_first_show(self, _event=None):
        """Recenter once on first show/configure; never recenters continuously."""
        if self._one_shot_recenter_done:
            return
        self._one_shot_recenter_done = True
        # Two-pass centering to absorb late window-manager size adjustments.
        self.after_idle(self._safe_recenter)
        self.after(140, self._safe_recenter)

    def _safe_recenter(self):
        """Shared recenter helper used by first-show one-shot recenter."""
        try:
            self._center_on_screen("one_shot")
        except Exception:
            pass

    def _center_on_screen(self, source: str = "unknown"):
        """Center horizontally on screen and align vertically with parent window."""
        self.update_idletasks()
        window_width = self.winfo_width()
        window_height = self.winfo_height()
        screen_x = 0
        screen_y = 0
        screen_width = self.winfo_screenwidth()
        screen_height = self.winfo_screenheight()

        # On Windows, center on the monitor nearest to the parent window.
        try:
            import platform
            if platform.system() == "Windows":
                import ctypes

                class _RECT(ctypes.Structure):
                    _fields_ = [
                        ("left", ctypes.c_long),
                        ("top", ctypes.c_long),
                        ("right", ctypes.c_long),
                        ("bottom", ctypes.c_long),
                    ]

                class _MONITORINFO(ctypes.Structure):
                    _fields_ = [
                        ("cbSize", ctypes.c_ulong),
                        ("rcMonitor", _RECT),
                        ("rcWork", _RECT),
                        ("dwFlags", ctypes.c_ulong),
                    ]

                monitor_default_to_nearest = 2
                hwnd = int(self.parent.winfo_id()) if getattr(self, "parent", None) else int(self.winfo_id())
                monitor = ctypes.windll.user32.MonitorFromWindow(hwnd, monitor_default_to_nearest)
                mi = _MONITORINFO()
                mi.cbSize = ctypes.sizeof(_MONITORINFO)
                ok = ctypes.windll.user32.GetMonitorInfoW(monitor, ctypes.byref(mi))
                if ok:
                    # Use full monitor rectangle for true screen center.
                    screen_x = int(mi.rcMonitor.left)
                    screen_y = int(mi.rcMonitor.top)
                    screen_width = int(mi.rcMonitor.right - mi.rcMonitor.left)
                    screen_height = int(mi.rcMonitor.bottom - mi.rcMonitor.top)
        except Exception:
            pass

        x = screen_x + (screen_width - window_width) // 2
        # Keep popup at the same vertical level as the main app window.
        y = screen_y + (screen_height - window_height) // 2
        try:
            if getattr(self, "parent", None):
                y = int(self.parent.winfo_rooty())
        except Exception:
            pass

        x = max(screen_x, min(x, screen_x + screen_width - window_width))
        y = max(screen_y, min(y, screen_y + screen_height - window_height))

        self.geometry(f"{window_width}x{window_height}+{x}+{y}")

    def _setup_ui(self):
        """Setup the dialog UI with improved design and layout.
        
        Layout:
        ┌──────────────────────────────────────┐
        │ [Forme juridique] | [Type génération] │
        ├──────────────────────────────────────┤
        │ [Options de génération]               │
        ├──────────────────────────────────────┤
        │ [Liste des modèles sélectionnables]   │
        ├──────────────────────────────────────┤
        │ [Actions modèles]                     │
        ├──────────────────────────────────────┤
        │ [Résumé + Boutons: Générer/Annuler]   │
        └──────────────────────────────────────┘
        """
        main_frame = ttk.Frame(self, padding=14)
        main_frame.pack(fill='both', expand=True)

        # Bottom fixed area: selection summary + action buttons
        footer = ttk.Frame(main_frame)
        footer.pack(side='bottom', fill='x')
        summary_row = ttk.Frame(footer)
        summary_row.pack(fill='x', pady=(2, 8))
        self.summary_var = tk.StringVar(value="Résumé sélection  |  Forme: —  |  Type: —  |  Nb modèles: 0")
        self.summary_label = ttk.Label(
            summary_row,
            textvariable=self.summary_var,
            style='Subheader.TLabel',
        )
        self.summary_label.pack(side='left', anchor='w')

        bottom_actions = ttk.Frame(footer)
        bottom_actions.pack(fill='x')

        self.cancel_btn = WidgetFactory.create_button(
            bottom_actions,
            text="❌ Annuler",
            command=self._cancel,
            style='Cancel.TButton'
        )
        self.cancel_btn.pack(side='right', padx=5)

        proceed_wrap = ttk.Frame(bottom_actions)
        proceed_wrap.pack(side='right')

        self.proceed_btn = WidgetFactory.create_button(
            proceed_wrap,
            text="✅ Procéder à la génération",
            command=self._confirm,
            style='Success.TButton'
        )
        self.proceed_btn.pack(side='right', padx=5)
        proceed_wrap.bind('<Enter>', self._show_proceed_tooltip_if_disabled)
        proceed_wrap.bind('<Leave>', self._hide_proceed_tooltip)
        self.proceed_btn.bind('<Enter>', self._show_proceed_tooltip_if_disabled)
        self.proceed_btn.bind('<Leave>', self._hide_proceed_tooltip)

        # ===== SECTIONS 1 & 2: SIDE BY SIDE =====
        top_sections_frame = ttk.Frame(main_frame)
        top_sections_frame.pack(fill='x', pady=(0, 14))

        # Configure columns: 50/50 split
        top_sections_frame.columnconfigure(0, weight=1)
        top_sections_frame.columnconfigure(1, weight=1)

        # LEFT: LEGAL FORM SELECTION (Combobox)
        legal_form_frame = ttk.LabelFrame(top_sections_frame, text="📋 Forme juridique", padding=15)
        legal_form_frame.grid(row=0, column=0, padx=(0, 10), sticky='nsew')

        self.legal_form_var = tk.StringVar(value=self.initial_legal_form)
        self.legal_form_combo = ttk.Combobox(
            legal_form_frame,
            textvariable=self.legal_form_var,
            values=LEGAL_FORM_OPTIONS,
            state='readonly',
            width=34,
        )
        self.legal_form_combo.pack(fill='x', pady=(6, 0))
        self.legal_form_combo.bind('<<ComboboxSelected>>', lambda _e: self._on_legal_form_changed())

        # RIGHT: GENERATION TYPE SELECTION
        type_frame = ttk.LabelFrame(top_sections_frame, text="📊 Type de génération", padding=15)
        type_frame.grid(row=0, column=1, padx=(10, 0), sticky='nsew')

        self.gen_type_var = tk.StringVar(value='creation')
        type_frame.columnconfigure(0, weight=1)
        type_frame.columnconfigure(1, weight=1)

        ttk.Radiobutton(
            type_frame,
            text="📄 Création",
            variable=self.gen_type_var,
            value='creation',
            command=self._on_generation_type_changed
        ).grid(row=0, column=0, sticky='w', padx=(0, 14), pady=4)

        ttk.Radiobutton(
            type_frame,
            text="🏢 Domiciliation",
            variable=self.gen_type_var,
            value='domiciliation',
            command=self._on_generation_type_changed
        ).grid(row=0, column=1, sticky='w', pady=4)

        # OPTIONS (imported from old popup flow)
        options_frame = ttk.LabelFrame(main_frame, text="⚙️Options de génération", padding=12)
        options_frame.pack(fill='x', pady=(0, 14))
        options_frame.columnconfigure(0, weight=1)
        options_frame.columnconfigure(1, weight=1)
        options_frame.columnconfigure(2, weight=1)

        ttk.Radiobutton(
            options_frame,
            text="📄 Word uniquement",
            variable=self.output_format_var,
            value='word',
        ).grid(row=0, column=0, sticky='w', padx=(0, 12))

        ttk.Radiobutton(
            options_frame,
            text="📑 PDF uniquement",
            variable=self.output_format_var,
            value='pdf',
        ).grid(row=0, column=1, sticky='w', padx=(0, 12))

        ttk.Radiobutton(
            options_frame,
            text="📄+📑 Word & PDF",
            variable=self.output_format_var,
            value='both',
        ).grid(row=0, column=2, sticky='w')

        ttk.Checkbutton(
            options_frame,
            text="💾 Sauvegarder dans la base avant génération",
            variable=self.save_before_var,
        ).grid(row=1, column=0, columnspan=3, sticky='w', pady=(8, 0))

        # ===== SECTION 3: TEMPLATE LIST =====
        template_frame = ttk.LabelFrame(main_frame, text="Modèles disponibles", padding=15)
        template_frame.pack(fill='both', expand=True, pady=(0, 10))

        self.template_count_var = tk.StringVar(value="0 modèle détecté")
        self.template_status_var = tk.StringVar(value="Sélectionnez les modèles à générer.")

        ttk.Label(
            template_frame,
            textvariable=self.template_count_var,
            font=('Segoe UI', 12, 'bold'),
        ).pack(anchor='w')

        ttk.Label(
            template_frame,
            textvariable=self.template_status_var,
            style='FieldLabel.TLabel',
            justify='left',
            wraplength=1100,
        ).pack(anchor='w', fill='x', pady=(4, 10))

        list_frame = ttk.Frame(template_frame)
        list_frame.pack(fill='both', expand=True)

        scrollbar = ttk.Scrollbar(list_frame)
        scrollbar.pack(side='right', fill='y')

        canvas_bg = '#2b2b2b' if getattr(self.theme_manager, 'is_dark_mode', True) else '#f7f7f7'
        border_color = '#555555' if getattr(self.theme_manager, 'is_dark_mode', True) else '#cccccc'
        self.template_canvas = tk.Canvas(
            list_frame,
            bg=canvas_bg,
            highlightthickness=1,
            highlightbackground=border_color,
            relief='solid',
            height=1,
        )
        self.template_canvas.pack(side='left', fill='both', expand=True)
        scrollbar.config(command=self.template_canvas.yview)
        self.template_canvas.config(yscrollcommand=scrollbar.set)

        self.template_inner_frame = ttk.Frame(self.template_canvas)
        self.template_canvas_window = self.template_canvas.create_window((0, 0), window=self.template_inner_frame, anchor='nw')
        self.template_inner_frame.columnconfigure(0, weight=1)
        self.template_inner_frame.bind('<Configure>', self._on_frame_configure)
        self.template_canvas.bind('<Configure>', self._on_canvas_configure)

        actions_frame = ttk.Frame(main_frame)
        actions_frame.pack(fill='x', pady=(0, 12))

        refresh_btn = WidgetFactory.create_button(
            actions_frame,
            text="Actualiser",
            command=self._refresh_template_list,
            style='Refresh.TButton',
            icon_key='',
        )
        refresh_btn.configure(width=15)
        refresh_btn.pack(side='left', padx=(0, 6))

        view_btn = WidgetFactory.create_button(
            actions_frame,
            text="Consulter",
            command=self._view_templates,
            style='View.TButton',
            icon_key='',
        )
        view_btn.configure(width=15)
        view_btn.pack(side='left', padx=(0, 6))

        upload_btn = WidgetFactory.create_button(
            actions_frame,
            text="Uploader",
            command=self._upload_template,
            style='Upload.TButton',
            icon_key='',
        )
        upload_btn.configure(width=15)
        upload_btn.pack(side='left', padx=(0, 6))

        copy_btn = WidgetFactory.create_button(
            actions_frame,
            text="Copier modèles",
            command=self._copy_root_templates_to_legal_folders,
            style='Copy.TButton',
            icon_key='',
        )
        copy_btn.configure(width=15)
        copy_btn.pack(side='left', padx=(0, 6))

        delete_btn = WidgetFactory.create_button(
            actions_frame,
            text="Supprimer modèles",
            command=self._delete_templates,
            style='Cancel.TButton',
            icon_key='',
        )
        delete_btn.configure(width=18)
        delete_btn.pack(side='left')

        self.available_templates: List[Path] = []
        self.template_vars = {}

        self._refresh_template_list()
        if self.gen_type_var.get():
            self._auto_select_templates(self.gen_type_var.get())
        self._update_selection_feedback()

    def _on_frame_configure(self, event=None):
        """Update the scroll region when the inner frame changes."""
        if hasattr(self, 'template_canvas'):
            self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))

    def _on_canvas_configure(self, event=None):
        """Resize the inner frame to match the canvas width."""
        if not hasattr(self, 'template_canvas'):
            return
        canvas_width = event.width if event is not None else self.template_canvas.winfo_width()
        if canvas_width > 1:
            self.template_canvas.itemconfigure(self.template_canvas_window, width=canvas_width)

    def _on_legal_form_changed(self):
        """Handle legal form radio button changes and refresh template list."""
        self._refresh_template_list()
        if self.gen_type_var.get():
            self._auto_select_templates(self.gen_type_var.get())
        self._update_selection_feedback()

    def _on_generation_type_changed(self):
        """Handle generation type changes and refresh automatic template summary."""
        self._refresh_template_list()
        if self.gen_type_var.get():
            self._auto_select_templates(self.gen_type_var.get())
        self._update_selection_feedback()

    def _selected_templates_count(self) -> int:
        return sum(1 for var in getattr(self, 'template_vars', {}).values() if var.get())

    def _selected_legal_form_folder(self) -> str:
        selected = (self.legal_form_var.get() or '').strip()
        return LEGAL_FORM_TO_FOLDER.get(selected, selected)

    def _update_selection_feedback(self):
        """Refresh summary row and proceed button state."""
        feedback = compute_selection_feedback(
            self.legal_form_var.get() if hasattr(self, 'legal_form_var') else '',
            self.gen_type_var.get() if hasattr(self, 'gen_type_var') else '',
            self._selected_templates_count(),
            len(getattr(self, 'available_templates', [])),
        )
        if hasattr(self, 'summary_var'):
            self.summary_var.set(feedback['summary'])

        self._proceed_tooltip_text = feedback['tooltip']
        if hasattr(self, 'proceed_btn'):
            if feedback['is_ready']:
                self.proceed_btn.state(['!disabled'])
                self._hide_proceed_tooltip()
            else:
                self.proceed_btn.state(['disabled'])

    def _show_proceed_tooltip_if_disabled(self, event=None):
        if not hasattr(self, 'proceed_btn'):
            return
        if 'disabled' not in self.proceed_btn.state():
            return
        if self._proceed_tooltip_window is not None:
            return
        try:
            x = self.proceed_btn.winfo_rootx() + 10
            y = self.proceed_btn.winfo_rooty() - 30
            tooltip = tk.Toplevel(self.proceed_btn)
            tooltip.wm_overrideredirect(True)
            tooltip.wm_geometry(f"+{x}+{y}")
            ttk.Label(
                tooltip,
                text=self._proceed_tooltip_text or "Sélection incomplète.",
                justify='left',
                style='FieldLabel.TLabel',
            ).pack(ipadx=8, ipady=4)
            self._proceed_tooltip_window = tooltip
        except Exception:
            self._proceed_tooltip_window = None

    def _hide_proceed_tooltip(self, event=None):
        if self._proceed_tooltip_window is not None:
            try:
                self._proceed_tooltip_window.destroy()
            except Exception:
                pass
            self._proceed_tooltip_window = None

    def _on_template_selection_changed(self):
        """Update footer summary when a checkbox changes."""
        self._update_selection_feedback()

    def _auto_select_templates(self, generation_type: str):
        """Auto-select matching templates while keeping manual deselection possible afterwards."""
        for template_path, var in self.template_vars.items():
            var.set(template_matches_generation_type(template_path.name, generation_type))

    def _resolve_templates_for_selection(self) -> List[Path]:
        """Return templates that match the current legal form and generation type."""
        try:
            selected_legal_form = (self.legal_form_var.get() or '').strip()
            if not selected_legal_form:
                return []

            return self._get_templates_for_legal_form(selected_legal_form)

        except Exception as e:
            logger.exception(f"Erreur lors du chargement des modèles: {e}")
            return []

    def _get_templates_for_legal_form(self, legal_form: str) -> List[Path]:
        """Return all .docx templates for the given legal form folder."""
        models_dir = PathManager.MODELS_DIR
        if not models_dir.exists():
            return []

        selected_form = (legal_form or '').strip()
        if not selected_form:
            return []

        form_path = models_dir / LEGAL_FORM_TO_FOLDER.get(selected_form, selected_form)
        if form_path.exists() and form_path.is_dir():
            return sorted(form_path.glob('*.docx'))
        return []

    def _format_template_display_name(self, template: Path) -> str:
        """Return the exact filename as stored on disk for the selected legal form."""
        return template.name

    def _refresh_template_list(self):
        """Refresh the list of available templates with manual checkboxes."""
        self.available_templates = self._resolve_templates_for_selection()
        previously_selected = {
            template.name for template, var in getattr(self, 'template_vars', {}).items()
            if var.get()
        }
        for widget in self.template_inner_frame.winfo_children():
            widget.destroy()
        self.template_vars = {}

        count = len(self.available_templates)
        count_label = "modèle détecté" if count == 1 else "modèles détectés"
        self.template_count_var.set(f"{count} {count_label}")

        selected_legal_form = (self.legal_form_var.get() or '').strip()
        gen_type = (self.gen_type_var.get() or '').strip()
        generation_label = {'creation': 'Création', 'domiciliation': 'Domiciliation'}.get(gen_type, '—')

        if not PathManager.MODELS_DIR.exists():
            self.template_status_var.set("Le dossier Models est introuvable.")
        elif not selected_legal_form:
            self.template_status_var.set("Choisissez une forme juridique pour afficher les modèles.")
        elif not gen_type:
            self.template_status_var.set("Choisissez un type de génération pour pré-cocher les modèles.")
        elif not self.available_templates:
            self.template_status_var.set(f"Aucun modèle trouvé pour {selected_legal_form} en mode {generation_label}.")
        elif gen_type == 'creation':
            self.template_status_var.set(
                "En mode Création, tous les modèles de cette forme juridique sont cochés automatiquement."
            )
        else:
            self.template_status_var.set(
                "Les modèles compatibles sont pré-cochés automatiquement. Vous pouvez ensuite décocher ceux à exclure."
            )

        if not self.available_templates:
            msg = ttk.Label(
                self.template_inner_frame,
                text="Aucun modèle à afficher.",
                style='FieldLabel.TLabel',
                justify='center',
            )
            msg.pack(fill='x', expand=True, padx=20, pady=40)
        else:
            for idx, template in enumerate(self.available_templates):
                is_selected = template.name in previously_selected
                var = tk.BooleanVar(value=is_selected)
                var.trace_add('write', lambda *_args: self._on_template_selection_changed())
                self.template_vars[template] = var

                row = ttk.Frame(self.template_inner_frame)
                row.pack(fill='x', padx=10, pady=2)

                display_name = self._format_template_display_name(template)
                if self._last_uploaded_template_name and template.name == self._last_uploaded_template_name:
                    display_name += "  [Nouveau]"

                ttk.Checkbutton(
                    row,
                    text=f"📄 {display_name}",
                    variable=var,
                ).pack(anchor='w', fill='x', padx=8, pady=5)

                if idx < len(self.available_templates) - 1:
                    ttk.Separator(self.template_inner_frame, orient='horizontal').pack(fill='x', padx=12, pady=(0, 2))

        self.template_inner_frame.update_idletasks()
        self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
        self.after(50, self._on_canvas_configure)
        self._last_uploaded_template_name = None
        self._update_selection_feedback()

    def _delete_templates(self):
        """Open a modal dialog to choose a legal form and delete templates from it."""
        selected_form = (self.legal_form_var.get() or '').strip() or LEGAL_FORM_OPTIONS[0]
        dialog = tk.Toplevel(self)
        dialog.title("Supprimer des modèles")
        dialog.geometry("760x560")
        dialog.minsize(680, 480)
        try:
            dialog.transient(self)
            dialog.grab_set()
        except Exception:
            pass

        container = ttk.Frame(dialog, padding=14)
        container.pack(fill='both', expand=True)

        top_frame = ttk.LabelFrame(container, text="Choisir la forme juridique", padding=12)
        top_frame.pack(fill='x', pady=(0, 10))

        delete_form_var = tk.StringVar(value=selected_form)
        delete_count_var = tk.StringVar(value="0 modèle sélectionné")
        delete_status_var = tk.StringVar(value="Choisissez les modèles à supprimer.")

        form_combo = ttk.Combobox(
            top_frame,
            textvariable=delete_form_var,
            values=LEGAL_FORM_OPTIONS,
            state='readonly',
            width=34,
        )
        form_combo.pack(fill='x')

        ttk.Label(
            container,
            textvariable=delete_count_var,
            font=('Segoe UI', 11, 'bold'),
        ).pack(anchor='w')
        ttk.Label(
            container,
            textvariable=delete_status_var,
            style='FieldLabel.TLabel',
            justify='left',
            wraplength=700,
        ).pack(anchor='w', fill='x', pady=(2, 10))

        list_frame = ttk.Frame(container)
        list_frame.pack(fill='both', expand=True)

        scrollbar = ttk.Scrollbar(list_frame)
        scrollbar.pack(side='right', fill='y')

        canvas_bg = '#2b2b2b' if getattr(self.theme_manager, 'is_dark_mode', True) else '#f7f7f7'
        border_color = '#555555' if getattr(self.theme_manager, 'is_dark_mode', True) else '#cccccc'
        canvas = tk.Canvas(
            list_frame,
            bg=canvas_bg,
            highlightthickness=1,
            highlightbackground=border_color,
            relief='solid',
            height=1,
        )
        canvas.pack(side='left', fill='both', expand=True)
        scrollbar.config(command=canvas.yview)
        canvas.config(yscrollcommand=scrollbar.set)

        inner_frame = ttk.Frame(canvas)
        canvas_window = canvas.create_window((0, 0), window=inner_frame, anchor='nw')

        dialog_vars = {}

        def _selected_delete_count() -> int:
            return sum(1 for var in dialog_vars.values() if var.get())

        def _update_delete_count():
            count = _selected_delete_count()
            label = "modèle sélectionné" if count == 1 else "modèles sélectionnés"
            delete_count_var.set(f"{count} {label}")

        def _on_delete_toggle(*_args):
            _update_delete_count()

        def _select_all_delete_templates():
            for var in dialog_vars.values():
                var.set(True)

        def _clear_all_delete_templates():
            for var in dialog_vars.values():
                var.set(False)

        def _refresh_delete_list(*_args):
            for widget in inner_frame.winfo_children():
                widget.destroy()
            dialog_vars.clear()

            legal_form = (delete_form_var.get() or '').strip()
            templates = self._get_templates_for_legal_form(legal_form)
            if templates:
                delete_status_var.set(
                    f"Sélectionnez les modèles à supprimer définitivement pour {legal_form}."
                )
                for idx, template in enumerate(templates):
                    var = tk.BooleanVar(value=False)
                    var.trace_add('write', _on_delete_toggle)
                    dialog_vars[template] = var

                    row = ttk.Frame(inner_frame)
                    row.pack(fill='x', padx=10, pady=2)
                    ttk.Checkbutton(
                        row,
                        text=f"📄 {self._format_template_display_name(template)}",
                        variable=var,
                    ).pack(anchor='w', fill='x', padx=8, pady=5)

                    if idx < len(templates) - 1:
                        ttk.Separator(inner_frame, orient='horizontal').pack(fill='x', padx=12, pady=(0, 2))
            else:
                delete_status_var.set(f"Aucun modèle disponible pour {legal_form}.")
                ttk.Label(
                    inner_frame,
                    text="Aucun modèle à supprimer.",
                    style='FieldLabel.TLabel',
                    justify='center',
                ).pack(fill='x', expand=True, padx=20, pady=40)

            inner_frame.update_idletasks()
            canvas.configure(scrollregion=canvas.bbox('all'))
            canvas.itemconfigure(canvas_window, width=canvas.winfo_width())
            _update_delete_count()

        def _on_delete_canvas_configure(event=None):
            width = event.width if event is not None else canvas.winfo_width()
            if width > 1:
                canvas.itemconfigure(canvas_window, width=width)

        def _delete_templates_targets(targets: List[Path], legal_form: str, refresh_main: bool = False):
            deleted = 0
            failures = []
            for template in targets:
                try:
                    template.unlink()
                    deleted += 1
                except Exception as exc:
                    failures.append(f"{template.name}: {exc}")

            if failures:
                messagebox.showwarning(
                    "Suppression partielle",
                    f"{deleted} modèle(s) supprimé(s).\n\nErreurs:\n" + "\n".join(failures[:6]),
                    parent=dialog,
                )
            else:
                messagebox.showinfo(
                    "Suppression terminée",
                    f"{deleted} modèle(s) supprimé(s) pour {legal_form}.",
                    parent=dialog,
                )

            if refresh_main or legal_form == (self.legal_form_var.get() or '').strip():
                self._refresh_template_list()
                if self.gen_type_var.get():
                    self._auto_select_templates(self.gen_type_var.get())
                self._update_selection_feedback()
            _refresh_delete_list()

        def _confirm_delete():
            targets = [template for template, var in dialog_vars.items() if var.get()]
            legal_form = (delete_form_var.get() or '').strip()
            if not legal_form:
                messagebox.showwarning("Forme requise", "Veuillez choisir une forme juridique.", parent=dialog)
                return
            if not targets:
                messagebox.showwarning("Aucun modèle", "Veuillez cocher au moins un modèle à supprimer.", parent=dialog)
                return

            names = "\n".join(f"• {template.name}" for template in targets[:8])
            if len(targets) > 8:
                names += f"\n• ... et {len(targets) - 8} autre(s)"
            confirmed = messagebox.askyesno(
                "Confirmer la suppression",
                f"Supprimer {len(targets)} modèle(s) pour {legal_form} ?\n\n{names}",
                parent=dialog,
            )
            if not confirmed:
                return

            _delete_templates_targets(targets, legal_form)

        def _confirm_delete_all():
            targets = []
            for form_name in LEGAL_FORM_OPTIONS:
                targets.extend(self._get_templates_for_legal_form(form_name))
            # Keep deterministic order and avoid duplicates.
            targets = sorted(set(targets), key=lambda p: str(p).lower())
            if not targets:
                messagebox.showwarning("Aucun modèle", "Aucun modèle trouvé dans les dossiers des formes juridiques.", parent=dialog)
                return

            messagebox.showwarning(
                "⚠️ Avertissement",
                (
                    "Vous êtes sur le point de supprimer TOUS les modèles de TOUTES les formes juridiques.\n"
                    "(Les modèles racine ne seront pas supprimés.)\n"
                    "Cette action est définitive."
                ),
                parent=dialog,
            )
            confirmed = messagebox.askyesno(
                "Confirmer suppression totale",
                f"Supprimer définitivement {len(targets)} modèle(s) de toutes les formes juridiques ?",
                parent=dialog,
            )
            if not confirmed:
                return

            _delete_templates_targets(targets, "toutes les formes juridiques", refresh_main=True)

        button_row = ttk.Frame(container)
        button_row.pack(fill='x', pady=(10, 0))

        clear_all_btn = WidgetFactory.create_button(
            button_row,
            text="Désélectionner tous",
            command=_clear_all_delete_templates,
            style='Secondary.TButton',
            icon_key='',
        )
        clear_all_btn.configure(width=18)
        clear_all_btn.pack(side='left', padx=(0, 6))

        select_all_btn = WidgetFactory.create_button(
            button_row,
            text="Sélectionner tous",
            command=_select_all_delete_templates,
            style='View.TButton',
            icon_key='',
        )
        select_all_btn.configure(width=18)
        select_all_btn.pack(side='left')

        cancel_btn = WidgetFactory.create_button(
            button_row,
            text="Annuler",
            command=dialog.destroy,
            style='Secondary.TButton',
            icon_key='',
        )
        cancel_btn.configure(width=14)
        cancel_btn.pack(side='right')

        confirm_btn = WidgetFactory.create_button(
            button_row,
            text="Supprimer",
            command=_confirm_delete,
            style='Cancel.TButton',
            icon_key='',
        )
        confirm_btn.configure(width=14)
        confirm_btn.pack(side='right', padx=(0, 6))

        delete_all_btn = WidgetFactory.create_button(
            button_row,
            text="Supprimer tous",
            command=_confirm_delete_all,
            style='Cancel.TButton',
            icon_key='',
        )
        delete_all_btn.configure(width=16)
        delete_all_btn.pack(side='right', padx=(0, 6))

        form_combo.bind('<<ComboboxSelected>>', _refresh_delete_list)
        inner_frame.bind('<Configure>', lambda _event=None: canvas.configure(scrollregion=canvas.bbox('all')))
        canvas.bind('<Configure>', _on_delete_canvas_configure)
        _refresh_delete_list()
        dialog.after_idle(lambda: WindowManager.center_window(dialog))

    def _view_templates(self):
        """Open the Models folder to view existing templates."""
        try:
            models_dir = PathManager.MODELS_DIR
            target_dir = models_dir
            selected_form = (self.legal_form_var.get() if hasattr(self, 'legal_form_var') else '').strip()
            if selected_form:
                form_dir = models_dir / LEGAL_FORM_TO_FOLDER.get(selected_form, selected_form)
                if form_dir.exists() and form_dir.is_dir():
                    target_dir = form_dir

            if target_dir.exists():
                import os
                import platform
                if platform.system() == 'Windows':
                    os.startfile(str(target_dir))
                elif platform.system() == 'Darwin':  # macOS
                    os.system(f'open "{target_dir}"')
                else:  # Linux
                    os.system(f'xdg-open "{target_dir}"')
            else:
                messagebox.showwarning("Dossier introuvable", f"Le dossier Models n'existe pas: {models_dir}")
        except Exception as e:
            logger.exception(f"Erreur lors de l'ouverture du dossier: {e}")
            ErrorHandler.handle_error(e, "Erreur lors de l'ouverture du dossier")

    def _get_root_templates_for_distribution(self) -> List[Path]:
        """Return root Models templates matching naming charter pattern."""
        models_dir = PathManager.MODELS_DIR
        if not models_dir.exists():
            return []
        templates = []
        for file_path in models_dir.glob("*.docx"):
            name = file_path.name
            # Keep only charter-compliant root templates (exclude utility docs).
            if re.match(r"^\d{4}-\d{2}_.+_Template\.docx$", name):
                templates.append(file_path)
        return sorted(templates)

    def _build_prefixed_template_name(self, source_name: str, legal_prefix: str) -> str:
        """Build target name: PREFIX_YYYY-MM_<Document>_Template.docx"""
        return f"{legal_prefix}_{source_name}"

    def _copy_root_templates_to_legal_folders(self):
        """Open a modal dialog to copy selected root templates to a chosen legal-form folder."""
        try:
            models_dir = PathManager.MODELS_DIR
            if not models_dir.exists():
                messagebox.showwarning("Dossier introuvable", f"Le dossier Models n'existe pas: {models_dir}")
                return

            if not self._get_root_templates_for_distribution():
                messagebox.showwarning(
                    "Aucun template conforme",
                    "Aucun template racine conforme à la charte n'a été trouvé.\n"
                    "Format attendu: YYYY-MM_Nom-Document_Template.docx"
                )
                return

            selected_form = (self.legal_form_var.get() or '').strip() or LEGAL_FORM_OPTIONS[0]
            dialog = tk.Toplevel(self)
            dialog.title("Copier des modèles")
            dialog.geometry("760x560")
            dialog.minsize(680, 480)
            try:
                dialog.transient(self)
                dialog.grab_set()
            except Exception:
                pass

            container = ttk.Frame(dialog, padding=14)
            container.pack(fill='both', expand=True)

            top_frame = ttk.LabelFrame(container, text="Choisir la forme juridique de destination", padding=12)
            top_frame.pack(fill='x', pady=(0, 10))

            copy_form_var = tk.StringVar(value=selected_form)
            copy_count_var = tk.StringVar(value="0 modèle sélectionné")
            copy_status_var = tk.StringVar(value="Choisissez les modèles racine à copier.")

            form_combo = ttk.Combobox(
                top_frame,
                textvariable=copy_form_var,
                values=LEGAL_FORM_OPTIONS,
                state='readonly',
                width=34,
            )
            form_combo.pack(fill='x')

            ttk.Label(
                container,
                textvariable=copy_count_var,
                font=('Segoe UI', 11, 'bold'),
            ).pack(anchor='w')
            ttk.Label(
                container,
                textvariable=copy_status_var,
                style='FieldLabel.TLabel',
                justify='left',
                wraplength=700,
            ).pack(anchor='w', fill='x', pady=(2, 10))

            list_frame = ttk.Frame(container)
            list_frame.pack(fill='both', expand=True)

            scrollbar = ttk.Scrollbar(list_frame)
            scrollbar.pack(side='right', fill='y')

            canvas_bg = '#2b2b2b' if getattr(self.theme_manager, 'is_dark_mode', True) else '#f7f7f7'
            border_color = '#555555' if getattr(self.theme_manager, 'is_dark_mode', True) else '#cccccc'
            canvas = tk.Canvas(
                list_frame,
                bg=canvas_bg,
                highlightthickness=1,
                highlightbackground=border_color,
                relief='solid',
                height=1,
            )
            canvas.pack(side='left', fill='both', expand=True)
            scrollbar.config(command=canvas.yview)
            canvas.config(yscrollcommand=scrollbar.set)

            inner_frame = ttk.Frame(canvas)
            canvas_window = canvas.create_window((0, 0), window=inner_frame, anchor='nw')

            dialog_vars = {}

            def _selected_copy_count() -> int:
                return sum(1 for var in dialog_vars.values() if var.get())

            def _update_copy_count():
                count = _selected_copy_count()
                label = "modèle sélectionné" if count == 1 else "modèles sélectionnés"
                copy_count_var.set(f"{count} {label}")

            def _on_copy_toggle(*_args):
                _update_copy_count()

            def _select_all_copy_templates():
                for var in dialog_vars.values():
                    var.set(True)

            def _clear_all_copy_templates():
                for var in dialog_vars.values():
                    var.set(False)

            def _refresh_copy_list(*_args):
                for widget in inner_frame.winfo_children():
                    widget.destroy()
                dialog_vars.clear()

                legal_form = (copy_form_var.get() or '').strip()
                root_templates = self._get_root_templates_for_distribution()
                if root_templates:
                    copy_status_var.set(
                        f"Sélectionnez les modèles racine à copier vers {legal_form}."
                    )
                    for idx, template in enumerate(root_templates):
                        var = tk.BooleanVar(value=False)
                        var.trace_add('write', _on_copy_toggle)
                        dialog_vars[template] = var

                        row = ttk.Frame(inner_frame)
                        row.pack(fill='x', padx=10, pady=2)
                        ttk.Checkbutton(
                            row,
                            text=f"📄 {template.name}",
                            variable=var,
                        ).pack(anchor='w', fill='x', padx=8, pady=5)

                        if idx < len(root_templates) - 1:
                            ttk.Separator(inner_frame, orient='horizontal').pack(fill='x', padx=12, pady=(0, 2))
                else:
                    copy_status_var.set("Aucun modèle racine conforme disponible.")
                    ttk.Label(
                        inner_frame,
                        text="Aucun modèle à copier.",
                        style='FieldLabel.TLabel',
                        justify='center',
                    ).pack(fill='x', expand=True, padx=20, pady=40)

                inner_frame.update_idletasks()
                canvas.configure(scrollregion=canvas.bbox('all'))
                canvas.itemconfigure(canvas_window, width=canvas.winfo_width())
                _update_copy_count()

            def _on_copy_canvas_configure(event=None):
                width = event.width if event is not None else canvas.winfo_width()
                if width > 1:
                    canvas.itemconfigure(canvas_window, width=width)

            def _confirm_copy():
                targets = [template for template, var in dialog_vars.items() if var.get()]
                legal_form = (copy_form_var.get() or '').strip()
                if not legal_form:
                    messagebox.showwarning("Forme requise", "Veuillez choisir une forme juridique.", parent=dialog)
                    return
                if not targets:
                    messagebox.showwarning("Aucun modèle", "Veuillez cocher au moins un modèle à copier.", parent=dialog)
                    return

                folder_name = LEGAL_FORM_TO_FOLDER.get(legal_form, legal_form)
                legal_prefix = LEGAL_FORM_PREFIXES.get(folder_name)
                if not legal_prefix:
                    messagebox.showerror(
                        "Configuration invalide",
                        f"Aucun préfixe configuré pour la forme '{legal_form}'.",
                        parent=dialog,
                    )
                    return

                names = "\n".join(f"• {template.name}" for template in targets[:8])
                if len(targets) > 8:
                    names += f"\n• ... et {len(targets) - 8} autre(s)"
                confirmed = messagebox.askyesno(
                    "Confirmer la copie",
                    f"Copier {len(targets)} modèle(s) vers {legal_form} ?\n\n{names}",
                    parent=dialog,
                )
                if not confirmed:
                    return

                target_dir = models_dir / folder_name
                target_dir.mkdir(parents=True, exist_ok=True)

                copied = 0
                skipped = 0
                failures = []
                for src in targets:
                    target_name = self._build_prefixed_template_name(src.name, legal_prefix)
                    target_path = target_dir / target_name
                    if target_path.exists():
                        skipped += 1
                        continue
                    try:
                        shutil.copy2(src, target_path)
                        copied += 1
                    except Exception as exc:
                        failures.append(f"{src.name}: {exc}")

                if failures:
                    messagebox.showwarning(
                        "Copie partielle",
                        (
                            f"{copied} modèle(s) copié(s), {skipped} ignoré(s) (déjà existants).\n\n"
                            "Erreurs:\n" + "\n".join(failures[:6])
                        ),
                        parent=dialog,
                    )
                else:
                    messagebox.showinfo(
                        "Copie terminée",
                        f"{copied} modèle(s) copié(s), {skipped} ignoré(s) (déjà existants) vers {legal_form}.",
                        parent=dialog,
                    )

                if folder_name == self._selected_legal_form_folder():
                    self._refresh_template_list()
                    if self.gen_type_var.get():
                        self._auto_select_templates(self.gen_type_var.get())
                    self._update_selection_feedback()
                _refresh_copy_list()

            button_row = ttk.Frame(container)
            button_row.pack(fill='x', pady=(10, 0))

            clear_all_btn = WidgetFactory.create_button(
                button_row,
                text="Désélectionner tous",
                command=_clear_all_copy_templates,
                style='Secondary.TButton',
                icon_key='',
            )
            clear_all_btn.configure(width=18)
            clear_all_btn.pack(side='left', padx=(0, 6))

            select_all_btn = WidgetFactory.create_button(
                button_row,
                text="Sélectionner tous",
                command=_select_all_copy_templates,
                style='View.TButton',
                icon_key='',
            )
            select_all_btn.configure(width=18)
            select_all_btn.pack(side='left')

            cancel_btn = WidgetFactory.create_button(
                button_row,
                text="Annuler",
                command=dialog.destroy,
                style='Secondary.TButton',
                icon_key='',
            )
            cancel_btn.configure(width=14)
            cancel_btn.pack(side='right')

            confirm_btn = WidgetFactory.create_button(
                button_row,
                text="Copier",
                command=_confirm_copy,
                style='Copy.TButton',
                icon_key='',
            )
            confirm_btn.configure(width=14)
            confirm_btn.pack(side='right', padx=(0, 6))

            form_combo.bind('<<ComboboxSelected>>', _refresh_copy_list)
            inner_frame.bind('<Configure>', lambda _event=None: canvas.configure(scrollregion=canvas.bbox('all')))
            canvas.bind('<Configure>', _on_copy_canvas_configure)
            _refresh_copy_list()
            dialog.after_idle(lambda: WindowManager.center_window(dialog))

        except Exception as e:
            logger.exception("Erreur lors de la copie des templates: %s", e)
            ErrorHandler.handle_error(e, "Erreur lors de la copie des templates")

    def _upload_template(self):
        """Upload a new template file to the appropriate folder with improved UX.
        
        Asks user to select which legal form the template is for:
        - SARL AU
        - SARL
        - Personne Physique
        - SA
        """
        try:
            # Get selected legal form first
            selected_form = (self.legal_form_var.get() or '').strip()
            
            if not selected_form:
                messagebox.showwarning("Sélection requise", "Veuillez d'abord sélectionner une forme juridique")
                return

            # Ask for file
            file_path = filedialog.askopenfilename(
                title="Sélectionner un modèle Word (.docx) à uploader",
                filetypes=[("Word Documents", "*.docx"), ("Tous les fichiers", "*.*")]
            )

            if not file_path:
                return

            file_path = Path(file_path)

            # Validate file
            if not file_path.suffix.lower() == '.docx':
                messagebox.showerror("Format invalide", "Veuillez sélectionner un fichier .docx")
                return

            # Determine destination directory
            models_dir = PathManager.MODELS_DIR
            models_dir.mkdir(parents=True, exist_ok=True)
            
            dest_dir = models_dir / LEGAL_FORM_TO_FOLDER.get(selected_form, selected_form)
            dest_dir.mkdir(parents=True, exist_ok=True)

            dest_path = dest_dir / file_path.name

            # Ask for confirmation if file exists
            if dest_path.exists():
                response = messagebox.askyesno(
                    "Fichier existant",
                    f"Le modèle '{file_path.name}' existe déjà dans {selected_form}/.\nVoulez-vous le remplacer ?"
                )
                if not response:
                    return

            # Copy file
            shutil.copy2(file_path, dest_path)
            messagebox.showinfo(
                "✅ Succès",
                f"Modèle '{file_path.name}' téléchargé vers\n📂 {selected_form}/"
            )

            # Refresh list
            self._last_uploaded_template_name = file_path.name
            self._refresh_template_list()

        except Exception as e:
            logger.exception(f"Erreur lors de l'upload du modèle: {e}")
            ErrorHandler.handle_error(e, "Erreur lors de l'upload du modèle")


    def _confirm(self):
        """Validate, generate templates, and show progress."""
        # Validate legal form selection (FIRST)
        legal_form = self.legal_form_var.get()
        if not legal_form:
            messagebox.showwarning(
                "Sélection requise",
                "⬆️ Veuillez d'abord sélectionner une forme juridique"
            )
            return
        
        # Validate generation type selection (SECOND)
        gen_type = self.gen_type_var.get()
        if not gen_type:
            messagebox.showwarning(
                "Sélection requise",
                "⬆️ Veuillez choisir un type de génération\n(Création ou Domiciliation)"
            )
            return

        # Store selections
        self.creation_type = legal_form
        self.generation_type = gen_type

        # Resolve templates from the manual checkbox selection.
        selected_templates = [
            template for template, var in self.template_vars.items()
            if var.get()
        ]

        if not selected_templates:
            messagebox.showwarning(
                "Aucun modèle sélectionné",
                "📄 Veuillez cocher au moins un modèle à générer."
            )
            return

        # Optional save to DB (choice now lives in this selector)
        if self.save_before_var.get():
            if not self.save_callback:
                messagebox.showwarning(
                    "Sauvegarde indisponible",
                    "La sauvegarde avant génération n'est pas disponible dans ce contexte."
                )
                return
            try:
                db_path = self.save_callback()
            except Exception as _err:
                logger.exception("Erreur lors de la sauvegarde avant génération: %s", _err)
                messagebox.showwarning(
                    "Sauvegarde échouée",
                    "La sauvegarde a échoué. La génération a été annulée."
                )
                return
            if not db_path:
                messagebox.showwarning(
                    "Sauvegarde manquante",
                    "La sauvegarde a échoué ou a été annulée. La génération a été annulée."
                )
                return

        # Ask for output directory
        out_dir = filedialog.askdirectory(title="Sélectionner le dossier de sortie")
        if not out_dir:
            return  # User cancelled

        self.selected_templates = selected_templates

        # Disable dialog controls during generation
        self.withdraw()  # Hide dialog temporarily

        # Create progress window
        progress_win = tk.Toplevel(self.parent)
        progress_win.title("Génération en cours")
        progress_win.geometry("600x400")
        try:
            progress_win.transient(self.parent)
        except Exception:
            pass
        progress_win.after_idle(lambda: WindowManager.center_window(progress_win, center_on_parent=False))
        progress_win.after(120, lambda: WindowManager.center_window(progress_win, center_on_parent=False))

        progress_frame = ttk.Frame(progress_win, padding=12)
        progress_frame.pack(fill='both', expand=True)

        ttk.Label(progress_frame, text="Génération des documents", font=('Segoe UI', 12, 'bold')).pack(anchor='w')
        counts_label = ttk.Label(progress_frame, text="0 / 0")
        counts_label.pack(anchor='w', pady=(6, 0))

        pb = ttk.Progressbar(progress_frame, orient='horizontal', length=400, mode='determinate')
        pb.pack(pady=(6, 6), fill='x')

        status_text = tk.Text(progress_frame, height=15, width=80, state='disabled')
        status_text.pack(fill='both', expand=True, pady=(6, 6))

        scrollbar = ttk.Scrollbar(status_text)
        scrollbar.pack(side='right', fill='y')
        status_text.config(yscrollcommand=scrollbar.set)

        def progress_cb(processed, total, template_name, entry):
            """Update progress from worker thread"""
            def _update():
                counts_label.configure(text=f"{processed} / {total}")
                pb['maximum'] = total
                pb['value'] = processed
                status_text.configure(state='normal')
                status = entry.get('status', 'pending')
                error = entry.get('error', '')
                msg = f"[{status}] {template_name}"
                if error:
                    msg += f" - {error}"
                status_text.insert('end', msg + "\n")
                status_text.see('end')
                status_text.configure(state='disabled')
            try:
                self.parent.after(1, _update)
            except Exception:
                pass

        def worker():
            """Run generation in background thread"""
            try:
                # Prepare template paths
                tpl_paths = [str(t.resolve()) for t in selected_templates]

                # Determine PDF conversion
                to_pdf = self.output_format_var.get() in ('pdf', 'both')

                # Call render_templates with generation type and legal form info
                report = render_templates(
                    self.values,
                    templates_dir=str(PathManager.MODELS_DIR),
                    out_dir=out_dir,
                    to_pdf=to_pdf,
                    templates_list=tpl_paths,
                    progress_callback=progress_cb,
                    generation_type=self.generation_type,  # 'creation' or 'domiciliation'
                    legal_form=self.creation_type,  # 'SARL AU', 'SARL', 'Personne Physique', 'SA'
                )

                def _show_done():
                    # Show completion message
                    try:
                        import os
                        paths: List[str] = []
                        for e in report:
                            out_docx = e.get('out_docx')
                            if out_docx and isinstance(out_docx, (str, Path)):
                                paths.append(str(Path(out_docx).parent))
                        folder = os.path.commonpath(paths) if paths else out_dir
                    except Exception:
                        folder = out_dir

                    messagebox.showinfo(
                        '✅ Génération terminée',
                        f"Génération réussie!\n\n{len(report)} document(s) générés.\n\nFichiers enregistrés dans:\n{folder}"
                    )

                self.parent.after(10, _show_done)

            except Exception as e:
                logger.exception(f"Erreur lors de la génération: {e}")

                def _show_error():
                    ErrorHandler.handle_error(e, "Erreur pendant la génération des documents")

                self.parent.after(10, _show_error)

            finally:
                def _cleanup():
                    try:
                        progress_win.destroy()
                    except Exception:
                        pass
                    try:
                        self.destroy()
                    except Exception:
                        pass

                self.parent.after(100, _cleanup)

        # Start generation thread
        t = threading.Thread(target=worker, daemon=True)
        t.start()


    def _cancel(self):
        """Cancel and close the dialog."""
        self.result = None
        self.destroy()

    def get_result(self) -> Optional[dict]:
        """Get the dialog result."""
        return self.result


def show_generation_selector(
    parent,
    values: Optional[dict] = None,
    output_format: str = 'word',
    save_callback: Optional[Callable[[], Optional[Path]]] = None,
) -> Optional[dict]:
    """Show the generation selector dialog and return the result.

    Args:
        parent: Parent window
        values: Dictionary of form values (societe, associes, contrat)
        output_format: 'word', 'pdf', or 'both'
        save_callback: optional callback to persist values before generation

    Returns:
        Dictionary with generation result, or None if cancelled
    """
    dialog = GenerationSelectorDialog(parent, values or {}, output_format, save_callback=save_callback)
    parent.wait_window(dialog)
    return dialog.get_result()
