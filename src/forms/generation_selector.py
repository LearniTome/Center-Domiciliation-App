"""
Generation Selector Dialog - Choose document generation type (Creation vs Domiciliation)
and upload custom templates.
"""

import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from pathlib import Path
from typing import Optional, Tuple, List
import shutil
import logging

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler

logger = logging.getLogger(__name__)


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
        template_frame = ttk.LabelFrame(main_frame, text="2️⃣ Gestion des modèles", padding=10)
        template_frame.pack(fill='both', expand=True, pady=10)

        # Buttons for template management
        btn_frame = ttk.Frame(template_frame)
        btn_frame.pack(fill='x', pady=(0, 10))

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

        # Template list
        ttk.Label(template_frame, text="Modèles disponibles:", font=('Segoe UI', 10, 'bold')).pack(anchor='w', pady=(0, 8))

        # Create listbox with scrollbar
        list_frame = ttk.Frame(template_frame)
        list_frame.pack(fill='both', expand=True, pady=(0, 10))

        scrollbar = ttk.Scrollbar(list_frame)
        scrollbar.pack(side='right', fill='y')

        self.template_listbox = tk.Listbox(
            list_frame,
            height=8,
            yscrollcommand=scrollbar.set
        )
        self.template_listbox.pack(side='left', fill='both', expand=True)
        scrollbar.config(command=self.template_listbox.yview)

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

    def _on_generation_type_changed(self):
        """Handle generation type radio button changes."""
        # Enable/disable creation sub-options based on selection
        if self.gen_type_var.get() == 'creation':
            for widget in self.creation_options_frame.winfo_children():
                widget.configure(state='normal')
        else:
            for widget in self.creation_options_frame.winfo_children():
                widget.configure(state='disabled')

    def _refresh_template_list(self):
        """Refresh the template listbox with available .docx files."""
        self.template_listbox.delete(0, 'end')

        try:
            models_dir = PathManager.MODELS_DIR
            if models_dir.exists():
                templates = sorted([f for f in models_dir.glob('*.docx')])
                for template in templates:
                    # Display template name without .docx extension
                    display_name = f"📄 {template.stem}"
                    self.template_listbox.insert('end', display_name)
            else:
                self.template_listbox.insert('end', "⚠️ Dossier Models non trouvé")
        except Exception as e:
            logger.exception(f"Erreur lors du chargement des modèles: {e}")
            self.template_listbox.insert('end', f"❌ Erreur: {str(e)}")

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

        self.generation_type = gen_type
        self.result = {
            'type': gen_type,
            'creation_type': self.creation_type
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
