import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import DateEntry
from typing import Optional, List
from decimal import Decimal, InvalidOperation
from ..utils.constants import DenSte, Formjur, Capital, PartsSocial, SteAdresse, Tribunnaux
from ..utils.utils import ThemeManager, WidgetFactory

class SocieteForm(ttk.Frame):
    def __init__(self, parent, theme_manager: Optional[ThemeManager] = None, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = {}

        # Backward compatibility: SocieteForm(parent, values_dict)
        if isinstance(theme_manager, dict) and values_dict is None:
            values_dict = theme_manager
            theme_manager = None

        # Initialiser le gestionnaire de thème
        self.theme_manager = theme_manager or ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Liste pour stocker les références aux combobox
        self.combos = []
        self._numeric_entry_bindings = []

        # Variables pour stocker les valeurs des champs
        self.initialize_variables()

        # Création du formulaire
        self.setup_gui()
        self._apply_numeric_display_format()

        # Définir les valeurs initiales si fournies
        if values_dict:
            self.set_values(values_dict)

    def initialize_variables(self):
        """Initialise toutes les variables du formulaire avec des valeurs par défaut raisonnables"""
        # Variables pour les champs de texte — valeurs par défaut prises depuis les constantes
        defaults = self._get_default_values()
        from ..utils.utils import get_reference_data

        self.den_ste_var = tk.StringVar(value=defaults['denomination'])
        self.forme_jur_var = tk.StringVar(value=defaults['forme_juridique'])
        self.ice_var = tk.StringVar(value=defaults['ice'])
        self.date_ice_var = tk.StringVar(value=defaults['date_ice'])
        self.date_expiration_certificat_negatif_var = tk.StringVar(value=defaults['date_expiration_certificat_negatif'])
        self.capital_var = tk.StringVar(value=defaults['capital'])
        self.parts_social_var = tk.StringVar(value=defaults['parts_social'])
        self.valeur_nominale_var = tk.StringVar(value=defaults['valeur_nominale'])
        self.mode_signature_gerance_var = tk.StringVar(value=defaults['mode_signature_gerance'])
        self.type_generation_var = tk.StringVar(
            value=self._normalize_generation_type(defaults.get('type_generation'))
        )
        self.procedure_creation_var = tk.StringVar(
            value=self._normalize_creation_procedure(defaults.get('procedure_creation'))
        )
        self.mode_depot_creation_var = tk.StringVar(
            value=self._normalize_creation_depot_mode(defaults.get('mode_depot_creation'))
        )
        self._societe_calc_trace_refs = []

        # Load reference data from database
        self.ste_adresses = get_reference_data('SteAdresses')
        self.tribunaux = get_reference_data('Tribunaux')

        self.ste_adress_var = tk.StringVar(value=defaults['adresse'])
        self.tribunal_var = tk.StringVar(value=defaults['tribunal'])

        # Liste pour stocker les variables des activités
        self.activites_vars = []
        self._activity_rows: List[dict] = []

    def _get_default_values(self):
        """Retourne les valeurs par défaut société (constants + defaults manager)."""
        from ..utils.constants import DenSte, Formjur, Capital, PartsSocial, SteAdresse, Tribunnaux
        from ..utils.defaults_manager import get_defaults_manager
        import datetime

        defaults_mgr = get_defaults_manager()
        today = datetime.date.today().strftime('%d/%m/%Y')
        return {
            'denomination': defaults_mgr.get_default('societe', 'DenSte') or (DenSte[0] if DenSte else ""),
            'forme_juridique': defaults_mgr.get_default('societe', 'FormJur') or (Formjur[0] if Formjur else ""),
            'ice': defaults_mgr.get_default('societe', 'Ice') or "",
            'date_ice': defaults_mgr.get_default('societe', 'DateIce') or today,
            'date_expiration_certificat_negatif': defaults_mgr.get_default('societe', 'DateExpCertNeg') or '',
            'capital': defaults_mgr.get_default('societe', 'Capital') or (Capital[0] if Capital else ""),
            'parts_social': defaults_mgr.get_default('societe', 'PartsSocial') or (PartsSocial[0] if PartsSocial else ""),
            'valeur_nominale': defaults_mgr.get_default('societe', 'ValeurNominale') or "100",
            'adresse': defaults_mgr.get_default('societe', 'SteAdresse') or (SteAdresse[0] if SteAdresse else ""),
            'tribunal': defaults_mgr.get_default('societe', 'Tribunal') or (Tribunnaux[0] if Tribunnaux else ""),
            'mode_signature_gerance': defaults_mgr.get_default('societe', 'ModeSignatureGerance') or "separee",
            'type_generation': defaults_mgr.get_default('societe', 'TypeGeneration') or "creation",
            'procedure_creation': defaults_mgr.get_default('societe', 'ProcedureCreation') or "normal",
            'mode_depot_creation': defaults_mgr.get_default('societe', 'ModeDepotCreation') or "depot_physique",
        }

    def reset(self):
        """Réinitialise le formulaire société aux valeurs par défaut."""
        defaults = self._get_default_values()
        self.den_ste_var.set(defaults['denomination'])
        self.forme_jur_var.set(defaults['forme_juridique'])
        self.ice_var.set(defaults['ice'])
        self.date_ice_var.set(defaults['date_ice'])
        self.date_expiration_certificat_negatif_var.set(defaults['date_expiration_certificat_negatif'])
        self.capital_var.set(defaults['capital'])
        self.valeur_nominale_var.set(defaults['valeur_nominale'])
        self._update_parts_social()
        self.ste_adress_var.set(defaults['adresse'])
        self.tribunal_var.set(defaults['tribunal'])
        self.mode_signature_gerance_var.set(defaults['mode_signature_gerance'])
        self.type_generation_var.set(self._normalize_generation_type(defaults.get('type_generation')))
        self.procedure_creation_var.set(self._normalize_creation_procedure(defaults.get('procedure_creation')))
        self.mode_depot_creation_var.set(self._normalize_creation_depot_mode(defaults.get('mode_depot_creation')))
        self.clear_all_activities()
        self._update_mode_signature_visibility()
        self._update_generation_options_visibility()
        self._apply_numeric_display_format()

    def setup_gui(self):
        """Configure une mise en page compacte sans sous-blocs internes."""
        main_frame = ttk.Frame(self, padding=(10, 5))
        main_frame.pack(fill="both", expand=True)

        # Première section après le grand titre: nature de procédure de création
        self._create_generation_options_section(main_frame)

        fields = ttk.Frame(main_frame)
        fields.pack(fill="x", padx=5, pady=(2, 4))
        for col in range(6):
            fields.columnconfigure(col, weight=1, uniform="societe_cols")

        def _cell(row: int, col: int, label_text: str, span: int = 1, pad: int = 8) -> ttk.Frame:
            cell = ttk.Frame(fields)
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

        # Ligne 1: 6 colonnes (Tribunal juste après Forme Juridique)
        den_cell = _cell(0, 0, "Dénomination:")
        den_combo = ttk.Combobox(den_cell, textvariable=self.den_ste_var, values=DenSte)
        den_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(den_combo)

        form_cell = _cell(0, 1, "Forme Juridique:")
        form_combo = ttk.Combobox(form_cell, textvariable=self.forme_jur_var, values=Formjur)
        form_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(form_combo)
        form_combo.bind("<<ComboboxSelected>>", self._on_forme_juridique_changed)

        tribunal_cell = _cell(0, 2, "Tribunal:")
        tribunal_combo = ttk.Combobox(tribunal_cell, textvariable=self.tribunal_var, values=self.tribunaux)
        tribunal_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(tribunal_combo)

        ice_cell = _cell(0, 3, "ICE:")
        ttk.Entry(ice_cell, textvariable=self.ice_var).grid(row=1, column=0, sticky="ew")

        date_cell = _cell(0, 4, "Date certificat négatif:")
        DateEntry(
            date_cell,
            textvariable=self.date_ice_var,
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="ew")

        date_exp_cell = _cell(0, 5, "Date expiration certificat négatif:")
        DateEntry(
            date_exp_cell,
            textvariable=self.date_expiration_certificat_negatif_var,
            date_pattern='dd/mm/yyyy',
            width=12,
        ).grid(row=1, column=0, sticky="ew")

        # Ligne 2: Adresse en dernier (2 colonnes)
        capital_cell = _cell(1, 0, "Capital:")
        capital_entry = ttk.Entry(capital_cell, textvariable=self.capital_var)
        capital_entry.grid(row=1, column=0, sticky="ew")
        self._bind_numeric_entry(capital_entry, self.capital_var)

        parts_cell = _cell(1, 1, "Parts:")
        parts_entry = ttk.Entry(parts_cell, textvariable=self.parts_social_var, state="readonly")
        parts_entry.grid(row=1, column=0, sticky="ew")

        valeur_nominale_cell = _cell(1, 2, "Valeur nominale (DH):")
        valeur_nominale_entry = ttk.Entry(valeur_nominale_cell, textvariable=self.valeur_nominale_var)
        valeur_nominale_entry.grid(row=1, column=0, sticky="ew")
        self._bind_numeric_entry(valeur_nominale_entry, self.valeur_nominale_var)
        self._bind_parts_social_calculation()

        adresse_cell = _cell(1, 3, "Adresse:", span=2)
        adresse_combo = ttk.Combobox(adresse_cell, textvariable=self.ste_adress_var, values=self.ste_adresses)
        adresse_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(adresse_combo)

        self.mode_signature_cell = _cell(1, 5, "Mode signature gérance:")
        self.mode_signature_combo = ttk.Combobox(
            self.mode_signature_cell,
            textvariable=self.mode_signature_gerance_var,
            values=["separee", "conjointe"],
            state="readonly",
        )
        self.mode_signature_combo.grid(row=1, column=0, sticky="ew")
        self.combos.append(self.mode_signature_combo)
        self.forme_jur_var.trace_add("write", self._on_forme_juridique_var_changed)
        self._update_mode_signature_visibility()

        # Ligne 3+: activités
        self.create_activities_section(main_frame)

    @staticmethod
    def _format_numeric_text(value: str) -> str:
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

        if number == number.to_integral():
            return f"{int(number):,}".replace(',', ' ')

        decimals = normalized.split('.', 1)[1] if '.' in normalized else ''
        trimmed_decimals = decimals.rstrip('0')
        decimal_places = min(max(len(trimmed_decimals), 1), 4)
        formatted = f"{number:,.{decimal_places}f}"
        integer_part, fractional_part = formatted.split('.')
        integer_part = integer_part.replace(',', ' ')
        fractional_part = fractional_part.rstrip('0')
        if not fractional_part:
            return integer_part
        return f"{integer_part},{fractional_part}"

    def _on_numeric_focus_out(self, variable: tk.StringVar):
        try:
            variable.set(self._format_numeric_text(variable.get()))
        except Exception:
            pass
        self._update_parts_social()

    def _bind_numeric_entry(self, entry: ttk.Entry, variable: tk.StringVar):
        entry.bind("<FocusOut>", lambda _event, var=variable: self._on_numeric_focus_out(var), add="+")
        entry.bind("<Return>", lambda _event, var=variable: self._on_numeric_focus_out(var), add="+")
        self._numeric_entry_bindings.append((entry, variable))

    def _apply_numeric_display_format(self):
        for _entry, variable in self._numeric_entry_bindings:
            self._on_numeric_focus_out(variable)
        self._update_parts_social()

    @staticmethod
    def _parse_decimal(value):
        text = str(value or '').strip()
        if not text:
            return None
        normalized = text.replace('\xa0', ' ').replace(' ', '')
        if ',' in normalized and '.' in normalized:
            normalized = normalized.replace(',', '')
        elif ',' in normalized:
            normalized = normalized.replace(',', '.')
        try:
            return Decimal(normalized)
        except (InvalidOperation, ValueError):
            return None

    @staticmethod
    def _format_parts_value(value: Decimal) -> str:
        if value == value.to_integral():
            return f"{int(value):,}".replace(',', ' ')
        rendered = f"{value:,.4f}"
        integer_part, fractional_part = rendered.split('.')
        integer_part = integer_part.replace(',', ' ')
        fractional_part = fractional_part.rstrip('0')
        if not fractional_part:
            return integer_part
        return f"{integer_part},{fractional_part}"

    def _update_parts_social(self, *_args):
        capital = self._parse_decimal(self.capital_var.get())
        nominal = self._parse_decimal(self.valeur_nominale_var.get())
        if capital is None or nominal is None or nominal == 0:
            self.parts_social_var.set('')
            return
        try:
            self.parts_social_var.set(self._format_parts_value(capital / nominal))
        except Exception:
            self.parts_social_var.set('')

    def _bind_parts_social_calculation(self):
        try:
            for var in (self.capital_var, self.valeur_nominale_var):
                self._societe_calc_trace_refs.append(
                    (var, var.trace_add('write', self._update_parts_social))
                )
        except Exception:
            pass
        self._update_parts_social()

    @staticmethod
    def _normalize_generation_type(raw_value) -> str:
        raw = str(raw_value or "").strip().lower()
        mapping = {
            "creation": "creation",
            "création": "creation",
            "domiciliation": "domiciliation",
        }
        return mapping.get(raw, "creation")

    @staticmethod
    def _normalize_creation_procedure(raw_value) -> str:
        raw = str(raw_value or "").strip().lower()
        mapping = {
            "normal": "normal",
            "acceleree": "acceleree",
            "accélérée": "acceleree",
            "accelere": "acceleree",
            "accélérer": "acceleree",
        }
        return mapping.get(raw, "normal")

    @staticmethod
    def _normalize_creation_depot_mode(raw_value) -> str:
        raw = str(raw_value or "").strip().lower()
        mapping = {
            "depot_physique": "depot_physique",
            "dépôt physique": "depot_physique",
            "depot physique": "depot_physique",
            "physique": "depot_physique",
            "depot_en_ligne": "depot_en_ligne",
            "dépôt en ligne": "depot_en_ligne",
            "depot en ligne": "depot_en_ligne",
            "en_ligne": "depot_en_ligne",
            "en ligne": "depot_en_ligne",
        }
        return mapping.get(raw, "depot_physique")

    def _create_generation_options_section(self, parent):
        section = ttk.LabelFrame(parent, text="Nature de Procédure de Création", padding=(8, 8))
        section.pack(fill="x", padx=5, pady=(0, 6))
        section.columnconfigure(0, weight=1)

        generation_row = ttk.Frame(section)
        generation_row.grid(row=0, column=0, sticky="w")
        ttk.Radiobutton(
            generation_row,
            text="Création",
            variable=self.type_generation_var,
            value="creation",
            command=self._on_generation_options_changed,
        ).pack(side="left", padx=(0, 14))
        ttk.Radiobutton(
            generation_row,
            text="Domiciliation",
            variable=self.type_generation_var,
            value="domiciliation",
            command=self._on_generation_options_changed,
        ).pack(side="left")

        self.creation_procedure_frame = ttk.Frame(section)
        self.creation_procedure_frame.grid(row=1, column=0, sticky="w", pady=(8, 0))
        ttk.Label(self.creation_procedure_frame, text="Procédure (Création):").pack(side="left", padx=(0, 8))
        ttk.Radiobutton(
            self.creation_procedure_frame,
            text="Normal",
            variable=self.procedure_creation_var,
            value="normal",
            command=self._on_generation_options_changed,
        ).pack(side="left", padx=(0, 10))
        ttk.Radiobutton(
            self.creation_procedure_frame,
            text="Accélérer",
            variable=self.procedure_creation_var,
            value="acceleree",
            command=self._on_generation_options_changed,
        ).pack(side="left")

        self.creation_depot_frame = ttk.Frame(section)
        self.creation_depot_frame.grid(row=2, column=0, sticky="w", pady=(6, 0))
        ttk.Label(self.creation_depot_frame, text="Dépôt (Accélérer):").pack(side="left", padx=(0, 8))
        ttk.Radiobutton(
            self.creation_depot_frame,
            text="Dépôt Physique",
            variable=self.mode_depot_creation_var,
            value="depot_physique",
            command=self._on_generation_options_changed,
        ).pack(side="left", padx=(0, 10))
        ttk.Radiobutton(
            self.creation_depot_frame,
            text="Dépôt En Ligne",
            variable=self.mode_depot_creation_var,
            value="depot_en_ligne",
            command=self._on_generation_options_changed,
        ).pack(side="left")

        self._update_generation_options_visibility()

    def _on_generation_options_changed(self, *_args):
        self._update_generation_options_visibility()

    def _update_generation_options_visibility(self):
        generation_type = self._normalize_generation_type(self.type_generation_var.get())
        is_creation = generation_type == "creation"
        is_accelerated = is_creation and self._normalize_creation_procedure(self.procedure_creation_var.get()) == "acceleree"

        if hasattr(self, "creation_procedure_frame"):
            if is_creation:
                self.creation_procedure_frame.grid()
            else:
                self.creation_procedure_frame.grid_remove()

        if hasattr(self, "creation_depot_frame"):
            if is_accelerated:
                self.creation_depot_frame.grid()
            else:
                self.creation_depot_frame.grid_remove()

    def create_activities_section(self, parent):
        """Crée une section activités sous forme de tableau compact."""
        from ..utils.utils import get_reference_data
        from ..utils.constants import Activities

        section = ttk.Frame(parent)
        section.pack(fill="both", expand=True, padx=5, pady=(6, 2))

        # Load activities from reference sheet
        self.activities_list = get_reference_data('Activites')
        if not self.activities_list:
            self.activities_list = list(Activities)
        self.default_activities = list(Activities[:4])

        # Tableau activités
        table = ttk.LabelFrame(section, text="Activités", padding=(8, 8))
        table.pack(fill="both", expand=True, pady=(2, 8))
        table.columnconfigure(0, weight=1)
        table.rowconfigure(2, weight=1)

        header_row = ttk.Frame(table)
        header_row.grid(row=0, column=0, sticky="ew", pady=(0, 4))
        header_row.columnconfigure(1, weight=1)
        ttk.Label(header_row, text="N°", width=5, anchor="w").grid(row=0, column=0, padx=(0, 8), sticky="w")
        ttk.Label(header_row, text="Activité", anchor="w").grid(row=0, column=1, sticky="w")
        ttk.Label(header_row, text="Action", width=26, anchor="e").grid(row=0, column=2, padx=(8, 0), sticky="e")
        ttk.Separator(table, orient="horizontal").grid(row=1, column=0, sticky="ew", pady=(0, 6))

        list_area = ttk.Frame(table)
        list_area.grid(row=2, column=0, sticky="ew")
        list_area.columnconfigure(0, weight=1)
        list_area.rowconfigure(0, weight=1)

        canvas_bg = '#2b2b2b' if getattr(self.theme_manager, 'is_dark_mode', True) else '#f7f7f7'
        border_color = '#555555' if getattr(self.theme_manager, 'is_dark_mode', True) else '#cccccc'
        self.activities_canvas = tk.Canvas(
            list_area,
            height=220,
            bg=canvas_bg,
            highlightthickness=1,
            highlightbackground=border_color,
            relief='solid',
        )
        self.activities_canvas.grid(row=0, column=0, sticky="nsew")

        activities_scroll = ttk.Scrollbar(list_area, orient="vertical", command=self.activities_canvas.yview)
        activities_scroll.grid(row=0, column=1, sticky="ns")
        self.activities_canvas.configure(yscrollcommand=activities_scroll.set)

        self.activities_container = ttk.Frame(self.activities_canvas)
        self.activities_canvas_window = self.activities_canvas.create_window((0, 0), window=self.activities_container, anchor="nw")
        self.activities_container.columnconfigure(0, weight=1)

        self.activities_container.bind(
            "<Configure>",
            lambda _e=None: self.activities_canvas.configure(scrollregion=self.activities_canvas.bbox("all"))
        )
        self.activities_canvas.bind(
            "<Configure>",
            lambda event: self.activities_canvas.itemconfigure(self.activities_canvas_window, width=event.width)
        )
        self.activities_canvas.bind("<MouseWheel>", self._on_activities_mousewheel)
        self.activities_canvas.bind("<Button-4>", self._on_activities_mousewheel_linux_up)
        self.activities_canvas.bind("<Button-5>", self._on_activities_mousewheel_linux_down)

        actions_bottom = ttk.Frame(section)
        actions_bottom.pack(fill="x", pady=(0, 2))

        add_btn = WidgetFactory.create_button(
            actions_bottom,
            text="➕ Ajouter une activité",
            command=self.add_activity,
            style='Success.TButton',
        )
        add_btn.pack(side="left")

        clear_btn = WidgetFactory.create_button(
            actions_bottom,
            text="🔄 Réinitialiser (4 par défaut)",
            command=self._on_clear_activities_clicked,
            style='Secondary.TButton',
        )
        clear_btn.pack(side="left", padx=(6, 0))

        clear_all_btn = WidgetFactory.create_button(
            actions_bottom,
            text="🗑Vider tout (0 activité)",
            command=self._on_clear_all_activities_clicked,
            style='Cancel.TButton',
        )
        clear_all_btn.pack(side="left", padx=(6, 0))

        self._load_default_activities()

    def _load_default_activities(self):
        """Précharge les 4 activités par défaut si aucune activité n'est présente."""
        if self.activites_vars:
            return
        defaults = [a for a in self.default_activities if str(a).strip()]
        if not defaults:
            self.add_activity()
            return
        for activity in defaults:
            self.add_activity(initial_value=activity)

    def _refresh_activity_rows(self):
        """Met à jour la numérotation et le placement des lignes d'activités."""
        total = len(self._activity_rows)
        for idx, row_info in enumerate(self._activity_rows, start=1):
            frame = row_info.get("frame")
            index_label = row_info.get("index_label")
            up_btn = row_info.get("up_btn")
            down_btn = row_info.get("down_btn")
            if frame is not None:
                frame.grid(row=idx - 1, column=0, sticky="ew", pady=2)
            if index_label is not None:
                index_label.configure(text=f"{idx:02d}")
            if up_btn is not None:
                up_btn.state(["disabled"] if idx == 1 else ["!disabled"])
            if down_btn is not None:
                down_btn.state(["disabled"] if idx == total else ["!disabled"])
        try:
            self.activities_container.update_idletasks()
            self.activities_canvas.configure(scrollregion=self.activities_canvas.bbox("all"))
        except Exception:
            pass

    def add_activity(self, initial_value: str = ""):
        """Ajoute une nouvelle activité"""
        var = tk.StringVar()
        if initial_value:
            var.set(initial_value)
        self.activites_vars.append(var)

        # Ligne d'activité (table)
        activity_frame = ttk.Frame(self.activities_container)
        activity_frame.columnconfigure(1, weight=1)

        index_label = ttk.Label(activity_frame, text="", width=5, anchor="w")
        index_label.grid(row=0, column=0, padx=(0, 8), sticky="w")

        combo = ttk.Combobox(
            activity_frame,
            textvariable=var,
            values=self.activities_list,
            width=90,
        )
        combo.grid(row=0, column=1, sticky="ew", padx=(0, 10))

        actions = ttk.Frame(activity_frame)
        actions.grid(row=0, column=2, sticky="e")

        up_btn = WidgetFactory.create_button(
            actions,
            text="▲",
            command=lambda v=var: self.move_activity(v, -1),
            style='Secondary.TButton',
            icon_key='',
        )
        up_btn.configure(width=4)
        up_btn.pack(side="left", padx=(0, 4))

        down_btn = WidgetFactory.create_button(
            actions,
            text="▼",
            command=lambda v=var: self.move_activity(v, 1),
            style='Secondary.TButton',
            icon_key='',
        )
        down_btn.configure(width=4)
        down_btn.pack(side="left", padx=(0, 4))

        remove_btn = WidgetFactory.create_button(
            actions,
            text="Retirer",
            command=lambda f=activity_frame, v=var: self.remove_activity(f, v),
            style='Secondary.TButton',
            icon_key='',
        )
        remove_btn.configure(width=10)
        remove_btn.pack(side="left")

        self._activity_rows.append(
            {
                "frame": activity_frame,
                "index_label": index_label,
                "var": var,
                "up_btn": up_btn,
                "down_btn": down_btn,
            }
        )
        self._refresh_activity_rows()

    def clear_all_activities(self):
        """Supprime toutes les activités puis recharge les 4 par défaut."""
        self._clear_activities(load_defaults=True)

    def clear_activities_completely(self):
        """Supprime toutes les activités sans remettre les valeurs par défaut."""
        self._clear_activities(load_defaults=False)

    def _clear_activities(self, load_defaults: bool = True):
        """Vide la table des activités."""
        for row_info in list(self._activity_rows):
            frame = row_info.get("frame")
            if frame is not None:
                frame.destroy()
        self._activity_rows = []
        self.activites_vars = []
        if load_defaults:
            self._load_default_activities()

    def _on_clear_activities_clicked(self):
        """Demande confirmation avant de réinitialiser le tableau des activités."""
        if not self.activites_vars:
            self.clear_all_activities()
            return
        confirmed = messagebox.askyesno(
            "Réinitialiser les activités",
            "Voulez-vous vider le tableau et remettre les 4 activités par défaut ?"
        )
        if confirmed:
            self.clear_all_activities()

    def _on_clear_all_activities_clicked(self):
        """Demande confirmation avant de vider complètement la table des activités."""
        if not self.activites_vars:
            self.clear_activities_completely()
            return
        confirmed = messagebox.askyesno(
            "Vider toutes les activités",
            "Voulez-vous supprimer toutes les activités ?\n\nCette action vide entièrement le tableau."
        )
        if confirmed:
            self.clear_activities_completely()

    def move_activity(self, var, direction: int):
        """Déplace une activité vers le haut ou le bas."""
        if direction == 0:
            return
        try:
            current_idx = next(i for i, row in enumerate(self._activity_rows) if row.get("var") is var)
        except StopIteration:
            return
        target_idx = current_idx + direction
        if target_idx < 0 or target_idx >= len(self._activity_rows):
            return

        self._activity_rows[current_idx], self._activity_rows[target_idx] = (
            self._activity_rows[target_idx],
            self._activity_rows[current_idx],
        )
        self.activites_vars[current_idx], self.activites_vars[target_idx] = (
            self.activites_vars[target_idx],
            self.activites_vars[current_idx],
        )
        self._refresh_activity_rows()

    def remove_activity(self, frame, var):
        """Supprime une activité"""
        if var in self.activites_vars:
            self.activites_vars.remove(var)
        self._activity_rows = [row for row in self._activity_rows if row.get("var") is not var]
        frame.destroy()
        self._refresh_activity_rows()

    def _on_activities_mousewheel(self, event):
        try:
            delta = int(getattr(event, "delta", 0))
        except Exception:
            delta = 0
        if delta == 0:
            return "break"
        steps = max(1, abs(delta) // 120)
        direction = -1 if delta > 0 else 1
        self.activities_canvas.yview_scroll(direction * steps, "units")
        return "break"

    def _on_activities_mousewheel_linux_up(self, _event):
        self.activities_canvas.yview_scroll(-1, "units")
        return "break"

    def _on_activities_mousewheel_linux_down(self, _event):
        self.activities_canvas.yview_scroll(1, "units")
        return "break"

    def _is_sarl_form(self) -> bool:
        return str(self.forme_jur_var.get() or "").strip().upper() == "SARL"

    def _update_mode_signature_visibility(self):
        if self._is_sarl_form():
            self.mode_signature_cell.grid()
        else:
            self.mode_signature_cell.grid_remove()

    def _on_forme_juridique_changed(self, _event=None):
        self._update_mode_signature_visibility()

    def _on_forme_juridique_var_changed(self, *_args):
        self._update_mode_signature_visibility()

    def get_values(self):
        """Récupère toutes les valeurs du formulaire"""
        self._apply_numeric_display_format()
        generation_type = self._normalize_generation_type(self.type_generation_var.get())
        creation_procedure = self._normalize_creation_procedure(self.procedure_creation_var.get())
        depot_mode = self._normalize_creation_depot_mode(self.mode_depot_creation_var.get())

        if generation_type != 'creation':
            creation_procedure = ''
            depot_mode = ''
        elif creation_procedure != 'acceleree':
            depot_mode = ''

        values = {
            'denomination': self.den_ste_var.get(),
            'forme_juridique': self.forme_jur_var.get(),
            'ice': self.ice_var.get(),
            'date_ice': self.date_ice_var.get(),
            'date_certificat_negatif': self.date_ice_var.get(),
            'date_expiration_certificat_negatif': self.date_expiration_certificat_negatif_var.get(),
            'capital': self.capital_var.get(),
            'parts_social': self.parts_social_var.get(),
            'valeur_nominale': self.valeur_nominale_var.get(),
            'adresse': self.ste_adress_var.get(),
            'tribunal': self.tribunal_var.get(),
            'type_generation': generation_type,
            'generation_type': generation_type,
            'procedure_creation': creation_procedure,
            'creation_procedure': creation_procedure,
            'mode_depot_creation': depot_mode,
            'creation_depot_mode': depot_mode,
            'activites': [var.get().strip() for var in self.activites_vars if var.get().strip()]
        }
        if self._is_sarl_form():
            values['mode_signature_gerance'] = self.mode_signature_gerance_var.get().strip()
        return values

    def set_values(self, values_dict):
        """Définit les valeurs du formulaire"""
        if not values_dict:
            self.reset()
            return

        # Mise à jour des champs simples
        self.den_ste_var.set(values_dict.get('denomination', ''))
        self.forme_jur_var.set(values_dict.get('forme_juridique', ''))
        self.ice_var.set(values_dict.get('ice', ''))
        self.date_ice_var.set(values_dict.get('date_ice', values_dict.get('date_certificat_negatif', '')))
        self.date_expiration_certificat_negatif_var.set(
            values_dict.get('date_expiration_certificat_negatif', values_dict.get('date_exp_certificat_negatif', ''))
        )
        self.capital_var.set(values_dict.get('capital', ''))
        self.valeur_nominale_var.set(values_dict.get('valeur_nominale', values_dict.get('valeur_nominal', '')))
        self.ste_adress_var.set(values_dict.get('adresse', ''))
        self.tribunal_var.set(values_dict.get('tribunal', ''))
        self.mode_signature_gerance_var.set(
            values_dict.get('mode_signature_gerance', values_dict.get('mode_signature', self.mode_signature_gerance_var.get()))
        )

        generation_type = values_dict.get(
            'type_generation',
            values_dict.get(
                'generation_type',
                values_dict.get('TYPE_GENERATION', self.type_generation_var.get())
            )
        )
        self.type_generation_var.set(self._normalize_generation_type(generation_type))

        creation_procedure = values_dict.get(
            'procedure_creation',
            values_dict.get(
                'creation_procedure',
                values_dict.get('PROCEDURE_CREATION', self.procedure_creation_var.get())
            )
        )
        self.procedure_creation_var.set(self._normalize_creation_procedure(creation_procedure))

        depot_mode = values_dict.get(
            'mode_depot_creation',
            values_dict.get(
                'creation_depot_mode',
                values_dict.get('MODE_DEPOT_CREATION', self.mode_depot_creation_var.get())
            )
        )
        self.mode_depot_creation_var.set(self._normalize_creation_depot_mode(depot_mode))

        self._update_mode_signature_visibility()
        self._update_generation_options_visibility()
        self._apply_numeric_display_format()
        self._update_parts_social()

        # Mise à jour des activités
        self._clear_activities(load_defaults=False)
        activites = values_dict.get('activites', [])
        if activites:
            for activite in activites:
                self.add_activity(initial_value=str(activite))
        else:
            self._load_default_activities()
