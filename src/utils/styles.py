import logging
from tkinter import ttk
from ttkthemes import ThemedStyle

logger = logging.getLogger(__name__)

class ModernTheme:
    def __init__(self, root, mode: str = 'light'):
        self.root = root
        try:
            self.style = ThemedStyle(root)
        except Exception:
            logger.exception("ThemedStyle indisponible, fallback vers ttk.Style")
            self.style = ttk.Style(root)
        self.mode = mode if mode in ('light', 'dark') else 'light'
        self.setup_colors()
        self.apply_theme()

    def setup_colors(self):
        """Configure le thème de couleurs modernes pour light et dark"""
        PALETTE_LIGHT = {
            'bg': '#ffffff',
            'fg': '#333333',
            'accent': '#6f7a86',
            'accent_light': '#8a95a1',
            'error': '#dc3545',
            'success': '#28a745',
            'success_row_bg': '#dff3e6',
            'success_row_fg': '#1f5c3a',
            'error_row_bg': '#fde2e1',
            'error_row_fg': '#7a2e2e',
            'warning': '#ffc107',
            'info': '#17a2b8',
            'border': '#dee2e6',
            'hover': '#e9ecef',
            'disabled': '#6c757d',
            'label_fg': '#495057',
            'input_bg': '#ffffff',
            'input_border': '#ced4da',
            'section_bg': '#f8f9fa',
            'section_header_bg': '#e9ecef',
        }

        PALETTE_DARK = {
            # Slightly lighter main background to avoid pure black which can hide borders
            'bg': '#1f1f1f',
            # Ensure high contrast text on dark backgrounds
            'fg': '#f3f3f3',
            'accent': '#6c7783',
            'accent_light': '#828d99',
            'error': '#ff6b6b',
            'success': '#28a745',
            'success_row_bg': '#1f5f3a',
            'success_row_fg': '#e7f7ed',
            'error_row_bg': '#5a2a2a',
            'error_row_fg': '#f4d7d6',
            'warning': '#ffc107',
            'info': '#17a2b8',
            # Make borders slightly brighter than background so framed sections are visible
            'border': '#3a3a3a',
            'hover': '#2b2b2b',
            'disabled': '#7a848c',
            # Label color more readable
            'label_fg': '#e6e6e6',
            # Inputs slightly lighter than background to stand out
            'input_bg': '#262626',
            'input_border': '#4a4a4a',
            # Section backgrounds: keep subtle contrast but visible
            'section_bg': '#232323',
            'section_header_bg': '#2b2b2b',
        }

        self.colors = PALETTE_DARK if self.mode == 'dark' else PALETTE_LIGHT

    def apply_theme(self):
        """Applique le thème moderne à tous les widgets"""
        try:
            self.style.set_theme("clam")
        except AttributeError:
            self.style.theme_use("clam")

        # Configuration générale
        # Base style applied to all widgets: set default font and colors
        self.style.configure(".",
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            font=('Segoe UI', 10))

        # Configuration des widgets spécifiques
        self._setup_entry_styles()
        self._setup_button_styles()
        self._setup_combobox_styles()
        self._setup_frame_styles()
        self._setup_label_styles()
        self._setup_section_styles()
        self._setup_treeview_styles()
        self._setup_notebook_styles()
        self._setup_scrollbar_styles()

    def _setup_section_styles(self):
        """Configure les styles pour les sections de formulaire"""
        # Section frame with visible border and padding so content doesn't touch edges
        self.style.configure('Section.TFrame',
            background=self.colors['section_bg'],
            relief='solid',
            borderwidth=1,
            padding=(8, 8))

        # Header uses accent color and stronger padding to avoid cramped text
        self.style.configure('SectionHeader.TLabel',
            background=self.colors['section_header_bg'],
            foreground=self.colors['accent'],
            font=('Segoe UI', 11, 'bold'),
            padding=(12, 8))

        # Field labels: add more padding and slightly bolder font for readability
        self.style.configure('FieldLabel.TLabel',
            background=self.colors['bg'],
            foreground=self.colors['label_fg'],
            font=('Segoe UI', 10, 'normal'),
            padding=(6, 4))

    def _setup_entry_styles(self):
        """Configure les styles pour les champs de saisie"""
        # Entries: ensure visible border and internal padding so text isn't clipped
        self.style.configure('TEntry',
            fieldbackground=self.colors['input_bg'],
            selectbackground=self.colors['accent'],
            selectforeground='white',
            borderwidth=1,
            relief='solid',
            padding=(6, 6))

        self.style.map('TEntry',
            fieldbackground=[('readonly', self.colors['section_bg'])],
            # Some ttk themes may not support bordercolor; provide focus background as fallback
            background=[('focus', self.colors['input_bg'])])

    def _setup_button_styles(self):
        # Palette douce et compacte pour tous les boutons de l'application.
        secondary_bg = '#4a4a4a' if self.mode == 'dark' else '#f2f4f7'
        secondary_fg = '#f3f3f3' if self.mode == 'dark' else '#2f3742'
        success_bg = '#4E9D78'
        cancel_bg = '#B95F53'
        manage_bg = '#6f7b87'
        confirm_bg = '#77828d'
        close_bg = '#6A7077'
        refresh_bg = '#6E7F90'
        view_bg = '#7D748A'
        upload_bg = '#5F8577'
        copy_bg = '#8A7A66'
        compact_pad = (10, 6)
        unified_button_pad = (12, 7)
        unified_button_font = ('Segoe UI', 10, 'bold')

        # Style principal pour les boutons
        self.style.configure('TButton',
            background=self.colors['accent'],
            foreground='white',
            font=unified_button_font,
            padding=unified_button_pad,
            relief='flat',
            borderwidth=1)

        # Navigation / primary buttons used throughout the app
        self.style.configure('Nav.TButton',
            background=self.colors['accent'],
            foreground='white',
            font=unified_button_font,
            padding=unified_button_pad,
            relief='flat',
            borderwidth=1)

        tab_inactive_bg = '#343434' if self.mode == 'dark' else '#e7ebf0'
        tab_inactive_fg = '#d7dbe0' if self.mode == 'dark' else '#46515d'
        tab_active_bg = '#7b8794' if self.mode == 'dark' else '#c9d5e2'
        tab_active_fg = '#ffffff' if self.mode == 'dark' else '#223042'

        self.style.configure('DashboardTab.TButton',
            background=tab_inactive_bg,
            foreground=tab_inactive_fg,
            relief='flat',
            borderwidth=0,
            padding=(16, 8),
            font=('Segoe UI', 10, 'bold'))

        self.style.configure('DashboardTabActive.TButton',
            background=tab_active_bg,
            foreground=tab_active_fg,
            relief='solid',
            borderwidth=1,
            padding=(18, 9),
            font=('Segoe UI', 10, 'bold'))

        # Style pour les boutons d'action
        self.style.configure('Action.TButton',
            background=self.colors['accent_light'],
            padding=compact_pad)

        self.style.map('TButton',
            background=[('active', self.colors['accent_light']),
                       ('disabled', self.colors['disabled'])],
            foreground=[('disabled', '#ffffff')])

        # Boutons secondaires (Configuration style) — bordered, neutral background
        self.style.configure('Secondary.TButton',
            background=secondary_bg,
            foreground=secondary_fg,
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Make other logical button styles visually match Secondary by default
        try:
            self.style.configure('Nav.TButton',
                background=secondary_bg,
                foreground=secondary_fg,
                relief='solid',
                borderwidth=1,
                padding=unified_button_pad,
                font=unified_button_font)
            self.style.configure('Action.TButton',
                background=secondary_bg,
                foreground=secondary_fg,
                relief='solid',
                borderwidth=1,
                padding=unified_button_pad,
                font=unified_button_font)
            self.style.configure('Danger.TButton',
                background=secondary_bg,
                foreground=secondary_fg,
                relief='solid',
                borderwidth=1,
                padding=unified_button_pad,
                font=unified_button_font)
        except Exception:
            pass

        # Boutons de danger
        self.style.configure('Danger.TButton',
            background=cancel_bg,
            foreground='white')

        # Boutons de succès (vert doux)
        self.style.configure('Success.TButton',
            background=success_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Boutons d'annulation (rouge doux)
        self.style.configure('Cancel.TButton',
            background=cancel_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Boutons de gestion (bleu doux)
        self.style.configure('Manage.TButton',
            background=manage_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Quatre styles dédiés pour les actions de modèles (couleurs différenciées)
        self.style.configure('Refresh.TButton',
            background=refresh_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)
        self.style.configure('View.TButton',
            background=view_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)
        self.style.configure('Upload.TButton',
            background=upload_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)
        self.style.configure('Copy.TButton',
            background=copy_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Boutons de confirmation (bleu doux)
        self.style.configure('Confirm.TButton',
            background=confirm_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Boutons de fermeture (gris doux)
        self.style.configure('Close.TButton',
            background=close_bg,
            foreground='white',
            relief='solid',
            borderwidth=1,
            padding=unified_button_pad,
            font=unified_button_font)

        # Make sure focus/active mappings don't introduce a distinct blue ring
        try:
            # Keep background consistent on focus/pressed so OS focus rings are less prominent
            self.style.map('TButton',
                background=[('active', self.colors['accent_light']), ('disabled', self.colors['disabled']), ('pressed', self.colors['accent_light'])],
                foreground=[('disabled', '#ffffff')])
            self.style.map('Nav.TButton',
                background=[('active', self.colors['accent_light']), ('disabled', self.colors['disabled']), ('pressed', self.colors['accent_light'])],
                foreground=[('disabled', '#ffffff')])
            self.style.map('Secondary.TButton',
                background=[('active', '#5b5b5b' if self.mode == 'dark' else '#e7ebf0'), ('disabled', self.colors['disabled'])],
                foreground=[('disabled', self.colors['fg'])])
            self.style.map('DashboardTab.TButton',
                background=[('active', '#444444' if self.mode == 'dark' else '#dde4ec'), ('disabled', self.colors['disabled'])],
                foreground=[('disabled', '#bfc5cb' if self.mode == 'dark' else '#7a8691')])
            self.style.map('DashboardTabActive.TButton',
                background=[('active', '#8a97a5' if self.mode == 'dark' else '#d6e0ea'), ('disabled', self.colors['disabled'])],
                foreground=[('disabled', '#d8d8d8' if self.mode == 'dark' else '#8a8a8a')])
            # Success button states
            self.style.map('Success.TButton',
                background=[('active', '#5EAE88'), ('disabled', self.colors['disabled']), ('pressed', '#3F8767')],
                foreground=[('disabled', '#cccccc')])
            # Cancel button states
            self.style.map('Cancel.TButton',
                background=[('active', '#C86F62'), ('disabled', self.colors['disabled']), ('pressed', '#A35147')],
                foreground=[('disabled', '#cccccc')])
            # Manage button states
            self.style.map('Manage.TButton',
                background=[('active', '#7e8b97'), ('disabled', self.colors['disabled']), ('pressed', '#5f6b76')],
                foreground=[('disabled', '#cccccc')])
            # Confirm button states
            self.style.map('Confirm.TButton',
                background=[('active', '#87929d'), ('disabled', self.colors['disabled']), ('pressed', '#66717c')],
                foreground=[('disabled', '#cccccc')])
            # Close button states
            self.style.map('Close.TButton',
                background=[('active', '#7A8189'), ('disabled', self.colors['disabled']), ('pressed', '#565E66')],
                foreground=[('disabled', '#cccccc')])
            # Dedicated template action button states
            self.style.map('Refresh.TButton',
                background=[('active', '#7C8FA2'), ('disabled', self.colors['disabled']), ('pressed', '#596B7C')],
                foreground=[('disabled', '#cccccc')])
            self.style.map('View.TButton',
                background=[('active', '#8C829A'), ('disabled', self.colors['disabled']), ('pressed', '#695F76')],
                foreground=[('disabled', '#cccccc')])
            self.style.map('Upload.TButton',
                background=[('active', '#6E9688'), ('disabled', self.colors['disabled']), ('pressed', '#4C7164')],
                foreground=[('disabled', '#cccccc')])
            self.style.map('Copy.TButton',
                background=[('active', '#9A8A76'), ('disabled', self.colors['disabled']), ('pressed', '#786854')],
                foreground=[('disabled', '#cccccc')])
        except Exception:
            pass

    def _setup_combobox_styles(self):
        self.style.configure('TCombobox',
            background=self.colors['input_bg'],
            fieldbackground=self.colors['input_bg'],
            foreground=self.colors['fg'],
            selectbackground=self.colors['accent'],
            selectforeground='white',
            borderwidth=1,
            arrowsize=12,
            padding=(6, 4),
            relief='solid')

        # Map for different states
        self.style.map('TCombobox',
            fieldbackground=[('readonly', self.colors['input_bg']),
                           ('focus', self.colors['input_border'])],
            background=[('readonly', self.colors['input_bg']),
                       ('hover', self.colors['hover'])],
            foreground=[('readonly', self.colors['fg'])],
            arrowcolor=[('readonly', self.colors['fg'])])

    def _setup_treeview_styles(self):
        # Treeview rows slightly taller for readability and visible separators
        # Use section_bg for better contrast in dark mode
        treeview_bg = self.colors['section_bg'] if self.mode == 'dark' else self.colors['bg']
        selection_bg = '#2a2f36' if self.mode == 'dark' else '#eef2f6'
        selection_fg = self.colors['fg'] if self.mode == 'dark' else '#2a2f36'

        self.style.configure('Treeview',
            background=treeview_bg,
            fieldbackground=treeview_bg,
            foreground=self.colors['fg'],
            rowheight=28,
            borderwidth=1,
            relief='solid')

        self.style.configure('Treeview.Heading',
            background=self.colors['accent'],
            foreground='white',
            relief='solid',
            padding=(8, 4),
            font=('Segoe UI', 9, 'bold'))

        self.style.map('Treeview',
            background=[('selected', selection_bg),
                       ('alternate', self.colors['bg'])],
            foreground=[('selected', selection_fg)])

    def _setup_notebook_styles(self):
        self.style.configure('TNotebook',
            background=self.colors['bg'],
            tabmargins=[2, 5, 2, 0])

        self.style.configure('TNotebook.Tab',
            padding=[10, 5],
            font=('Segoe UI', 9))

        self.style.map('TNotebook.Tab',
            background=[('selected', self.colors['accent'])],
            foreground=[('selected', 'white')],
            expand=[('selected', [1, 1, 1, 0])])

    def _setup_frame_styles(self):
        # Default frame background
        self.style.configure('TFrame',
            background=self.colors['bg'])

        self.style.configure('Card.TFrame',
            background=self.colors['bg'],
            relief='solid',
            borderwidth=1)

    def _setup_label_styles(self):
        # Labels: ensure they inherit background and have enough padding where needed
        self.style.configure('TLabel',
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            font=('Segoe UI', 10))

        # Global title tokens used across dialogs and sections.
        self.style.configure('Title.TLabel',
            background=self.colors['bg'],
            foreground=self.colors['fg'],
            font=('Segoe UI', 16, 'bold'))

        self.style.configure('SectionTitle.TLabel',
            background=self.colors['bg'],
            foreground=self.colors['label_fg'],
            font=('Segoe UI', 12, 'bold'))

        # Make all LabelFrame captions bold by default.
        self.style.configure('TLabelframe.Label',
            background=self.colors['bg'],
            foreground=self.colors['label_fg'],
            font=('Segoe UI', 11, 'bold'))

        self.style.configure('Header.TLabel',
            font=('Segoe UI', 14, 'bold'),
            foreground=self.colors['accent'])

        self.style.configure('Subheader.TLabel',
            font=('Segoe UI', 12),
            foreground=self.colors['label_fg'])
        # Ensure checkbuttons and radiobuttons have visible selection color
        try:
            selected_bg = '#5f6974' if self.mode == 'dark' else '#c0c9d2'
            self.style.configure('TCheckbutton',
                background=self.colors['bg'],
                foreground=self.colors['fg'])
            self.style.map('TCheckbutton',
                background=[('active', self.colors['hover']), ('selected', selected_bg)],
                foreground=[('selected', 'white')])

            self.style.configure('TRadiobutton',
                background=self.colors['bg'],
                foreground=self.colors['fg'])
            self.style.map('TRadiobutton',
                background=[('active', self.colors['hover']), ('selected', selected_bg)],
                foreground=[('selected', 'white')])
        except Exception:
            # Some themes may not support mapping these states; ignore safely
            pass

    def _setup_scrollbar_styles(self):
        """Configure styles for Scrollbars"""
        scrollbar_trough = self.colors['section_bg']
        scrollbar_thumb = self.colors['accent']

        self.style.configure('TScrollbar',
            background=scrollbar_trough,
            troughcolor=scrollbar_trough,
            borderwidth=0,
            arrowcolor=self.colors['fg'],
            darkcolor=scrollbar_trough,
            lightcolor=scrollbar_trough)
