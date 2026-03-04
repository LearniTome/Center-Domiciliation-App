<!-- GitHub Copilot instructions — concise and actionable for this repo -->

# Center-Domiciliation-App — Agent Guidance

**Purpose:** Help an AI agent be productive in this Tkinter desktop app that remplits Word templates, manages Excel records, and provides a dashboard for document generation and data visualization.

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

1. **Always use centralized helpers:**
   - UI: `WidgetFactory.create_*()`, `ThemeManager` for colors
   - Paths: `PathManager.MODELS_DIR`, `PathManager.DATABASE_DIR`
   - Errors: `ErrorHandler.handle_error()`
   - Excel: `ensure_excel_db()`, `constants.excel_sheets`

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

## Testing

**Run tests:** `pytest -q` (or `pytest` for verbose)

**Key tests:**
- `tests/test_doc_generator_folder_naming.py` — validates output folder structure
- `tests/test_doc_generator_report.py` — checks JSON report format
- `tests/test_dashboard_actions.py` — tests Dashboard edit/add/delete
- `tests/test_ids_increment.py` — verifies ID auto-increment logic

**Commands (Windows with venv active):**
```bash
python main.py                # Run app
python -m pytest -q           # Run tests
python -m pytest -v           # Verbose
python -m pytest tests/test_doc_generator_folder_naming.py -v
```

## Common Tasks for Agents

### Add New Form Field
1. Add field to appropriate form class (`src/forms/societe_form.py`, etc.)
2. Update `get_values()` to include new field key
3. Add Excel header to `constants.py` if persistence needed
4. Update `.docx` template with new variable name
5. Test with `python main.py`

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

## Important Notes

- **Excel Lock:** Close Excel before running tests that write to database (Windows may lock file).
- **PDF Conversion:** Prefer `docx2pdf` (requires MS Word + Python package). Fallback: LibreOffice CLI (`soffice`).
- **Company Name Sanitization:** `render_templates()` auto-sanitizes folder names (spaces→underscore, remove special chars).
- **Preferences:** Dark mode & UI state saved to `config/preferences.json` via `ThemeManager.save_preferences()`.
- **Logging:** `app.log` in project root captures all app activity.

## Git Workflow for Agents

After making changes:
```bash
git add --all
git commit -m "chore|feat|fix: <description>"
git push origin current_branch
```
