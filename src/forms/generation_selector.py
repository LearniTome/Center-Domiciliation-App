"""
Generation Selector Dialog - Choose document generation type (Creation vs Domiciliation)
with automatic template selection and upload custom templates.
"""

import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from pathlib import Path
from typing import Optional, Tuple, List, Union
import shutil
import logging
import threading

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler
from ..utils.doc_generator import render_templates

logger = logging.getLogger(__name__)

# Template mappings for different document types
CREATION_TEMPLATES_KEYWORDS = ['SARL', 'Statuts', 'Annonce', 'Décl', 'Dépot', 'AU', 'Decl']
DOMICILIATION_TEMPLATES = ['Attest', 'Contrat', 'domiciliation']


class GenerationSelectorDialog(tk.Toplevel):
    """Modal dialog to select generation type and manage templates."""

    def __init__(self, parent, values: Optional[dict] = None, output_format: str = 'docx'):
        super().__init__(parent)
        self.parent = parent
        self.values = values or {}
        self.output_format = output_format  # 'docx', 'pdf', or 'both'

        self.title("📄 Sélectionner les documents à générer")
        # Taille réduite et optimisée
        self.geometry("1100x700")
        self.resizable(True, True)
        self.minsize(900, 600)  # Taille minimum raisonnable

        # Apply the SAME theme as the parent window (not a new one)
        # This ensures the dialog inherits the parent's style configuration
        from ..utils.utils import WindowManager
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

        # Center window on screen using deferred callback
        # This ensures the window is fully rendered before calculating position
        self.after(100, self._center_window_on_screen)

        # Results
        self.generation_type: Optional[str] = None
        self.creation_type: Optional[str] = None
        self.selected_templates: List[Path] = []
        self.result = None

        # Setup UI
        self._setup_ui()

    def _center_window_on_screen(self):
        """Center the window on screen after it's fully rendered."""
        self.update_idletasks()
        
        # Get window and screen dimensions
        window_width = self.winfo_width()
        window_height = self.winfo_height()
        screen_width = self.winfo_screenwidth()
        screen_height = self.winfo_screenheight()
        
        # Calculate center position
        x = (screen_width - window_width) // 2
        y = (screen_height - window_height) // 2
        
        # Ensure window stays on screen (with margin)
        x = max(0, min(x, screen_width - window_width))
        y = max(0, min(y, screen_height - window_height))
        
        # Apply geometry with position
        self.geometry(f"{window_width}x{window_height}+{x}+{y}")
        self.focus_set()

    def _setup_ui(self):
        """Setup the dialog UI with improved design and layout.
        
        Layout:
        ┌─────────────────────────────────────┐
        │ Titre                                │
        ├─────────────────────────────────────┤
        │ [Forme juridique] | [Type génération]│
        ├─────────────────────────────────────┤
        │ [Modèles disponibles - full width]  │
        ├─────────────────────────────────────┤
        │ [Boutons: Confirmer | Annuler]      │
        └─────────────────────────────────────┘
        """
        main_frame = ttk.Frame(self, padding=20)
        main_frame.pack(fill='both', expand=True)

        # Title with buttons on the right
        top_frame = ttk.Frame(main_frame)
        top_frame.pack(fill='x', pady=(0, 20))

        title_label = ttk.Label(
            top_frame,
            text="📄 Sélectionner les documents à générer",
            font=('Segoe UI', 16, 'bold')
        )
        title_label.pack(side='left', anchor='w')

        # Buttons on the right of title
        button_frame = ttk.Frame(top_frame)
        button_frame.pack(side='right', anchor='e')

        WidgetFactory.create_button(
            button_frame,
            text="✅ Procéder à la génération",
            command=self._confirm,
            style='Success.TButton'
        ).pack(side='left', padx=5, ipady=8)

        WidgetFactory.create_button(
            button_frame,
            text="❌ Annuler",
            command=self._cancel,
            style='Cancel.TButton'
        ).pack(side='left', padx=5, ipady=8)

        # Separator
        ttk.Separator(main_frame, orient='horizontal').pack(fill='x', pady=(0, 20))

        # ===== SECTIONS 1 & 2: SIDE BY SIDE =====
        top_sections_frame = ttk.Frame(main_frame)
        top_sections_frame.pack(fill='x', pady=(0, 20))

        # Configure columns: 50/50 split
        top_sections_frame.columnconfigure(0, weight=1)
        top_sections_frame.columnconfigure(1, weight=1)

        # LEFT: LEGAL FORM SELECTION
        legal_form_frame = ttk.LabelFrame(top_sections_frame, text="📋 Forme juridique", padding=15)
        legal_form_frame.grid(row=0, column=0, padx=(0, 10), sticky='nsew')

        self.legal_form_var = tk.StringVar(value='')
        
        legal_forms = [
            ('🏢 SARL AU - Unipersonnelle', 'SARL AU'),
            ('🏢 SARL - Société Limitée', 'SARL'),
            ('👤 Personne Physique', 'Personne Physique'),
            ('🏛️ SA - Anonyme', 'SA')
        ]

        for label, value in legal_forms:
            ttk.Radiobutton(
                legal_form_frame,
                text=label,
                variable=self.legal_form_var,
                value=value,
                command=self._on_legal_form_changed
            ).pack(anchor='w', pady=5)

        # RIGHT: GENERATION TYPE SELECTION
        type_frame = ttk.LabelFrame(top_sections_frame, text="📊 Type de génération", padding=15)
        type_frame.grid(row=0, column=1, padx=(10, 0), sticky='nsew')

        self.gen_type_var = tk.StringVar(value='')

        # Option: Creation
        ttk.Radiobutton(
            type_frame,
            text="📄 Création de Société",
            variable=self.gen_type_var,
            value='creation',
            command=self._on_generation_type_changed
        ).pack(anchor='w', pady=8)

        # Option: Domiciliation
        ttk.Radiobutton(
            type_frame,
            text="🏢 Domiciliation",
            variable=self.gen_type_var,
            value='domiciliation',
            command=self._on_generation_type_changed
        ).pack(anchor='w', pady=8)

        # Separator
        ttk.Separator(main_frame, orient='horizontal').pack(fill='x', pady=(0, 20))

        # ===== SECTION 3: TEMPLATE MANAGEMENT & SELECTION (FULL WIDTH) =====
        template_frame = ttk.LabelFrame(main_frame, text="🗂️  Modèles disponibles", padding=15)
        template_frame.pack(fill='both', expand=True, pady=(0, 20))

        # Buttons for template management
        btn_frame = ttk.Frame(template_frame)
        btn_frame.pack(fill='x', pady=(0, 15))

        WidgetFactory.create_button(
            btn_frame,
            text="🔄 Actualiser",
            command=self._refresh_template_list,
            style='Manage.TButton'
        ).pack(side='left', padx=3, ipady=5)

        WidgetFactory.create_button(
            btn_frame,
            text="📁 Consulter",
            command=self._view_templates,
            style='Manage.TButton'
        ).pack(side='left', padx=3, ipady=5)

        WidgetFactory.create_button(
            btn_frame,
            text="⬆️ Uploader",
            command=self._upload_template,
            style='Manage.TButton'
        ).pack(side='left', padx=3, ipady=5)

        # Template list with checkboxes - avec plus d'espace
        ttk.Label(template_frame, text="Sélectionner les modèles:", font=('Segoe UI', 11, 'bold')).pack(anchor='w', pady=(0, 12))

        # Create frame with scrollbar for template checkboxes - PLUS GRAND
        list_frame = ttk.Frame(template_frame)
        list_frame.pack(fill='both', expand=True, pady=(0, 0))

        scrollbar = ttk.Scrollbar(list_frame)
        scrollbar.pack(side='right', fill='y')

        # Use Canvas for checkboxes - avec hauteur appropriée
        self.template_canvas = tk.Canvas(
            list_frame, 
            bg='#2b2b2b', 
            highlightthickness=1, 
            highlightbackground='#555555',
            relief='solid',
            height=200  # Hauteur adaptée à la fenêtre réduite
        )
        self.template_canvas.pack(side='left', fill='both', expand=True)
        scrollbar.config(command=self.template_canvas.yview)
        self.template_canvas.config(yscrollcommand=scrollbar.set)

        # Inner frame for checkboxes
        self.template_inner_frame = ttk.Frame(self.template_canvas)
        self.template_canvas_window = self.template_canvas.create_window((0, 0), window=self.template_inner_frame, anchor='nw')

        # Bind canvas resizing to update layout
        self.template_inner_frame.bind('<Configure>', self._on_frame_configure)
        self.template_canvas.bind('<Configure>', self._on_canvas_configure)

        # Dictionary to store template checkbox variables
        self.template_vars = {}

        # Populate template list
        self._refresh_template_list()

    def _on_frame_configure(self, event=None):
        """Update the scroll region of the canvas when frame is resized."""
        # Update the canvas scroll region to encompass all content
        self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))

    def _on_canvas_configure(self, event=None):
        """Resize the inner frame to match canvas width when canvas is resized."""
        # Make the inner frame match the canvas width for proper layout
        canvas_width = self.template_canvas.winfo_width()
        if canvas_width > 1:
            # Configure the frame itself to have the same width as canvas
            self.template_inner_frame.configure(width=canvas_width)

    def _on_legal_form_changed(self):
        """Handle legal form radio button changes and refresh template list."""
        # Refresh template list based on new legal form
        self._refresh_template_list()
        # Auto-select templates if generation type is already selected
        gen_type = self.gen_type_var.get()
        if gen_type:
            self._auto_select_templates(gen_type)

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

    def _auto_select_templates(self, doc_type: str):
        """Automatically select templates based on document type and legal form.

        Args:
            doc_type: 'creation' or 'domiciliation'
        """
        # Uncheck all first
        for var in self.template_vars.values():
            var.set(False)

        # Get the selected legal form
        legal_form = self.legal_form_var.get()
        if not legal_form:
            return  # No legal form selected, don't auto-select anything

        # Select templates based on type
        if doc_type == 'creation':
            # Select creation templates that match the legal form
            for template_path, var in self.template_vars.items():
                template_name = template_path.name.lower()
                
                # Check if it's a creation template
                is_creation = any(keyword.lower() in template_name for keyword in CREATION_TEMPLATES_KEYWORDS)
                
                # Check if it matches the legal form
                form_match = self._template_matches_legal_form(template_path, legal_form)
                
                if is_creation and form_match:
                    var.set(True)

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
        form_to_folder = {
            'SARL AU': 'SARL AU',
            'SARL_AU': 'SARL AU',
            'SARL': 'SARL',
            'Personne Physique': 'Personne Physique',
            'Personne_Physique': 'Personne Physique',
            'SA': 'SA',
        }
        
        # Normalize legal form
        normalized_form = legal_form.strip()
        expected_folder = form_to_folder.get(normalized_form, normalized_form)
        
        # Template matches if:
        # 1. It's in the appropriate legal form folder, OR
        # 2. It's in the root Models folder (shared for all forms)
        return parent_folder == expected_folder or parent_folder == 'Models'

    def _refresh_template_list(self):
        """Refresh the template list with checkboxes for available .docx files.
        
        Shows ONLY templates for the selected legal form, no shared templates.
        If no legal form is selected, shows a message.
        """
        # Clear existing widgets
        for widget in self.template_inner_frame.winfo_children():
            widget.destroy()
        self.template_vars.clear()

        try:
            models_dir = PathManager.MODELS_DIR
            if not models_dir.exists():
                self._show_template_message("⚠️ Dossier Models non trouvé")
                return

            # Get selected legal form
            selected_legal_form = self.legal_form_var.get()
            
            if not selected_legal_form:
                # No legal form selected - show message with padding
                msg_frame = ttk.Frame(self.template_inner_frame)
                msg_frame.pack(fill='both', expand=True, padx=20, pady=40)
                
                ttk.Label(
                    msg_frame,
                    text="⬆️ Veuillez sélectionner une forme juridique",
                    font=('Segoe UI', 11, 'italic'),
                    foreground='#888888'
                ).pack(anchor='center', expand=True)
                
                self.template_inner_frame.update_idletasks()
                self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
                self.after(100, self._on_canvas_configure)
                return

            # Get templates ONLY from the selected legal form folder
            all_templates = []
            form_path = models_dir / selected_legal_form
            
            if form_path.exists() and form_path.is_dir():
                all_templates = sorted([f for f in form_path.glob('*.docx')])
            
            if all_templates:
                for template in all_templates:
                    # Create checkbox variable
                    var = tk.BooleanVar(value=False)
                    self.template_vars[template] = var

                    # Create checkbox widget with nice display name
                    # Extract clean name (remove 2026_Modèle_SARLAU_ prefix)
                    clean_name = template.stem
                    for prefix in ['2026_Modèle_SARLAU_', '2026_Modèle_SARL_', '2026_Modèle_PP_', '2026_Modèle_SA_']:
                        if clean_name.startswith(prefix):
                            clean_name = clean_name[len(prefix):]
                            break
                    
                    display_name = f"📄 {clean_name}"
                    
                    # Create checkbutton - let it expand naturally with fill='x'
                    chk = ttk.Checkbutton(
                        self.template_inner_frame,
                        text=display_name,
                        variable=var
                    )
                    chk.pack(anchor='w', pady=7, padx=15, fill='x')
            else:
                msg_frame = ttk.Frame(self.template_inner_frame)
                msg_frame.pack(fill='both', expand=True, padx=20, pady=40)
                
                ttk.Label(
                    msg_frame,
                    text=f"⚠️ Aucun modèle trouvé pour {selected_legal_form}",
                    font=('Segoe UI', 11, 'italic'),
                    foreground='#888888'
                ).pack(anchor='center', expand=True)
                
                self.template_inner_frame.update_idletasks()
                self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
                self.after(100, self._on_canvas_configure)

            # Update canvas scroll region
            self.template_inner_frame.update_idletasks()
            self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
            
            # Force canvas width update
            self.after(100, self._on_canvas_configure)

        except Exception as e:
            logger.exception(f"Erreur lors du chargement des modèles: {e}")
            msg_frame = ttk.Frame(self.template_inner_frame)
            msg_frame.pack(fill='both', expand=True, padx=20, pady=40)
            
            ttk.Label(
                msg_frame,
                text=f"❌ Erreur: {str(e)}",
                font=('Segoe UI', 10),
                foreground='#ff6666'
            ).pack(anchor='center', expand=True)
            
            self.template_inner_frame.update_idletasks()
            self.template_canvas.configure(scrollregion=self.template_canvas.bbox('all'))
            self.after(100, self._on_canvas_configure)

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
        """Upload a new template file to the appropriate folder with improved UX.
        
        Asks user to select which legal form the template is for:
        - SARL AU
        - SARL
        - Personne Physique
        - SA
        """
        try:
            # Get selected legal form first
            selected_form = self.legal_form_var.get()
            
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
            
            dest_dir = models_dir / selected_form
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
                to_pdf = self.output_format in ('pdf', 'both')

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


def show_generation_selector(parent, values: Optional[dict] = None, output_format: str = 'docx') -> Optional[dict]:
    """Show the generation selector dialog and return the result.

    Args:
        parent: Parent window
        values: Dictionary of form values (societe, associes, contrat)
        output_format: 'docx', 'pdf', or 'both'

    Returns:
        Dictionary with generation result, or None if cancelled
    """
    dialog = GenerationSelectorDialog(parent, values or {}, output_format)
    parent.wait_window(dialog)
    return dialog.get_result()
