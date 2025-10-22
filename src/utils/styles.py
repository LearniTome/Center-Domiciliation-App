from tkinter import ttk
from ttkthemes import ThemedStyle

class ModernTheme:
    def __init__(self, root, mode: str = 'light'):
        self.root = root
        self.style = ThemedStyle(root)
        self.mode = mode if mode in ('light', 'dark') else 'light'
        self.setup_colors()
        self.apply_theme()

    def setup_colors(self):
        """Configure le thème de couleurs modernes pour light et dark"""
        PALETTE_LIGHT = {
            'bg': '#ffffff',
            'fg': '#333333',
            'accent': '#2171cd',
            'accent_light': '#4a90e2',
            'error': '#dc3545',
            'success': '#28a745',
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
            'accent': '#4a90e2',
            'accent_light': '#6fb3ff',
            'error': '#ff6b6b',
            'success': '#28a745',
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
        self.style.set_theme("clam")

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
        # Style principal pour les boutons
        # Buttons: keep consistent padding and ensure foreground on dark themes
        self.style.configure('TButton',
            background=self.colors['accent'],
            foreground='white',
            font=('Segoe UI', 10),
            padding=(10, 6),
            relief='flat',
            borderwidth=0)

        # Style pour les boutons d'action
        self.style.configure('Action.TButton',
            background=self.colors['accent_light'],
            padding=(15, 8))

        self.style.map('TButton',
            background=[('active', self.colors['accent_light']),
                       ('disabled', self.colors['disabled'])],
            foreground=[('disabled', '#ffffff')])

        # Boutons secondaires
        # Secondary buttons should have clear border and contrast
        self.style.configure('Secondary.TButton',
            background=self.colors['border'],
            foreground=self.colors['fg'],
            relief='solid',
            borderwidth=1,
            padding=(8, 6))

        # Boutons de danger
        self.style.configure('Danger.TButton',
            background=self.colors['error'],
            foreground='white')

    def _setup_combobox_styles(self):
        self.style.configure('TCombobox',
            background=self.colors['input_bg'],
            fieldbackground=self.colors['input_bg'],
            selectbackground=self.colors['accent'],
            selectforeground=self.colors['fg'],
            borderwidth=1,
            arrowsize=12,
            padding=(6, 4))

    def _setup_treeview_styles(self):
        # Treeview rows slightly taller for readability and visible separators
        self.style.configure('Treeview',
            background=self.colors['bg'],
            fieldbackground=self.colors['bg'],
            foreground=self.colors['fg'],
            rowheight=28,
            borderwidth=1)

        self.style.configure('Treeview.Heading',
            background=self.colors['accent'],
            foreground='white',
            relief='flat',
            font=('Segoe UI', 9, 'bold'))

        self.style.map('Treeview',
            background=[('selected', self.colors['accent'])],
            foreground=[('selected', 'white')])

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

        self.style.configure('Header.TLabel',
            font=('Segoe UI', 14, 'bold'),
            foreground=self.colors['accent'])

        self.style.configure('Subheader.TLabel',
            font=('Segoe UI', 12),
            foreground=self.colors['label_fg'])
        # Ensure checkbuttons and radiobuttons have visible selection color
        try:
            self.style.configure('TCheckbutton',
                background=self.colors['bg'],
                foreground=self.colors['fg'])
            self.style.map('TCheckbutton',
                background=[('active', self.colors['hover']), ('selected', self.colors['accent'])],
                foreground=[('selected', 'white')])

            self.style.configure('TRadiobutton',
                background=self.colors['bg'],
                foreground=self.colors['fg'])
            self.style.map('TRadiobutton',
                background=[('active', self.colors['hover']), ('selected', self.colors['accent'])],
                foreground=[('selected', 'white')])
        except Exception:
            # Some themes may not support mapping these states; ignore safely
            pass
