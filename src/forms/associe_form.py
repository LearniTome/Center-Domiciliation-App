import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import DateEntry
from typing import Optional, Callable, Tuple, List
from decimal import Decimal, InvalidOperation
from src.utils.utils import ThemeManager

class AssocieForm(ttk.Frame):
    _CIVILITY_CANONICAL = ("Monsieur", "Madame")
    _CIVILITY_ALIASES = {
        "m": "Monsieur",
        "m.": "Monsieur",
        "mr": "Monsieur",
        "monsieur": "Monsieur",
        "mme": "Madame",
        "mme.": "Madame",
        "madame": "Madame",
        "mlle": "Madame",
        "melle": "Madame",
    }

    def __init__(
        self,
        parent,
        theme_manager: Optional[ThemeManager] = None,
        values_dict=None,
        get_societe_totals: Optional[Callable[[], Tuple[float, float]]] = None,
    ):
        """AssocieForm supports two calling conventions for backward compatibility:

        - AssocieForm(parent, theme_manager)
        - AssocieForm(parent, values_dict={})  # legacy dashboard usage

        We detect the types and adapt accordingly.
        """
        super().__init__(parent)

        # Backwards-compatible handling: if the second positional arg is a dict or list,
        # it's actually the legacy `values_dict`.
        if isinstance(theme_manager, (dict, list)) and values_dict is None:
            values_dict = theme_manager
            theme_manager = None

        # Ensure we have a ThemeManager instance
        if theme_manager is None:
            try:
                theme_manager = ThemeManager(self.winfo_toplevel())
            except Exception:
                # Fallback: try constructing with self; if not available keep None
                try:
                    theme_manager = ThemeManager(self)
                except Exception:
                    theme_manager = None

        self.theme_manager = theme_manager
        self.associe_vars = []
        self.get_societe_totals = get_societe_totals
        self._societe_trace_refs = []
        self._percentage_trace_refs = []
        self._capital_summary_vars = {}
        self._capital_entry_widgets = {}
        self._capital_layout_widgets = {}
        self._remove_buttons = {}
        self._associe_layout_widgets = {}
        self._legal_form_min_associes = 0
        self._legal_form_max_associes = 10
        self.add_button: Optional[ttk.Button] = None
        self._is_updating_distribution = False
        self._label_width = 12
        self._label_width_long = 18
        self._numeric_entry_bindings = []
        self._init_local_styles()

        # Load reference data from database
        from ..utils.utils import get_reference_data
        self.nationalites = get_reference_data('Nationalites')
        self.lieux_naissance = get_reference_data('LieuxNaissance')

        # Configure weights to expand properly
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(0, weight=1)

        # Configure main container
        self.main_container = ttk.Frame(self)
        self.main_container.grid(row=0, column=0, sticky="nsew")
        self.main_container.grid_columnconfigure(0, weight=1)
        # Keep natural content height to avoid large empty spaces with one associé.
        self.main_container.grid_rowconfigure(0, weight=0)
        # Setup UI components
        self.setup_scrollable_container()
        self.setup_control_buttons()
        # Note: do NOT auto-add an initial associé here. The parent MainForm
        # will control how many associés are created initially to avoid
        # duplications when embedded in different contexts.

        # Cleanup on destroy
        self.bind("<Destroy>", self._cleanup)

        # If legacy values_dict was provided, populate fields
        if values_dict is not None:
            # If it's a list of associés, set them all
            if isinstance(values_dict, list):
                if len(values_dict) == 0:
                    # create a single empty associe to allow data entry
                    self.add_associe()
                else:
                    self.set_values(values_dict)
            elif isinstance(values_dict, dict):
                # create one associe and populate
                self.add_associe()
                if len(values_dict) > 0:
                    self.set_values([values_dict])

    def _init_local_styles(self):
        """Local styles to improve readability of computed fields."""
        try:
            style = ttk.Style(self)
            colors = self.theme_manager.colors if self.theme_manager else {}
            section_bg = colors.get('section_bg', '#2a2a2a')
            fg_muted = '#c8d0da'
            style.configure(
                'Computed.TEntry',
                fieldbackground=section_bg,
                foreground=fg_muted,
                borderwidth=1,
                relief='solid',
                padding=(5, 4),
            )
            style.map(
                'Computed.TEntry',
                fieldbackground=[('readonly', section_bg)],
                foreground=[('readonly', fg_muted)],
            )
        except Exception:
            pass

    @classmethod
    def normalize_civility(cls, value: Optional[str]) -> str:
        raw = str(value or "").strip()
        if not raw:
            return cls._CIVILITY_CANONICAL[0]
        normalized = raw.replace(" ", "").lower()
        return cls._CIVILITY_ALIASES.get(normalized, cls._CIVILITY_ALIASES.get(raw.lower(), cls._CIVILITY_CANONICAL[0]))

    @staticmethod
    def parse_legacy_bool(value) -> bool:
        if isinstance(value, bool):
            return value
        if value is None:
            return False
        if isinstance(value, (int, float)):
            return value != 0
        s = str(value).strip().lower()
        if s in {"1", "true", "vrai", "yes", "oui", "on"}:
            return True
        if s in {"0", "false", "faux", "no", "non", "off", ""}:
            return False
        return bool(s)

    def create_associe_vars(self):
        """Crée et retourne les variables pour un nouvel associé"""
        # Get custom defaults or use sensible defaults
        from ..utils.defaults_manager import get_defaults_manager
        from ..utils.constants import Civility, QualityAssocie
        
        defaults_mgr = get_defaults_manager()
        
        default_civility = self.normalize_civility(
            defaults_mgr.get_default('associe', 'Civility') or (Civility[0] if Civility else self._CIVILITY_CANONICAL[0])
        )
        default_quality = defaults_mgr.get_default('associe', 'Quality') or (QualityAssocie[0] if QualityAssocie else 'Associé')
        default_nom = defaults_mgr.get_default('associe', 'Nom') or 'NOM'
        default_prenom = defaults_mgr.get_default('associe', 'Prenom') or 'PRENOM'
        default_nationalite = defaults_mgr.get_default('associe', 'Nationality') or (self.nationalites[0] if self.nationalites else '')
        default_lieu = self.lieux_naissance[0] if self.lieux_naissance else ''
        default_num_piece = defaults_mgr.get_default('associe', 'NumPiece') or ''
        default_adresse = defaults_mgr.get_default('associe', 'Adresse') or ''
        default_telephone = defaults_mgr.get_default('associe', 'Telephone') or ''
        default_email = defaults_mgr.get_default('associe', 'Email') or ''

        return {
            'civilite': tk.StringVar(value=default_civility),
            'nom': tk.StringVar(value=default_nom),
            'prenom': tk.StringVar(value=default_prenom),
            'parts': tk.StringVar(value=''),
            'date_naiss': tk.StringVar(value=''),
            'lieu_naiss': tk.StringVar(value=default_lieu),
            'nationalite': tk.StringVar(value=default_nationalite),
            'num_piece': tk.StringVar(value=default_num_piece),
            'validite_piece': tk.StringVar(value=''),
            'adresse': tk.StringVar(value=default_adresse),
            'telephone': tk.StringVar(value=default_telephone),
            'email': tk.StringVar(value=default_email),
            'est_gerant': tk.BooleanVar(value=True),
            'qualite': tk.StringVar(value=default_quality),
            'percentage': tk.StringVar(value='100'),
            'capital_detenu': tk.StringVar(value=''),
            'num_parts': tk.StringVar(value='')
        }

    def create_associe_fields(self, parent, index):
        """Crée les champs pour un associé"""
        vars_dict = self.create_associe_vars()
        assoc_id = id(vars_dict)

        # Frame principal de l'associé
        frame = ttk.LabelFrame(parent, text=f"👤 Associé {index + 1}")
        frame.pack(fill="x", padx=5, pady=(4, 8))

        # Zone principale sans sous-blocs/titres, organisée en 6 colonnes.
        content = ttk.Frame(frame)
        content.pack(fill="x", padx=6, pady=3)
        content.columnconfigure(0, weight=1, uniform="assoc_form_cols")
        content.columnconfigure(1, weight=1, uniform="assoc_form_cols")
        content.columnconfigure(2, weight=1, uniform="assoc_form_cols")
        content.columnconfigure(3, weight=1, uniform="assoc_form_cols")
        content.columnconfigure(4, weight=1, uniform="assoc_form_cols")
        content.columnconfigure(5, weight=1, uniform="assoc_form_cols")

        def _cell(row: int, col: int, label_text: str, pad: int = 8, span: int = 1) -> ttk.Frame:
            cell = ttk.Frame(content)
            cell.grid(
                row=row,
                column=col,
                columnspan=span,
                sticky="ew",
                padx=(0, pad) if (col + span - 1) < 5 else (0, 0),
                pady=(0, 4),
            )
            cell.columnconfigure(0, weight=1)
            ttk.Label(cell, text=label_text, anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
            return cell

        # Ligne 1: identité principale (6 champs)
        civil_cell = _cell(0, 0, "Civilité:")
        ttk.Combobox(
            civil_cell,
            textvariable=vars_dict['civilite'],
            values=list(self._CIVILITY_CANONICAL),
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        nom_cell = _cell(0, 1, "Nom:")
        ttk.Entry(nom_cell, textvariable=vars_dict['nom']).grid(row=1, column=0, sticky="ew")

        prenom_cell = _cell(0, 2, "Prénom:")
        ttk.Entry(prenom_cell, textvariable=vars_dict['prenom']).grid(row=1, column=0, sticky="ew")

        nat_cell = _cell(0, 3, "Nationalité:")
        ttk.Combobox(
            nat_cell,
            textvariable=vars_dict['nationalite'],
            values=self.nationalites,
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        cin_cell = _cell(0, 4, "N° CIN:")
        ttk.Entry(cin_cell, textvariable=vars_dict['num_piece']).grid(row=1, column=0, sticky="ew")

        validite_cell = _cell(0, 5, "Validité CIN:")
        DateEntry(
            validite_cell,
            textvariable=vars_dict['validite_piece'],
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="ew")
        try:
            vars_dict['validite_piece'].set('')
        except Exception:
            pass

        # Ligne 2: profil + contact
        date_cell = _cell(1, 0, "Date de naissance:")
        DateEntry(
            date_cell,
            textvariable=vars_dict['date_naiss'],
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="ew")
        try:
            vars_dict['date_naiss'].set('')
        except Exception:
            pass

        lieu_cell = _cell(1, 1, "Lieu de naissance:")
        ttk.Combobox(
            lieu_cell,
            textvariable=vars_dict['lieu_naiss'],
            values=self.lieux_naissance,
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        qualite_cell = _cell(1, 2, "Qualité:")
        try:
            from ..utils import constants as _constants
            qual_options = getattr(_constants, 'QualityAssocie', ["Associé", "Associé Unique"])
        except Exception:
            qual_options = ["Associé", "Associé Unique"]
        ttk.Combobox(
            qualite_cell,
            textvariable=vars_dict['qualite'],
            values=qual_options,
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        tel_cell = _cell(1, 3, "Téléphone:")
        ttk.Entry(tel_cell, textvariable=vars_dict['telephone']).grid(row=1, column=0, sticky="ew")

        email_cell = _cell(1, 4, "Email:")
        ttk.Entry(email_cell, textvariable=vars_dict['email']).grid(row=1, column=0, sticky="ew")

        # Ligne 3: adresse élargie + capital, statut en dernier
        adr_cell = _cell(2, 0, "Adresse:", span=2)
        ttk.Entry(adr_cell, textvariable=vars_dict['adresse']).grid(row=1, column=0, sticky="ew")

        pct_cell = _cell(2, 2, "% des parts:")
        pct_entry = ttk.Entry(pct_cell, textvariable=vars_dict['percentage'])
        pct_entry.grid(row=1, column=0, sticky="ew")
        self._bind_numeric_entry(pct_entry, vars_dict['percentage'], decimals=4)

        cap_cell = _cell(2, 3, "Capital détenu (DH):")
        capital_entry = ttk.Entry(
            cap_cell,
            textvariable=vars_dict['capital_detenu'],
            state="readonly",
            style='Computed.TEntry',
        )
        capital_entry.grid(row=1, column=0, sticky="ew")

        parts_cell = _cell(2, 4, "Nombre de parts:")
        parts_entry = ttk.Entry(
            parts_cell,
            textvariable=vars_dict['num_parts'],
            state="readonly",
            style='Computed.TEntry',
        )
        parts_entry.grid(row=1, column=0, sticky="ew")

        statut_cell = _cell(2, 5, "Statut:")
        statut_row = ttk.Frame(statut_cell)
        statut_row.grid(row=1, column=0, sticky="ew")
        statut_row.columnconfigure(0, weight=1)

        ttk.Checkbutton(
            statut_row,
            text="Est Gérant",
            variable=vars_dict['est_gerant'],
        ).grid(row=0, column=0, sticky="w")

        remove_btn = ttk.Button(
            statut_row,
            text="❌ Supprimer",
            style='Cancel.TButton',
            command=lambda: self.remove_associe(frame, vars_dict),
        )
        remove_btn.grid(row=0, column=1, sticky="e", padx=(6, 0))
        self._remove_buttons[assoc_id] = remove_btn

        summary_var = tk.StringVar(value="Total société: — | Part associé: —")
        self._capital_summary_vars[assoc_id] = summary_var
        self._capital_entry_widgets[assoc_id] = (capital_entry, parts_entry)

        summary_label = ttk.Label(content, textvariable=summary_var, style='FieldLabel.TLabel')
        summary_label.grid(row=3, column=0, columnspan=6, sticky="w", pady=(3, 0))
        self._capital_layout_widgets[assoc_id] = {
            'grid': content,
            'pct_cell': pct_cell,
            'cap_cell': cap_cell,
            'parts_cell': parts_cell,
            'summary_label': summary_label,
        }

        self.associe_vars.append(vars_dict)
        try:
            pct_var = vars_dict.get('percentage')
            if pct_var is not None:
                self._percentage_trace_refs.append((pct_var, pct_var.trace_add('write', self._on_percentage_changed)))
        except Exception:
            pass
        return frame

    def create_basic_info_section(self, parent, vars_dict):
        """Crée la section Informations de base"""
        info_frame = ttk.LabelFrame(parent, text="📝 Informations de base")
        info_frame.pack(fill="x", pady=5)

        grid = ttk.Frame(info_frame)
        grid.pack(fill="x", padx=6, pady=6)
        grid.columnconfigure(1, weight=1)
        grid.columnconfigure(3, weight=1)

        # Ligne 1: Civilité et Prénom
        ttk.Label(grid, text="Civilité:", anchor="e", width=self._label_width).grid(row=0, column=0, padx=(0, 5), pady=2)
        civilite_cb = ttk.Combobox(
            grid,
            textvariable=vars_dict['civilite'],
            values=list(self._CIVILITY_CANONICAL),
            width=12,
            state="readonly",
        )
        civilite_cb.grid(row=0, column=1, sticky="w", padx=(0, 15), pady=2)

        ttk.Label(grid, text="Prénom:", anchor="e", width=self._label_width).grid(row=0, column=2, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['prenom']).grid(row=0, column=3, sticky="ew", pady=2)

        # Ligne 2: Nom (Parts removed per request)
        ttk.Label(grid, text="Nom:", anchor="e", width=self._label_width).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['nom']).grid(row=1, column=1, sticky="ew", padx=(0, 15), pady=2)

    def create_merged_identity_section(self, parent, vars_dict):
        """Fusion Informations de base + Identité sans sous-titres."""
        container = ttk.Frame(parent)
        container.pack(fill="x", pady=1)
        container.columnconfigure(0, weight=14, uniform="merged_info_cols")
        container.columnconfigure(1, weight=18, uniform="merged_info_cols")
        container.columnconfigure(2, weight=18, uniform="merged_info_cols")
        container.columnconfigure(3, weight=16, uniform="merged_info_cols")
        container.columnconfigure(4, weight=16, uniform="merged_info_cols")
        container.columnconfigure(5, weight=18, uniform="merged_info_cols")

        def _cell(col, label, weight=1):
            cell = ttk.Frame(container)
            cell.grid(row=0, column=col, sticky="ew", padx=(0, 6))
            cell.columnconfigure(0, weight=weight)
            ttk.Label(cell, text=label, anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
            return cell

        civil_cell = _cell(0, "Civilité:")
        ttk.Combobox(
            civil_cell,
            textvariable=vars_dict['civilite'],
            values=list(self._CIVILITY_CANONICAL),
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        nom_cell = _cell(1, "Nom:")
        ttk.Entry(nom_cell, textvariable=vars_dict['nom']).grid(row=1, column=0, sticky="ew")

        prenom_cell = _cell(2, "Prénom:")
        ttk.Entry(prenom_cell, textvariable=vars_dict['prenom']).grid(row=1, column=0, sticky="ew")

        nat_cell = _cell(3, "Nationalité:")
        ttk.Combobox(
            nat_cell,
            textvariable=vars_dict['nationalite'],
            values=self.nationalites,
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        cin_cell = _cell(4, "N° CIN:")
        ttk.Entry(cin_cell, textvariable=vars_dict['num_piece']).grid(row=1, column=0, sticky="ew")

        validite_cell = _cell(5, "Validité CIN:")
        DateEntry(
            validite_cell,
            textvariable=vars_dict['validite_piece'],
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="w")
        try:
            vars_dict['validite_piece'].set('')
        except Exception:
            pass

    def create_birth_section(self, parent, vars_dict):
        """Crée la section Naissance"""
        birth_frame = ttk.LabelFrame(parent, text="👶 Naissance")
        birth_frame.pack(fill="x", pady=5)

        grid = ttk.Frame(birth_frame)
        grid.pack(fill="x", padx=6, pady=6)
        grid.columnconfigure(1, weight=1)

        # Date de naissance (use DateEntry for convenience)
        ttk.Label(grid, text="Date de naissance:", anchor="e", width=self._label_width_long).grid(row=0, column=0, padx=(0, 5), pady=2)
        DateEntry(grid, textvariable=vars_dict['date_naiss'], date_pattern='dd/mm/yyyy', width=12).grid(row=0, column=1, sticky="w", pady=2)
        # Start with an empty date so the user must pick one explicitly
        try:
            vars_dict['date_naiss'].set('')
        except Exception:
            pass

        # Lieu de naissance
        ttk.Label(grid, text="Lieu de naissance:", anchor="e", width=self._label_width_long).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Combobox(grid, textvariable=vars_dict['lieu_naiss'], values=self.lieux_naissance, state="readonly").grid(row=1, column=1, sticky="ew", pady=2)

    def create_manager_section(self, parent, vars_dict):
        """Crée la section Statut de l'Associé"""
        manager_frame = ttk.LabelFrame(parent, text="👔 Statut de l'Associé")
        manager_frame.pack(fill="x", pady=5)

        grid = ttk.Frame(manager_frame)
        grid.pack(fill="x", padx=6, pady=6)
        grid.columnconfigure(1, weight=1)

        # Checkbox Est Gérant
        ttk.Checkbutton(grid, text="Est Gérant", variable=vars_dict['est_gerant']).grid(
            row=0, column=0, columnspan=2, sticky="w", pady=2)

        # Qualité — use Combobox to present common roles while keeping the current default
        ttk.Label(grid, text="Qualité:", anchor="e", width=self._label_width).grid(row=1, column=0, padx=(0, 5), pady=2)
        try:
            from ..utils import constants as _constants
            qual_options = getattr(_constants, 'QualityAssocie', ["Associé", "Associé Unique"])
        except Exception:
            qual_options = ["Associé", "Associé Unique"]
        ttk.Combobox(
            grid,
            textvariable=vars_dict['qualite'],
            values=qual_options,
            state="readonly",
        ).grid(row=1, column=1, sticky="ew", pady=2)

    def create_profile_section(self, parent, vars_dict):
        """Section Profil associé: Naissance + Statut dans une ligne."""
        profile_frame = ttk.LabelFrame(parent, text="🧾 Profil associé")
        profile_frame.pack(fill="x", pady=3)

        content = ttk.Frame(profile_frame)
        content.pack(fill="x", padx=6, pady=4)
        content.columnconfigure(0, weight=18, uniform="profile_cols")
        content.columnconfigure(1, weight=34, uniform="profile_cols")
        content.columnconfigure(2, weight=14, uniform="profile_cols")
        content.columnconfigure(3, weight=34, uniform="profile_cols")

        date_cell = ttk.Frame(content)
        date_cell.grid(row=0, column=0, sticky="ew", padx=(0, 6))
        date_cell.columnconfigure(0, weight=1)
        ttk.Label(date_cell, text="Date de naissance:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        DateEntry(
            date_cell,
            textvariable=vars_dict['date_naiss'],
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="w")
        try:
            vars_dict['date_naiss'].set('')
        except Exception:
            pass

        lieu_cell = ttk.Frame(content)
        lieu_cell.grid(row=0, column=1, sticky="ew", padx=(0, 6))
        lieu_cell.columnconfigure(0, weight=1)
        ttk.Label(lieu_cell, text="Lieu de naissance:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        ttk.Combobox(
            lieu_cell,
            textvariable=vars_dict['lieu_naiss'],
            values=self.lieux_naissance,
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        check_cell = ttk.Frame(content)
        check_cell.grid(row=0, column=2, sticky="ew", padx=(0, 6))
        check_cell.columnconfigure(0, weight=1)
        ttk.Label(check_cell, text="Statut:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        ttk.Checkbutton(
            check_cell,
            text="Est Gérant",
            variable=vars_dict['est_gerant'],
        ).grid(row=1, column=0, sticky="w")

        qual_cell = ttk.Frame(content)
        qual_cell.grid(row=0, column=3, sticky="ew")
        qual_cell.columnconfigure(0, weight=1)
        ttk.Label(qual_cell, text="Qualité:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        try:
            from ..utils import constants as _constants
            qual_options = getattr(_constants, 'QualityAssocie', ["Associé", "Associé Unique"])
        except Exception:
            qual_options = ["Associé", "Associé Unique"]
        ttk.Combobox(
            qual_cell,
            textvariable=vars_dict['qualite'],
            values=qual_options,
            state="readonly",
        ).grid(row=1, column=0, sticky="ew")

        return profile_frame

    def create_identity_section(self, parent, vars_dict):
        """Crée la section Identité"""
        identity_frame = ttk.LabelFrame(parent, text="🆔 Identité")
        identity_frame.pack(fill="x", pady=5)

        grid = ttk.Frame(identity_frame)
        grid.pack(fill="x", padx=6, pady=6)
        grid.columnconfigure(1, weight=1)

        # Nationalité — use data loaded from reference sheet
        ttk.Label(grid, text="Nationalité:", anchor="e", width=self._label_width).grid(row=0, column=0, padx=(0, 5), pady=2)
        ttk.Combobox(
            grid,
            textvariable=vars_dict['nationalite'],
            values=self.nationalites,
            state="readonly",
        ).grid(row=0, column=1, sticky="ew", pady=2)

        # N° CIN
        ttk.Label(grid, text="N° CIN:", anchor="e", width=self._label_width).grid(row=1, column=0, padx=(0, 5), pady=2)
        ttk.Entry(grid, textvariable=vars_dict['num_piece']).grid(row=1, column=1, sticky="ew", pady=2)

        # Validité CIN (use DateEntry for convenience)
        ttk.Label(grid, text="Validité CIN:", anchor="e", width=self._label_width).grid(row=2, column=0, padx=(0, 5), pady=2)
        DateEntry(grid, textvariable=vars_dict['validite_piece'], date_pattern='dd/mm/yyyy', width=12).grid(row=2, column=1, sticky="w", pady=2)
        # Keep the validity date empty until the user selects it
        try:
            vars_dict['validite_piece'].set('')
        except Exception:
            pass

    def create_contact_section(self, parent, vars_dict):
        """Crée la section Contact en ligne unique."""
        contact_frame = ttk.LabelFrame(parent, text="📞 Contact")
        contact_frame.pack(fill="x", pady=3)

        grid = ttk.Frame(contact_frame)
        grid.pack(fill="x", padx=6, pady=4)
        grid.columnconfigure(0, weight=44, uniform="contact_cols")
        grid.columnconfigure(1, weight=24, uniform="contact_cols")
        grid.columnconfigure(2, weight=32, uniform="contact_cols")

        adresse_cell = ttk.Frame(grid)
        adresse_cell.grid(row=0, column=0, sticky="ew", padx=(0, 6))
        adresse_cell.columnconfigure(0, weight=1)
        ttk.Label(adresse_cell, text="Adresse:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        ttk.Entry(adresse_cell, textvariable=vars_dict['adresse']).grid(row=1, column=0, sticky="ew")

        tel_cell = ttk.Frame(grid)
        tel_cell.grid(row=0, column=1, sticky="ew", padx=(0, 6))
        tel_cell.columnconfigure(0, weight=1)
        ttk.Label(tel_cell, text="Téléphone:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        ttk.Entry(tel_cell, textvariable=vars_dict['telephone']).grid(row=1, column=0, sticky="ew")

        email_cell = ttk.Frame(grid)
        email_cell.grid(row=0, column=2, sticky="ew")
        email_cell.columnconfigure(0, weight=1)
        ttk.Label(email_cell, text="Email:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        ttk.Entry(email_cell, textvariable=vars_dict['email']).grid(row=1, column=0, sticky="ew")

    def create_capital_section(self, parent, vars_dict):
        """Crée la section Capital"""
        capital_frame = ttk.LabelFrame(parent, text="💰 Capital")
        capital_frame.pack(fill="x", pady=3)

        grid = ttk.Frame(capital_frame)
        grid.pack(fill="x", padx=6, pady=4)
        grid.columnconfigure(0, weight=40, uniform="capital_cols")
        grid.columnconfigure(1, weight=35, uniform="capital_cols")
        grid.columnconfigure(2, weight=25, uniform="capital_cols")

        pct_cell = ttk.Frame(grid)
        pct_cell.columnconfigure(0, weight=1)
        ttk.Label(pct_cell, text="% des parts:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        pct_entry = ttk.Entry(pct_cell, textvariable=vars_dict['percentage'])
        pct_entry.grid(row=1, column=0, sticky="ew")
        self._bind_numeric_entry(pct_entry, vars_dict['percentage'], decimals=4)

        cap_cell = ttk.Frame(grid)
        cap_cell.columnconfigure(0, weight=1)
        ttk.Label(cap_cell, text="Capital détenu (DH):", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        capital_entry = ttk.Entry(
            cap_cell,
            textvariable=vars_dict['capital_detenu'],
            state="readonly",
            style='Computed.TEntry',
        )
        capital_entry.grid(row=1, column=0, sticky="ew")

        parts_cell = ttk.Frame(grid)
        parts_cell.columnconfigure(0, weight=1)
        ttk.Label(parts_cell, text="Nombre de parts:", anchor="w").grid(row=0, column=0, sticky="w", pady=(0, 1))
        parts_entry = ttk.Entry(
            parts_cell,
            textvariable=vars_dict['num_parts'],
            state="readonly",
            style='Computed.TEntry',
        )
        parts_entry.grid(row=1, column=0, sticky="ew")

        # Mini résumé de répartition (par associé + total société).
        summary_var = tk.StringVar(value="Total société: — | Part associé: —")
        self._capital_summary_vars[id(vars_dict)] = summary_var
        self._capital_entry_widgets[id(vars_dict)] = (capital_entry, parts_entry)
        summary_label = ttk.Label(
            grid,
            textvariable=summary_var,
            style='FieldLabel.TLabel',
        )

        self._capital_layout_widgets[id(vars_dict)] = {
            'grid': grid,
            'pct_cell': pct_cell,
            'cap_cell': cap_cell,
            'parts_cell': parts_cell,
            'summary_label': summary_label,
        }
        self._apply_capital_layout(id(vars_dict), compact=False)

    def setup_scrollable_container(self):
        """Configure le conteneur scrollable pour les associés"""
        # Use a simple frame inside the main container. The outer MainForm
        # canvas (the page container) will handle scrolling for the whole page.
        canvas_frame = ttk.Frame(self.main_container)
        canvas_frame.grid(row=0, column=0, sticky="ew")
        canvas_frame.grid_columnconfigure(0, weight=1)
        canvas_frame.grid_rowconfigure(0, weight=0)

        # The associes_frame holds the individual associé LabelFrames.
        # It is a plain Frame (no inner canvas/scrollbar) so the outer
        # MainForm canvas controls scrolling for the whole page.
        self.associes_frame = ttk.Frame(canvas_frame)
        self.associes_frame.grid(row=0, column=0, sticky="ew")
        self.associes_frame.grid_columnconfigure(0, weight=1)

    def setup_control_buttons(self):
        """Configure les boutons de contrôle"""
        buttons_frame = ttk.Frame(self.main_container)
        buttons_frame.grid(row=1, column=0, sticky="ew", pady=3, padx=5)
        buttons_frame.grid_columnconfigure(1, weight=1)

        self.repartition_status_var = tk.StringVar(value="Répartition: —")
        status_bg = self.theme_manager.colors.get('bg', '#1f1f1f') if self.theme_manager else '#1f1f1f'
        self.repartition_status_label = tk.Label(
            buttons_frame,
            textvariable=self.repartition_status_var,
            anchor='w',
            bg=status_bg,
            fg='#c5ced8',
            font=('Segoe UI', 10, 'bold'),
        )
        self.repartition_status_label.grid(row=0, column=0, sticky="w")

        actions = ttk.Frame(buttons_frame)
        actions.grid(row=0, column=1, sticky="e")

        self.equalize_button = ttk.Button(
            actions,
            text="⚖️ Répartir équitablement",
            style='Manage.TButton',
            command=self._distribute_evenly_across_associes,
        )
        self.equalize_button.grid(row=0, column=0, sticky="e", padx=(0, 4))

        add_button = ttk.Button(
            actions,
            text="➕ Ajouter un associé",
            style='Success.TButton',
            command=self.add_associe
        )
        add_button.grid(row=0, column=1, sticky="e")
        self.add_button = add_button

    @staticmethod
    def _normalize_legal_form(raw_value: Optional[str]) -> str:
        text = str(raw_value or "").strip().upper()
        if not text:
            return ""
        text = text.replace("_", " ").replace("-", " ")
        text = " ".join(text.split())
        return text

    @classmethod
    def _legal_form_rules(cls, legal_form: Optional[str]) -> Tuple[int, int]:
        normalized = cls._normalize_legal_form(legal_form)
        if normalized in {"SARL AU", "SARLAU"} or ("SARL" in normalized and "AU" in normalized):
            return 1, 1
        if normalized == "SARL":
            return 2, 10
        return 0, 10

    def apply_legal_form_constraints(self, legal_form: Optional[str], auto_adjust: bool = True, add_if_empty: bool = False):
        min_count, max_count = self._legal_form_rules(legal_form)
        self._legal_form_min_associes = min_count
        self._legal_form_max_associes = max_count

        current_count = len(self.associe_vars)
        target_min = min_count
        if add_if_empty and current_count == 0 and min_count == 0:
            target_min = 1

        added = False
        if auto_adjust and current_count < target_min:
            for _ in range(target_min - current_count):
                self.add_associe()
                added = True
            if added and current_count == 0 and target_min >= 2:
                self._distribute_evenly_across_associes()

        removed = False
        if max_count > 0 and len(self.associe_vars) > max_count:
            frames = self._associe_frames()
            while len(self.associe_vars) > max_count:
                frame = frames.pop() if frames else None
                vars_dict = self.associe_vars[-1]
                self._force_remove_associe(frame, vars_dict)
                removed = True

        if removed:
            self.update_associes_numbers()
            self._distribute_evenly_across_associes()
            messagebox.showinfo(
                "Nombre d'associés",
                "Les associés en trop ont été supprimés automatiquement pour la forme SARL AU."
            )

        self._update_associe_action_states()

    def _update_associe_action_states(self):
        current_count = len(self.associe_vars)
        max_count = self._legal_form_max_associes
        min_count = self._legal_form_min_associes

        if self.add_button is not None:
            try:
                if max_count > 0 and current_count >= max_count:
                    self.add_button.configure(state="disabled")
                else:
                    self.add_button.configure(state="normal")
            except Exception:
                pass

        for vars_dict in self.associe_vars:
            btn = self._remove_buttons.get(id(vars_dict))
            if btn is None:
                continue
            try:
                if current_count <= min_count:
                    btn.configure(state="disabled")
                else:
                    btn.configure(state="normal")
            except Exception:
                pass

    def _associe_frames(self) -> List[ttk.LabelFrame]:
        try:
            children = list(self.associes_frame.winfo_children())
        except Exception:
            return []
        return [child for child in children if isinstance(child, ttk.LabelFrame)]

    def _force_remove_associe(self, frame: Optional[tk.Widget], vars_dict):
        vars_id = id(vars_dict)
        try:
            if vars_dict in self.associe_vars:
                self.associe_vars.remove(vars_dict)
        except Exception:
            pass
        self._capital_summary_vars.pop(vars_id, None)
        self._capital_entry_widgets.pop(vars_id, None)
        self._capital_layout_widgets.pop(vars_id, None)
        self._remove_buttons.pop(vars_id, None)
        self._associe_layout_widgets.pop(vars_id, None)
        if frame is not None:
            try:
                frame.destroy()
            except Exception:
                pass

    def _layout_mode_for_width(self, width: int) -> str:
        if width >= 1300:
            return "wide"
        if width >= 1000:
            return "medium"
        return "narrow"

    def _on_associe_frame_configure(self, assoc_id: int, width: int):
        self._apply_associe_layout(assoc_id, width)

    def _apply_associe_layout(self, assoc_id: int, width: int):
        layout = self._associe_layout_widgets.get(assoc_id)
        if not layout:
            return

        mode = self._layout_mode_for_width(max(1, int(width or 1)))
        if layout.get('mode') == mode:
            return
        layout['mode'] = mode

        main_grid = layout['main_grid']
        merged_slot = layout['merged_slot']
        profile_slot = layout['profile_slot']
        contact_slot = layout['contact_slot']
        capital_slot = layout['capital_slot']

        for slot in (merged_slot, profile_slot, contact_slot, capital_slot):
            try:
                slot.grid_forget()
            except Exception:
                pass

        main_grid.columnconfigure(0, weight=1)

        merged_slot.grid(row=0, column=0, sticky="nsew", padx=2, pady=(0, 1))
        profile_slot.grid(row=1, column=0, sticky="nsew", padx=2, pady=1)
        contact_slot.grid(row=2, column=0, sticky="nsew", padx=2, pady=1)
        capital_slot.grid(row=3, column=0, sticky="nsew", padx=2, pady=(1, 0))

        self._apply_capital_layout(assoc_id, mode == "narrow")

    def _apply_capital_layout(self, assoc_id: int, compact: bool):
        widgets = self._capital_layout_widgets.get(assoc_id)
        if not widgets:
            return
        grid = widgets.get('grid')
        if grid is None:
            return

        pct_cell = widgets.get('pct_cell')
        cap_cell = widgets.get('cap_cell')
        parts_cell = widgets.get('parts_cell')
        summary_label = widgets.get('summary_label')

        for item in (pct_cell, cap_cell, parts_cell, summary_label):
            if item is None:
                continue
            try:
                item.grid_forget()
            except Exception:
                pass

        if compact:
            grid.columnconfigure(0, weight=34, uniform="capital_cols")
            grid.columnconfigure(1, weight=33, uniform="capital_cols")
            grid.columnconfigure(2, weight=33, uniform="capital_cols")
            pad_mid = 6
        else:
            grid.columnconfigure(0, weight=40, uniform="capital_cols")
            grid.columnconfigure(1, weight=35, uniform="capital_cols")
            grid.columnconfigure(2, weight=25, uniform="capital_cols")
            pad_mid = 8

        pct_cell.grid(row=0, column=0, sticky="ew", padx=(0, pad_mid), pady=(0, 2))
        cap_cell.grid(row=0, column=1, sticky="ew", padx=pad_mid, pady=(0, 2))
        parts_cell.grid(row=0, column=2, sticky="ew", padx=(pad_mid, 0), pady=(0, 2))
        summary_label.grid(row=1, column=0, columnspan=3, sticky="w", pady=(2, 0))

    def add_first_associe(self):
        """Ajoute le premier associé"""
        # kept for backward compatibility but left intentionally empty
        return

    def add_associe(self):
        """Ajoute un nouvel associé en préservant la répartition existante."""
        current_count = len(self.associe_vars)
        max_count = min(10, self._legal_form_max_associes) if self._legal_form_max_associes else 10
        if max_count > 0 and current_count >= max_count:
            messagebox.showwarning(
                "Limite atteinte",
                "Vous ne pouvez pas ajouter plus d'associés pour cette forme juridique."
            )
            return
        if len(self.associe_vars) >= 10:
            messagebox.showwarning(
                "Limite atteinte",
                "Vous ne pouvez pas ajouter plus de 10 associés."
            )
            return

        total_before = sum(self._iter_percentages()) if self.associe_vars else 0.0
        self.create_associe_fields(self.associes_frame, len(self.associe_vars))
        if not self.associe_vars:
            return

        # Keep existing percentages untouched; new associate gets remaining quota.
        remaining = max(0.0, 100.0 - total_before)
        new_vars = self.associe_vars[-1]
        self._is_updating_distribution = True
        try:
            new_vars['percentage'].set(self._format_number(remaining, 4))
        finally:
            self._is_updating_distribution = False
        self._redistribute_by_percentages()
        self._update_associe_action_states()

    def get_values(self):
        """Retourne la liste des associés sous forme de dictionnaires."""
        self._apply_numeric_display_format()
        results = []
        for vars_dict in self.associe_vars:
            item = {}
            for k, v in vars_dict.items():
                try:
                    # BooleanVar -> bool
                    if isinstance(v, tk.BooleanVar):
                        item[k] = bool(v.get())
                    else:
                        item[k] = v.get()
                except Exception:
                    item[k] = None
            item['civilite'] = self.normalize_civility(item.get('civilite'))
            results.append(item)
        return results

    def _clear_associes_ui(self):
        """Clear associate widgets and traces to avoid stale references."""
        try:
            for var, trace_id in self._percentage_trace_refs:
                try:
                    var.trace_remove('write', trace_id)
                except Exception:
                    pass
            self._percentage_trace_refs = []
        except Exception:
            pass

        for child in list(self.associes_frame.winfo_children()):
            child.destroy()

        self.associe_vars = []
        self._capital_summary_vars = {}
        self._capital_entry_widgets = {}
        self._capital_layout_widgets = {}
        self._remove_buttons = {}
        self._associe_layout_widgets = {}
        self._update_associe_action_states()

    def set_values(self, associes_list):
        """Remplit le formulaire des associés avec une liste de dicts.

        Each element of associes_list should be a dict mapping the field names
        to values. This will clear existing entries and recreate them.
        """
        associes_list = associes_list or []
        self._clear_associes_ui()

        self._is_updating_distribution = True
        try:
            for assoc in associes_list:
                self.create_associe_fields(self.associes_frame, len(self.associe_vars))
                # Populate the latest vars dict
                vars_dict = self.associe_vars[-1]
                for k, val in assoc.items():
                    if k in vars_dict:
                        try:
                            if isinstance(vars_dict[k], tk.BooleanVar):
                                vars_dict[k].set(self.parse_legacy_bool(val))
                            elif k == 'civilite':
                                vars_dict[k].set(self.normalize_civility(val))
                            else:
                                vars_dict[k].set(val)
                        except Exception:
                            pass
        finally:
            self._is_updating_distribution = False
        self._apply_numeric_display_format()
        self._backfill_percentages_for_loaded_data()
        self._redistribute_by_percentages()
        self._update_associe_action_states()

    def remove_associe(self, frame, vars_dict):
        """Supprime un associé"""
        current_count = len(self.associe_vars)
        if current_count <= self._legal_form_min_associes:
            messagebox.showwarning(
                "Suppression impossible",
                "Le nombre minimal d'associés pour cette forme juridique est atteint."
            )
            return
        if messagebox.askyesno("Confirmation",
                             "Voulez-vous vraiment supprimer cet associé ?"):
            vars_id = id(vars_dict)
            self.associe_vars.remove(vars_dict)
            self._capital_summary_vars.pop(vars_id, None)
            self._capital_entry_widgets.pop(vars_id, None)
            self._capital_layout_widgets.pop(vars_id, None)
            self._remove_buttons.pop(vars_id, None)
            self._associe_layout_widgets.pop(vars_id, None)
            frame.destroy()
            self.update_associes_numbers()
            self._redistribute_by_percentages()
            self._update_associe_action_states()

    def update_associes_numbers(self):
        """Met à jour les numéros des associés après une suppression"""
        for i, frame in enumerate(self.associes_frame.winfo_children()):
            if isinstance(frame, ttk.LabelFrame):
                frame.configure(text=f"👤 Associé {i + 1}")

    def bind_societe_totals_vars(self, capital_var, parts_var):
        """Bind company total capital/parts vars to recalculate associates live."""
        try:
            if capital_var is not None:
                self._societe_trace_refs.append((capital_var, capital_var.trace_add('write', self._on_societe_totals_changed)))
            if parts_var is not None:
                self._societe_trace_refs.append((parts_var, parts_var.trace_add('write', self._on_societe_totals_changed)))
        except Exception:
            pass

    def _on_societe_totals_changed(self, *_args):
        self._redistribute_by_percentages()

    def _on_percentage_changed(self, *_args):
        self._redistribute_by_percentages()

    def _to_float(self, value) -> Optional[float]:
        try:
            s = str(value or '').strip()
            if not s:
                return None
            s = s.replace(' ', '').replace(',', '.')
            return float(s)
        except Exception:
            return None

    def _to_int(self, value) -> Optional[int]:
        v = self._to_float(value)
        if v is None:
            return None
        try:
            return int(round(v))
        except Exception:
            return None

    def _format_number(self, value: Optional[float], decimals: int = 2) -> str:
        if value is None:
            return ''
        try:
            number = Decimal(str(float(value)))
        except Exception:
            return ''
        if decimals <= 0:
            return f"{int(round(float(value))):,}".replace(',', ' ')
        s = f"{float(value):,.{decimals}f}"
        integer_part, fractional_part = s.split('.')
        integer_part = integer_part.replace(',', ' ')
        fractional_part = fractional_part.rstrip('0')
        if not fractional_part:
            return integer_part
        return f"{integer_part},{fractional_part}"

    def _format_numeric_text(self, value, decimals: int = 4) -> str:
        text = str(value or '').strip()
        if not text:
            return ''
        normalized = text.replace('\xa0', ' ').replace(' ', '')
        if ',' in normalized and '.' in normalized:
            normalized = normalized.replace(',', '')
        elif ',' in normalized:
            normalized = normalized.replace(',', '.')
        try:
            number = Decimal(normalized)
        except (InvalidOperation, ValueError):
            return text

        if decimals <= 0:
            return f"{int(round(float(number))):,}".replace(',', ' ')

        rendered = f"{float(number):,.{decimals}f}"
        integer_part, fractional_part = rendered.split('.')
        integer_part = integer_part.replace(',', ' ')
        fractional_part = fractional_part.rstrip('0')
        if not fractional_part:
            return integer_part
        return f"{integer_part},{fractional_part}"

    def _bind_numeric_entry(self, entry: ttk.Entry, variable, decimals: int = 4):
        entry.bind(
            "<FocusOut>",
            lambda _event, var=variable, places=decimals: var.set(self._format_numeric_text(var.get(), places)),
            add="+",
        )
        entry.bind(
            "<Return>",
            lambda _event, var=variable, places=decimals: var.set(self._format_numeric_text(var.get(), places)),
            add="+",
        )
        self._numeric_entry_bindings.append((entry, variable, decimals))

    def _apply_numeric_display_format(self):
        for _entry, variable, decimals in self._numeric_entry_bindings:
            try:
                variable.set(self._format_numeric_text(variable.get(), decimals))
            except Exception:
                pass

    def _get_societe_totals(self) -> Tuple[Optional[float], Optional[int]]:
        """Return (capital_total, parts_total) from callback or top-level MainForm."""
        if callable(self.get_societe_totals):
            try:
                cap, parts = self.get_societe_totals()
                return self._to_float(cap), self._to_int(parts)
            except Exception:
                pass
        try:
            top = self.winfo_toplevel()
            main_form = getattr(top, 'main_form', None)
            societe_form = getattr(main_form, 'societe_form', None)
            if societe_form is not None:
                cap = societe_form.capital_var.get() if hasattr(societe_form, 'capital_var') else None
                parts = societe_form.parts_social_var.get() if hasattr(societe_form, 'parts_social_var') else None
                return self._to_float(cap), self._to_int(parts)
        except Exception:
            pass
        return None, None

    def _iter_percentages(self):
        values = []
        for vars_dict in self.associe_vars:
            pct = self._to_float(vars_dict.get('percentage').get() if vars_dict.get('percentage') else '')
            values.append(max(0.0, pct if pct is not None else 0.0))
        return values

    def _distribute_evenly_across_associes(self):
        """Explicit equal split action: percentage + capital + parts."""
        if self._is_updating_distribution:
            return
        count = len(self.associe_vars)
        if count <= 0:
            return

        self._is_updating_distribution = True
        try:
            equal_pct = 100.0 / count
            for vars_dict in self.associe_vars:
                vars_dict['percentage'].set(self._format_number(equal_pct, 4))
        finally:
            self._is_updating_distribution = False
        self._redistribute_by_percentages()

    def _redistribute_by_percentages(self):
        """Recompute capital détenu + parts from current percentages and company totals."""
        if self._is_updating_distribution:
            return
        count = len(self.associe_vars)
        if count <= 0:
            return

        cap_total, parts_total = self._get_societe_totals()
        percentages = self._iter_percentages()
        sum_pct = sum(percentages)
        if sum_pct <= 0:
            percentages = [100.0 / count] * count
            sum_pct = 100.0

        shares = [p / 100.0 for p in percentages]
        self._is_updating_distribution = True
        try:
            # Capital détenu (DH)
            if cap_total is not None:
                for idx, vars_dict in enumerate(self.associe_vars):
                    cap_val = cap_total * shares[idx]
                    vars_dict['capital_detenu'].set(self._format_number(cap_val, 2))

            # Nombre de parts (entier) avec conservation du total.
            if parts_total is not None:
                raw_parts = [parts_total * s for s in shares]
                if abs(sum_pct - 100.0) <= 0.01:
                    int_parts = [int(x) for x in raw_parts]
                    remaining = int(parts_total - sum(int_parts))
                    frac_idx = sorted(
                        range(len(raw_parts)),
                        key=lambda i: (raw_parts[i] - int_parts[i]),
                        reverse=True
                    )
                    for i in range(max(0, remaining)):
                        int_parts[frac_idx[i % len(frac_idx)]] += 1
                else:
                    int_parts = [max(0, int(round(x))) for x in raw_parts]
                for idx, vars_dict in enumerate(self.associe_vars):
                    vars_dict['num_parts'].set(self._format_number(max(0, int_parts[idx]), 0))
        finally:
            self._is_updating_distribution = False
        self._update_repartition_status()

    def _backfill_percentages_for_loaded_data(self):
        """When older records have no percentage, derive them (parts or equal split)."""
        if not self.associe_vars:
            return

        has_explicit = False
        for vars_dict in self.associe_vars:
            if self._to_float(vars_dict['percentage'].get()) is not None:
                has_explicit = True
                break
        if has_explicit:
            return

        _cap_total, parts_total = self._get_societe_totals()
        derived = []
        if parts_total and parts_total > 0:
            ok = True
            for vars_dict in self.associe_vars:
                p = self._to_int(vars_dict['num_parts'].get())
                if p is None:
                    ok = False
                    break
                derived.append((p * 100.0) / float(parts_total))
            if not ok:
                derived = []

        if not derived:
            count = len(self.associe_vars)
            derived = [100.0 / count] * count

        self._is_updating_distribution = True
        try:
            for idx, vars_dict in enumerate(self.associe_vars):
                vars_dict['percentage'].set(self._format_number(derived[idx], 4))
        finally:
            self._is_updating_distribution = False
        self._update_repartition_status()

    def _set_status(self, text: str, level: str = 'neutral'):
        self.repartition_status_var.set(text)
        if not hasattr(self, 'repartition_status_label'):
            return
        color_map = {
            'ok': '#72b58f',
            'warn': '#e1b678',
            'error': '#e08a8a',
            'neutral': '#c5ced8',
        }
        try:
            self.repartition_status_label.configure(fg=color_map.get(level, color_map['neutral']))
        except Exception:
            pass

    def _update_repartition_status(self):
        total_pct = sum(self._iter_percentages()) if self.associe_vars else 0.0
        cap_total, parts_total = self._get_societe_totals()
        cap_txt = self._format_number(cap_total, 2) if cap_total is not None else '—'
        parts_txt = self._format_number(parts_total, 0) if parts_total is not None else '—'

        if not self.associe_vars:
            self._set_status("Répartition: aucun associé", 'neutral')
            return

        diff = round(total_pct - 100.0, 4)
        if abs(diff) <= 0.01:
            msg = f"Répartition valide: 100% | Total société: {cap_txt} DH | {parts_txt} parts"
            self._set_status(msg, 'ok')
        elif diff < 0:
            msg = (
                f"Répartition incomplète: {self._format_number(total_pct, 4)}% "
                f"(il manque {self._format_number(abs(diff), 4)}%)"
                f" | Total société: {cap_txt} DH | {parts_txt} parts"
            )
            self._set_status(msg, 'warn')
        else:
            msg = (
                f"Répartition dépasse 100%: {self._format_number(total_pct, 4)}% "
                f"(excès {self._format_number(abs(diff), 4)}%)"
                f" | Total société: {cap_txt} DH | {parts_txt} parts"
            )
            self._set_status(msg, 'error')

        # Update mini summary inside each capital block.
        for vars_dict in self.associe_vars:
            summary_var = self._capital_summary_vars.get(id(vars_dict))
            if summary_var is None:
                continue
            pct_txt = self._format_number(self._to_float(vars_dict['percentage'].get()) or 0.0, 4)
            summary_var.set(
                f"Total société: {cap_txt} DH | {parts_txt} parts | Part associé: {pct_txt}%"
            )

    def validate_for_submit(self, show_dialog: bool = True) -> Tuple[bool, List[str]]:
        """Validate associates data before save/generation."""
        errors: List[str] = []

        if not self.associe_vars:
            errors.append("Au moins un associé est requis.")

        total_pct = 0.0
        for idx, vars_dict in enumerate(self.associe_vars, start=1):
            nom = str(vars_dict.get('nom').get() if vars_dict.get('nom') else '').strip()
            prenom = str(vars_dict.get('prenom').get() if vars_dict.get('prenom') else '').strip()
            if not nom:
                errors.append(f"Associé {idx}: le nom est obligatoire.")
            if not prenom:
                errors.append(f"Associé {idx}: le prénom est obligatoire.")

            pct_raw = vars_dict.get('percentage').get() if vars_dict.get('percentage') else ''
            pct = self._to_float(pct_raw)
            if pct is None:
                errors.append(f"Associé {idx}: le pourcentage doit être numérique.")
                continue
            if pct < 0 or pct > 100:
                errors.append(f"Associé {idx}: le pourcentage doit être entre 0 et 100.")
                continue
            total_pct += pct

        if total_pct > 100.0 + 0.01:
            errors.append(
                f"Le total des pourcentages dépasse 100% ({self._format_number(total_pct, 4)}%)."
            )

        is_valid = len(errors) == 0
        if not is_valid and show_dialog:
            messagebox.showerror(
                "Validation des associés",
                "\n".join(f"- {msg}" for msg in errors),
            )
        return is_valid, errors

    def _cleanup(self, event):
        """Nettoyage lors de la destruction"""
        try:
            canvas = getattr(self, 'canvas', None)
            if canvas is not None:
                canvas.unbind_all("<MouseWheel>")
        except Exception:
            pass
        # Remove traces bound to percentage vars.
        try:
            for var, trace_id in self._percentage_trace_refs:
                try:
                    var.trace_remove('write', trace_id)
                except Exception:
                    pass
            self._percentage_trace_refs = []
        except Exception:
            pass
        # Remove traces bound to company total vars.
        try:
            for var, trace_id in self._societe_trace_refs:
                try:
                    var.trace_remove('write', trace_id)
                except Exception:
                    pass
            self._societe_trace_refs = []
        except Exception:
            pass
        self._capital_summary_vars = {}
        self._capital_entry_widgets = {}
        self._capital_layout_widgets = {}
        self._remove_buttons = {}
        self._associe_layout_widgets = {}
        self._numeric_entry_bindings = []
