<!-- GitHub Copilot instructions — concise and actionable for this repo -->

# Center-Domiciliation-App — Agent Guidance

**Purpose:** Help an AI agent be productive in this Tkinter desktop app that remplits Word templates, manages Excel records, and provides a dashboard for document generation and data visualization.

**Python Version:** 3.9+ (tested on 3.10, 3.11, 3.12)

**Package Manager:** Use `uv` (fast, modern Python package manager) for installing dependencies. If `pip` needed, use `pip` as fallback.

## Architecture Overview

```
MainApp (main.py)
  ├─ MainForm (multi-page vertical navigation)
  │  ├─ SocieteForm (company info, page 1)
  │  ├─ AssocieForm (partners/associates, page 2)
  │  ├─ ContratForm (contract details, page 3)
  │  └─ DashboardView (modal: view/manage Excel data)
  ├─ GenerationSelectorDialog (modal: choose docs + templates)
  └─ ThemeManager (light/dark mode, preferences)
```

## Key Entry Points & Workflows

### 1. **Application Entry** → `main.py`
- `MainApp` (Tk root)
  - Initializes `MainForm` with multi-page navigation
  - Wires toolbar: Config, Dashboard, Generate, Save, Finish, etc.
  - `generate_documents()` → asks output format → shows `GenerationSelectorDialog`

### 2. **Document Generation Flow**
```
User clicks "Générer les documents"
  ↓
generate_documents() collects form values
  ↓
Ask output format (Word/PDF/Both)
  ↓
ShowGenerationSelectorDialog(values, format_choice)
  ├─ Auto-select templates (by keywords: SARL, Statuts, Annonce, etc.)
  ├─ Allow manual template upload/override
  └─ Call render_templates(values, templates_list, out_dir, to_pdf)
    ↓
Generate .docx & optionally .pdf
Create JSON report: tmp_out/YYYY-MM-DD_<Company>_Raport_Docs_generer_HH-MM-SS.json
```

### 3. **Data Management & Dashboard**
```
MainForm.show_dashboard()
  ↓
DashboardView (modal)
  ├─ Load Excel: databases/DataBase_domiciliation.xlsx
  ├─ Display Societes, Associes, Contrats in TreeView tables
  ├─ Edit/Add/Delete rows with auto-ID increment
  └─ Save changes back to Excel
```

## Project Structure

```
center-domiciliation-app/
├─ main.py                          # Application entry point (MainApp)
├─ src/
│  ├─ forms/                        # UI forms and dialogs
│  │  ├─ main_form.py              # Multi-page form container
│  │  ├─ societe_form.py           # Company info form
│  │  ├─ associe_form.py           # Partners/associates form
│  │  ├─ contrat_form.py           # Contract info form
│  │  ├─ generation_selector.py    # Document generation dialog
│  │  └─ dashboard_view.py         # Excel data viewer/editor
│  └─ utils/                        # Utilities and helpers
│     ├─ doc_generator.py          # Template rendering engine
│     ├─ utils.py                  # Core utilities (Theme, Widgets, Paths, etc.)
│     ├─ defaults_manager.py       # Default values persistence
│     ├─ constants.py              # Constants, headers, dropdowns
│     └─ styles.py                 # Theme definitions
├─ Models/                          # Word templates (.docx files)
├─ databases/                       # Excel database storage
├─ tests/                           # Unit and integration tests
│  └─ test_*.py                    # Test files (13 tests built-in)
├─ docs/                            # 📚 DOCUMENTATION (ORGANIZED)
│  ├─ README.md                    # Documentation index
│  ├─ guides/                      # User & developer guides
│  │  ├─ USER_GUIDE.md            # Complete user guide
│  │  ├─ DEFAULTS_MANAGEMENT.md   # Default values guide
│  │  ├─ CONTRIBUTING.md          # Contribution guidelines
│  │  ├─ TROUBLESHOOTING.md       # Troubleshooting guide
│  │  ├─ GIT_BEST_PRACTICES.md    # Git workflow guide
│  │  └─ USER_GUIDE_GENERATION_SELECTOR*.md  # Feature guides
│  ├─ architecture/                # Technical documentation
│  │  ├─ ARCHITECTURE.md          # System architecture overview
│  │  ├─ REFERENCE_DATA_IMPLEMENTATION.md
│  │  ├─ BACKUP_SYSTEM.md         # Backup system docs
│  │  ├─ database/                # Database docs
│  │  └─ testing/                 # Testing docs
│  ├─ setup/                       # Installation & configuration
│  │  ├─ SETUP.md                 # Installation guide
│  │  ├─ QUICKSTART.md            # 3-minute quick start
│  │  └─ README.md                # Setup section index
│  └─ archive/                     # Historical documentation
│     ├─ features/                # Feature versions (dark mode, etc.)
│     ├─ releases/                # Release notes (v2.1.0, etc.)
│     ├─ sessions/                # Session summaries
│     └─ [30+ archived docs]       # Old/versioned files
├─ scripts/                         # Utility scripts (.py)
├─ tmp_out/                         # Generated reports (JSON)
├─ config/                          # User preferences & defaults
│  ├─ preferences.json            # UI state, theme, window size
│  └─ defaults.json               # Application default values
├─ requirements.txt                 # Dependencies (pip format)
├─ pyproject.toml                   # Project metadata
├─ pytest.ini                       # Pytest configuration
└─ DOCUMENTATION_ORGANIZATION.md   # File organization tracking
```

## Core Files & Their Roles

| File | Purpose |
|------|---------|
| `main.py` | MainApp: window setup, toolbar, generate_documents(), collect_values(), form navigation |
| `src/forms/main_form.py` | MainForm: multi-page container, page switching, save/finish logic |
| `src/forms/societe_form.py` | SocieteForm: company name, legal form, ICE, capital, address, court |
| `src/forms/associe_form.py` | AssocieForm: partners grid, civility/name/nationality/CIN, management roles |
| `src/forms/contrat_form.py` | ContratForm: contract dates, domiciliation period, pricing |
| `src/forms/generation_selector.py` | GenerationSelectorDialog: template selection & generation orchestration |
| `src/forms/dashboard_view.py` | DashboardView: Excel table editor (Societes, Associes, Contrats) |
| `src/utils/doc_generator.py` | render_templates(): docx rendering, PDF conversion, report generation |
| `src/utils/utils.py` | ThemeManager, WidgetFactory, PathManager, WindowManager, ErrorHandler, ensure_excel_db |
| `src/utils/constants.py` | excel_sheets, headers, dropdowns (DenSte, Civility, Formjur, etc.) |
| `src/utils/styles.py` | ModernTheme: dark mode colors & ttk style definitions |
| `src/utils/defaults_manager.py` | DefaultsManager: persistent default values storage (JSON) |

## 📚 Documentation Organization

The documentation is well organized in the `docs/` directory:

### Navigation by Purpose

| Purpose | Location | Files |
|---------|----------|-------|
| **New to project** | `docs/setup/` | QUICKSTART.md, SETUP.md |
| **Using the app** | `docs/guides/` | USER_GUIDE.md, DEFAULTS_MANAGEMENT.md |
| **Contributing code** | `docs/guides/` | CONTRIBUTING.md, GIT_BEST_PRACTICES.md |
| **Troubleshooting** | `docs/guides/` | TROUBLESHOOTING.md |
| **Understanding code** | `docs/architecture/` | ARCHITECTURE.md, API.md |
| **Technical details** | `docs/architecture/` | DATABASE.md, BACKUP_SYSTEM.md, REFERENCE_DATA_IMPLEMENTATION.md |
| **Old/historical info** | `docs/archive/` | features/, releases/, sessions/ subdirectories |

### Key Documentation Files

- **[docs/README.md](../docs/README.md)** - Main documentation index with full table of contents
- **[docs/setup/QUICKSTART.md](../docs/setup/QUICKSTART.md)** - 3-minute quick start guide
- **[docs/guides/CONTRIBUTING.md](../docs/guides/CONTRIBUTING.md)** - How to contribute, conventions, Git workflow
- **[docs/guides/DEFAULTS_MANAGEMENT.md](../docs/guides/DEFAULTS_MANAGEMENT.md)** - Managing default values
- **[docs/guides/TROUBLESHOOTING.md](../docs/guides/TROUBLESHOOTING.md)** - Common issues and solutions
- **[docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md)** - System architecture overview with diagrams

### File Organization

The project root is clean with only 3 markdown files:
- `README.md` - Project overview (links to docs/)
- `CHANGELOG.md` - Version history
- `DOCUMENTATION_ORGANIZATION.md` - File organization tracking

All other documentation is organized in `docs/`:
- `docs/guides/` - 8 files (user guides, contribution, troubleshooting, best practices)
- `docs/architecture/` - 5+ files (architecture, database, backup, testing docs)
- `docs/setup/` - 3 files (installation, quickstart, environment)
- `docs/archive/` - 30+ files in subdirectories:
  - `features/` - Feature documentation versions (dark mode, generation selector)
  - `releases/` - Release notes and version information
  - `sessions/` - Session summaries and work logs

## Forms API

All forms inherit from `ttk.Frame` and implement `get_values()` → nested dict:

```python
MainForm.get_values() → {
  'societe': {'DenSte': str, 'FormJur': str, ...},
  'associes': [{'Prenom': str, 'Nom': str, ...}, ...],
  'contrat': {'DateContrat': str, 'PeriodDomcil': str, ...}
}
```

Pass this directly to `render_templates(values, ...)`.

## Key Dependencies

**Core packages:**
- `tkinter` — GUI framework (built-in Python)
- `docxtpl` — Jinja2-based Word template rendering
- `openpyxl` — Excel spreadsheet manipulation
- `pandas` — Data analysis and Excel I/O

**Optional (for PDF conversion):**
- `docx2pdf` — Windows/MS Word-based conversion (preferred on Windows)
- LibreOffice CLI (`soffice`) — Cross-platform fallback

**Development:**
- `pytest` — Test framework
- `black`, `pylint` — Code formatting/linting (optional)

**Installation with UV:**
```bash
# Install all dependencies
uv pip install -r requirements.txt

# Install specific package
uv pip install docxtpl

# Add to requirements.txt and lock
uv pip install --upgrade -r requirements.txt
```

**Installation with pip (fallback):**
```bash
pip install -r requirements.txt
```

## Document Generation API

**Function:** `render_templates(values, templates_dir=None, out_dir=None, to_pdf=False, templates_list=None, progress_callback=None)`

**Parameters:**
- `values`: dict from `MainForm.get_values()`
- `templates_dir`: folder with .docx files (auto-scan if `templates_list=None`)
- `templates_list`: explicit list of template Path objects
- `out_dir`: output directory (required)
- `to_pdf`: if True, convert docx→pdf (requires docx2pdf or LibreOffice)
- `progress_callback`: optional callback `(current, total, template_name, doc_dict) → None`

**Returns:** list of doc dicts: `[{'template': str, 'out_docx': str, 'out_pdf': str|None, 'status': 'ok'|'error', 'error': str|None}, ...]`

**Output Folder Structure:**
```
out_dir/
  └─ YYYY-MM-DD_<Company>_Constitution/
     ├─ template1_filled.docx
     ├─ template1_filled.pdf (if to_pdf=True)
     └─ template2_filled.docx
```

**Report:** `tmp_out/YYYY-MM-DD_<Company>_Raport_Docs_generer_HH-MM-SS.json`

## Database & Excel

**Location:** `databases/DataBase_domiciliation.xlsx`

**Sheets & Headers:** (from `src/utils/constants.py`)
- `Societes`: ID_SOCIETE, DEN_STE, FORME_JUR, ICE, DATE_ICE, CAPITAL, PART_SOCIAL, STE_ADRESS, TRIBUNAL
- `Associes`: ID_ASSOCIE, ID_SOCIETE, CIVIL, PRENOM, NOM, NATIONALITY, CIN_NUM, CIN_VALIDATY, DATE_NAISS, LIEU_NAISS, ADRESSE, PHONE, EMAIL, PARTS, CAPITAL_DETENU, IS_GERANT, QUALITY
- `Contrats`: ID_CONTRAT, ID_SOCIETE, DATE_CONTRAT, PERIOD_DOMCIL, PRIX_CONTRAT, PRIX_INTERMEDIARE_CONTRAT, DOM_DATEDEB, DOM_DATEFIN
- Reference sheets: `SteAdresses`, `Tribunaux`, `Activites`, `Nationalites`, `LieuxNaissance`

**Key Helper:** `ensure_excel_db()` in `src/utils/utils.py` — idempotently creates/validates database schema.

## Template Rendering with Jinja2

Templates use **Jinja2 syntax** via `docxtpl`. Keys match form `get_values()` results:

```jinja2
{# Simple variable substitution #}
Company: {{ societe.DenSte }}
Legal Form: {{ societe.FormJur }}

{# Conditional blocks #}
{% if societe.FormJur == 'SARL AU' %}
Unique manager company
{% else %}
Multiple partners
{% endif %}

{# Loops for associates #}
{% for associate in associes %}
  Partner: {{ associate.Prenom }} {{ associate.Nom }}
  CIN: {{ associate.CIN_NUM }}
  is_manager: {{ associate.IS_GERANT }}
{% endfor %}

{# Date formatting #}
Contract Date: {{ contrat.DateContrat | strftime('%d/%m/%Y') }}

{# Table rendering (inside table cells) #}
{# docxtpl handles table rows automatically with loops #}
```

**Key points:**
- Templates are `.docx` files created in MS Word/LibreOffice
- Variables wrapped in `{{ }}` are replaced with form values
- Use `|` filters for formatting (e.g., `strftime`, `upper`, `lower`)
- Loops must be inside table rows for proper formatting
- Test template variables with `python -c "from src.forms.main_form import MainForm; print(MainForm(...).get_values())"`

## UI Patterns & Utilities

### ThemeManager
```python
ThemeManager(root_window)
  .colors = {'bg': '#2b2b2b', 'fg': '#ffffff', ...}  # dark/light mode
  .style = ttk.Style()  # configured with colors
  .toggle_dark_mode()
  .save_preferences()
```

### WidgetFactory
```python
WidgetFactory.create_button(parent, text="...", command=fn, style='Secondary.TButton')
  # Styles: 'Success.TButton', 'Cancel.TButton', 'Confirm.TButton', 'Close.TButton', 'Secondary.TButton'
WidgetFactory.create_entry(parent, placeholder="...")
WidgetFactory.create_combobox(parent, values=[...])
```

### PathManager
```python
PathManager.MODELS_DIR      # templates folder
PathManager.DATABASE_DIR    # databases folder
PathManager.CONFIG_DIR      # config folder
PathManager.PROJECT_ROOT    # repo root
```

### ErrorHandler
```python
ErrorHandler.handle_error(exception, "User-facing message", show_dialog=True, callback=None)
  # Logs + shows messagebox + calls callback
```

### WindowManager
```python
WindowManager.setup_window(root, title)  # configures window size, icon, position
WindowManager.center_window(window)      # centers on screen
```

## Code Patterns to Follow

1. **Always use centralized helpers and error handling:**
   - UI: `WidgetFactory.create_*()`, `ThemeManager` for colors
   - Paths: `PathManager.MODELS_DIR`, `PathManager.DATABASE_DIR`
   - Errors: `ErrorHandler.handle_error()` — logs AND shows user dialog
   - Excel: `ensure_excel_db()`, `constants.excel_sheets`
   - **Example:** Prefer `ErrorHandler.handle_error(e, "Operation failed")` over try/except + passing

2. **Form Updates:**
   - Modify field names in `get_values()` → update templates (`.docx` variables)
   - Update Excel headers → modify `constants.py` `excel_sheets` dict
   - Add new dropdown values → update `constants.py` lists (e.g., `DenSte`, `Civility`)

3. **Template Variables:**
   - Extract company name in context (e.g., `values['societe']['DenSte']`)
   - Use nested dict keys matching form structure: `{{ societe.DenSte }}`, `{% for associate in associes %}...{% endfor %}`
   - Test rendering with sample `values` dict

4. **Threading for Long Operations:**
   - Document generation runs in optional background thread (see `GenerationSelectorDialog._threaded_generate()`)
   - Use `progress_callback` to update UI during generation
   - **Never block UI thread** — use `threading.Thread()` for file I/O or PDF conversion

5. **Error Handling Pattern:**
   ```python
   try:
       result = risky_operation()
   except Exception as e:
       ErrorHandler.handle_error(e, "Operation failed, please check logs", show_dialog=True)
       return None  # or appropriate fallback
   ```

6. **Performance Optimization:**
   - Cache `ThemeManager`, `PathManager` instances — don't recreate per widget
   - Use `lazy_load` for large Excel files (split into sheets)
   - Batch Excel writes (don't write after every row edit — batch at window close)
   - Use `progress_callback` for long document generation (shows user progress)

## Testing

**Run tests:** `pytest -q` (or `pytest` for verbose)

**Key tests:**
- `tests/test_doc_generator_folder_naming.py` — validates output folder structure
- `tests/test_doc_generator_report.py` — checks JSON report format
- `tests/test_dashboard_actions.py` — tests Dashboard edit/add/delete
- `tests/test_ids_increment.py` — verifies ID auto-increment logic

**Commands (Windows with uv/venv active):**
```bash
# With UV (recommended)
uv run python main.py                              # Run app
uv run python -m pytest -q                        # Run tests
uv run python -m pytest -v                        # Verbose
uv run python -m pytest tests/test_doc_*.py -v   # Run doc generator tests

# With venv (fallback)
python main.py                # Run app
python -m pytest -q           # Run tests
python -m pytest -v           # Verbose
```

**Test coverage:**
```bash
uv run python -m pytest --cov=src --cov=main tests/
```

## Debugging Guide

### 1. **Template Rendering Errors**
- **Problem:** `KeyError: 'societe.DenSte'`
- **Cause:** Form doesn't include this field or key is misnamed
- **Fix:** Check `get_values()` output matches template variables exactly
```python
# In Python REPL:
from main import MainApp
app = MainApp()
app.collect_values()
print(app.values)  # Verify all keys match template {{ }} placeholders
```

### 2. **PDF Conversion Fails**
- **Problem:** `.docx` generated but no `.pdf`
- **Cause:** `docx2pdf` or LibreOffice not available
- **Fix:** Check log `app.log` for detailed error; install LibreOffice or `docx2pdf` package
```bash
# Windows: Install docx2pdf (requires MS Word)
uv pip install docx2pdf

# Cross-platform: Install LibreOffice
# Then verify soffice is in PATH
where soffice  # Windows
which soffice  # Linux/Mac
```

### 3. **Dashboard Won't Load**
- **Problem:** `DashboardView` shows no data or crashes
- **Cause:** Excel file locked or schema mismatch
- **Fix:** 
  - Close Excel completely (check Task Manager)
  - Delete `databases/DataBase_domiciliation.xlsx` — app will recreate it
  - Run `python main.py` to reinitialize

### 4. **Form Fields Not Saving**
- **Problem:** Values collect but don't appear in generated docs
- **Cause:** Form's `get_values()` doesn't include the field
- **Fix:** 
  - Add the field to form UI
  - Update `get_values()` dict with the key
  - Restart app and verify with `print(app.values)`

### 5. **Dark Mode Not Applying**
- **Problem:** UI colors wrong or unreadable
- **Cause:** Theme not initialized or `config/preferences.json` corrupted
- **Fix:** Delete `config/preferences.json` and restart app

**Logging:**
All issues are logged to `app.log` in project root with full tracebacks. Always check this file first.

## Common Tasks for Agents

### Add New Form Field
1. Add field to appropriate form class (`src/forms/societe_form.py`, etc.)
   ```python
   self.new_field = WidgetFactory.create_entry(parent, placeholder="...")
   ```
2. Update `get_values()` to include new field key
   ```python
   values_dict['societe']['new_field'] = self.new_field.get()
   ```
3. Add Excel header to `constants.py` if persistence needed
   ```python
   societe_headers.append('NEW_FIELD')
   ```
4. Update `.docx` template with new variable name: `{{ societe.new_field }}`
5. Test with `python main.py` and verify in generated docs

### Modify Excel Schema
1. Edit `src/utils/constants.py` → update `excel_sheets` dict
2. Add helper to migrate existing data (if backward compat needed)
3. Update `DashboardView` to display new columns
4. Run `ensure_excel_db()` to validate

### Add New Document Type/Template Filter
1. Update `CREATION_TEMPLATES_KEYWORDS` or `DOMICILIATION_TEMPLATES` in `generation_selector.py`
2. Test auto-selection in `GenerationSelectorDialog`
3. Ensure templates are in `Models/` folder

### Debug Generation Report
1. Open `tmp_out/YYYY-MM-DD_<Company>_Raport_*_HH-MM-SS.json`
2. Check `status` field for each template: `'ok'` vs `'error'`
3. If error: check `error` field for details (missing vars, conversion failure, etc.)
4. Look for patterns:
   - Missing variables → check template has correct `{{ key }}` syntax
   - PDF conversion error → verify LibreOffice/docx2pdf installed
   - File not found → verify template path in `Models/` folder

## Important Notes

- **Documentation:** All documentation is organized in `docs/` directory. Root contains only README.md, CHANGELOG.md, and DOCUMENTATION_ORGANIZATION.md. See [📚 Documentation Organization](#-documentation-organization) section above.
- **Excel Lock:** Close Excel before running tests that write to database (Windows may lock file).
- **PDF Conversion:** Prefer `docx2pdf` (requires MS Word + Python package). Fallback: LibreOffice CLI (`soffice`).
- **Company Name Sanitization:** `render_templates()` auto-sanitizes folder names (spaces→underscore, remove special chars).
- **Preferences:** Dark mode & UI state saved to `config/preferences.json` via `ThemeManager.save_preferences()`.
- **Defaults:** Application defaults saved to `config/defaults.json` via `DefaultsManager` singleton.
- **Logging:** `app.log` in project root captures all app activity.
- **External Dependencies:** Ensure LibreOffice or MS Word installed if PDF conversion required.
- **Jinja2 Filters:** docxtpl supports standard Jinja2 filters; custom filters can be added in `render_templates()` context.
- **venv Setup:** Project includes venv; activate with `venv\Scripts\Activate.ps1` (Windows PowerShell).

## Setup & Environment

### Initial Setup (Windows)

**Option 1: With UV (recommended)**
```bash
# Install UV
pip install uv

# Create virtual environment
uv venv

# Activate
.\\venv\\Scripts\\Activate.ps1

# Install dependencies
uv pip install -r requirements.txt

# Run app
python main.py
```

**Option 2: Traditional venv**
```bash
# Create virtual environment
python -m venv venv

# Activate
.\\venv\\Scripts\\Activate.ps1

# Install dependencies
pip install -r requirements.txt

# Run app
python main.py
```

### Running in CI/CD or Containers

```dockerfile
FROM python:3.11-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
CMD ["python", "main.py"]
```

## Git Workflow for Agents

After making changes:
```bash
git add --all
git commit -m "chore|feat|fix: <description>"
git push origin current_branch
```

## File Organization (Updated March 2026)

The project has been professionally reorganized for better maintainability:

### Tests Directory
- All `test_*.py` files are now in `tests/` directory
- Original test files moved from root: test_dark_mode.py, test_dashboard_final.py, test_dashboard_headless.py, test_generation_selector.py, test_keywords.py, test_selector_simple.py
- 19 total test files (13 core tests + 6 migration tests)

### Documentation Structure (docs/)
- **docs/guides/** - User and developer guides (8 files)
  - USER_GUIDE.md, DEFAULTS_MANAGEMENT.md, CONTRIBUTING.md, TROUBLESHOOTING.md, GIT_BEST_PRACTICES.md
- **docs/architecture/** - Technical documentation (5+ files)
  - ARCHITECTURE.md, REFERENCE_DATA_IMPLEMENTATION.md, BACKUP_SYSTEM.md, database/, testing/ subdirectories
- **docs/setup/** - Installation and configuration (3 files)
  - SETUP.md, QUICKSTART.md (3-minute guide), ENVIRONMENT.md
- **docs/archive/** - Historical documentation (30+ files)
  - features/ (dark mode, generation selector versions)
  - releases/ (release notes)
  - sessions/ (session summaries)
  - Other archived files (.txt, .md versions)

### Cleaned Root Directory
The project root is now clean and professional:
- Only 3 markdown files: README.md, CHANGELOG.md, DOCUMENTATION_ORGANIZATION.md
- Empty directories (documentations/, refrences/) removed
- All `.md` files organized into docs/ structure
- All utility `.py` test files organized into tests/

### Migration Details
- **Total files migrated:** 45
- **Files moved to tests/:** 6 (.py files)
- **Files moved to docs/guides/:** 3 (.md files)
- **Files moved to docs/architecture/:** 3 (.md files)
- **Files moved to docs/archive/:** 30+ (.md and .txt files)
- **Subdirectories created:** 8 (features/, releases/, sessions/, database/, testing/)

### For New Agents or Contributors
- **First time?** Start with [docs/setup/QUICKSTART.md](../docs/setup/QUICKSTART.md)
- **Want to contribute?** Read [docs/guides/CONTRIBUTING.md](../docs/guides/CONTRIBUTING.md)
- **Have a problem?** Check [docs/guides/TROUBLESHOOTING.md](../docs/guides/TROUBLESHOOTING.md)
- **Understand architecture?** Read [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md)
- **Looking for old docs?** Browse [docs/archive/](../docs/archive/)

