# 🏗️ Architecture Générale

Vue d'ensemble de l'architecture complète de l'application **Center-Domiciliation-App**.

## 📊 Vue de Haut Niveau

```
┌─────────────────────────────────────────────────────────────┐
│                      USER INTERFACE (Tkinter)               │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                    MainApp (Root Window)              │  │
│  │  ┌──────────────────────────────────────────────────┐ │  │
│  │  │         MainForm (Multi-page Navigation)        │ │  │
│  │  │  ┌─────────┬──────────┬──────────┐              │ │  │
│  │  │  │Page 1:  │ Page 2:  │ Page 3:  │              │ │  │
│  │  │  │Societe  │ Associe  │ Contrat  │              │ │  │
│  │  │  └─────────┴──────────┴──────────┘              │ │  │
│  │  │                                                  │ │  │
│  │  │  [Toolbar: Config | Dashboard | Generate | ...] │ │  │
│  │  └──────────────────────────────────────────────────┘ │  │
│  │                                                         │  │
│  │  [GenerationSelectorDialog] [DashboardView Modal]      │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────┐  ┌──────────────────┐               │
│  │  ThemeManager    │  │ WindowManager    │               │
│  │  (Dark/Light)    │  │ (Size/Position)  │               │
│  └──────────────────┘  └──────────────────┘               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC & UTILS                   │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ DefaultsManager  │  │ PathManager      │                │
│  │ (Persistence)    │  │ (File Paths)     │                │
│  └──────────────────┘  └──────────────────┘                │
│                                                             │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ WidgetFactory    │  │ ErrorHandler     │                │
│  │ (UI Components)  │  │ (Errors & Logs)  │                │
│  └──────────────────┘  └──────────────────┘                │
│                                                             │
│  ┌──────────────────────────────────────┐                 │
│  │    doc_generator.py                  │                 │
│  │    - Jinja2 Rendering                │                 │
│  │    - PDF Conversion                  │                 │
│  │    - Report Generation               │                 │
│  └──────────────────────────────────────┘                 │
│                                                             │
│  Constants & Styles (Dropdowns, Colors, Config)            │
└─────────────────────────────────────────────────────────────┘
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                      DATA LAYER                             │
│  ┌────────────────────┐  ┌──────────────────────┐           │
│  │ Excel Database     │  │ JSON Config Files    │           │
│  │ - Societes Sheet   │  │ - preferences.json   │           │
│  │ - Associes Sheet   │  │ - defaults.json      │           │
│  │ - Contrats Sheet   │  │                      │           │
│  │ - References       │  │ JSON Reports         │           │
│  └────────────────────┘  │ - generation report  │           │
│                          └──────────────────────┘           │
│                                                             │
│  Word Templates (Jinja2 Format)                             │
│  Output: Generated DOCX/PDF Files                           │
└─────────────────────────────────────────────────────────────┘
```

## 🔄 Flux de Données Principaux

### 1. Génération de Documents

```
User Input (Form)
    ↓
collect_values() → Nested Dict {societe, associes, contrat}
    ↓
GenerationSelectorDialog (Template Selection)
    ↓
render_templates(values, templates, output_dir, to_pdf)
    ├─ Load Word templates from Models/
    ├─ Replace {{ variables }} with values
    ├─ Generate DOCX files
    ├─ (Optional) Convert to PDF
    └─ Create JSON report
    ↓
Output Folder Structure:
    YYYY-MM-DD_CompanyName_Constitution/
    ├─ template1_filled.docx
    ├─ template1_filled.pdf (if to_pdf=True)
    └─ template2_filled.docx
```

### 2. Gestion des Données

```
User Opens Dashboard
    ↓
DashboardView (Modal)
    ├─ Reads: DataBase_domiciliation.xlsx
    ├─ Displays: Societes, Associes, Contrats tabs
    └─ Supports: Add, Edit, Delete rows
    ↓
User Makes Changes
    ↓
Save to Excel
    ├─ Update Excel file with new values
    ├─ Auto-increment IDs
    └─ Validate schema
```

### 3. Configuration et Préférences

```
User Click Configuration
    ↓
open_configuration() Dialog
    ├─ Display tabs: Entreprise, Associé, Contrat
    └─ Load from defaults.json
    ↓
User Modifies Values
    ↓
Save to defaults.json
    ↓
Forms Are Updated
    ├─ SocieteForm loads new defaults
    ├─ AssocieForm loads new defaults
    └─ ContratForm loads new defaults
```

## 📁 Structure des Répertoires

### Racine du Projet

```
center-domiciliation-app/
├── main.py                    # Entry point - MainApp class
├── requirements.txt           # Pip dependencies
├── pyproject.toml            # Project metadata
├── pytest.ini                # Pytest configuration
├── CHANGELOG.md              # Version history
└── README.md                 # Main documentation
```

### src/ - Code Source

```
src/
├── __init__.py               # Package marker
├── forms/                    # UI Forms
│   ├── __init__.py
│   ├── main_form.py          # Multi-page form container
│   ├── societe_form.py       # Page 1: Company info
│   ├── associe_form.py       # Page 2: Associates
│   ├── contrat_form.py       # Page 3: Contract
│   ├── generation_selector.py # Document generation dialog
│   └── dashboard_view.py     # Excel data viewer/editor
└── utils/                    # Utilities & Helpers
    ├── __init__.py
    ├── utils.py              # Core utilities (Managers & Handlers)
    ├── doc_generator.py      # Template rendering engine
    ├── defaults_manager.py    # Default values persistence
    ├── constants.py          # Constants & dropdowns
    └── styles.py             # Theme definitions
```

### Other Directories

```
Models/                       # Word templates (.docx files)
├── Template1.docx
├── Template2.docx
└── ...

databases/                    # Excel data storage
└── DataBase_domiciliation.xlsx
   ├── Societes sheet
   ├── Associes sheet
   ├── Contrats sheet
   └── Reference sheets

config/                       # User preferences & defaults
├── preferences.json          # UI state, theme, window size
└── defaults.json            # Application default values

tests/                        # Unit & integration tests
├── conftest.py              # Test configuration
├── test_*.py                # Test files
└── __pycache__/

tmp_out/                      # Generated reports (JSON)
└── YYYY-MM-DD_*_Report.json

docs/                         # Documentation
├── guides/                   # User & Dev guides
├── architecture/             # Technical docs
├── setup/                    # Installation & setup
└── archive/                  # Archived docs
```

## 🏛️ Des Composants Principaux

### 1. MainApp (main.py)

**Responsibilité:** Application root window and main orchestration

**Key Methods:**
- `__init__()` - Initialize window, theme, forms
- `collect_values()` - Gather all form values
- `generate_documents()` - Start generation workflow
- `save_application_state()` - Save preferences

**Dependencies:**
- ThemeManager, WindowManager, WidgetFactory
- MainForm, GenerationSelectorDialog
- ErrorHandler, PathManager

### 2. MainForm (src/forms/main_form.py)

**Responsibilité:** Multi-page form navigation and coordination

**Key Methods:**
- `show_page(page_n)` - Switch between pages
- `get_values()` - Collect values from all pages
- `open_configuration()` - Open defaults dialog
- `show_dashboard()` - Open Excel viewer modal

**Pages:**
- Page 1: SocieteForm (company info)
- Page 2: AssocieForm (associates grid)
- Page 3: ContratForm (contract details)

### 3. DefaultsManager (src/utils/defaults_manager.py)

**Responsibilité:** Centralized default values management with persistence

**Key Methods:**
- `get_default(section, key)` - Get a default value
- `set_default(section, key, value)` - Set a default value
- `get_all_defaults()` - Get all defaults dict
- `reset_to_initial()` - Reset to initial values
- `load()` - Load from JSON file
- `save()` - Save to JSON file

**Storage:** `config/defaults.json`

### 4. doc_generator.py

**Responsibilité:** Template rendering and document generation

**Key Functions:**
- `render_templates(values, ...)` - Main function
  - Scans template directory
  - Renders DOCX with Jinja2
  - Optionally converts to PDF
  - Creates JSON report

**Output Structure:**
```
tmp_out/YYYY-MM-DD_CompanyName_Constitution/
├── template1_filled.docx
├── template1_filled.pdf (if to_pdf=True)
└── ...
```

### 5. DashboardView (src/forms/dashboard_view.py)

**Responsibilité:** Excel data visualization and editing

**Features:**
- Display Societes, Associes, Contrats in tabs
- TreeView tables with scrolling
- Add/Edit/Delete rows with validation
- Auto-increment IDs
- Save changes back to Excel

### 6. ThemeManager (src/utils/utils.py)

**Responsibilité:** Dark/Light mode management and consistency

**Key Methods:**
- `toggle_dark_mode()` - Switch themes
- `apply_theme()` - Apply to widgets
- `save_preferences()` - Persist theme choice

**Colors:** Defined in `src/utils/styles.py`

## 🔌 Interfaces et Conventions

### Form Interface

All forms inherit from `ttk.Frame` and implement:

```python
class CustomForm(ttk.Frame):
    def get_values(self) -> dict:
        """Return form data as nested dict."""
        return {
            'field1': self.field1.get(),
            'field2': self.field2.get(),
        }
```

### Data Structure Pattern

Forms return nested dicts matching spreadsheet structure:

```python
{
    'societe': {
        'DenSte': str,
        'FormJur': str,
        'Capital': str,
        'PartsSocial': str,
        'SteAdresse': str,
        'Tribunal': str,
    },
    'associes': [
        {
            'Prenom': str,
            'Nom': str,
            'Civility': str,
            'Nationality': str,
            'CIN_NUM': str,
            'IS_GERANT': bool,
            'Quality': str,
        },
        # ... more associates
    ],
    'contrat': {
        'DateContrat': str,
        'PeriodDomcil': str,
        'NbMois': str,
        'PrixContrat': str,
        # ... other fields
    }
}
```

## 🔐 Error Handling

All errors use centralized `ErrorHandler`:

```python
try:
    result = risky_operation()
except Exception as e:
    ErrorHandler.handle_error(
        e, 
        "User-friendly message",
        show_dialog=True,
        callback=optional_function
    )
```

**Features:**
- Logs to `app.log`
- Shows user messagebox
- Calls optional callback
- Preserves stack trace

## 📊 Data Flow Summary

```
┌────────────────────┐
│  User Interaction  │
└────────┬───────────┘
         ↓
┌────────────────────────────┐
│  Form.get_values()         │
│  Returns nested dict       │
└────────┬───────────────────┘
         ↓
┌────────────────────────────┐
│  Document Generation       │
│  or Data Management        │
└────────┬───────────────────┘
         ↓
┌────────────────────────────┐
│  Data Persistence          │
│  Excel, JSON, DOCX files   │
└────────────────────────────┘
```

## 🔍 Module Dependencies

### High-Level Dependencies

```
main.py
├── src/forms/main_form.py
│   ├── src/forms/societe_form.py
│   ├── src/forms/associe_form.py
│   ├── src/forms/contrat_form.py
│   ├── src/forms/dashboard_view.py
│   └── src/forms/generation_selector.py
│       └── src/utils/doc_generator.py
│
└── src/utils/utils.py
    ├── ThemeManager
    ├── WindowManager
    ├── WidgetFactory
    ├── ErrorHandler
    └── PathManager
```

## 🎯 Design Patterns Used

1. **Singleton Pattern**: ThemeManager, DefaultsManager
2. **Factory Pattern**: WidgetFactory
3. **Observer Pattern**: Theme changes notify UI
4. **MVC Pattern**: Forms (View) → get_values() (Controller) → Data (Model)
5. **Template Method**: Form.get_values() in all form classes

## 📈 Scalability Considerations

### Current Architecture Supports

- **Multiple Forms**: Easy to add new form pages
- **New Templates**: Auto-scanned from Models/ directory
- **Database Expansion**: Added sheets work automatically
- **Theme Customization**: CentralizedStyles definition
- **Error Handling**: Centralized logging and messages

### Future Extensibility

- Multi-language support (already designed for i18n)
- Custom field validation
- Template versioning
- User roles & permissions
- Cloud data sync

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026

Pour Plus de Détails, Voir [API.md](API.md)
