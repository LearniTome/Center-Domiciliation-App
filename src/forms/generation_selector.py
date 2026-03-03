"""
Generation Selector Dialog - Choose document generation type (Creation vs Domiciliation)
with automatic template selection and upload custom templates.
"""

import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from pathlib import Path
from typing import Optional, Tuple, List
import shutil
import logging

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler

logger = logging.getLogger(__name__)

# Template mappings for different document types
CREATION_TEMPLATES_KEYWORDS = ['SARL', 'Statuts', 'Annonce', 'Décl', 'Dépot', 'AU', 'Decl']
DOMICILIATION_TEMPLATES = ['Attest', 'Contrat', 'domiciliation']


class GenerationSelectorDialog(tk.Toplevel):
    """Modal dialog to select generation type and manage templates."""

    def __init__(self, parent):
        super().__init__(parent)
        self.parent = parent
        self.title("Sélectionner les documents à générer")
        self.geometry("700x600")
        self.resizable(False, False)

        # Make modal
        try:
            self.transient(parent)
            self.grab_set()
        except Exception:
            pass

        # Theme
        self.theme_manager = ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Results
        self.generation_type: Optional[str] = None  # 'creation' or 'domiciliation'
        self.creation_type: Optional[str] = None     # 'SARL' or 'SARL_AU'
        self.selected_templates: List[Path] = []
        self.result = None

        # Setup UI
        self._setup_ui()

    def _setup_ui(self):
        """Setup the dialog UI."""
        main_frame = ttk.Frame(self, padding=15)
        main_frame.pack(fill='both', expand=True)

        # Title
        title_label = ttk.Label(
            main_frame,
            text="📄 Sélectionner les documents à générer",
            font=('Segoe UI', 14, 'bold')
        )
        title_label.pack(anchor='w', pady=(0, 15))

        # Separator
        ttk.Separator(main_frame, orient='horizontal').pack(fill='x', pady=10)

        # Section 1: Document Type Selection
        type_frame = ttk.LabelFrame(main_frame, text="1️⃣ Type de génération", padding=10)
        type_frame.pack(fill='x', pady=10)

        self.gen_type_var = tk.StringVar(value='')

        # Option: Creation
        creation_frame = ttk.Frame(type_frame)
        creation_frame.pack(fill='x', pady=8)

        ttk.Radiobutton(
            creation_frame,
            text="📋 Générer les documents de Création de Société",
            variable=self.gen_type_var,
            value='creation',
            command=self._on_generation_type_changed
        ).pack(anchor='w')

        # Sub-options for creation
        self.creation_options_frame = ttk.Frame(creation_frame)
        self.creation_options_frame.pack(fill='x', padx=30, pady=(8, 0))

        self.creation_type_var = tk.StringVar(value='')

        ttk.Radiobutton(
            self.creation_options_frame,
            text="• SARL (Société à Responsabilité Limitée)",
            variable=self.creation_type_var,
            value='SARL'
        ).pack(anchor='w', pady=4)

        ttk.Radiobutton(
            self.creation_options_frame,
            text="• SARL.AU (Société Unipersonnelle)",
            variable=self.creation_type_var,
            value='SARL_AU'
        ).pack(anchor='w', pady=4)

        # Option: Domiciliation
        domiciliation_frame = ttk.Frame(type_frame)
        domiciliation_frame.pack(fill='x', pady=8)

        ttk.Radiobutton(
            domiciliation_frame,
            text="🏢 Générer les documents de Domiciliation",
            variable=self.gen_type_var,
            value='domiciliation'
        ).pack(anchor='w')

        # Separator
        ttk.Separator(main_frame, orient='horizontal').pack(fill='x', pady=10)

        # Section 2: Template Management
        template_frame = ttk.LabelFrame(main_frame, text="2️⃣ Sélection et gestion des modèles", padding=10)
        template_frame.pack(fill='both', expand=True, pady=10)

        # Buttons for template management
        btn_frame = ttk.Frame(template_frame)
        btn_frame.pack(fill='x', pady=(0, 10))

        WidgetFactory.create_button(
            btn_frame,
            text="🔄 Actualiser les modèles",
            command=self._refresh_template_list
        ).pack(side='left', padx=5)

        WidgetFactory.create_button(
            btn_frame,
            text="📁 Consulter les modèles existants",
            command=self._view_templates
        ).pack(side='left', padx=5)

        WidgetFactory.create_button(
            btn_frame,
            text="⬆️ Uploader un nouveau modèle",
            command=self._upload_template
        ).pack(side='left', padx=5)

        # Template list with checkboxes for selection
        ttk.Label(template_frame, text="Modèles à générer:", font=('Segoe UI', 10, 'bold')).pack(anchor='w', pady=(0, 8))

        # Create frame with scrollbar for template checkboxes
        list_frame = ttk.Frame(template_frame)
        list_frame.pack(fill='both', expand=True, pady=(0, 10))

        scrollbar = ttk.Scrollbar(list_frame)
        scrollbar.pack(side='right', fill='y')

        # Use Frame instead of Listbox to hold checkboxes
        self.template_canvas = tk.Canvas(list_frame)
        self.template_canvas.pack(side='left', fill='both', expand=True)
        scrollbar.config(command=self.template_canvas.yview)
        self.template_canvas.config(yscrollcommand=scrollbar.set)

        # Inner frame for checkboxes
        self.template_inner_frame = ttk.Frame(self.template_canvas)
        self.template_canvas_window = self.template_canvas.create_window((0, 0), window=self.template_inner_frame, anchor='nw')

        # Bind canvas resizing
        self.template_inner_frame.bind('<Configure>', self._on_frame_configure)

        # Dictionary to store template checkbox variables
        self.template_vars = {}

        # Populate template list
        self._refresh_template_list()

        # Footer buttons
        footer_frame = ttk.Frame(main_frame)
        footer_frame.pack(fill='x', pady=15)

        WidgetFactory.create_button(
            footer_frame,
            text="✅ Procéder à la génération",
            command=self._confirm
        ).pack(side='right', padx=5)

        WidgetFactory.create_button(
            footer_frame,
            text="❌ Annuler",
            command=self._cancel
        ).pack(side='right', padx=5)

    def _on_frame_configure(self, event=None):
        """Update the scroll region of the canvas when frame is resized."""
        self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
        # Adjust width
        self.template_canvas.itemconfig(self.template_canvas_window, width=event.width if event else 0)

    def _on_generation_type_changed(self):
        """Handle generation type radio button changes and auto-select templates."""
        gen_type = self.gen_type_var.get()

        # Enable/disable creation sub-options based on selection
        if gen_type == 'creation':
            for widget in self.creation_options_frame.winfo_children():
                widget.configure(state='normal')
            # Auto-select all creation templates
            self._auto_select_templates('creation')
        else:
            for widget in self.creation_options_frame.winfo_children():
                widget.configure(state='disabled')

        # Auto-select templates for domiciliation
        if gen_type == 'domiciliation':
            self._auto_select_templates('domiciliation')

    def _auto_select_templates(self, doc_type: str):
        """Automatically select templates based on document type.

        Args:
            doc_type: 'creation' or 'domiciliation'
        """
        # Uncheck all first
        for var in self.template_vars.values():
            var.set(False)

        # Select templates based on type
        if doc_type == 'creation':
            # Select all templates that are for creation (SARL, Statuts, Annonce, etc.)
            for template_path, var in self.template_vars.items():
                template_name = template_path.name.lower()  # Case-insensitive
                if any(keyword.lower() in template_name for keyword in CREATION_TEMPLATES_KEYWORDS):
                    var.set(True)

        elif doc_type == 'domiciliation':
            # Select only Attestation and Contrat for domiciliation
            for template_path, var in self.template_vars.items():
                template_name = template_path.name.lower()  # Case-insensitive
                if any(keyword.lower() in template_name for keyword in DOMICILIATION_TEMPLATES):
                    var.set(True)

    def _refresh_template_list(self):
        """Refresh the template list with checkboxes for available .docx files."""
        # Clear existing widgets
        for widget in self.template_inner_frame.winfo_children():
            widget.destroy()
        self.template_vars.clear()

        try:
            models_dir = PathManager.MODELS_DIR
            if models_dir.exists():
                templates = sorted([f for f in models_dir.glob('*.docx')])

                if templates:
                    for template in templates:
                        # Create checkbox variable
                        var = tk.BooleanVar(value=False)
                        self.template_vars[template] = var

                        # Create checkbox widget
                        display_name = f"📄 {template.stem}"
                        chk = ttk.Checkbutton(
                            self.template_inner_frame,
                            text=display_name,
                            variable=var
                        )
                        chk.pack(anchor='w', pady=4)
                else:
                    ttk.Label(
                        self.template_inner_frame,
                        text="⚠️ Aucun modèle trouvé"
                    ).pack(anchor='w', pady=10)
            else:
                ttk.Label(
                    self.template_inner_frame,
                    text="⚠️ Dossier Models non trouvé"
                ).pack(anchor='w', pady=10)

            # Update canvas scroll region
            self.template_inner_frame.update_idletasks()
            self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))

        except Exception as e:
            logger.exception(f"Erreur lors du chargement des modèles: {e}")
            ttk.Label(
                self.template_inner_frame,
                text=f"❌ Erreur: {str(e)}"
            ).pack(anchor='w', pady=10)

    def _view_templates(self):
        """Open the Models folder to view existing templates."""
        try:
            models_dir = PathManager.MODELS_DIR
            if models_dir.exists():
                import os
                import platform
                if platform.system() == 'Windows':
                    os.startfile(str(models_dir))
                elif platform.system() == 'Darwin':  # macOS
                    os.system(f'open "{models_dir}"')
                else:  # Linux
                    os.system(f'xdg-open "{models_dir}"')
            else:
                messagebox.showwarning("Dossier introuvable", f"Le dossier Models n'existe pas: {models_dir}")
        except Exception as e:
            logger.exception(f"Erreur lors de l'ouverture du dossier: {e}")
            ErrorHandler.handle_error(e, "Erreur lors de l'ouverture du dossier")

    def _upload_template(self):
        """Upload a new template file."""
        try:
            file_path = filedialog.askopenfilename(
                title="Sélectionner un modèle Word (.docx)",
                filetypes=[("Word Documents", "*.docx"), ("Tous les fichiers", "*.*")]
            )

            if not file_path:
                return

            file_path = Path(file_path)

            # Validate file
            if not file_path.suffix.lower() == '.docx':
                messagebox.showerror("Format invalide", "Veuillez sélectionner un fichier .docx")
                return

            # Copy to Models directory
            models_dir = PathManager.MODELS_DIR
            models_dir.mkdir(parents=True, exist_ok=True)

            dest_path = models_dir / file_path.name

            # Ask for confirmation if file exists
            if dest_path.exists():
                response = messagebox.askyesno(
                    "Fichier existant",
                    f"Le modèle '{file_path.name}' existe déjà.\nVoulez-vous le remplacer ?"
                )
                if not response:
                    return

            # Copy file
            shutil.copy2(file_path, dest_path)
            messagebox.showinfo("✅ Succès", f"Modèle '{file_path.name}' téléchargé avec succès!")

            # Refresh list
            self._refresh_template_list()

        except Exception as e:
            logger.exception(f"Erreur lors de l'upload du modèle: {e}")
            ErrorHandler.handle_error(e, "Erreur lors de l'upload du modèle")

    def _confirm(self):
        """Validate and confirm selection."""
        # Validate generation type
        gen_type = self.gen_type_var.get()
        if not gen_type:
            messagebox.showwarning("Sélection requise", "Veuillez choisir un type de génération")
            return

        # Validate creation type if creation is selected
        if gen_type == 'creation':
            creation_type = self.creation_type_var.get()
            if not creation_type:
                messagebox.showwarning("Sélection requise", "Veuillez choisir un type de création (SARL ou SARL.AU)")
                return
            self.creation_type = creation_type
        else:
            self.creation_type = None

        # Get selected templates
        selected_templates = [
            template for template, var in self.template_vars.items() if var.get()
        ]

        if not selected_templates:
            messagebox.showwarning(
                "Aucun modèle sélectionné",
                "Veuillez sélectionner au moins un modèle à générer"
            )
            return

        self.generation_type = gen_type
        self.selected_templates = selected_templates
        self.result = {
            'type': gen_type,
            'creation_type': self.creation_type,
            'templates': [str(t.resolve()) for t in selected_templates]
        }

        self.destroy()

    def _cancel(self):
        """Cancel and close the dialog."""
        self.result = None
        self.destroy()

    def get_result(self) -> Optional[dict]:
        """Get the dialog result."""
        return self.result


def show_generation_selector(parent) -> Optional[dict]:
    """Show the generation selector dialog and return the result."""
    dialog = GenerationSelectorDialog(parent)
    parent.wait_window(dialog)
    return dialog.get_result()
