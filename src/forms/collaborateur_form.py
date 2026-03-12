import tkinter as tk
from tkinter import ttk
from typing import Optional
from ..utils.utils import ThemeManager


class CollaborateurForm(ttk.Frame):
    def __init__(self, parent, theme_manager: Optional[ThemeManager] = None, values_dict=None):
        super().__init__(parent)
        self.parent = parent
        self.values = values_dict or {}

        # Backward compatibility: CollaborateurForm(parent, values_dict)
        if isinstance(theme_manager, dict) and values_dict is None:
            values_dict = theme_manager
            theme_manager = None
        self.values = values_dict or {}

        self.bind("<Destroy>", self._cleanup)

        self.theme_manager = theme_manager or ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        self.initialize_variables()
        self.setup_gui()

        if values_dict:
            self.set_values(values_dict)

    def initialize_variables(self):
        from ..utils.defaults_manager import get_defaults_manager

        defaults_mgr = get_defaults_manager()
        self.nom_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'Nom') or '')
        self.ice_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'Ice') or '')
        self.tp_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'Tp') or '')
        self.rc_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'Rc') or '')
        self.if_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'If') or '')
        self.tel_fixe_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'TelFixe') or '')
        self.tel_mobile_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'TelMobile') or '')
        self.adresse_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'Adresse') or '')
        self.email_var = tk.StringVar(value=defaults_mgr.get_default('collaborateur', 'Email') or '')

    def setup_gui(self):
        main_frame = ttk.Frame(self)
        main_frame.pack(fill="both", expand=True, padx=10, pady=6)

        fields = ttk.Frame(main_frame)
        fields.pack(fill="x")
        for col in range(6):
            fields.columnconfigure(col, weight=1, uniform="collab_cols")

        def _cell(row: int, col: int, label_text: str, span: int = 1, pad: int = 8) -> ttk.Frame:
            cell = ttk.Frame(fields)
            cell.grid(
                row=row,
                column=col,
                columnspan=span,
                sticky="ew",
                padx=(0, pad) if (col + span - 1) < 5 else (0, 0),
                pady=(0, 5),
            )
            cell.columnconfigure(0, weight=1)
            ttk.Label(cell, text=label_text + ':', anchor="w").grid(row=0, column=0, sticky="w")
            return cell

        # Ligne 1
        nom_cell = _cell(0, 0, "Nom / Raison sociale", span=2)
        ttk.Entry(nom_cell, textvariable=self.nom_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        ice_cell = _cell(0, 2, "ICE")
        ttk.Entry(ice_cell, textvariable=self.ice_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        tp_cell = _cell(0, 3, "TP")
        ttk.Entry(tp_cell, textvariable=self.tp_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        rc_cell = _cell(0, 4, "RC")
        ttk.Entry(rc_cell, textvariable=self.rc_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        if_cell = _cell(0, 5, "IF")
        ttk.Entry(if_cell, textvariable=self.if_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        # Ligne 2
        tel_fixe_cell = _cell(1, 0, "Téléphone fixe")
        ttk.Entry(tel_fixe_cell, textvariable=self.tel_fixe_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        tel_mobile_cell = _cell(1, 1, "Téléphone mobile")
        ttk.Entry(tel_mobile_cell, textvariable=self.tel_mobile_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        email_cell = _cell(1, 2, "Email", span=2)
        ttk.Entry(email_cell, textvariable=self.email_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

        adresse_cell = _cell(1, 4, "Adresse", span=2)
        ttk.Entry(adresse_cell, textvariable=self.adresse_var).grid(row=1, column=0, sticky="ew", pady=(2, 0))

    def get_values(self):
        return {
            'nom': self.nom_var.get(),
            'ice': self.ice_var.get(),
            'tp': self.tp_var.get(),
            'rc': self.rc_var.get(),
            'if': self.if_var.get(),
            'tel_fixe': self.tel_fixe_var.get(),
            'tel_mobile': self.tel_mobile_var.get(),
            'adresse': self.adresse_var.get(),
            'email': self.email_var.get(),
        }

    def set_values(self, values_dict):
        if not values_dict:
            self.initialize_variables()
            return
        values_dict = values_dict or {}
        try:
            self.nom_var.set(values_dict.get('nom', '') or '')
            self.ice_var.set(values_dict.get('ice', '') or '')
            self.tp_var.set(values_dict.get('tp', '') or '')
            self.rc_var.set(values_dict.get('rc', '') or '')
            self.if_var.set(values_dict.get('if', '') or '')
            self.tel_fixe_var.set(values_dict.get('tel_fixe', '') or '')
            self.tel_mobile_var.set(values_dict.get('tel_mobile', '') or '')
            self.adresse_var.set(values_dict.get('adresse', '') or '')
            self.email_var.set(values_dict.get('email', '') or '')
        except Exception:
            pass

    def _cleanup(self, _event):
        return
