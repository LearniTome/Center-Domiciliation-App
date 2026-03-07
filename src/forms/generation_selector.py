"""
Generation Selector Dialog - Choose document generation type (Creation vs Domiciliation)
with automatic template selection and upload custom templates.
"""

import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from pathlib import Path
from typing import Optional, Tuple, List, Union, Callable
import shutil
import logging
import threading
import re

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler
from ..utils.doc_generator import render_templates

logger = logging.getLogger(__name__)

# Template mappings for different document types
CREATION_TEMPLATES_KEYWORDS = ['statuts', 'annonce', 'décl', 'decl', 'depot', 'dépot']
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
def compute_selection_feedback(legal_form: str, generation_type: str, selected_count: int) -> dict:
    """Compute summary text and readiness state for generation selection."""
    form_label = (legal_form or '').strip() or '—'
    generation_map = {
        'creation': 'Création',
        'domiciliation': 'Domiciliation',
    }
    type_label = generation_map.get((generation_type or '').strip().lower(), '—')
    count = max(0, int(selected_count or 0))

    missing = []
    if form_label == '—':
        missing.append('sélectionner une forme juridique')
    if type_label == '—':
        missing.append('choisir un type de génération')
    if count <= 0:
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
            initial_w = min(1240, max(980, sw - 80))
            initial_h = min(860, max(700, sh - 140))
            self.geometry(f"{initial_w}x{initial_h}")
        except Exception:
            self.geometry("1240x820")
        self.resizable(True, True)
        self.minsize(980, 640)

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
        ┌─────────────────────────────────────┐
        │ [Forme juridique] | [Type génération]│
        ├─────────────────────────────────────┤
        │ [Options de génération]              │
        ├─────────────────────────────────────┤
        │ [Modèles disponibles - full width]  │
        ├─────────────────────────────────────┤
        │ [Résumé + Boutons: Générer/Annuler] │
        └─────────────────────────────────────┘
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

        self.legal_form_var = tk.StringVar(value='SARL-AU')
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

        # ===== SECTION 3: TEMPLATE MANAGEMENT & SELECTION (FULL WIDTH) =====
        template_frame = ttk.LabelFrame(main_frame, text="Modèles disponibles", padding=15)
        template_frame.pack(fill='both', expand=True, pady=(0, 12))

        # Template list with checkboxes - avec plus d'espace
        ttk.Label(template_frame, text="Sélectionner les modèles:", font=('Segoe UI', 11, 'bold')).pack(anchor='w', pady=(0, 12))

        # Create frame with scrollbar for template checkboxes - PLUS GRAND
        list_frame = ttk.Frame(template_frame)
        list_frame.pack(fill='both', expand=True, pady=(0, 8))

        scrollbar = ttk.Scrollbar(list_frame)
        scrollbar.pack(side='right', fill='y')

        # Use Canvas for checkboxes - avec hauteur appropriée
        self.template_canvas = tk.Canvas(
            list_frame, 
            bg='#2b2b2b', 
            highlightthickness=1, 
            highlightbackground='#555555',
            relief='solid',
            height=1  # Let pack(expand=True) drive vertical growth with window resizing
        )
        self.template_canvas.pack(side='left', fill='both', expand=True)
        scrollbar.config(command=self.template_canvas.yview)
        self.template_canvas.config(yscrollcommand=scrollbar.set)

        # Inner frame for checkboxes
        self.template_inner_frame = ttk.Frame(self.template_canvas)
        self.template_canvas_window = self.template_canvas.create_window((0, 0), window=self.template_inner_frame, anchor='nw')
        self.template_inner_frame.columnconfigure(0, weight=1)

        # Bind canvas resizing to update layout
        self.template_inner_frame.bind('<Configure>', self._on_frame_configure)
        self.template_canvas.bind('<Configure>', self._on_canvas_configure)

        # Dictionary to store template checkbox variables
        self.template_vars = {}

        # Bottom actions for template area (single action bar).
        bottom_template_actions = ttk.Frame(template_frame)
        bottom_template_actions.pack(fill='x', pady=(4, 0))

        WidgetFactory.create_button(
            bottom_template_actions,
            text="🔄 Actualiser",
            command=self._refresh_template_list,
            style='Refresh.TButton'
        ).pack(side='left', padx=(0, 6))

        WidgetFactory.create_button(
            bottom_template_actions,
            text="📁 Consulter",
            command=self._view_templates,
            style='View.TButton'
        ).pack(side='left', padx=(0, 6))

        WidgetFactory.create_button(
            bottom_template_actions,
            text="⬆️ Uploader",
            command=self._upload_template,
            style='Upload.TButton',
            icon_gap=2,
        ).pack(side='left', padx=(0, 6))

        WidgetFactory.create_button(
            bottom_template_actions,
            text="📄 Copier modèles",
            command=self._copy_root_templates_to_legal_folders,
            style='Copy.TButton',
            icon_gap=2,
        ).pack(side='left')

        # Populate template list
        self._refresh_template_list()
        if self.gen_type_var.get():
            self._auto_select_templates(self.gen_type_var.get())
        self._update_selection_feedback()


    def _on_frame_configure(self, event=None):
        """Update the scroll region of the canvas when frame is resized."""
        # Update the canvas scroll region to encompass all content
        self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))

    def _on_canvas_configure(self, event=None):
        """Resize the inner frame to match canvas width when canvas is resized."""
        # Make the inner frame match the canvas width for proper layout
        canvas_width = (event.width if event is not None else self.template_canvas.winfo_width())
        if canvas_width > 1:
            try:
                self.template_canvas.itemconfigure(self.template_canvas_window, width=canvas_width)
            except Exception:
                self.template_inner_frame.configure(width=canvas_width)

    def _on_legal_form_changed(self):
        """Handle legal form radio button changes and refresh template list."""
        # Refresh template list based on new legal form
        self._refresh_template_list()
        # Auto-select templates if generation type is already selected
        gen_type = self.gen_type_var.get()
        if gen_type:
            self._auto_select_templates(gen_type)
        self._update_selection_feedback()

    def _on_generation_type_changed(self):
        """Handle generation type radio button changes and auto-select templates."""
        gen_type = self.gen_type_var.get()
        
        # Auto-select templates based on generation type
        if gen_type:
            self._auto_select_templates(gen_type)
        # If no generation type selected, uncheck all templates
        else:
            for var in self.template_vars.values():
                var.set(False)
        self._update_selection_feedback()

    def _on_template_selection_changed(self):
        """Handle checkbox updates in template selection list."""
        self._update_selection_feedback()

    def _selected_templates_count(self) -> int:
        return sum(1 for var in self.template_vars.values() if var.get())

    def _selected_legal_form_folder(self) -> str:
        selected = (self.legal_form_var.get() or '').strip()
        return LEGAL_FORM_TO_FOLDER.get(selected, selected)

    def _update_selection_feedback(self):
        """Refresh summary row and proceed button state."""
        feedback = compute_selection_feedback(
            self.legal_form_var.get() if hasattr(self, 'legal_form_var') else '',
            self.gen_type_var.get() if hasattr(self, 'gen_type_var') else '',
            self._selected_templates_count(),
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

    def _auto_select_templates(self, doc_type: str):
        """Automatically select templates based on document type and legal form.

        Args:
            doc_type: 'creation' or 'domiciliation'
        """
        # Uncheck all first
        for var in self.template_vars.values():
            var.set(False)

        # Get the selected legal form
        legal_form = (self.legal_form_var.get() or '').strip()
        if not legal_form:
            return  # No legal form selected, don't auto-select anything

        # Select templates based on type
        if doc_type == 'creation':
            # Requested behavior: in "Création", select all available templates
            # for the selected legal form.
            for template_path, var in self.template_vars.items():
                form_match = self._template_matches_legal_form(template_path, legal_form)
                var.set(bool(form_match))

        elif doc_type == 'domiciliation':
            # Select domiciliation templates for all legal forms
            for template_path, var in self.template_vars.items():
                template_name = template_path.name.lower()
                if any(keyword.lower() in template_name for keyword in DOMICILIATION_TEMPLATES):
                    var.set(True)

    def _template_matches_legal_form(self, template_path: Path, legal_form: str) -> bool:
        """Check if a template matches the selected legal form.
        
        Templates are organized in folders by legal form:
        - Models/SARL AU/
        - Models/SARL/
        - Models/Personne Physique/
        
        Or can be in a shared folder for all forms.
        
        Args:
            template_path: Path to the template file
            legal_form: Selected legal form (e.g., 'SARL', 'SARL AU')
            
        Returns:
            True if template matches the legal form or is in a shared folder
        """
        parent_folder = template_path.parent.name
        
        # If no legal form selected, include templates from shared folder (Models root)
        if not legal_form:
            return parent_folder == 'Models'
        
        # Map legal form to folder names
        expected_folder = LEGAL_FORM_TO_FOLDER.get(legal_form.strip(), legal_form.strip())
        
        # Template matches if:
        # 1. It's in the appropriate legal form folder, OR
        # 2. It's in the root Models folder (shared for all forms)
        return parent_folder == expected_folder or parent_folder == 'Models'

    def _refresh_template_list(self):
        """Refresh the template list with checkboxes for available .docx files.
        
        Shows ONLY templates for the selected legal form, no shared templates.
        If no legal form is selected, shows a message.
        """
        # Preserve currently selected templates when refreshing the list.
        previously_selected = {p.name for p, v in self.template_vars.items() if v.get()}

        # Clear existing widgets
        for widget in self.template_inner_frame.winfo_children():
            widget.destroy()
        self.template_vars.clear()

        try:
            models_dir = PathManager.MODELS_DIR
            if not models_dir.exists():
                self._show_template_message("⚠️ Dossier Models non trouvé")
                self._update_selection_feedback()
                return

            # Get selected legal form
            selected_legal_form = (self.legal_form_var.get() or '').strip()
            
            if not selected_legal_form:
                # No legal form selected - show message with padding
                msg_frame = ttk.Frame(self.template_inner_frame)
                msg_frame.pack(fill='x', expand=True, padx=20, pady=40)
                
                ttk.Label(
                    msg_frame,
                    text="⬆️ Veuillez sélectionner une forme juridique",
                    font=('Segoe UI', 11, 'italic'),
                    foreground='#888888'
                ).pack(anchor='center', fill='x', expand=True)
                
                self.template_inner_frame.update_idletasks()
                self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
                self.after(100, self._on_canvas_configure)
                self._update_selection_feedback()
                return

            # Get templates ONLY from the selected legal form folder
            all_templates = []
            form_path = models_dir / self._selected_legal_form_folder()
            
            if form_path.exists() and form_path.is_dir():
                all_templates = sorted([f for f in form_path.glob('*.docx')])
            
            if all_templates:
                for idx, template in enumerate(all_templates):
                    # Create checkbox variable
                    var = tk.BooleanVar(value=(template.name in previously_selected))
                    var.trace_add('write', lambda *_args: self._on_template_selection_changed())
                    self.template_vars[template] = var

                    # Create checkbox widget with nice display name
                    # Extract clean name (remove 2026_Modèle_SARLAU_ prefix)
                    clean_name = template.stem
                    for prefix in ['2026_Modèle_SARLAU_', '2026_Modèle_SARL_', '2026_Modèle_PP_', '2026_Modèle_SA_']:
                        if clean_name.startswith(prefix):
                            clean_name = clean_name[len(prefix):]
                            break
                    
                    display_name = f"📄 {clean_name}"
                    if self._last_uploaded_template_name and template.name == self._last_uploaded_template_name:
                        display_name += "  [Nouveau]"

                    # Create row with a cleaner line layout for template items.
                    row = ttk.Frame(self.template_inner_frame)
                    row.pack(fill='x', padx=10, pady=1)

                    chk = ttk.Checkbutton(
                        row,
                        text=display_name,
                        variable=var
                    )
                    chk.pack(anchor='w', fill='x', padx=8, pady=4, ipady=2)

                    if idx < len(all_templates) - 1:
                        ttk.Separator(self.template_inner_frame, orient='horizontal').pack(fill='x', padx=12, pady=(0, 1))
            else:
                msg_frame = ttk.Frame(self.template_inner_frame)
                msg_frame.pack(fill='x', expand=True, padx=20, pady=40)
                
                ttk.Label(
                    msg_frame,
                    text=f"⚠️ Aucun modèle trouvé pour {selected_legal_form}",
                    font=('Segoe UI', 11, 'italic'),
                    foreground='#888888'
                ).pack(anchor='center', fill='x', expand=True)
                
                self.template_inner_frame.update_idletasks()
                self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
                self.after(100, self._on_canvas_configure)

            # Update canvas scroll region
            self.template_inner_frame.update_idletasks()
            self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
            
            # Force canvas width update
            self.after(100, self._on_canvas_configure)
            self._last_uploaded_template_name = None
            self._update_selection_feedback()

        except Exception as e:
            logger.exception(f"Erreur lors du chargement des modèles: {e}")
            msg_frame = ttk.Frame(self.template_inner_frame)
            msg_frame.pack(fill='x', expand=True, padx=20, pady=40)
            
            ttk.Label(
                msg_frame,
                text=f"❌ Erreur: {str(e)}",
                font=('Segoe UI', 10),
                foreground='#ff6666'
            ).pack(anchor='center', fill='x', expand=True)
            
            self.template_inner_frame.update_idletasks()
            self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
            self.after(100, self._on_canvas_configure)
            self._update_selection_feedback()

    def _show_template_message(self, message: str):
        """Display a centered informational message in template list area."""
        msg_frame = ttk.Frame(self.template_inner_frame)
        msg_frame.pack(fill='x', expand=True, padx=20, pady=40)
        ttk.Label(
            msg_frame,
            text=message,
            font=('Segoe UI', 11, 'italic'),
            foreground='#888888',
        ).pack(anchor='center', fill='x', expand=True)

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
        """Copy root templates into each legal-form folder with prefixed naming."""
        try:
            models_dir = PathManager.MODELS_DIR
            if not models_dir.exists():
                messagebox.showwarning("Dossier introuvable", f"Le dossier Models n'existe pas: {models_dir}")
                return

            root_templates = self._get_root_templates_for_distribution()
            if not root_templates:
                messagebox.showwarning(
                    "Aucun template conforme",
                    "Aucun template racine conforme à la charte n'a été trouvé.\n"
                    "Format attendu: YYYY-MM_Nom-Document_Template.docx"
                )
                return

            copied = 0
            skipped = 0
            created_dirs = 0

            for folder_name, prefix in LEGAL_FORM_PREFIXES.items():
                target_dir = models_dir / folder_name
                if not target_dir.exists():
                    target_dir.mkdir(parents=True, exist_ok=True)
                    created_dirs += 1

                for src in root_templates:
                    target_name = self._build_prefixed_template_name(src.name, prefix)
                    target_path = target_dir / target_name

                    if target_path.exists():
                        skipped += 1
                        continue

                    shutil.copy2(src, target_path)
                    copied += 1

            messagebox.showinfo(
                "Copie terminée",
                f"Distribution des templates terminée.\n\n"
                f"Templates source: {len(root_templates)}\n"
                f"Dossiers créés: {created_dirs}\n"
                f"Fichiers copiés: {copied}\n"
                f"Fichiers déjà existants (ignorés): {skipped}"
            )

            self._refresh_template_list()

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

        # Get selected templates
        selected_templates = [
            template for template, var in self.template_vars.items() if var.get()
        ]

        if not selected_templates:
            messagebox.showwarning(
                "Aucun modèle sélectionné",
                "📄 Veuillez sélectionner au moins un modèle à générer"
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
