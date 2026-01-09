import tkinter as tk
from tkinter import ttk, messagebox
from datetime import datetime
from pathlib import Path
import logging
from typing import Optional

try:
    import pandas as pd
except Exception:
    pd = None

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler
from ..utils import constants as _const

logger = logging.getLogger(__name__)


class DashboardView(tk.Toplevel):
    """A compact, elegant dashboard modal.

    Features:
    - Modal Toplevel (transient + grab_set)
    - Header with app title, current date and live clock
    - Left navigation (Sociétés / Associés / Contrats)
    - Three pages with Treeviews sourced from the Excel sheets
    - Action buttons that close the dashboard and call
      `main_form.handle_dashboard_action(action, payload)` on the parent if available.
    """

    def __init__(self, parent):
        super().__init__(parent)
        self.parent = parent
        self.title("Tableau de Bord — Centre de Domiciliation")
        self.geometry("1100x700")
        self.minsize(800, 520)

        # Make modal
        try:
            self.transient(parent)
            self.grab_set()
        except Exception:
            pass

        # Track whether we disabled or withdrew parent so we can restore it
        self._parent_disabled = False
        self._parent_withdrawn = False
        try:
            try:
                parent.attributes('-disabled', True)
                self._parent_disabled = True
            except Exception:
                try:
                    parent.withdraw()
                    self._parent_withdrawn = True
                except Exception:
                    pass
        except Exception:
            pass

        # Theme
        self.theme = ThemeManager(self.winfo_toplevel())
        self.style = self.theme.style

        # Layout
        self._build_header()
        self._build_body()
        self._build_status()

        # Load data
        self._df = None
        self._load_data()

        # Start clock
        self._update_clock()

        # When closed, clean up and restore parent
        self.protocol('WM_DELETE_WINDOW', self._on_close)

    # --- UI builders ---
    def _build_header(self):
        header = ttk.Frame(self, padding=(12, 8), style='Card.TFrame')
        header.pack(fill='x')

        title = ttk.Label(header, text='Centre de Domiciliation — Tableau de Bord', font=('Segoe UI', 12, 'bold'))
        title.pack(side='left')

        right = ttk.Frame(header)
        right.pack(side='right')

        self.date_label = ttk.Label(right, text='', font=('Segoe UI', 10))
        self.date_label.pack(anchor='e')

        self.clock_label = ttk.Label(right, text='', font=('Segoe UI', 13, 'bold'))
        self.clock_label.pack(anchor='e')

    def _build_body(self):
        body = ttk.Frame(self)
        body.pack(fill='both', expand=True)

        # Left nav
        nav = ttk.Frame(body, width=200, padding=(8, 8))
        nav.pack(side='left', fill='y')

        # Buttons
        btn_cfg = dict(style='Accent.TButton')
        WidgetFactory.create_button(nav, text='🏢 Sociétés', command=lambda: self.show_page('societe'), **btn_cfg).pack(fill='x', pady=6)
        WidgetFactory.create_button(nav, text='👥 Associés', command=lambda: self.show_page('associe'), **btn_cfg).pack(fill='x', pady=6)
        WidgetFactory.create_button(nav, text='📄 Contrats', command=lambda: self.show_page('contrat'), **btn_cfg).pack(fill='x', pady=6)

        # Action buttons
        act_frame = ttk.Frame(nav, padding=(0, 12))
        act_frame.pack(side='bottom', fill='x')
        WidgetFactory.create_button(act_frame, text='➕ Ajouter', command=lambda: self._on_action('add'), **btn_cfg).pack(fill='x', pady=3)
        WidgetFactory.create_button(act_frame, text='✏️ Modifier', command=lambda: self._on_action('edit'), **btn_cfg).pack(fill='x', pady=3)
        WidgetFactory.create_button(act_frame, text='🗑️ Supprimer', command=lambda: self._on_action('delete'), **btn_cfg).pack(fill='x', pady=3)
        WidgetFactory.create_button(act_frame, text='🔄 Actualiser', command=lambda: self._on_action('refresh'), **btn_cfg).pack(fill='x', pady=3)

        # Content area
        self.content = ttk.Frame(body, padding=8)
        self.content.pack(side='left', fill='both', expand=True)

        # Pages
        self.pages = {}
        self.current_page = None

        self.pages['societe'] = self._create_table_page('Sociétés', self._societe_columns())
        self.pages['associe'] = self._create_table_page('Associés', self._associe_columns())
        self.pages['contrat'] = self._create_table_page('Contrats', self._contrat_columns())

        self.show_page('societe')

    def _build_status(self):
        self.status = ttk.Label(self, text='Prêt', relief='sunken', anchor='w')
        self.status.pack(side='bottom', fill='x')

    # --- Pages / tables ---
    def _create_table_page(self, title, columns):
        frame = ttk.Frame(self.content)
        hdr = ttk.Label(frame, text=title, font=('Segoe UI', 10, 'bold'))
        hdr.pack(anchor='w')

        tree = ttk.Treeview(frame, columns=columns, show='headings')
        for c in columns:
            tree.heading(c, text=c)
            tree.column(c, width=120, minwidth=60)

        vsb = ttk.Scrollbar(frame, orient='vertical', command=tree.yview)
        hsb = ttk.Scrollbar(frame, orient='horizontal', command=tree.xview)
        tree.configure(yscrollcommand=vsb.set, xscrollcommand=hsb.set)
        vsb.pack(side='right', fill='y')
        hsb.pack(side='bottom', fill='x')
        tree.pack(fill='both', expand=True)

        # keep reference
        frame._tree = tree
        return frame

    def _societe_columns(self):
        return [c for c in _const.societe_headers if not c.startswith('ID_')]

    def _associe_columns(self):
        cols = ['DEN_STE']
        cols.extend([c for c in _const.associe_headers if not c.startswith('ID_')][2:])
        return cols

    def _contrat_columns(self):
        cols = ['DEN_STE']
        cols.extend([c for c in _const.contrat_headers if not c.startswith('ID_')][2:])
        return cols

    # --- Data loading ---
    def _load_data(self):
        self.status.config(text='Chargement des données...')
        excel_path = PathManager.get_database_path(_const.DB_FILENAME)
        if pd is None:
            # pandas missing: show placeholder
            self._df = None
            self.status.config(text='pandas non disponible — données non chargées')
            return

        try:
            if not excel_path.exists():
                self._df = pd.DataFrame(columns=self._societe_columns())
                self.status.config(text='Aucune base de données trouvée — vide')
                return

            # Try reading the three sheets
            xls = pd.ExcelFile(excel_path)
            sheets = [str(s).lower() for s in xls.sheet_names]

            soc_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('') if 'societes' in sheets else pd.DataFrame(columns=_const.societe_headers)
            assoc_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('') if 'associes' in sheets else pd.DataFrame(columns=_const.associe_headers)
            contrat_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('') if 'contrats' in sheets else pd.DataFrame(columns=_const.contrat_headers)

            # Build combined df for easy lookups: keep societes rows and join associes/contrats where possible
            # For display we will populate each tree separately
            self._soc_df = soc_df
            self._assoc_df = assoc_df
            self._contrat_df = contrat_df
            self.status.config(text=f'Données chargées: societes={len(soc_df)}, associes={len(assoc_df)}, contrats={len(contrat_df)}')
            self._refresh_tables()
        except Exception as e:
            ErrorHandler.handle_error(e, 'Erreur lors du chargement des données', show_dialog=False)
            self.status.config(text='Erreur lors du chargement des données')

    # --- Table population ---
    def _refresh_tables(self):
        # Societes
        try:
            tree = self.pages['societe']._tree
            tree.delete(*tree.get_children())
            if getattr(self, '_soc_df', None) is not None:
                for _, r in self._soc_df.iterrows():
                    vals = [r.get(c, '') for c in self._societe_columns()]
                    tree.insert('', 'end', values=vals)
        except Exception:
            pass

        # Associes (grouped by DEN_STE)
        try:
            tree = self.pages['associe']._tree
            tree.delete(*tree.get_children())
            if getattr(self, '_assoc_df', None) is not None and not self._assoc_df.empty:
                # Build parents per company (DEN_STE) by linking ID_SOCIETE to soc_df
                parents = {}
                for _, r in self._assoc_df.iterrows():
                    # try to find company name
                    den = None
                    try:
                        sid = r.get('ID_SOCIETE', '')
                        if sid and getattr(self, '_soc_df', None) is not None and 'ID_SOCIETE' in self._soc_df.columns:
                            match = self._soc_df[self._soc_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip()]
                            if not match.empty:
                                den = match.iloc[0].get('DEN_STE', '')
                    except Exception:
                        den = None
                    if not den:
                        den = r.get('DEN_STE', '') or 'Sans société'

                    if den not in parents:
                        parents[den] = tree.insert('', 'end', values=[den] + ['']*(len(self._associe_columns())-1))

                    values = [den]
                    for c in self._associe_columns()[1:]:
                        try:
                            values.append(r.get(c, ''))
                        except Exception:
                            values.append('')
                    tree.insert(parents[den], 'end', values=values)
        except Exception:
            pass

        # Contrats
        try:
            tree = self.pages['contrat']._tree
            tree.delete(*tree.get_children())
            if getattr(self, '_contrat_df', None) is not None and not self._contrat_df.empty:
                parents = {}
                for _, r in self._contrat_df.iterrows():
                    den = None
                    try:
                        sid = r.get('ID_SOCIETE', '')
                        if sid and getattr(self, '_soc_df', None) is not None and 'ID_SOCIETE' in self._soc_df.columns:
                            match = self._soc_df[self._soc_df['ID_SOCIETE'].astype(str).str.strip() == str(sid).strip()]
                            if not match.empty:
                                den = match.iloc[0].get('DEN_STE', '')
                    except Exception:
                        den = None
                    if not den:
                        den = r.get('DEN_STE', '') or 'Sans société'

                    if den not in parents:
                        parents[den] = tree.insert('', 'end', values=[den] + ['']*(len(self._contrat_columns())-1))

                    values = [den]
                    for c in self._contrat_columns()[1:]:
                        try:
                            values.append(r.get(c, ''))
                        except Exception:
                            values.append('')
                    tree.insert(parents[den], 'end', values=values)
        except Exception:
            pass

    # --- Actions ---
    def _on_action(self, action: str):
        # Build payload where applicable
        payload = None
        if action in ('edit', 'delete'):
            # Look at current visible page's tree selection
            page = self.current_page or 'societe'
            frame = self.pages.get(page)
            tree = getattr(frame, '_tree', None) if frame is not None else None
            if tree is None:
                messagebox.showwarning('Aucune sélection', 'Aucune donnée disponible à sélectionner.')
                return
            sel = tree.selection()
            if not sel:
                messagebox.showwarning('Sélection requise', 'Veuillez sélectionner un élément.')
                return
            try:
                item = tree.item(sel[0])
                den = item.get('values', [''])[0]
                if den and getattr(self, '_soc_df', None) is not None:
                    m = self._soc_df[self._soc_df['DEN_STE'].astype(str).str.strip().str.lower() == str(den).strip().lower()]
                    if not m.empty:
                        payload = m.iloc[0].to_dict()
                    else:
                        payload = {'DEN_STE': den}
            except Exception:
                payload = None

        if action == 'refresh':
            self._load_data()

        # Close and hand control to main form if available
        try:
            # Restore parent before handing control
            self._on_close(call_parent=False)
        except Exception:
            pass

        top = getattr(self.parent, 'main_form', None)
        if top is not None and hasattr(top, 'handle_dashboard_action'):
            try:
                top.handle_dashboard_action(action, payload)
            except Exception:
                pass

    # --- UI helpers ---
    def show_page(self, key: str):
        # hide all
        for k, frame in self.pages.items():
            frame.pack_forget()
        # show requested
        frame = self.pages.get(key)
        if frame is not None:
            frame.pack(fill='both', expand=True)
            self.current_page = key
            # refresh visible data
            self._refresh_tables()

    def _update_clock(self):
        try:
            now = datetime.now()
            self.date_label.config(text=now.strftime('%A %d %B %Y'))
            self.clock_label.config(text=now.strftime('%H:%M:%S'))
        except Exception:
            pass
        try:
            self.after(1000, self._update_clock)
        except Exception:
            pass

    def _on_close(self, call_parent=True):
        # Restore parent state
        try:
            if getattr(self, '_parent_disabled', False):
                try:
                    self.parent.attributes('-disabled', False)
                except Exception:
                    pass
            elif getattr(self, '_parent_withdrawn', False):
                try:
                    self.parent.deiconify()
                except Exception:
                    pass
        except Exception:
            pass

        # Release modal grab
        try:
            self.grab_release()
        except Exception:
            pass

        # Destroy window
        try:
            self.destroy()
        except Exception:
            try:
                self.withdraw()
            except Exception:
                pass

        # Optional callback on parent
        if call_parent:
            cb = getattr(self.parent, 'on_dashboard_closed', None)
            if callable(cb):
                try:
                    cb()
                except Exception:
                    pass
import tkinter as tk
from tkinter import ttk, messagebox
from typing import Optional, Any
import pandas as pd
from pathlib import Path
import logging
from datetime import datetime
from ..utils.constants import (
    societe_headers, associe_headers, contrat_headers,
    DenSte, Civility, Formjur, Capital, PartsSocial
)
from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler

# Configuration du logging
logger = logging.getLogger(__name__)

class DashboardView(tk.Toplevel):
    def __init__(self, parent):
        super().__init__(parent)
        self.parent = parent
        self.title("Tableau de Bord - Centre de Domiciliation")
        self.geometry("1200x700")
        self.minsize(800, 600)

        # Make window modal
        self.transient(parent)
        self.grab_set()

        # Try to visually disable or hide the parent window so it appears
        # "on hold" while the dashboard is open. On Windows, attributes
        # ('-disabled', True) will grey out the parent. Fall back to withdraw
        # if disabling isn't supported. Track which operation we performed so
        # we can restore the parent on close.
        self._parent_was_disabled = False
        self._parent_was_withdrawn = False
        try:
            try:
                parent.attributes('-disabled', True)
                self._parent_was_disabled = True
            except Exception:
                try:
                    parent.withdraw()
                    self._parent_was_withdrawn = True
                except Exception:
                    # If we can't hide or disable, continue without failing
                    pass
        except Exception:
            pass

        # Nettoyage lors de la destruction
        self.bind("<Destroy>", self._cleanup)

        # Configuration du thème
        self.theme_manager = ThemeManager(self.winfo_toplevel())
        self.style = self.theme_manager.style

        # Définition des en-têtes pour le tableau de bord
        self.setup_headers()

        # Configuration initiale
        self.setup_styles()
        self.setup_layout()

        # Chargement des données
        self.load_data()

    def setup_headers(self):
        """Configure les en-têtes pour l'affichage"""
        # En-têtes combinées pour la vue dashboard
        self.excel_headers = (
            societe_headers +
            [h for h in associe_headers if h not in ['ID_ASSOCIE', 'ID_SOCIETE']] +
            [h for h in contrat_headers if h not in ['ID_CONTRAT', 'ID_SOCIETE']]
        )

        # En-têtes pour l'affichage (sans les IDs techniques)
        self.display_headers = [h for h in self.excel_headers
                              if not h.startswith('ID_')]

    def setup_styles(self):
        """Configure les styles pour le dashboard"""
        # Dark mode friendly colors (fall back to sensible defaults)
        bg = self.theme_manager.colors.get('bg', '#2b2b2b')
        fg = self.theme_manager.colors.get('fg', '#eaeaea')
        accent = '#4a90e2'
        accent_active = '#2171cd'

        # Set window background
        try:
            self.configure(bg=bg)
        except Exception:
            pass

        # Style des frames
        self.style.configure('Dashboard.TFrame', background=bg, foreground=fg, padding=10)

        # Style des labels
        self.style.configure('Dashboard.TLabel', background=bg, foreground=fg)

        # Style des tableaux
        self.style.configure('Dashboard.Treeview', rowheight=25, fieldbackground=bg, background=bg, foreground=fg)
        self.style.configure('Dashboard.Treeview.Heading', background=accent, foreground='white', relief='flat', font=('Segoe UI', 9, 'bold'))
        self.style.map('Dashboard.Treeview.Heading', background=[('active', accent_active)])

        # Style des contrôles (boutons)
        self.style.configure('Dashboard.TButton', padding=6, relief='flat', background=accent, foreground='white')

        # Optional: style for notebook / tabs if used elsewhere
        self.style.configure('Dashboard.TNotebook', background=bg)

    def setup_layout(self):
        """Configure la disposition des éléments"""
        # En-tête avec date et horloge
        self.create_header()

        # Zone principale avec navigation et contenu (3 pages)
        self.create_nav_and_content()

        # Barre de statut
        self.create_status_bar()

    def create_toolbar(self):
        """Crée la barre d'outils avec recherche et filtres

        NOTE: kept for backward compatibility but the main layout now
        uses a left navigation and pages. This toolbar is still available
        if needed (placed under the header).
        """
        toolbar = ttk.Frame(self, style='Dashboard.TFrame')
        toolbar.pack(fill="x", padx=10, pady=5)

        # Zone de recherche
        search_frame = ttk.LabelFrame(toolbar, text="Recherche", padding=10)
        search_frame.pack(side="left", fill="x", expand=True)

        # Champ de recherche avec icône
        search_container = ttk.Frame(search_frame)
        search_container.pack(fill="x")

        ttk.Label(search_container, text="🔍").pack(side="left")
        self.search_var = tk.StringVar()
        self.search_entry = ttk.Entry(search_container,
                                    textvariable=self.search_var,
                                    width=40)
        self.search_entry.pack(side="left", padx=5, fill="x", expand=True)
        self.search_entry.bind('<KeyRelease>', self.search_records)

        # Menu de filtre
        self.filter_var = tk.StringVar(value="Tout")
        filter_menu = ttk.OptionMenu(
            search_container,
            self.filter_var,
            "Tout",
            "Société", "Associés", "Contrat",
            command=self.search_records
        )
        filter_menu.pack(side="left", padx=5)

        # Boutons d'actions
        button_frame = ttk.Frame(toolbar)
        button_frame.pack(side="right")

        self.create_action_buttons(button_frame)

    def create_action_buttons(self, parent):
        """Crée les boutons d'action"""
        buttons = [
            ("➕ Ajouter", 'add', "Ajouter un nouvel enregistrement"),
            ("✏️ Modifier", 'edit', "Modifier l'enregistrement sélectionné"),
            ("🗑️ Supprimer", 'delete', "Supprimer l'enregistrement sélectionné"),
            ("🔄 Actualiser", 'refresh', "Actualiser les données")
        ]

        for text, action_key, tooltip in buttons:
            # Create a command that will inspect the current selection (if any),
            # build a payload and then close the dashboard and hand control to
            # the main generator.
            def _make_cmd(a):
                return lambda: self._action_button_clicked(a)

            WidgetFactory.create_button(
                parent,
                text=text,
                command=_make_cmd(action_key),
                style='Dashboard.TButton',
                tooltip=tooltip
            ).pack(side="left", padx=5)

    def _action_button_clicked(self, action_key: str):
        """Handle clicks from Ajouter/Modifier/Supprimer/Actualiser buttons.

        This will attempt to collect the selected record (if applicable)
        from the currently visible page and pass it as a payload to the
        main form when reopening the generator.
        """
        payload = None

        # Determine which page is currently visible
        current = getattr(self, 'current_page_key', 'societe')
        tree = None
        try:
            tree = getattr(self, f"{current}_tree", None)
        except Exception:
            tree = None

        if action_key in ('edit', 'delete'):
            # Need a selected item to act on
            if tree is None:
                messagebox.showwarning('Sélection requise', 'Aucun élément sélectionnable sur cette page.')
                return
            sel = tree.selection()
            if not sel:
                messagebox.showwarning('Sélection requise', 'Veuillez sélectionner un élément.')
                return
            # Get DEN_STE from first column and look up the full row in self.df
            try:
                item = tree.item(sel[0])
                den = item.get('values', [None])[0]
                if den:
                    # find first matching row
                    try:
                        row = self.df[self.df['DEN_STE'].astype(str).str.strip().str.lower() == str(den).strip().lower()]
                        if not row.empty:
                            payload = row.iloc[0].to_dict()
                    except Exception:
                        # best-effort: build minimal payload
                        payload = {'DEN_STE': den}
            except Exception:
                messagebox.showerror('Erreur', 'Impossible de lire la sélection.')
                return

        if action_key == 'refresh':
            # perform refresh first so generator sees up-to-date data
            try:
                self.refresh_data()
            except Exception:
                pass

        # Now close dashboard and open generator, handing over the action and payload
        self._open_generator_and_focus(action_key, payload)

    def create_main_view(self):
        """Legacy main view kept for compatibility.

        The new dashboard layout uses `create_nav_and_content` which
        builds three separate pages (Sociétés, Associés, Contrats).
        """
        # Keep a simple placeholder in case other code expects this method
        placeholder = ttk.Frame(self)
        placeholder.pack(fill="both", expand=True)

    def create_data_tables(self, parent):
        """Crée les tableaux de données"""
        # NOTE: this helper is now rarely used; prefer create_nav_and_content
        # which creates dedicated pages. We'll still provide the three tables
        # inside the given parent if called.
        self.societe_tree = self.create_table_section(
            parent, "Sociétés", self.get_societe_columns()
        )

        self.associe_tree = self.create_table_section(
            parent, "Associés", self.get_associe_columns()
        )

        self.contrat_tree = self.create_table_section(
            parent, "Contrats", self.get_contrat_columns()
        )

        return (self.societe_tree, self.associe_tree, self.contrat_tree)

    def create_header(self):
        """Crée l'en-tête contenant la date et une horloge en temps réel"""
        header = ttk.Frame(self, style='Dashboard.TFrame')
        header.pack(fill='x', padx=10, pady=(10, 5))

        # Left: App title
        title = ttk.Label(header, text="Tableau de Bord — Centre de Domiciliation",
                          font=('Segoe UI', 12, 'bold'))
        title.pack(side='left')

        # Right: date and clock
        right = ttk.Frame(header, style='Dashboard.TFrame')
        right.pack(side='right')

        self.date_label = ttk.Label(right, text="", font=('Segoe UI', 10))
        self.date_label.pack(side='top', anchor='e')

        # Use a tk.Label for the clock so we can easily style it bold and larger
        self.clock_label = tk.Label(right, text="", font=('Segoe UI', 14, 'bold'))
        self.clock_label.pack(side='top', anchor='e')

        # Initialize clock update loop
        self._update_clock()

    def create_nav_and_content(self):
        """Crée la zone de navigation (gauche) et le contenu (droite) avec 3 pages"""
        container = ttk.Frame(self)
        container.pack(fill='both', expand=True, padx=10, pady=5)

        # Left navigation
        nav = ttk.Frame(container, width=180, style='Dashboard.TFrame')
        nav.pack(side='left', fill='y', padx=(0,10))

        btn_soc = WidgetFactory.create_button(
            nav, text="🏢 Sociétés", command=lambda: self.show_page('societe'),
            style='Dashboard.TButton', tooltip='Afficher les sociétés')
        btn_soc.pack(fill='x', pady=5)

        btn_assoc = WidgetFactory.create_button(
            nav, text="👥 Associés", command=lambda: self.show_page('associe'),
            style='Dashboard.TButton', tooltip='Afficher les associés')
        btn_assoc.pack(fill='x', pady=5)

        btn_contrat = WidgetFactory.create_button(
            nav, text="📄 Contrats", command=lambda: self.show_page('contrat'),
            style='Dashboard.TButton', tooltip='Afficher les contrats')
        btn_contrat.pack(fill='x', pady=5)

        # Optional toolbar under nav
        self.create_toolbar()

        # Content area (pages)
        self.content = ttk.Frame(container)
        self.content.pack(side='left', fill='both', expand=True)

        # Create pages
        self.pages = {}

        # Societe page
        soc_page = ttk.Frame(self.content)
        soc_page.pack(fill='both', expand=True)
        self.societe_tree = self.create_table_section(soc_page, "Sociétés", self.get_societe_columns())
        self.pages['societe'] = soc_page

        # Associe page
        assoc_page = ttk.Frame(self.content)
        assoc_page.pack_forget()
        self.associe_tree = self.create_table_section(assoc_page, "Associés", self.get_associe_columns())
        self.pages['associe'] = assoc_page

        # Contrat page
        contrat_page = ttk.Frame(self.content)
        contrat_page.pack_forget()
        self.contrat_tree = self.create_table_section(contrat_page, "Contrats", self.get_contrat_columns())
        self.pages['contrat'] = contrat_page

        # Start with societes page
        self.show_page('societe')

    def create_table_section(self, parent, title, columns):
        """Crée une section de tableau avec titre"""
        # Use a plain Frame with Label for better dark theme control
        frame = ttk.Frame(parent, style='Dashboard.TFrame')
        title_label = ttk.Label(frame, text=title, font=('Segoe UI', 10, 'bold'))
        title_label.pack(anchor='w', padx=5, pady=(0,5))
        frame.pack(fill="x", padx=5, pady=5)

        # Création du tableau
        tree = ttk.Treeview(
            frame,
            columns=columns,
            show="headings",
            style='Dashboard.Treeview'
        )

        # Configuration des colonnes
        for col in columns:
            tree.heading(col, text=col, command=lambda c=col: self.sort_table(tree, c))
            width = 150 if "ADRESS" in col else 100
            tree.column(col, width=width, minwidth=50)

        # Scrollbars
        y_scroll = ttk.Scrollbar(frame, orient="vertical", command=tree.yview)
        x_scroll = ttk.Scrollbar(frame, orient="horizontal", command=tree.xview)
        tree.configure(yscrollcommand=y_scroll.set, xscrollcommand=x_scroll.set)
        # Placement des éléments
        y_scroll.pack(side="right", fill="y")
        x_scroll.pack(side="bottom", fill="x")
        tree.pack(fill="both", expand=True)

        frame.pack(fill='both', expand=True, padx=5, pady=5)

        return tree

    def show_page(self, key: str):
        """Affiche la page demandée et cache les autres"""
        # remember which page is visible so action buttons know where to look
        self.current_page_key = key
        for k, page in self.pages.items():
            if k == key:
                page.pack(fill='both', expand=True)
            else:
                page.pack_forget()

        # Refresh to ensure the visible page shows current data
        try:
            self.refresh_display()
        except Exception:
            pass

    def _update_clock(self):
        """Met à jour la date et l'horloge toutes les secondes"""
        try:
            now = datetime.now()
            # Example: 'Wednesday 29 October 2025' (locale independent)
            self.date_label.config(text=now.strftime("%A %d %B %Y"))
            self.clock_label.config(text=now.strftime("%H:%M:%S"))
        except Exception:
            # ignore update errors
            pass
        finally:
            # schedule next update
            try:
                self.after(1000, self._update_clock)
            except Exception:
                pass

    def create_status_bar(self):
        """Crée la barre de statut"""
        self.status_bar = ttk.Label(
            self,
            text="Prêt",
            relief=tk.SUNKEN,
            anchor=tk.W
        )
        self.status_bar.pack(side=tk.BOTTOM, fill=tk.X)

    def _open_generator_and_focus(self, action: Optional[str] = None, payload: Optional[dict] = None):
        """Close the dashboard and return focus to the main application.

        action can be 'add', 'edit', 'delete', or 'refresh' to hint the
        main form what the user wants to do. We perform safe checks so this
        works even if the parent doesn't expose the expected attributes.
        """
        # Release modal grab if set
        try:
            self.grab_release()
        except Exception:
            pass

        # Close/destroy the dashboard
        try:
            self.destroy()
        except Exception:
            try:
                self.withdraw()
            except Exception:
                pass

        # Bring parent (main app) back to front and give it focus
        top = getattr(self, 'parent', None)
        if top is not None:
            # Restore parent state if we disabled or withdrew it
            try:
                if getattr(self, '_parent_was_disabled', False):
                    try:
                        top.attributes('-disabled', False)
                    except Exception:
                        pass
                elif getattr(self, '_parent_was_withdrawn', False):
                    try:
                        top.deiconify()
                    except Exception:
                        pass
            except Exception:
                pass
            try:
                top.deiconify()
            except Exception:
                pass
            try:
                top.lift()
            except Exception:
                pass
            try:
                top.focus_force()
            except Exception:
                pass

            # If the main app exposes its MainForm, prepare it according to the action
            mf = getattr(top, 'main_form', None)
            if mf is not None:
                try:
                    if action == 'add':
                        mf.reset()
                        mf.show_page(0)
                    elif action in ('edit', 'delete', 'refresh'):
                        mf.show_page(0)
                except Exception:
                    pass

    def get_societe_columns(self):
        """Retourne les colonnes pour le tableau des sociétés"""
        return [col for col in societe_headers if not col.startswith('ID_')]

    def get_associe_columns(self):
        """Retourne les colonnes pour le tableau des associés"""
        cols = ['DEN_STE']  # Ajout du nom de la société
        cols.extend([col for col in associe_headers[2:]])
        return cols

    def get_contrat_columns(self):
        """Retourne les colonnes pour le tableau des contrats"""
        cols = ['DEN_STE']  # Ajout du nom de la société
        cols.extend([col for col in contrat_headers[2:]])
        return cols

    def load_data(self):
        """Charge les données depuis la base de données"""
        try:
            # Assurer que les répertoires existent
            PathManager.ensure_directories()

            # Chemin du fichier de base de données
            excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

            # If file doesn't exist, create an empty DataBaseDom sheet with canonical headers
            if not excel_path.exists():
                self.df = pd.DataFrame(columns=self.excel_headers)
                # create the file with a DataBaseDom sheet so future reads succeed
                try:
                    with pd.ExcelWriter(excel_path, engine='openpyxl') as writer:
                        self.df.to_excel(writer, sheet_name='DataBaseDom', index=False)
                except Exception:
                    # fallback to save_data method
                    self.save_data(excel_path)
                logger.info("Nouvelle base de données créée")
                return

            # Try to load the canonical aggregated sheet first. If it's missing,
            # attempt to build a DataFrame from 'Societes' (and leave other fields empty).
            try:
                self.df = pd.read_excel(excel_path, sheet_name="DataBaseDom")
                logger.info(f"Données chargées (DataBaseDom): {len(self.df)} enregistrements")
            except ValueError as e:
                # Sheet not found — fallback
                logger.debug(f"DataBaseDom sheet not found: {e}")
                try:
                    xls = pd.ExcelFile(excel_path)
                    sheets = [str(s).lower() for s in xls.sheet_names]
                except Exception:
                    sheets = []

                if 'societes' in sheets:
                    try:
                        soc_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
                    except Exception:
                        soc_df = pd.DataFrame()

                    # Read associes and contrats if present
                    try:
                        assoc_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
                    except Exception:
                        assoc_df = pd.DataFrame(columns=[c for c in associe_headers])
                    try:
                        contrat_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
                    except Exception:
                        contrat_df = pd.DataFrame(columns=[c for c in contrat_headers])

                    # Helper to get societe row by ID or DEN_STE
                    def find_societe_by_id(sid):
                        if 'ID_SOCIETE' in soc_df.columns:
                            m = soc_df[soc_df['ID_SOCIETE'].astype(str) == str(sid)]
                            if not m.empty:
                                return m.iloc[0].to_dict()
                        return None

                    def find_societe_by_name(name):
                        if 'DEN_STE' in soc_df.columns:
                            m = soc_df[soc_df['DEN_STE'].astype(str).str.strip().str.lower() == str(name).strip().lower()]
                            if not m.empty:
                                return m.iloc[0].to_dict()
                        return None

                    rows = []

                    # For each associe, create a combined row (societe + associe)
                    if not assoc_df.empty:
                        for _, arow in assoc_df.iterrows():
                            soc = None
                            if 'ID_SOCIETE' in arow and str(arow.get('ID_SOCIETE', '')).strip():
                                soc = find_societe_by_id(arow.get('ID_SOCIETE'))
                            if soc is None and 'DEN_STE' in arow and str(arow.get('DEN_STE', '')).strip():
                                soc = find_societe_by_name(arow.get('DEN_STE'))
                            combined = {h: '' for h in self.excel_headers}
                            # fill societe fields
                            if soc:
                                for sh in soc_df.columns:
                                    if sh in combined:
                                        combined[sh] = soc.get(sh, '')
                            # fill associe fields
                            for col in associe_headers:
                                if col in ('ID_ASSOCIE', 'ID_SOCIETE'):
                                    continue
                                combined[col] = arow.get(col, '') if col in arow.index else ''
                            rows.append(combined)

                    # For each contrat, create a combined row (societe + contrat)
                    if not contrat_df.empty:
                        for _, crow in contrat_df.iterrows():
                            soc = None
                            if 'ID_SOCIETE' in crow and str(crow.get('ID_SOCIETE', '')).strip():
                                soc = find_societe_by_id(crow.get('ID_SOCIETE'))
                            if soc is None and 'DEN_STE' in crow and str(crow.get('DEN_STE', '')).strip():
                                soc = find_societe_by_name(crow.get('DEN_STE'))
                            combined = {h: '' for h in self.excel_headers}
                            # fill societe fields
                            if soc:
                                for sh in soc_df.columns:
                                    if sh in combined:
                                        combined[sh] = soc.get(sh, '')
                            # fill contrat fields
                            for col in contrat_headers:
                                if col in ('ID_CONTRAT', 'ID_SOCIETE'):
                                    continue
                                combined[col] = crow.get(col, '') if col in crow.index else ''
                            rows.append(combined)

                    # Add societe-only rows for any societes not represented yet
                    represented = set()
                    for r in rows:
                        den = (r.get('DEN_STE') or '').strip()
                        if den:
                            represented.add(den.lower())
                    for _, srow in soc_df.iterrows():
                        den = str(srow.get('DEN_STE', '')).strip()
                        if den and den.lower() in represented:
                            continue
                        combined = {h: '' for h in self.excel_headers}
                        for sh in soc_df.columns:
                            if sh in combined:
                                combined[sh] = srow.get(sh, '')
                        rows.append(combined)

                    self.df = pd.DataFrame(rows, columns=self.excel_headers)
                    logger.info(f"Données construites à partir des feuilles: societes={len(soc_df)}, associes={len(assoc_df)}, contrats={len(contrat_df)} -> total rows {len(self.df)}")
                    # Persist a DataBaseDom sheet optionally for compatibility (non-destructive)
                    try:
                        with pd.ExcelWriter(excel_path, engine='openpyxl', mode='a', if_sheet_exists='replace') as writer:
                            self.df.to_excel(writer, sheet_name='DataBaseDom', index=False)
                    except Exception:
                        logger.debug('Failed to persist DataBaseDom sheet after building from separate sheets')
                else:
                    # No suitable sheets found — create empty df and persist DataBaseDom
                    self.df = pd.DataFrame(columns=self.excel_headers)
                    try:
                        with pd.ExcelWriter(excel_path, engine='openpyxl', mode='a', if_sheet_exists='replace') as writer:
                            self.df.to_excel(writer, sheet_name='DataBaseDom', index=False)
                    except Exception:
                        try:
                            self.save_data(excel_path)
                        except Exception:
                            pass

            # Mettre à jour l'affichage
            self.refresh_display()

        except Exception as e:
            ErrorHandler.handle_error(
                e,
                "Erreur lors du chargement des données",
                callback=self.create_empty_database
            )

    def save_data(self, path=None):
        """Sauvegarde les données dans le fichier Excel"""
        if path is None:
            path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

        try:
            self.df.to_excel(path, sheet_name="DataBaseDom", index=False)
            logger.info("Données sauvegardées avec succès")
        except Exception as e:
            ErrorHandler.handle_error(e, "Erreur lors de la sauvegarde des données")

    def refresh_display(self):
        """Actualise l'affichage des données"""
        # Effacer les tableaux
        for tree in [self.societe_tree, self.associe_tree, self.contrat_tree]:
            for item in tree.get_children():
                tree.delete(item)

        if self.df.empty:
            self.status_bar.config(text="Aucune donnée à afficher")
            return

        # Remplir les tableaux
        self.populate_societe_tree()
        self.populate_associe_tree()
        self.populate_contrat_tree()

        # Mettre à jour le statut
        self.status_bar.config(
            text=f"Total: {len(self.df)} enregistrements"
        )

    def populate_societe_tree(self):
        """Remplit le tableau des sociétés"""
        for _, row in self.df.drop_duplicates(subset=['DEN_STE']).iterrows():
            values = [str(row.get(col, "")) for col in self.get_societe_columns()]
            self.societe_tree.insert("", "end", values=values)

    def populate_associe_tree(self):
        """Remplit le tableau des associés"""
        for den_ste, group in self.df.groupby('DEN_STE'):
            # Créer le nœud parent pour la société
            parent = self.associe_tree.insert(
                "", "end",
                values=[den_ste] + ["" for _ in range(len(self.get_associe_columns())-1)],
                tags=('society',)
            )

            # Ajouter les associés
            for _, row in group.iterrows():
                values = [den_ste]
                values.extend(str(row.get(col, ""))
                            for col in self.get_associe_columns()[1:])
                self.associe_tree.insert(parent, "end", values=values)

    def populate_contrat_tree(self):
        """Remplit le tableau des contrats"""
        for den_ste, group in self.df.groupby('DEN_STE'):
            # Créer le nœud parent pour la société
            parent = self.contrat_tree.insert(
                "", "end",
                values=[den_ste] + ["" for _ in range(len(self.get_contrat_columns())-1)],
                tags=('society',)
            )

            # Ajouter les contrats
            for _, row in group.iterrows():
                values = [den_ste]
                values.extend(str(row.get(col, ""))
                            for col in self.get_contrat_columns()[1:])
                self.contrat_tree.insert(parent, "end", values=values)

    def sort_table(self, tree, col):
        """Trie un tableau selon une colonne"""
        # Récupérer les données
        data = [
            (tree.set(item, col), item)
            for item in tree.get_children('')
        ]

        # Trier
        data.sort()

        # Réorganiser
        for idx, (_, item) in enumerate(data):
            tree.move(item, '', idx)

    def search_records(self, event=None):
        """Recherche dans les enregistrements"""
        search_term = self.search_var.get().lower()
        search_filter = self.filter_var.get()

        # Mettre à jour le statut
        self.status_bar.config(text="Recherche en cours...")
        self.update()

        # Si vide, afficher tout
        if not search_term:
            self.refresh_display()
            return

        # Filtrer les données
        if search_filter == "Société":
            cols = self.get_societe_columns()
        elif search_filter == "Associés":
            cols = self.get_associe_columns()
        elif search_filter == "Contrat":
            cols = self.get_contrat_columns()
        else:
            cols = self.excel_headers

        # Appliquer le filtre
        mask = self.df[cols].astype(str).apply(
            lambda x: x.str.contains(search_term, case=False)
        ).any(axis=1)

        filtered_df = self.df[mask]

        # Sauvegarder temporairement
        original_df = self.df
        self.df = filtered_df

        # Actualiser l'affichage
        self.refresh_display()

        # Restaurer
        self.df = original_df

    def refresh_data(self):
        """Actualise les données"""
        self.load_data()
        self.refresh_display()
        messagebox.showinfo("Succès", "Données actualisées")

    def add_record(self):
        """Ajoute un nouvel enregistrement"""
        from .societe_form import SocieteForm
        from .associe_form import AssocieForm
        from .contrat_form import ContratForm

        # Créer une nouvelle fenêtre
        dialog = tk.Toplevel(self)
        dialog.title("Ajouter un enregistrement")
        dialog.geometry("800x600")

        # Créer un notebook pour les onglets
        notebook = ttk.Notebook(dialog)
        notebook.pack(fill="both", expand=True, padx=10, pady=5)

        # Créer les onglets avec les formulaires
        societe_frame = ttk.Frame(notebook)
        associe_frame = ttk.Frame(notebook)
        contrat_frame = ttk.Frame(notebook)

        notebook.add(societe_frame, text="📋 Société")
        notebook.add(associe_frame, text="👥 Associés")
        notebook.add(contrat_frame, text="📄 Contrat")

        # Créer les formulaires
        societe_form = SocieteForm(societe_frame, values_dict={})
        societe_form.pack(fill="both", expand=True)

        associe_form = AssocieForm(associe_frame, values_dict={})
        associe_form.pack(fill="both", expand=True)

        contrat_form = ContratForm(contrat_frame, values_dict={})
        contrat_form.pack(fill="both", expand=True)

        # Boutons de contrôle
        btn_frame = ttk.Frame(dialog)
        btn_frame.pack(fill="x", padx=10, pady=5)

        def save_and_close():
            try:
                # Collecter les données de tous les formulaires
                societe_vals = societe_form.get_values() or {}
                associes_vals = associe_form.get_values() or []
                contrat_vals = contrat_form.get_values() or {}

                # Build a single row where each section is stored under its key.
                # This avoids calling dict.update() with a list (which would iterate keys).
                all_values = {
                    'societe': societe_vals,
                    'associes': associes_vals,
                    'contrat': contrat_vals
                }

                # Ajouter à la base de données: create a one-row DataFrame and concat
                new_row_df = pd.DataFrame([all_values])
                self.df = pd.concat([self.df, new_row_df], ignore_index=True)
                self.save_data()
                self.refresh_display()

                messagebox.showinfo("Succès", "Enregistrement ajouté avec succès!")
                dialog.destroy()
            except Exception as e:
                ErrorHandler.handle_error(e, "Erreur lors de l'ajout de l'enregistrement")

        def cancel():
            if messagebox.askyesno("Confirmation", "Voulez-vous vraiment annuler ?"):
                dialog.destroy()

        # Boutons
        WidgetFactory.create_button(
            btn_frame,
            text="💾 Enregistrer",
            command=save_and_close,
            style="Dashboard.TButton",
            tooltip="Enregistrer et fermer"
        ).pack(side="right", padx=5)

        WidgetFactory.create_button(
            btn_frame,
            text="❌ Annuler",
            command=cancel,
            style="Dashboard.TButton",
            tooltip="Annuler et fermer"
        ).pack(side="right", padx=5)

    def edit_record(self):
        """Modifie l'enregistrement sélectionné"""
        # Vérifier qu'une société est sélectionnée
        selected = self.societe_tree.selection()
        if not selected:
            messagebox.showwarning(
                "Sélection requise",
                "Veuillez sélectionner une société à modifier."
            )
            return

        # Obtenir les données de la société sélectionnée
        item = self.societe_tree.item(selected[0])
        den_ste = item['values'][0]

        # Récupérer toutes les données de cette société
        societe_data = self.df[self.df['DEN_STE'] == den_ste].iloc[0].to_dict()

        # Créer le même type de dialogue que pour l'ajout
        self.add_record()  # Réutiliser la même structure
        # TODO: Ajouter le code pour pré-remplir les formulaires

    def delete_record(self):
        """Supprime l'enregistrement sélectionné"""
        selected = self.societe_tree.selection()
        if not selected:
            messagebox.showwarning(
                "Sélection requise",
                "Veuillez sélectionner une société à supprimer."
            )
            return

        # Obtenir les données de la société sélectionnée
        item = self.societe_tree.item(selected[0])
        den_ste = item['values'][0]

        if messagebox.askyesno(
            "Confirmation",
            f"Voulez-vous vraiment supprimer la société {den_ste} ?"
        ):
            # Supprimer toutes les entrées liées à cette société
            self.df = self.df[self.df['DEN_STE'] != den_ste]
            self.save_data()
            self.refresh_display()
            messagebox.showinfo("Succès", "Société supprimée avec succès!")

    def create_empty_database(self):
        """Crée une base de données vide"""
        self.df = pd.DataFrame(columns=self.excel_headers)
        try:
            self.save_data()
            logger.info("Base de données vide créée")
            messagebox.showinfo(
                "Information",
                "Une nouvelle base de données vide a été créée"
            )
        except Exception as e:
            ErrorHandler.handle_error(
                e,
                "Erreur lors de la création de la base de données vide"
            )

    def _cleanup(self, event=None):
        """Nettoie les événements lors de la destruction du widget"""
        # Remove any global bindings we may have added
        try:
            self.unbind_all("<MouseWheel>")
        except Exception:
            pass

        try:
            self.unbind("<Destroy>")
        except Exception:
            pass

        # Release modal grab if still set
        try:
            self.grab_release()
        except Exception:
            pass

        # Try to restore and focus the parent (main application)
        top = getattr(self, 'parent', None)
        if top is not None:
            try:
                top.deiconify()
            except Exception:
                pass
            try:
                top.lift()
            except Exception:
                pass
            try:
                top.focus_force()
            except Exception:
                pass

            # If parent exposes a callback for dashboard close, call it
            cb = getattr(top, 'on_dashboard_closed', None)
            if callable(cb):
                try:
                    cb()
                except Exception:
                    pass
