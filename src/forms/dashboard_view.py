import tkinter as tk
from tkinter import ttk, messagebox
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

        WidgetFactory.create_button(nav, text="🏢 Sociétés", command=lambda: self._show_page('societe'), style='Secondary.TButton').pack(fill='x', pady=3, ipady=2)
        WidgetFactory.create_button(nav, text="👥 Associés", command=lambda: self._show_page('associe'), style='Secondary.TButton').pack(fill='x', pady=3, ipady=2)
        WidgetFactory.create_button(nav, text="📄 Contrats", command=lambda: self._show_page('contrat'), style='Secondary.TButton').pack(fill='x', pady=3, ipady=2)

        # Action buttons
        action_frame = ttk.Frame(nav)
        action_frame.pack(fill='x', pady=10)
        WidgetFactory.create_button(action_frame, text="➕ Ajouter", command=lambda: self._action('add'), style='Success.TButton').pack(fill='x', pady=3, ipady=2)
        WidgetFactory.create_button(action_frame, text="✏️ Modifier", command=lambda: self._action('edit'), style='Manage.TButton').pack(fill='x', pady=3, ipady=2)
        WidgetFactory.create_button(action_frame, text="🗑️ Supprimer", command=lambda: self._action('delete'), style='Cancel.TButton').pack(fill='x', pady=3, ipady=2)
        WidgetFactory.create_button(action_frame, text="🔄 Actualiser", command=lambda: self._action('refresh'), style='Manage.TButton').pack(fill='x', pady=3, ipady=2)

        # Content area
        self.content = ttk.Frame(body)
        self.content.pack(side='left', fill='both', expand=True)

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
                tree.heading(col, text=col)
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
        self.status_label = ttk.Label(self, text='Prêt', relief=tk.SUNKEN)
        self.status_label.pack(fill='x', side='bottom')

    def _load_data(self):
        """Load data from database (all three sheets)"""
        try:
            PathManager.ensure_directories()
            excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

            if excel_path.exists():
                try:
                    # Load all three main sheets
                    self._societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
                    self._associes_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
                    self._contrats_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
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

        self._refresh_display()

    def _refresh_display(self):
        """Refresh the displayed data"""
        if self._df is None or self._df.empty:
            self.status_label.config(text='Aucune donnée')
            return

        # Clear current trees
        for tree in self.trees.values():
            for item in tree.get_children():
                tree.delete(item)

        # Populate current tree
        tree = self.trees.get(self._current_page)
        if tree is not None:
            # Get column names from tree (these are display columns without ID_*)
            columns = list(tree["columns"])
            logger.debug(f"Tree columns for '{self._current_page}': {columns}")
            logger.debug(f"DataFrame columns: {list(self._df.columns)}")

            # Populate tree with data
            for idx, (_, row) in enumerate(self._df.iterrows()):
                values = []
                for col in columns:
                    # Get value from row, handling missing columns gracefully
                    if col in self._df.columns:
                        val = row.get(col, '')
                    else:
                        logger.warning(f"Column '{col}' not found in DataFrame")
                        val = ''
                    values.append(str(val))

                logger.debug(f"Row {idx} values: {values}")
                tree.insert('', 'end', values=values)

        self.status_label.config(text=f'Total: {len(self._df)} enregistrements')

    def _action(self, action: str):
        """Handle action buttons and send to parent MainForm"""
        try:
            if action == 'refresh':
                self._load_data()
                messagebox.showinfo('Info', 'Données actualisées')
            elif action == 'add':
                # Add new record - pass empty payload to MainForm
                if hasattr(self.parent, 'handle_dashboard_action'):
                    self.parent.handle_dashboard_action('add', None)
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

                df = self._df  # Narrow type for type checker
                selected_idx = tree.index(selection[0])
                if selected_idx >= len(df):
                    messagebox.showerror('Modifier', 'Index de ligne invalide')
                    return

                row = df.iloc[selected_idx]
                payload = row.to_dict()

                # Send to parent
                if hasattr(self.parent, 'handle_dashboard_action'):
                    self.parent.handle_dashboard_action('edit', payload)
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

                df = self._df  # Narrow type for type checker
                selected_idx = tree.index(selection[0])
                if selected_idx >= len(df):
                    messagebox.showerror('Supprimer', 'Index de ligne invalide')
                    return

                row = df.iloc[selected_idx]
                payload = row.to_dict()

                # Send to parent
                if hasattr(self.parent, 'handle_dashboard_action'):
                    self.parent.handle_dashboard_action('delete', payload)
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
