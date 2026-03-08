import tkinter as tk
from tkinter import ttk, messagebox
from tkcalendar import DateEntry
from typing import Optional, List
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

        # Variables pour stocker les valeurs des champs
        self.initialize_variables()

        # Création du formulaire
        self.setup_gui()

        # Définir les valeurs initiales si fournies
        if values_dict:
            self.set_values(values_dict)

    def initialize_variables(self):
        """Initialise toutes les variables du formulaire avec des valeurs par défaut raisonnables"""
        # Variables pour les champs de texte — valeurs par défaut prises depuis les constantes
        from ..utils.constants import DenSte, Formjur, Capital, PartsSocial, SteAdresse, Tribunnaux
        from ..utils.utils import get_reference_data
        from ..utils.defaults_manager import get_defaults_manager

        # Get defaults manager
        defaults_mgr = get_defaults_manager()
        
        # Get custom defaults or fall back to constants
        default_den_ste = defaults_mgr.get_default('societe', 'DenSte') or (DenSte[0] if DenSte else "")
        default_form_jur = defaults_mgr.get_default('societe', 'FormJur') or (Formjur[0] if Formjur else "")
        default_ice = defaults_mgr.get_default('societe', 'Ice') or ""
        default_capital = defaults_mgr.get_default('societe', 'Capital') or (Capital[0] if Capital else "")
        default_parts_social = defaults_mgr.get_default('societe', 'PartsSocial') or (PartsSocial[0] if PartsSocial else "")
        default_valeur_nominale = defaults_mgr.get_default('societe', 'ValeurNominale') or "100"
        default_ste_adresse = defaults_mgr.get_default('societe', 'SteAdresse') or (SteAdresse[0] if SteAdresse else "")
        default_tribunal = defaults_mgr.get_default('societe', 'Tribunal') or (Tribunnaux[0] if Tribunnaux else "")
        default_mode_signature = defaults_mgr.get_default('societe', 'ModeSignatureGerance') or "separee"

        self.den_ste_var = tk.StringVar(value=default_den_ste)
        self.forme_jur_var = tk.StringVar(value=default_form_jur)
        self.ice_var = tk.StringVar(value=default_ice)
        # default date to today's date in dd/mm/yyyy
        import datetime
        today = datetime.date.today().strftime('%d/%m/%Y')
        default_date_ice = defaults_mgr.get_default('societe', 'DateIce') or today
        default_date_expiration = defaults_mgr.get_default('societe', 'DateExpCertNeg') or ''
        self.date_ice_var = tk.StringVar(value=default_date_ice)
        self.date_expiration_certificat_negatif_var = tk.StringVar(value=default_date_expiration)
        self.capital_var = tk.StringVar(value=default_capital)
        self.parts_social_var = tk.StringVar(value=default_parts_social)
        self.valeur_nominale_var = tk.StringVar(value=default_valeur_nominale)
        self.mode_signature_gerance_var = tk.StringVar(value=default_mode_signature)

        # Load reference data from database
        self.ste_adresses = get_reference_data('SteAdresses')
        self.tribunaux = get_reference_data('Tribunaux')

        self.ste_adress_var = tk.StringVar(value=default_ste_adresse)
        self.tribunal_var = tk.StringVar(value=default_tribunal)

        # Liste pour stocker les variables des activités
        self.activites_vars = []
        self._activity_rows: List[dict] = []

    def setup_gui(self):
        """Configure une mise en page compacte sans sous-blocs internes."""
        main_frame = ttk.Frame(self, padding=(10, 5))
        main_frame.pack(fill="both", expand=True)

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
        ttk.Entry(capital_cell, textvariable=self.capital_var).grid(row=1, column=0, sticky="ew")

        parts_cell = _cell(1, 1, "Parts:")
        ttk.Entry(parts_cell, textvariable=self.parts_social_var).grid(row=1, column=0, sticky="ew")

        valeur_nominale_cell = _cell(1, 2, "Valeur nominale (DH):")
        ttk.Entry(valeur_nominale_cell, textvariable=self.valeur_nominale_var).grid(row=1, column=0, sticky="ew")

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

    def create_activities_section(self, parent):
        """Crée une section activités sous forme de tableau compact."""
        from ..utils.utils import get_reference_data
        from ..utils.constants import Activities

        section = ttk.Frame(parent)
        section.pack(fill="both", expand=True, padx=5, pady=(6, 2))

        header = ttk.Frame(section)
        header.pack(fill="x", pady=(0, 4))
        ttk.Label(header, text="Activités:", anchor="w").pack(side="left")
        ttk.Label(header, text="Tableau des activités (modifiable)", anchor="w").pack(side="left", padx=(10, 0))

        # Load activities from reference sheet
        self.activities_list = get_reference_data('Activites')
        if not self.activities_list:
            self.activities_list = list(Activities)
        self.default_activities = list(Activities[:4])

        # Tableau activités
        table = ttk.LabelFrame(section, text="Tableau des activités", padding=(8, 8))
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
            height=300,
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
        self.activities_container.columnconfigure(1, weight=1)

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
            text="🧹 Vider",
            command=self._on_clear_activities_clicked,
            style='Secondary.TButton',
        )
        clear_btn.pack(side="left", padx=(6, 0))

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
            'activites': [var.get().strip() for var in self.activites_vars if var.get().strip()]
        }
        if self._is_sarl_form():
            values['mode_signature_gerance'] = self.mode_signature_gerance_var.get().strip()
        return values

    def set_values(self, values_dict):
        """Définit les valeurs du formulaire"""
        if not values_dict:
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
        self.parts_social_var.set(values_dict.get('parts_social', ''))
        self.valeur_nominale_var.set(values_dict.get('valeur_nominale', values_dict.get('valeur_nominal', '')))
        self.ste_adress_var.set(values_dict.get('adresse', ''))
        self.tribunal_var.set(values_dict.get('tribunal', ''))
        self.mode_signature_gerance_var.set(
            values_dict.get('mode_signature_gerance', values_dict.get('mode_signature', self.mode_signature_gerance_var.get()))
        )
        self._update_mode_signature_visibility()

        # Mise à jour des activités
        self._clear_activities(load_defaults=False)
        activites = values_dict.get('activites', [])
        if activites:
            for activite in activites:
                self.add_activity(initial_value=str(activite))
        else:
            self._load_default_activities()
