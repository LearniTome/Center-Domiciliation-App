from tkinter import ttk
from ttkthemes import ThemedStyle

class ModernTheme:
    def __init__(self, root):
        self.root = root
        self.style = ThemedStyle(root)
        self.setup_colors()
        self.apply_theme()

    def setup_colors(self):
        """Configure le thème de couleurs modernes"""
        self.colors = {
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

    def apply_theme(self):
        """Applique le thème moderne à tous les widgets"""
        self.style.set_theme("clam")

        # Configuration générale
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
        self.style.configure('Section.TFrame',
            background=self.colors['section_bg'],
            relief='groove',
            borderwidth=1)

        self.style.configure('SectionHeader.TLabel',
            background=self.colors['section_header_bg'],
            foreground=self.colors['accent'],
            font=('Segoe UI', 11, 'bold'),
            padding=(10, 5))

        self.style.configure('FieldLabel.TLabel',
            background=self.colors['bg'],
            foreground=self.colors['label_fg'],
            font=('Segoe UI', 10),
            padding=(5, 2))

    def _setup_entry_styles(self):
        """Configure les styles pour les champs de saisie"""
        self.style.configure('TEntry',
            fieldbackground=self.colors['input_bg'],
            selectbackground=self.colors['accent'],
            selectforeground='white',
            borderwidth=1,
            relief='solid',
            padding=5)

        self.style.map('TEntry',
            fieldbackground=[('readonly', self.colors['section_bg'])],
            bordercolor=[('focus', self.colors['accent'])])

    def _setup_button_styles(self):
        # Style principal pour les boutons
        self.style.configure('TButton',
            background=self.colors['accent'],
            foreground='white',
            font=('Segoe UI', 10),
            padding=(10, 5),
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
        self.style.configure('Secondary.TButton',
            background=self.colors['border'],
            foreground=self.colors['fg'])

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
            padding=5)

    def _setup_treeview_styles(self):
        self.style.configure('Treeview',
            background=self.colors['bg'],
            fieldbackground=self.colors['bg'],
            foreground=self.colors['fg'],
            rowheight=25,
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
        self.style.configure('TFrame',
            background=self.colors['bg'])

        self.style.configure('Card.TFrame',
            background=self.colors['bg'],
            relief='solid',
            borderwidth=1)

    def _setup_label_styles(self):
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
