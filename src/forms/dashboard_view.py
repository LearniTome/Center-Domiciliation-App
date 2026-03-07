import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from datetime import datetime
from pathlib import Path
import logging
from typing import Optional

import pandas as pd

from ..utils.utils import ThemeManager, WidgetFactory, PathManager, ErrorHandler
from ..utils import constants as _const
from ..utils.constants import societe_headers, associe_headers, contrat_headers

logger = logging.getLogger(__name__)


class DashboardView(tk.Toplevel):
    """A compact, elegant dashboard modal for viewing and managing data."""

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

        # Track parent state
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

        # Initialize data variables FIRST (before building body)
        self._df = None
        self._societes_df = None
        self._associes_df = None
        self._contrats_df = None
        self._current_page = 'societe'
        self._search_var = tk.StringVar()
        self._column_filter_var = tk.StringVar()
        self._column_filter_column_var = tk.StringVar(value='(Toutes)')
        self._nav_buttons = {}
        self.empty_state_label: Optional[ttk.Label] = None
        self._sort_column_by_page = {'societe': None, 'associe': None, 'contrat': None}
        self._sort_desc_by_page = {'societe': False, 'associe': False, 'contrat': False}
        self._page_index_by_page = {'societe': 0, 'associe': 0, 'contrat': 0}
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

        # Start clock
        self._update_clock()

        # Cleanup on close
        self.protocol('WM_DELETE_WINDOW', self._on_close)

    def _build_header(self):
        """Build header with title, date and clock"""
        header = ttk.Frame(self, padding=(12, 8))
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
        """Build main body with navigation and content"""
        body = ttk.Frame(self)
        body.pack(fill='both', expand=True, padx=10, pady=5)

        # Left navigation
        nav = ttk.Frame(body, width=180)
        nav.pack(side='left', fill='y', padx=(0, 10))

        self._nav_buttons['societe'] = WidgetFactory.create_button(
            nav, text="🏢 Sociétés", command=lambda: self._show_page('societe'), style='Secondary.TButton'
        )
        self._nav_buttons['societe'].pack(fill='x', pady=3)
        self._nav_buttons['associe'] = WidgetFactory.create_button(
            nav, text="👥 Associés", command=lambda: self._show_page('associe'), style='Secondary.TButton'
        )
        self._nav_buttons['associe'].pack(fill='x', pady=3)
        self._nav_buttons['contrat'] = WidgetFactory.create_button(
            nav, text="📄 Contrats", command=lambda: self._show_page('contrat'), style='Secondary.TButton'
        )
        self._nav_buttons['contrat'].pack(fill='x', pady=3)

        # Action buttons
        action_frame = ttk.Frame(nav)
        action_frame.pack(fill='x', pady=10)
        WidgetFactory.create_button(action_frame, text="➕ Ajouter", command=lambda: self._action('add'), style='Success.TButton').pack(fill='x', pady=3)
        WidgetFactory.create_button(action_frame, text="✏️ Modifier", command=lambda: self._action('edit'), style='Manage.TButton').pack(fill='x', pady=3)
        WidgetFactory.create_button(action_frame, text="🗑️ Supprimer", command=lambda: self._action('delete'), style='Cancel.TButton').pack(fill='x', pady=3)
        WidgetFactory.create_button(action_frame, text="🔄 Actualiser", command=lambda: self._action('refresh'), style='Manage.TButton').pack(fill='x', pady=3)

        # Content area
        self.content = ttk.Frame(body)
        self.content.pack(side='left', fill='both', expand=True)

        # Search bar (global to current page)
        search_frame = ttk.Frame(self.content)
        search_frame.pack(fill='x', padx=5, pady=(0, 5))
        ttk.Label(search_frame, text='Recherche:').pack(side='left', padx=(0, 8))
        search_entry = ttk.Entry(search_frame, textvariable=self._search_var)
        search_entry.pack(side='left', fill='x', expand=True, padx=(0, 8))
        ttk.Label(search_frame, text='Colonne:').pack(side='left', padx=(0, 6))
        self.column_filter_combo = ttk.Combobox(
            search_frame,
            textvariable=self._column_filter_column_var,
            state='readonly',
            width=18
        )
        self.column_filter_combo.pack(side='left', padx=(0, 6))
        self.column_filter_combo.bind('<<ComboboxSelected>>', self._on_column_filter_column_changed)
        ttk.Label(search_frame, text='Valeur:').pack(side='left', padx=(0, 6))
        column_filter_entry = ttk.Entry(search_frame, textvariable=self._column_filter_var, width=24)
        column_filter_entry.pack(side='left', padx=(0, 8))
        WidgetFactory.create_button(
            search_frame,
            text='Effacer',
            command=self._clear_filters,
            style='Secondary.TButton'
        ).pack(side='left', padx=(8, 0))
        WidgetFactory.create_button(
            search_frame,
            text='Exporter CSV',
            command=self._export_current_view_csv,
            style='Manage.TButton'
        ).pack(side='left', padx=(8, 0))
        self._search_var.trace_add('write', self._on_search_changed)
        self._column_filter_var.trace_add('write', self._on_column_filter_changed)

        self.empty_state_label = ttk.Label(
            self.content,
            text='Aucune donnée à afficher',
            font=('Segoe UI', 11, 'italic')
        )

        self.pages = {}
        self.trees = {}

        # Create tables for each page
        for page_key, page_title, columns in [
            ('societe', 'Sociétés', [c for c in societe_headers if not c.startswith('ID_')]),
            ('associe', 'Associés', [c for c in associe_headers if not c.startswith('ID_')]),
            ('contrat', 'Contrats', [c for c in contrat_headers if not c.startswith('ID_')]),
        ]:
            page = ttk.Frame(self.content)
            page.pack_forget()
            self.pages[page_key] = page

            # Title
            ttk.Label(page, text=page_title, font=('Segoe UI', 10, 'bold')).pack(anchor='w', padx=5, pady=(0, 5))

            # Table frame
            table_frame = ttk.Frame(page)
            table_frame.pack(fill='both', expand=True, padx=5, pady=5)

            # Create Treeview
            tree = ttk.Treeview(table_frame, columns=columns, show='headings', height=15)
            for col in columns:
                tree.heading(col, text=col, command=lambda c=col, p=page_key: self._on_sort_column(p, c))
                tree.column(col, width=100, minwidth=50)

            # Scrollbars
            y_scroll = ttk.Scrollbar(table_frame, orient='vertical', command=tree.yview)
            x_scroll = ttk.Scrollbar(table_frame, orient='horizontal', command=tree.xview)
            tree.configure(yscrollcommand=y_scroll.set, xscrollcommand=x_scroll.set)

            y_scroll.pack(side='right', fill='y')
            x_scroll.pack(side='bottom', fill='x')
            tree.pack(fill='both', expand=True)

            self.trees[page_key] = tree

    def _build_status(self):
        """Build status bar"""
        pagination = ttk.Frame(self)
        pagination.pack(fill='x', side='bottom')

        ttk.Label(pagination, text='Lignes/page:').pack(side='left', padx=(8, 4))
        page_size = ttk.Combobox(
            pagination,
            textvariable=self._page_size_var,
            values=(10, 25, 50, 100),
            state='readonly',
            width=6
        )
        page_size.pack(side='left')
        page_size.bind('<<ComboboxSelected>>', self._on_page_size_changed)

        self.prev_page_btn = WidgetFactory.create_button(
            pagination, text='◀ Précédent', command=self._go_prev_page, style='Secondary.TButton'
        )
        self.prev_page_btn.pack(side='right', padx=(4, 8), pady=2)
        self.next_page_btn = WidgetFactory.create_button(
            pagination, text='Suivant ▶', command=self._go_next_page, style='Secondary.TButton'
        )
        self.next_page_btn.pack(side='right', padx=4, pady=2)
        self.page_label = ttk.Label(pagination, text='Page 1/1')
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

            PathManager.ensure_directories()
            excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

            if excel_path.exists():
                try:
                    # Load all three main sheets
                    self._societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
                    self._associes_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
                    self._contrats_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
                    self._contrats_df = _normalize_contrats_df(self._contrats_df).reindex(
                        columns=_const.contrat_headers, fill_value=''
                    )
                    # Initialize with societes data
                    self._df = self._societes_df
                except Exception as e:
                    logger.warning(f"Error loading sheets: {e}")
                    self._societes_df = pd.DataFrame(columns=_const.societe_headers)
                    self._associes_df = pd.DataFrame(columns=_const.associe_headers)
                    self._contrats_df = pd.DataFrame(columns=_const.contrat_headers)
                    self._df = self._societes_df
            else:
                self._societes_df = pd.DataFrame(columns=_const.societe_headers)
                self._associes_df = pd.DataFrame(columns=_const.associe_headers)
                self._contrats_df = pd.DataFrame(columns=_const.contrat_headers)
                self._df = self._societes_df

            self._refresh_display()
        except Exception as e:
            logger.error(f"Error loading data: {e}")
            self._societes_df = pd.DataFrame()
            self._associes_df = pd.DataFrame()
            self._contrats_df = pd.DataFrame()
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
                    values.append(str(val))

                logger.debug(f"Row {idx} values: {values}")
                tree.insert('', 'end', values=values)

        self._update_empty_state(base_df, sorted_df)
        self._update_pagination_controls(page_idx, total_pages)

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
                button.configure(style='Nav.TButton' if key == self._current_page else 'Secondary.TButton')
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

        selected_column = self._column_filter_column_var.get() or '(Toutes)'
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
        values = ['(Toutes)'] + columns
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
            header = col
            if col == active_col:
                header = f"{col} {'▼' if descending else '▲'}"
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

            df.to_csv(target, index=False, encoding='utf-8-sig')
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

    def _action(self, action: str):
        """Handle action buttons and send to parent MainForm"""
        try:
            if action == 'refresh':
                self._load_data()
                self._show_toast('Données actualisées')
            elif action == 'add':
                # Add new record - pass empty payload to MainForm
                if hasattr(self.parent, 'handle_dashboard_action'):
                    self.parent.handle_dashboard_action('add', None)
                    self._show_toast('Passage au mode ajout')
                else:
                    messagebox.showwarning('Ajouter', 'Parent window does not support this action')
            elif action == 'edit':
                # Edit selected record
                tree = self.trees.get(self._current_page)
                if tree is None:
                    messagebox.showwarning('Modifier', 'Aucune page sélectionnée')
                    return

                # Get selected row
                selection = tree.selection()
                if not selection:
                    messagebox.showwarning('Modifier', 'Veuillez sélectionner un enregistrement')
                    return

                # Build payload from selected row and current DataFrame
                if self._df is None or self._df.empty:
                    messagebox.showwarning('Modifier', 'Aucune donnée disponible')
                    return

                df = self._current_view_df if self._current_view_df is not None else pd.DataFrame()
                selected_idx = tree.index(selection[0])
                if selected_idx >= len(df):
                    messagebox.showerror('Modifier', 'Index de ligne invalide')
                    return

                row = df.iloc[selected_idx]
                payload = row.to_dict()

                # Send to parent
                if hasattr(self.parent, 'handle_dashboard_action'):
                    self.parent.handle_dashboard_action('edit', payload)
                    self._show_toast('Édition ouverte')
                else:
                    messagebox.showwarning('Modifier', 'Parent window does not support this action')
            elif action == 'delete':
                # Delete selected record
                tree = self.trees.get(self._current_page)
                if tree is None:
                    messagebox.showwarning('Supprimer', 'Aucune page sélectionnée')
                    return

                # Get selected row
                selection = tree.selection()
                if not selection:
                    messagebox.showwarning('Supprimer', 'Veuillez sélectionner un enregistrement')
                    return

                # Build payload from selected row
                if self._df is None or self._df.empty:
                    messagebox.showwarning('Supprimer', 'Aucune donnée disponible')
                    return

                df = self._current_view_df if self._current_view_df is not None else pd.DataFrame()
                selected_idx = tree.index(selection[0])
                if selected_idx >= len(df):
                    messagebox.showerror('Supprimer', 'Index de ligne invalide')
                    return

                row = df.iloc[selected_idx]
                payload = row.to_dict()

                # Send to parent
                if hasattr(self.parent, 'handle_dashboard_action'):
                    self.parent.handle_dashboard_action('delete', payload)
                    self._show_toast('Suppression demandée')
                else:
                    messagebox.showwarning('Supprimer', 'Parent window does not support this action')
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

    def _on_close(self, call_parent=True):
        """Close dashboard and restore parent"""
        try:
            if self._parent_disabled:
                try:
                    self.parent.attributes('-disabled', False)
                except Exception:
                    pass
            elif self._parent_withdrawn:
                try:
                    self.parent.deiconify()
                except Exception:
                    pass
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
            try:
                self.parent.deiconify()
                self.parent.lift()
                self.parent.focus_force()
            except Exception:
                pass
