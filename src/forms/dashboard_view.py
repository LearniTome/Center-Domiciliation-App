import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from datetime import datetime
from decimal import Decimal, InvalidOperation
from pathlib import Path
import logging
import inspect
from typing import Optional

import pandas as pd

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler
from ..utils import constants as _const
from ..utils.constants import societe_headers, associe_headers, contrat_headers, collaborateur_headers

logger = logging.getLogger(__name__)

DATE_COLUMNS = {
    'date_ice',
    'date_exp_cert_neg',
    'cin_validaty',
    'date_naiss',
    'date_contrat',
    'date_debut_contrat',
    'date_fin_contrat',
}

BOOLEAN_COLUMNS = {
    'is_gerant',
}

INTEGER_COLUMNS = {
    'capital',
    'part_social',
    'valeur_nominale',
    'part_percent',
    'parts',
    'capital_detenu',
    'duree_contrat_mois',
    'taux_tva_pourcent',
    'taux_tva_renouvellement_pourcent',
}

AMOUNT_COLUMNS = {
    'loyer_mensuel_ttc',
    'frais_intermediaire_contrat',
    'loyer_mensuel_ht',
    'montant_total_ht_contrat',
    'montant_pack_demarrage_ttc',
    'loyer_mensuel_pack_demarrage_ttc',
    'loyer_mensuel_ht_renouvellement',
    'montant_total_ht_renouvellement',
    'loyer_mensuel_renouvellement_ttc',
    'loyer_annuel_renouvellement_ttc',
}


class DashboardView(tk.Toplevel):
    """A compact, elegant dashboard modal for viewing and managing data."""

    def __init__(self, parent, action_handler=None, start_fullscreen: bool = False):
        super().__init__(parent)
        self.parent = parent
        self.action_handler = action_handler
        self.title("Tableau de Bord — Centre de Domiciliation")
        self.geometry("1100x700")
        self.minsize(800, 520)
        self._is_fullscreen = False
        # Keep dashboard non-modal to allow opening tools/settings without blocking.
        self._use_modal_parent_lock = False
        self._parent_disabled = False
        self._parent_withdrawn = False

        # Theme
        self.theme = ThemeManager(self.winfo_toplevel())
        self.style = self.theme.style

        # Initialize data variables FIRST (before building body)
        self._df = None
        self._societes_df = None
        self._associes_df = None
        self._contrats_df = None
        self._collaborateurs_df = None
        self._current_page = 'societe'
        self._search_var = tk.StringVar()
        self._column_filter_var = tk.StringVar()
        self._column_filter_column_var = tk.StringVar(value='(Toutes)')
        self._column_filter_display_to_name = {'(Toutes)': '(Toutes)'}
        self._column_filter_name_to_display = {'(Toutes)': '(Toutes)'}
        self._nav_buttons = {}
        self._action_buttons = {}
        self.empty_state_label: Optional[ttk.Label] = None
        self._sort_column_by_page = {'societe': None, 'associe': None, 'contrat': None, 'collaborateur': None}
        self._sort_desc_by_page = {'societe': False, 'associe': False, 'contrat': False, 'collaborateur': False}
        self._page_index_by_page = {'societe': 0, 'associe': 0, 'contrat': 0, 'collaborateur': 0}
        self._page_size_var = tk.IntVar(value=50)
        self._current_view_df = pd.DataFrame()
        self._toast_job = None

        # Layout
        self._build_header()
        self._build_body()
        self._build_status()

        # Load data (after all widgets are created)
        self._load_data()

        # Show first page with data
        self._show_page('societe')

        if start_fullscreen:
            self._enter_fullscreen()
        try:
            self.lift()
            self.focus_force()
        except Exception:
            pass

        # Fullscreen shortcuts
        self.bind('<F11>', self._toggle_fullscreen)
        self.bind('<Escape>', self._exit_fullscreen)

        # Start clock
        self._update_clock()

        # Cleanup on close
        self.protocol('WM_DELETE_WINDOW', lambda: self._on_close(call_parent=True))

    def _enter_fullscreen(self):
        """Maximize dashboard window."""
        try:
            self.state('zoomed')
            self._is_fullscreen = True
            return
        except Exception:
            pass
        try:
            self.attributes('-zoomed', True)
            self._is_fullscreen = True
        except Exception:
            self._is_fullscreen = False

    def _toggle_fullscreen(self, _event=None):
        if self._is_fullscreen:
            return self._exit_fullscreen()
        self._enter_fullscreen()
        return "break"

    def _exit_fullscreen(self, _event=None):
        try:
            self.state('normal')
        except Exception:
            try:
                self.attributes('-zoomed', False)
            except Exception:
                pass
        self._is_fullscreen = False
        return "break"

    @staticmethod
    def _column_label(column_name: str) -> str:
        labels = {
            'DEN_STE': 'Dénomination sociale',
            'FORME_JUR': 'Forme Juridique',
            'ICE': 'ICE',
            'DATE_ICE': 'Date Cert. Négatif',
            'CAPITAL': 'Capital',
            'PART_SOCIAL': 'Parts Sociales',
            'VALEUR_NOMINALE': 'Valeur Nominale',
            'DATE_EXP_CERT_NEG': 'Date Exp. Cert. Négatif',
            'STE_ADRESS': 'Adresse',
            'TRIBUNAL': 'Tribunal',
            'TYPE_GENERATION': 'Type Génération',
            'PROCEDURE_CREATION': 'Procédure Création',
            'MODE_DEPOT_CREATION': 'Mode Dépôt Création',
            'CIVIL': 'Civilité',
            'PRENOM': 'Prénom',
            'NOM': 'Nom',
            'NATIONALITY': 'Nationalité',
            'CIN_NUM': 'N° CIN',
            'CIN_VALIDATY': 'Validité CIN',
            'DATE_NAISS': 'Date Naiss.',
            'LIEU_NAISS': 'Lieu Naiss.',
            'ADRESSE': 'Adresse',
            'PHONE': 'Téléphone',
            'EMAIL': 'Email',
            'PART_PERCENT': '% Parts',
            'PARTS': 'Nb Parts',
            'CAPITAL_DETENU': 'Capital Détenu',
            'IS_GERANT': 'Est Gérant',
            'QUALITY': 'Qualité',
            'DATE_CONTRAT': 'Date Contrat',
            'DUREE_CONTRAT_MOIS': 'Durée (mois)',
            'TYPE_CONTRAT_DOMICILIATION': 'Type Contrat',
            'TYPE_CONTRAT_DOMICILIATION_AUTRE': 'Type Autre',
            'LOYER_MENSUEL_TTC': 'Loyer Mensuel TTC',
            'FRAIS_INTERMEDIAIRE_CONTRAT': 'Frais Intermédiaire',
            'DATE_DEBUT_CONTRAT': 'Date Début',
            'DATE_FIN_CONTRAT': 'Date Fin',
            'TAUX_TVA_POURCENT': 'TVA %',
            'LOYER_MENSUEL_HT': 'Loyer Mensuel HT',
            'MONTANT_TOTAL_HT_CONTRAT': 'Montant Total HT',
            'MONTANT_PACK_DEMARRAGE_TTC': 'Pack Démarrage TTC',
            'LOYER_MENSUEL_PACK_DEMARRAGE_TTC': 'Loyer Pack TTC',
            'TYPE_RENOUVELLEMENT': 'Renouvellement',
            'TAUX_TVA_RENOUVELLEMENT_POURCENT': 'TVA Renouv. %',
            'LOYER_MENSUEL_HT_RENOUVELLEMENT': 'Loyer Renouv. HT',
            'MONTANT_TOTAL_HT_RENOUVELLEMENT': 'Montant Renouv. HT',
            'LOYER_MENSUEL_RENOUVELLEMENT_TTC': 'Loyer Renouv. TTC',
            'LOYER_ANNUEL_RENOUVELLEMENT_TTC': 'Loyer Annuel Renouv.',
            'COLLABORATEUR_TYPE': 'Type collaborateur',
            'COLLABORATEUR_CODE': 'Code collaborateur',
            'COLLABORATEUR_NOM': 'Nom / Raison sociale',
            'COLLABORATEUR_ICE': 'ICE',
            'COLLABORATEUR_TP': 'TP',
            'COLLABORATEUR_RC': 'RC',
            'COLLABORATEUR_IF': 'IF',
            'COLLABORATEUR_TEL_FIXE': 'Tél. Fixe',
            'COLLABORATEUR_TEL_MOBILE': 'Tél. Mobile',
            'COLLABORATEUR_ADRESSE': 'Adresse',
            'COLLABORATEUR_EMAIL': 'Email',
        }
        key = str(column_name or '')
        return labels.get(key.upper(), labels.get(key, column_name))

    @staticmethod
    def _column_width(column_name: str) -> int:
        widths = {
            'DEN_STE': 190,
            'FORME_JUR': 120,
            'ICE': 120,
            'DATE_ICE': 140,
            'CAPITAL': 110,
            'PART_SOCIAL': 110,
            'VALEUR_NOMINALE': 120,
            'DATE_EXP_CERT_NEG': 150,
            'STE_ADRESS': 280,
            'TRIBUNAL': 120,
            'TYPE_GENERATION': 140,
            'PROCEDURE_CREATION': 150,
            'MODE_DEPOT_CREATION': 170,
            'CIVIL': 100,
            'PRENOM': 130,
            'NOM': 140,
            'NATIONALITY': 130,
            'CIN_NUM': 130,
            'CIN_VALIDATY': 130,
            'DATE_NAISS': 130,
            'LIEU_NAISS': 140,
            'ADRESSE': 220,
            'PHONE': 120,
            'EMAIL': 200,
            'PART_PERCENT': 100,
            'PARTS': 90,
            'CAPITAL_DETENU': 120,
            'IS_GERANT': 90,
            'QUALITY': 140,
            'DATE_CONTRAT': 130,
            'DUREE_CONTRAT_MOIS': 110,
            'TYPE_CONTRAT_DOMICILIATION': 150,
            'TYPE_CONTRAT_DOMICILIATION_AUTRE': 140,
            'LOYER_MENSUEL_TTC': 140,
            'FRAIS_INTERMEDIAIRE_CONTRAT': 150,
            'DATE_DEBUT_CONTRAT': 130,
            'DATE_FIN_CONTRAT': 130,
            'TAUX_TVA_POURCENT': 90,
            'LOYER_MENSUEL_HT': 140,
            'MONTANT_TOTAL_HT_CONTRAT': 145,
            'MONTANT_PACK_DEMARRAGE_TTC': 150,
            'LOYER_MENSUEL_PACK_DEMARRAGE_TTC': 145,
            'TYPE_RENOUVELLEMENT': 130,
            'TAUX_TVA_RENOUVELLEMENT_POURCENT': 120,
            'LOYER_MENSUEL_HT_RENOUVELLEMENT': 145,
            'MONTANT_TOTAL_HT_RENOUVELLEMENT': 150,
            'LOYER_MENSUEL_RENOUVELLEMENT_TTC': 150,
            'LOYER_ANNUEL_RENOUVELLEMENT_TTC': 160,
            'COLLABORATEUR_TYPE': 180,
            'COLLABORATEUR_CODE': 140,
            'COLLABORATEUR_NOM': 200,
            'COLLABORATEUR_ICE': 140,
            'COLLABORATEUR_TP': 120,
            'COLLABORATEUR_RC': 120,
            'COLLABORATEUR_IF': 120,
            'COLLABORATEUR_TEL_FIXE': 130,
            'COLLABORATEUR_TEL_MOBILE': 140,
            'COLLABORATEUR_ADRESSE': 260,
            'COLLABORATEUR_EMAIL': 200,
        }
        key = str(column_name or '')
        return widths.get(key.upper(), widths.get(key, 120))

    @staticmethod
    def _format_date_value(value) -> str:
        if value in (None, ''):
            return ''
        text = str(value).strip()
        if not text or text.lower() == 'nan':
            return ''
        if len(text) == 10 and text[2] == '/' and text[5] == '/':
            return text

        parsed = None
        for fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%d', '%d/%m/%Y %H:%M:%S', '%d/%m/%Y'):
            try:
                parsed = datetime.strptime(text, fmt)
                break
            except Exception:
                continue

        if parsed is None:
            parsed = pd.to_datetime(text, errors='coerce')
        if pd.isna(parsed):
            return text
        return parsed.strftime('%d/%m/%Y')

    @staticmethod
    def _format_bool_value(value) -> str:
        if value in (None, ''):
            return ''
        text = str(value).strip()
        if not text:
            return ''

        lowered = text.lower()
        if lowered in {'1', 'true', 'vrai', 'yes', 'oui'}:
            return 'Oui'
        if lowered in {'0', 'false', 'faux', 'no', 'non'}:
            return 'Non'
        return text

    @staticmethod
    def _format_number_value(value, min_decimals: int = 0) -> str:
        if value in (None, ''):
            return ''

        text = str(value).strip()
        if not text or text.lower() == 'nan':
            return ''

        normalized = text.replace(' ', '')
        if ',' in normalized and '.' in normalized:
            normalized = normalized.replace(',', '')
        elif ',' in normalized:
            normalized = normalized.replace(',', '.')

        try:
            number = Decimal(normalized)
        except (InvalidOperation, ValueError):
            return text

        if min_decimals == 0 and number == number.to_integral():
            integer_part = f"{int(number):,}".replace(',', ' ')
            return integer_part

        decimal_part = normalized.split('.', 1)[1] if '.' in normalized else ''
        trimmed_places = len(decimal_part.rstrip('0'))
        decimal_places = max(min_decimals, min(max(trimmed_places, min_decimals or 1), 4))
        quantized = f"{number:,.{decimal_places}f}"
        integer_part, fractional_part = quantized.split('.')
        integer_part = integer_part.replace(',', ' ')
        if decimal_places > min_decimals:
            fractional_part = fractional_part.rstrip('0')
        if not fractional_part:
            return integer_part
        return f"{integer_part},{fractional_part}"

    def _format_display_value(self, column_name: str, value) -> str:
        key = str(column_name or '').lower()
        if key in DATE_COLUMNS:
            return self._format_date_value(value)
        if key in BOOLEAN_COLUMNS:
            return self._format_bool_value(value)
        if key in AMOUNT_COLUMNS:
            return self._format_number_value(value, min_decimals=2)
        if key in INTEGER_COLUMNS:
            return self._format_number_value(value)
        if value in (None, ''):
            return ''
        text = str(value).strip()
        return '' if text.lower() == 'nan' else text

    def _build_header(self):
        """Build header with title, date and clock"""
        header = ttk.Frame(self, padding=(12, 10))
        header.pack(fill='x')

        left = ttk.Frame(header)
        left.pack(side='left', fill='x', expand=True)

        title = ttk.Label(left, text='Centre de Domiciliation — Tableau de Bord', font=('Segoe UI', 12, 'bold'))
        title.pack(side='left')

        nav_row = ttk.Frame(left)
        nav_row.pack(side='left', padx=(18, 0))

        self._nav_buttons['societe'] = WidgetFactory.create_button(
            nav_row, text="🏢 Sociétés", command=lambda: self._show_page('societe'), style='DashboardTab.TButton'
        )
        self._nav_buttons['societe'].pack(side='left', padx=(0, 8))
        self._nav_buttons['associe'] = WidgetFactory.create_button(
            nav_row, text="👥 Associés", command=lambda: self._show_page('associe'), style='DashboardTab.TButton'
        )
        self._nav_buttons['associe'].pack(side='left', padx=(0, 8))
        self._nav_buttons['contrat'] = WidgetFactory.create_button(
            nav_row, text="📄 Contrats", command=lambda: self._show_page('contrat'), style='DashboardTab.TButton'
        )
        self._nav_buttons['contrat'].pack(side='left', padx=(0, 8))
        self._nav_buttons['collaborateur'] = WidgetFactory.create_button(
            nav_row, text="🤝 Collaborateurs", command=lambda: self._show_page('collaborateur'), style='DashboardTab.TButton'
        )
        self._nav_buttons['collaborateur'].pack(side='left')

        tools_cmd = self._open_tools
        self.tools_button = WidgetFactory.create_button(
            nav_row, text='🧰 Outils', command=tools_cmd, style='Secondary.TButton'
        )
        self.tools_button.pack(side='left', padx=(16, 0))

        right = ttk.Frame(header)
        right.pack(side='right')

        self.date_label = ttk.Label(right, text='', font=('Segoe UI', 10))
        self.date_label.pack(anchor='e')

        self.clock_label = ttk.Label(right, text='', font=('Segoe UI', 13, 'bold'))
        self.clock_label.pack(anchor='e')


    def _build_body(self):
        """Build main body with navigation and content"""
        body = ttk.Frame(self)
        body.pack(fill='both', expand=True, padx=10, pady=(2, 8))

        self.content = ttk.Frame(body)
        self.content.pack(fill='both', expand=True)

        # Search bar (global to current page)
        search_frame = ttk.Frame(self.content)
        search_frame.pack(fill='x', padx=5, pady=(0, 10))
        for col in range(8):
            search_frame.grid_columnconfigure(col, weight=0)
        search_frame.grid_columnconfigure(1, weight=1)

        ttk.Label(search_frame, text='Recherche:').grid(row=0, column=0, sticky='w', padx=(0, 8))
        search_entry = ttk.Entry(search_frame, textvariable=self._search_var)
        search_entry.grid(row=0, column=1, sticky='ew', padx=(0, 10))
        ttk.Label(search_frame, text='Colonne:').grid(row=0, column=2, sticky='w', padx=(0, 6))
        self.column_filter_combo = ttk.Combobox(
            search_frame,
            textvariable=self._column_filter_column_var,
            state='readonly',
            width=18
        )
        self.column_filter_combo.grid(row=0, column=3, sticky='w', padx=(0, 10))
        self.column_filter_combo.bind('<<ComboboxSelected>>', self._on_column_filter_column_changed)
        ttk.Label(search_frame, text='Valeur:').grid(row=0, column=4, sticky='w', padx=(0, 6))
        column_filter_entry = ttk.Entry(search_frame, textvariable=self._column_filter_var, width=24)
        column_filter_entry.grid(row=0, column=5, sticky='w', padx=(0, 10))
        WidgetFactory.create_button(
            search_frame,
            text='Effacer',
            command=self._clear_filters,
            style='Secondary.TButton'
        ).grid(row=0, column=6, sticky='e', padx=(0, 8))
        WidgetFactory.create_button(
            search_frame,
            text='Exporter CSV',
            command=self._export_current_view_csv,
            style='Manage.TButton'
        ).grid(row=0, column=7, sticky='e')
        self._search_var.trace_add('write', self._on_search_changed)
        self._column_filter_var.trace_add('write', self._on_column_filter_changed)

        ttk.Separator(self.content, orient='horizontal').pack(fill='x', padx=5, pady=(0, 10))

        self.page_container = ttk.Frame(self.content)
        self.page_container.pack(fill='both', expand=True)

        self.empty_state_label = ttk.Label(
            self.page_container,
            text='Aucune donnée à afficher',
            font=('Segoe UI', 11, 'italic')
        )

        self.pages = {}
        self.trees = {}

        # Create tables for each page
        associe_cols = [c for c in associe_headers if not str(c).lower().startswith('id_')]
        if 'den_ste' not in associe_cols:
            associe_cols = ['den_ste'] + associe_cols
        contrat_cols = [c for c in contrat_headers if not str(c).lower().startswith('id_')]
        if 'den_ste' not in contrat_cols:
            contrat_cols = ['den_ste'] + contrat_cols

        for page_key, page_title, columns in [
            ('societe', 'Sociétés', [c for c in societe_headers if not str(c).lower().startswith('id_')]),
            ('associe', 'Associés', associe_cols),
            ('contrat', 'Contrats', contrat_cols),
            ('collaborateur', 'Collaborateurs', [c for c in collaborateur_headers if not str(c).lower().startswith('id_')]),
        ]:
            page = ttk.Frame(self.page_container)
            page.pack_forget()
            self.pages[page_key] = page

            # Title
            ttk.Label(page, text=page_title, font=('Segoe UI', 10, 'bold')).pack(anchor='w', padx=5, pady=(0, 8))

            # Table frame
            table_frame = ttk.Frame(page)
            table_frame.pack(fill='both', expand=True, padx=5, pady=(0, 6))

            # Create Treeview
            tree = ttk.Treeview(table_frame, columns=columns, show='headings', height=15)
            for col in columns:
                tree.heading(
                    col,
                    text=self._column_label(col),
                    command=lambda c=col, p=page_key: self._on_sort_column(p, c),
                )
                tree.column(col, width=self._column_width(col), minwidth=70)

            # Scrollbars
            y_scroll = ttk.Scrollbar(table_frame, orient='vertical', command=tree.yview)
            x_scroll = ttk.Scrollbar(table_frame, orient='horizontal', command=tree.xview)
            tree.configure(yscrollcommand=y_scroll.set, xscrollcommand=x_scroll.set)
            tree.bind('<<TreeviewSelect>>', self._on_tree_selection_changed)

            y_scroll.pack(side='right', fill='y')
            x_scroll.pack(side='bottom', fill='x')
            tree.pack(fill='both', expand=True)

            self.trees[page_key] = tree

    def _build_status(self):
        """Build status bar"""
        pagination = ttk.Frame(self)
        pagination.pack(fill='x', side='bottom', pady=(6, 0))

        left_controls = ttk.Frame(pagination)
        left_controls.pack(side='left', padx=(8, 0), pady=(2, 4))

        center_actions = ttk.Frame(pagination)
        center_actions.pack(side='left', expand=True, pady=(2, 4))

        right_nav = ttk.Frame(pagination)
        right_nav.pack(side='right', padx=(0, 8), pady=(2, 4))

        ttk.Label(left_controls, text='Lignes/page:').pack(side='left', padx=(0, 4))
        page_size = ttk.Combobox(
            left_controls,
            textvariable=self._page_size_var,
            values=(10, 25, 50, 100),
            state='readonly',
            width=6
        )
        page_size.pack(side='left')
        page_size.bind('<<ComboboxSelected>>', self._on_page_size_changed)

        ttk.Label(center_actions, text='Actions:').pack(side='left', padx=(0, 8))
        self._action_buttons['add'] = WidgetFactory.create_button(
            center_actions, text="➕ Ajouter", command=lambda: self._action('add'), style='Success.TButton'
        )
        self._action_buttons['add'].pack(side='left', padx=(0, 6), pady=2)
        self._action_buttons['edit'] = WidgetFactory.create_button(
            center_actions, text="✏️ Modifier", command=lambda: self._action('edit'), style='Manage.TButton'
        )
        self._action_buttons['edit'].pack(side='left', padx=(0, 6), pady=2)
        self._action_buttons['delete'] = WidgetFactory.create_button(
            center_actions, text="🗑️ Supprimer", command=lambda: self._action('delete'), style='Cancel.TButton'
        )
        self._action_buttons['delete'].pack(side='left', padx=(0, 6), pady=2)
        self._action_buttons['refresh'] = WidgetFactory.create_button(
            center_actions, text="🔄 Actualiser", command=lambda: self._action('refresh'), style='Manage.TButton'
        )
        self._action_buttons['refresh'].pack(side='left', pady=2)

        self.prev_page_btn = WidgetFactory.create_button(
            right_nav, text='◀ Précédent', command=self._go_prev_page, style='Secondary.TButton'
        )
        self.next_page_btn = WidgetFactory.create_button(
            right_nav, text='Suivant ▶', command=self._go_next_page, style='Secondary.TButton'
        )
        self.quit_button = WidgetFactory.create_button(
            right_nav, text='❌ Quitter', command=self._quit_app, style='Cancel.TButton'
        )
        self.quit_button.pack(side='right', padx=(4, 0))
        self.next_page_btn.pack(side='right', padx=4)
        self.prev_page_btn.pack(side='right', padx=(4, 0))
        self.page_label = ttk.Label(right_nav, text='Page 1/1')
        self.page_label.pack(side='right', padx=8)

        self.status_label = ttk.Label(self, text='Prêt', relief=tk.SUNKEN)
        self.status_label.pack(fill='x', side='bottom')

    def _load_data(self):
        """Load data from database (all three sheets)"""
        try:
            def _normalize_contrats_df(df: pd.DataFrame) -> pd.DataFrame:
                if df is None:
                    return pd.DataFrame(columns=_const.contrat_headers)
                out = df.copy()
                aliases = getattr(_const, 'contrat_header_aliases', {}) or {}
                for old_col, new_col in aliases.items():
                    if old_col not in out.columns:
                        continue
                    if new_col not in out.columns:
                        out[new_col] = out[old_col]
                    else:
                        try:
                            old_vals = out[old_col].fillna('').astype(str).str.strip()
                            new_vals = out[new_col].fillna('').astype(str).str.strip()
                            mask = (new_vals == '') & (old_vals != '')
                            out.loc[mask, new_col] = out.loc[mask, old_col]
                        except Exception:
                            pass
                    try:
                        out.drop(columns=[old_col], inplace=True)
                    except Exception:
                        pass
                return out

            def _apply_aliases(df: pd.DataFrame, alias_map: dict) -> pd.DataFrame:
                if df is None or df.empty or not alias_map:
                    return df
                out = df.copy()
                for old_col, new_col in alias_map.items():
                    if old_col not in out.columns:
                        continue
                    if new_col not in out.columns:
                        out[new_col] = out[old_col]
                    else:
                        try:
                            old_vals = out[old_col].fillna('').astype(str).str.strip()
                            new_vals = out[new_col].fillna('').astype(str).str.strip()
                            mask = (new_vals == '') & (old_vals != '')
                            out.loc[mask, new_col] = out.loc[mask, old_col]
                        except Exception:
                            pass
                    try:
                        out.drop(columns=[old_col], inplace=True)
                    except Exception:
                        pass
                return out

            def _inject_denomination(df: pd.DataFrame, denom_map: dict) -> pd.DataFrame:
                if df is None:
                    return pd.DataFrame()
                out = df.copy()
                if 'den_ste' not in out.columns:
                    out['den_ste'] = ''
                if denom_map and 'id_societe' in out.columns:
                    mapped = (
                        out['id_societe']
                        .fillna('')
                        .astype(str)
                        .str.strip()
                        .map(denom_map)
                        .fillna('')
                    )
                    try:
                        existing = out['den_ste'].fillna('').astype(str).str.strip()
                        out.loc[existing == '', 'den_ste'] = mapped
                    except Exception:
                        out['den_ste'] = mapped
                return out

            PathManager.ensure_directories()
            excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

            if excel_path.exists():
                try:
                    # Load all three main sheets
                    self._societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
                    self._associes_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
                    self._contrats_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
                    self._societes_df = _apply_aliases(
                        self._societes_df,
                        getattr(_const, 'societe_header_aliases', {}) or {},
                    )
                    self._associes_df = _apply_aliases(
                        self._associes_df,
                        getattr(_const, 'associe_header_aliases', {}) or {},
                    )
                    self._contrats_df = _normalize_contrats_df(self._contrats_df)
                    self._contrats_df = _apply_aliases(
                        self._contrats_df,
                        getattr(_const, 'contrat_header_aliases', {}) or {},
                    ).reindex(columns=_const.contrat_headers, fill_value='')
                    denom_map = {}
                    try:
                        if 'id_societe' in self._societes_df.columns and 'den_ste' in self._societes_df.columns:
                            soc_ids = self._societes_df['id_societe'].fillna('').astype(str).str.strip()
                            soc_names = self._societes_df['den_ste'].fillna('').astype(str).str.strip()
                            denom_map = {
                                sid: den for sid, den in zip(soc_ids, soc_names) if sid and den
                            }
                    except Exception:
                        denom_map = {}
                    self._associes_df = _inject_denomination(self._associes_df, denom_map)
                    self._contrats_df = _inject_denomination(self._contrats_df, denom_map)
                    try:
                        self._collaborateurs_df = pd.read_excel(excel_path, sheet_name='Collaborateurs', dtype=str).fillna('')
                    except Exception:
                        self._collaborateurs_df = pd.DataFrame(columns=_const.collaborateur_headers)
                    self._collaborateurs_df = _apply_aliases(
                        self._collaborateurs_df,
                        getattr(_const, 'collaborateur_header_aliases', {}) or {},
                    )
                    # Initialize with societes data
                    self._df = self._societes_df
                except Exception as e:
                    logger.warning(f"Error loading sheets: {e}")
                    self._societes_df = pd.DataFrame(columns=_const.societe_headers)
                    self._associes_df = pd.DataFrame(columns=_const.associe_headers)
                    self._contrats_df = pd.DataFrame(columns=_const.contrat_headers)
                    self._collaborateurs_df = pd.DataFrame(columns=_const.collaborateur_headers)
                    self._df = self._societes_df
            else:
                self._societes_df = pd.DataFrame(columns=_const.societe_headers)
                self._associes_df = pd.DataFrame(columns=_const.associe_headers)
                self._contrats_df = pd.DataFrame(columns=_const.contrat_headers)
                self._collaborateurs_df = pd.DataFrame(columns=_const.collaborateur_headers)
                self._df = self._societes_df

            try:
                denom_map = {}
                if hasattr(self, '_societes_df') and isinstance(self._societes_df, pd.DataFrame):
                    if 'id_societe' in self._societes_df.columns and 'den_ste' in self._societes_df.columns:
                        soc_ids = self._societes_df['id_societe'].fillna('').astype(str).str.strip()
                        soc_names = self._societes_df['den_ste'].fillna('').astype(str).str.strip()
                        denom_map = {sid: den for sid, den in zip(soc_ids, soc_names) if sid and den}
                if hasattr(self, '_associes_df'):
                    self._associes_df = _inject_denomination(self._associes_df, denom_map)
                if hasattr(self, '_contrats_df'):
                    self._contrats_df = _inject_denomination(self._contrats_df, denom_map)
            except Exception:
                pass

            self._refresh_display()
        except Exception as e:
            logger.error(f"Error loading data: {e}")
            self._societes_df = pd.DataFrame()
            self._associes_df = pd.DataFrame()
            self._contrats_df = pd.DataFrame()
            self._collaborateurs_df = pd.DataFrame()
            self._df = pd.DataFrame()

    def _show_page(self, page_key: str):
        """Show a specific page and load corresponding data"""
        self._current_page = page_key

        # Switch to the appropriate DataFrame based on page
        if page_key == 'societe':
            self._df = self._societes_df
        elif page_key == 'associe':
            self._df = self._associes_df
        elif page_key == 'contrat':
            self._df = self._contrats_df
        elif page_key == 'collaborateur':
            self._df = self._collaborateurs_df

        # Show/hide pages
        for key, page in self.pages.items():
            if key == page_key:
                page.pack(fill='both', expand=True)
            else:
                page.pack_forget()

        self._update_nav_state()
        self._update_sort_headers()
        self._update_filter_controls()
        self._refresh_display()
        self._update_action_buttons_state()

    def _refresh_display(self):
        """Refresh the displayed data"""
        # Clear current trees
        for tree in self.trees.values():
            for item in tree.get_children():
                tree.delete(item)

        base_df = self._df if self._df is not None else pd.DataFrame()
        filtered_df = self._apply_search_filter(base_df)
        filtered_df = self._apply_column_filter(filtered_df)
        sorted_df = self._apply_sort(filtered_df)
        page_df, page_idx, total_pages = self._apply_pagination(sorted_df)
        self._current_view_df = page_df

        # Populate current tree
        tree = self.trees.get(self._current_page)
        if tree is not None:
            # Get column names from tree (these are display columns without ID_*)
            columns = list(tree["columns"])
            logger.debug(f"Tree columns for '{self._current_page}': {columns}")
            logger.debug(f"DataFrame columns: {list(base_df.columns)}")

            # Populate tree with data
            for idx, (_, row) in enumerate(page_df.iterrows()):
                values = []
                for col in columns:
                    # Get value from row, handling missing columns gracefully
                    if col in page_df.columns:
                        val = row.get(col, '')
                    else:
                        logger.warning(f"Column '{col}' not found in DataFrame")
                        val = ''
                    values.append(self._format_display_value(col, val))

                logger.debug(f"Row {idx} values: {values}")
                tree.insert('', 'end', values=values)

        self._update_empty_state(base_df, sorted_df)
        self._update_pagination_controls(page_idx, total_pages)
        self._update_action_buttons_state()

        if base_df.empty:
            self.status_label.config(text='Aucune donnée')
        elif len(sorted_df) != len(base_df):
            self.status_label.config(
                text=f'Affichage: {len(sorted_df)} / {len(base_df)} enregistrements'
            )
        else:
            self.status_label.config(text=f'Total: {len(base_df)} enregistrements')

    def _update_nav_state(self):
        """Highlight currently selected section in left navigation."""
        for key, button in self._nav_buttons.items():
            try:
                button.configure(style='DashboardTabActive.TButton' if key == self._current_page else 'DashboardTab.TButton')
            except Exception:
                pass

    def _on_search_changed(self, *_args):
        """Refresh table instantly when search text changes."""
        self._page_index_by_page[self._current_page] = 0
        self._refresh_display()

    def _on_column_filter_changed(self, *_args):
        self._page_index_by_page[self._current_page] = 0
        self._refresh_display()

    def _on_column_filter_column_changed(self, _event=None):
        self._page_index_by_page[self._current_page] = 0
        self._refresh_display()

    def _clear_filters(self):
        self._search_var.set('')
        self._column_filter_var.set('')
        self._column_filter_column_var.set('(Toutes)')
        self._page_index_by_page[self._current_page] = 0
        self._refresh_display()

    def _apply_search_filter(self, df: pd.DataFrame) -> pd.DataFrame:
        """Filter current table by any visible value containing the search text."""
        if df is None or df.empty:
            return pd.DataFrame(columns=(list(df.columns) if df is not None else []))

        query = (self._search_var.get() or '').strip().lower()
        if not query:
            return df

        try:
            mask = df.astype(str).apply(
                lambda row: row.str.lower().str.contains(query, regex=False, na=False).any(),
                axis=1
            )
            return df[mask]
        except Exception:
            logger.exception("Search filtering failed; falling back to unfiltered data")
            return df

    def _apply_column_filter(self, df: pd.DataFrame) -> pd.DataFrame:
        """Apply a targeted filter on one column (or all columns)."""
        if df is None or df.empty:
            return df

        query = (self._column_filter_var.get() or '').strip().lower()
        if not query:
            return df

        selected_display = self._column_filter_column_var.get() or '(Toutes)'
        selected_column = self._column_filter_display_to_name.get(selected_display, selected_display)
        try:
            if selected_column == '(Toutes)':
                mask = df.astype(str).apply(
                    lambda row: row.str.lower().str.contains(query, regex=False, na=False).any(),
                    axis=1
                )
                return df[mask]

            if selected_column not in df.columns:
                return df

            mask = df[selected_column].astype(str).str.lower().str.contains(query, regex=False, na=False)
            return df[mask]
        except Exception:
            logger.exception("Column filtering failed; falling back to unfiltered data")
            return df

    def _update_filter_controls(self):
        """Update column selector values when switching page."""
        tree = self.trees.get(self._current_page)
        if tree is None:
            return

        columns = list(tree["columns"])
        display_to_name = {'(Toutes)': '(Toutes)'}
        name_to_display = {'(Toutes)': '(Toutes)'}
        used_labels = {'(Toutes)'}

        for col in columns:
            base_label = self._column_label(col)
            display_label = base_label
            if display_label in used_labels:
                display_label = f"{base_label} ({col})"
            used_labels.add(display_label)
            display_to_name[display_label] = col
            name_to_display[col] = display_label

        self._column_filter_display_to_name = display_to_name
        self._column_filter_name_to_display = name_to_display
        values = list(display_to_name.keys())
        try:
            self.column_filter_combo.configure(values=values)
        except Exception:
            return

        current = self._column_filter_column_var.get()
        if current not in values:
            self._column_filter_column_var.set('(Toutes)')

    def _on_sort_column(self, page_key: str, column: str):
        """Toggle sort order when clicking a column header."""
        current_col = self._sort_column_by_page.get(page_key)
        if current_col == column:
            self._sort_desc_by_page[page_key] = not self._sort_desc_by_page.get(page_key, False)
        else:
            self._sort_column_by_page[page_key] = column
            self._sort_desc_by_page[page_key] = False

        self._page_index_by_page[page_key] = 0
        if self._current_page == page_key:
            self._update_sort_headers()
            self._refresh_display()

    def _apply_sort(self, df: pd.DataFrame) -> pd.DataFrame:
        """Apply current sort configuration for the active page."""
        if df is None or df.empty:
            return df

        sort_col = self._sort_column_by_page.get(self._current_page)
        if not sort_col or sort_col not in df.columns:
            return df

        descending = self._sort_desc_by_page.get(self._current_page, False)
        try:
            return df.sort_values(by=sort_col, ascending=not descending, kind='mergesort')
        except Exception:
            logger.exception("Sorting failed for column '%s'", sort_col)
            return df

    def _update_sort_headers(self):
        """Show visual sort indicator on the active page column headers."""
        tree = self.trees.get(self._current_page)
        if tree is None:
            return

        active_col = self._sort_column_by_page.get(self._current_page)
        descending = self._sort_desc_by_page.get(self._current_page, False)
        for col in tree["columns"]:
            header = self._column_label(col)
            if col == active_col:
                header = f"{header} {'▼' if descending else '▲'}"
            tree.heading(col, text=header, command=lambda c=col, p=self._current_page: self._on_sort_column(p, c))

    def _apply_pagination(self, df: pd.DataFrame):
        """Return page slice and pagination metadata for current page."""
        if df is None:
            df = pd.DataFrame()

        page_size = max(1, int(self._page_size_var.get() or 50))
        total_rows = len(df)
        total_pages = max(1, (total_rows + page_size - 1) // page_size)

        current_idx = self._page_index_by_page.get(self._current_page, 0)
        if current_idx >= total_pages:
            current_idx = total_pages - 1
        if current_idx < 0:
            current_idx = 0
        self._page_index_by_page[self._current_page] = current_idx

        start = current_idx * page_size
        end = start + page_size
        return df.iloc[start:end], current_idx, total_pages

    def _update_pagination_controls(self, current_idx: int, total_pages: int):
        """Refresh pagination label and prev/next button states."""
        try:
            self.page_label.config(text=f'Page {current_idx + 1}/{total_pages}')
            self.prev_page_btn.configure(state='normal' if current_idx > 0 else 'disabled')
            self.next_page_btn.configure(state='normal' if current_idx < (total_pages - 1) else 'disabled')
        except Exception:
            pass

    def _go_prev_page(self):
        idx = self._page_index_by_page.get(self._current_page, 0)
        if idx > 0:
            self._page_index_by_page[self._current_page] = idx - 1
            self._refresh_display()

    def _go_next_page(self):
        idx = self._page_index_by_page.get(self._current_page, 0)
        self._page_index_by_page[self._current_page] = idx + 1
        self._refresh_display()

    def _on_page_size_changed(self, _event=None):
        self._page_index_by_page[self._current_page] = 0
        self._refresh_display()

    def _update_empty_state(self, base_df: pd.DataFrame, filtered_df: pd.DataFrame):
        """Show contextual empty message when there is no row to display."""
        if self.empty_state_label is None:
            return

        if base_df.empty:
            self.empty_state_label.config(text='Aucune donnée disponible')
            self.empty_state_label.pack(pady=20)
        elif filtered_df.empty:
            search_query = (self._search_var.get() or '').strip()
            column_query = (self._column_filter_var.get() or '').strip()
            selected_column = (self._column_filter_column_var.get() or '').strip()
            if search_query and column_query:
                msg = f"Aucun résultat pour recherche « {search_query} » + filtre {selected_column} « {column_query} »"
            elif column_query:
                msg = f"Aucun résultat pour filtre {selected_column} « {column_query} »"
            else:
                msg = f"Aucun résultat pour « {search_query} »"
            self.empty_state_label.config(text=msg)
            self.empty_state_label.pack(pady=20)
        else:
            self.empty_state_label.pack_forget()

    def _get_filtered_sorted_df(self) -> pd.DataFrame:
        """Get active page rows after search/column filters and sort (without pagination)."""
        base_df = self._df if self._df is not None else pd.DataFrame()
        filtered_df = self._apply_search_filter(base_df)
        filtered_df = self._apply_column_filter(filtered_df)
        return self._apply_sort(filtered_df)

    def _export_current_view_csv(self):
        """Export filtered and sorted active view to CSV."""
        try:
            df = self._get_filtered_sorted_df()
            if df is None or df.empty:
                self._show_toast('Aucune donnée à exporter')
                return

            default_name = f"dashboard_{self._current_page}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
            target = filedialog.asksaveasfilename(
                parent=self,
                title='Exporter la vue en CSV',
                defaultextension='.csv',
                filetypes=[('CSV', '*.csv')],
                initialfile=default_name
            )
            if not target:
                return

            export_df = df.copy()
            used_labels = set()
            display_columns = []
            for col in export_df.columns:
                label = self._column_label(col)
                if label in used_labels:
                    label = f"{label} ({col})"
                used_labels.add(label)
                display_columns.append(label)
            export_df.columns = display_columns
            export_df.to_csv(target, index=False, encoding='utf-8-sig')
            self._show_toast('Export CSV réussi')
        except Exception as e:
            logger.error("CSV export failed: %s", e)
            messagebox.showerror('Erreur', f"Export CSV impossible: {e}")

    def _show_toast(self, message: str, duration_ms: int = 2200):
        """Display a temporary non-blocking toast near the top-right corner."""
        try:
            if hasattr(self, 'toast_label') and self.toast_label is not None:
                self.toast_label.place_forget()
            else:
                self.toast_label = ttk.Label(self, style='Header.TLabel')

            self.toast_label.config(text=message)
            self.toast_label.place(relx=0.98, rely=0.03, anchor='ne')

            if self._toast_job is not None:
                try:
                    self.after_cancel(self._toast_job)
                except Exception:
                    pass
            self._toast_job = self.after(duration_ms, self.toast_label.place_forget)
        except Exception:
            # Toast is best-effort only; avoid blocking normal flows.
            pass

    def _resolve_action_handler(self):
        """Resolve callback used by dashboard action buttons."""
        if callable(self.action_handler):
            return self.action_handler
        fn = getattr(self.parent, 'handle_dashboard_action', None)
        if callable(fn):
            return fn
        main_form = getattr(self.parent, 'main_form', None)
        fn = getattr(main_form, 'handle_dashboard_action', None)
        if callable(fn):
            return fn
        return None

    def _invoke_action_handler(self, action: str, payload: dict | None):
        """Call action handler, passing current page when supported."""
        action_handler = self._resolve_action_handler()
        if not callable(action_handler):
            return None

        try:
            signature = inspect.signature(action_handler)
            params = list(signature.parameters.values())
            accepts_page = any(p.kind == inspect.Parameter.VAR_POSITIONAL for p in params) or len(params) >= 3
        except Exception:
            accepts_page = True

        if accepts_page:
            return action_handler(action, payload, self._current_page)
        return action_handler(action, payload)

    def _get_selected_payload(self) -> Optional[dict]:
        """Return selected row as a record dict from the current filtered page."""
        tree = self.trees.get(self._current_page)
        if tree is None:
            return None

        selection = tree.selection()
        if not selection:
            return None

        df = self._current_view_df if self._current_view_df is not None else pd.DataFrame()
        selected_idx = tree.index(selection[0])
        if selected_idx >= len(df):
            return None

        row = df.iloc[selected_idx]
        return row.to_dict()

    def _on_tree_selection_changed(self, _event=None):
        self._update_action_buttons_state()

    def _update_action_buttons_state(self):
        """Keep action buttons aligned with handler availability and selection state."""
        action_handler = self._resolve_action_handler()
        has_handler = callable(action_handler)
        has_selection = self._get_selected_payload() is not None

        try:
            self._action_buttons['add'].configure(state='normal' if has_handler else 'disabled')
            self._action_buttons['refresh'].configure(state='normal')
            edit_delete_state = 'normal' if has_handler and has_selection else 'disabled'
            self._action_buttons['edit'].configure(state=edit_delete_state)
            self._action_buttons['delete'].configure(state=edit_delete_state)
        except Exception:
            pass

    def _restore_parent_window(self, fullscreen_parent: bool = False):
        """Restore and focus the parent window, optionally already maximized."""
        try:
            if self._parent_disabled:
                try:
                    self.parent.attributes('-disabled', False)
                except Exception:
                    pass
            elif self._parent_withdrawn:
                try:
                    if fullscreen_parent:
                        try:
                            self.parent.state('zoomed')
                        except Exception:
                            try:
                                self.parent.attributes('-zoomed', True)
                            except Exception:
                                pass
                    self.parent.deiconify()
                except Exception:
                    pass
            elif fullscreen_parent:
                try:
                    self.parent.state('zoomed')
                except Exception:
                    try:
                        self.parent.attributes('-zoomed', True)
                    except Exception:
                        pass
        except Exception:
            pass

        try:
            self.parent.deiconify()
        except Exception:
            pass

        try:
            self.parent.lift()
            self.parent.focus_force()
        except Exception:
            pass

    def _hide_for_parent_switch(self, fullscreen_parent: bool = False):
        """Hide dashboard while keeping it reusable, then focus the generator."""
        try:
            self.grab_release()
        except Exception:
            pass

        self._restore_parent_window(fullscreen_parent=fullscreen_parent)

        try:
            self.withdraw()
        except Exception:
            pass

    def _open_tools(self):
        """Open tools dialog from dashboard, releasing grabs first."""
        try:
            self.grab_release()
        except Exception:
            pass
        # Try main_form first (même logique que dans le générateur)
        try:
            mf = getattr(self.parent, 'main_form', None)
            if mf and callable(getattr(mf, 'open_configuration', None)):
                try:
                    mf.open_configuration(parent_window=self)
                except TypeError:
                    mf.open_configuration()
                return
        except Exception:
            pass
        # Fallback: directement via le parent
        try:
            tools_cmd = getattr(self.parent, 'open_configuration', None)
            if callable(tools_cmd):
                try:
                    tools_cmd(parent_window=self)
                except TypeError:
                    tools_cmd()
                return
        except Exception:
            pass
        try:
            messagebox.showwarning("Outils", "Impossible d'ouvrir les outils depuis le tableau de bord.")
        except Exception:
            pass

    def _action(self, action: str):
        """Handle action buttons and send to parent MainForm"""
        try:
            if action == 'refresh':
                self._load_data()
                self._show_toast('Données actualisées')
            elif action == 'add':
                if not callable(self._resolve_action_handler()):
                    messagebox.showwarning('Ajouter', 'Action Ajouter indisponible (handler introuvable).')
                    return
                result = self._invoke_action_handler('add', None)
                if not isinstance(result, dict) or result.get('status') != 'error':
                    self._hide_for_parent_switch(fullscreen_parent=True)
            elif action == 'edit':
                payload = self._get_selected_payload()
                if payload is None:
                    messagebox.showwarning('Modifier', 'Veuillez sélectionner un enregistrement')
                    return
                if self._df is None or self._df.empty:
                    messagebox.showwarning('Modifier', 'Aucune donnée disponible')
                    return
                if not callable(self._resolve_action_handler()):
                    messagebox.showwarning('Modifier', 'Action Modifier indisponible (handler introuvable).')
                    return
                result = self._invoke_action_handler('edit', payload)
                if not isinstance(result, dict) or result.get('status') == 'opened':
                    self._hide_for_parent_switch(fullscreen_parent=True)
            elif action == 'delete':
                payload = self._get_selected_payload()
                if payload is None:
                    messagebox.showwarning('Supprimer', 'Veuillez sélectionner un enregistrement')
                    return
                if self._df is None or self._df.empty:
                    messagebox.showwarning('Supprimer', 'Aucune donnée disponible')
                    return
                if not callable(self._resolve_action_handler()):
                    messagebox.showwarning('Supprimer', 'Action Supprimer indisponible (handler introuvable).')
                    return
                result = self._invoke_action_handler('delete', payload)
                status = result.get('status') if isinstance(result, dict) else None
                if status == 'deleted':
                    self._show_toast('Suppression effectuée')
                    self._load_data()
                elif status == 'cancelled':
                    self._show_toast('Suppression annulée')
        except Exception as e:
            logger.error(f"Error in _action('{action}'): {e}")
            messagebox.showerror('Erreur', f'Erreur lors de l\'action: {e}')

    def _update_clock(self):
        """Update date and clock"""
        try:
            now = datetime.now()
            self.date_label.config(text=now.strftime("%A %d %B %Y"))
            self.clock_label.config(text=now.strftime("%H:%M:%S"))
        except Exception:
            pass
        finally:
            try:
                self.after(1000, self._update_clock)
            except Exception:
                pass

    def _on_close(self, call_parent=False, fullscreen_parent: bool = False):
        """Close dashboard and restore parent"""
        if call_parent:
            try:
                if getattr(self.parent, "_startup_dashboard_mode", False):
                    self._quit_app()
                    return
            except Exception:
                pass
        try:
            main_form = getattr(self.parent, 'main_form', None)
            if getattr(main_form, '_dashboard_window', None) is self:
                main_form._dashboard_window = None
        except Exception:
            pass

        try:
            self.grab_release()
        except Exception:
            pass

        try:
            self.destroy()
        except Exception:
            try:
                self.withdraw()
            except Exception:
                pass

        if call_parent:
            self._restore_parent_window(fullscreen_parent=fullscreen_parent)
        elif self._parent_withdrawn:
            try:
                self.parent.destroy()
            except Exception:
                pass

    def _quit_app(self):
        """Exit the application from the dashboard."""
        try:
            self.grab_release()
        except Exception:
            pass
        try:
            if self.parent is not None:
                self.parent.destroy()
                return
        except Exception:
            pass
        try:
            self.destroy()
        except Exception:
            pass
