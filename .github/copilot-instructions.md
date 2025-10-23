<!-- GitHub Copilot instructions — concise and actionable for this repo -->

# Center-Domiciliation-App — Agent guidance (short)

Purpose: help an AI agent be productive quickly in this Tkinter desktop app that fills Word templates and stores records in Excel.

Key facts (why/read these files):

- Entrypoint: `main.py` — creates `MainApp`, wires `MainForm`, and calls `src.utils.doc_generator.render_templates` for document generation.
- UI code: `src/forms/` — `main_form.py`, `societe_form.py`, `associe_form.py`, `contrat_form.py` show how fields are grouped, default values, and how `get_values()` shapes data.
- Document generation: `src/utils/doc_generator.py` — renders `.docx` with `docxtpl`, optional PDF conversion (docx2pdf or LibreOffice), and creates an output folder named `<YYYY-MM-DD>_<Company>_Constitution`.
- Paths & helpers: `src/utils/utils.py` — `PathManager`, `ThemeManager`, `WidgetFactory`, `WindowManager`, and `ErrorHandler` centralize patterns used everywhere.

Developer workflows & commands (verified from repo):

- Run app (with venv active): `python main.py`
- Run tests: `pytest -q` (project has pytest config and tests/\*).
- Create venv and install (Windows): use `requirements-windows.txt` (preferred) or `requirements.txt`.

Note for automated edits by AI agents: after making code changes, stage, commit and push them to the current branch (e.g. `git add --all && git commit -m "chore: <brief>" && git push`).

Project-specific conventions and examples:

- Data shapes: forms return nested dicts — `MainForm.get_values()` returns {
  'societe': {...}, 'associes': [ {...}, ... ], 'contrat': {...} }
  Use those exact keys when calling `render_templates(values, ...)`.
- Output folder naming: `render_templates()` sanitizes company name and prefixes files with `{YYYY-MM-DD}_{Company}_` and places them under `out_dir/{YYYY-MM-DD}_{Company}_Constitution/`. Tests expect this (see `tests/test_doc_generator_folder_naming.py`).
- Template filenames: many templates start with `My_`; the generator strips `My_` and `_filled` from stems when creating output names.
- PDF conversion: preferred tool is `docx2pdf` (Windows + Word). If missing, code attempts LibreOffice (`soffice`) fallback — ensure either is available for end-to-end PDF tests.

Patterns to follow when editing code:

- Use `ThemeManager` / `WidgetFactory` for consistent styling and buttons — forms expect theme-managed colors and styles.
- Use `PathManager` for file locations: `PathManager.MODELS_DIR`, `PathManager.DATABASE_DIR`, `PathManager.CONFIG_DIR`.
- Persist preferences via `config/preferences.json` (ThemeManager reads/writes it).
- Error handling: prefer `ErrorHandler.handle_error(exception, message)` to show user dialogs and log tracebacks.

Tests and quick checks:

- `tests/smoke_test.py` verifies main imports and basic instantiation.
- `tests/test_doc_generator_folder_naming.py` asserts output folder naming — keep `render_templates()` behavior when changing naming.
- When adding features that touch generation or file paths, run pytest locally and verify `tmp_out/generation_report.json` content shape.

Notes for agents (do not assume external network or secrets):

- The project writes/reads local Excel files (`databases/DataBase_domiciliation.xlsx`) — Windows may lock Excel files; close Excel before tests that write.
- Avoid modifying UI layout unless updating all three form modules; forms use similar patterns and share ThemeManager/WidgetFactory.
- If you change template variable names, update both the forms' `get_values()` and any docx templates in `Models/`.

Quick reference (files to inspect when making changes):

- `main.py` — app flow and user-triggered generation
- `src/utils/doc_generator.py` — generation, sanitization, PDF flow, and `generation_report.json`
- `src/utils/utils.py` — PathManager, ThemeManager, ErrorHandler, WidgetFactory
- `src/forms/*.py` — UI fields, defaults, and `get_values()` implementations
- `models/` and `databases/` directories (repo root) — templates and sample DB

If anything in these notes is unclear or you'd like more examples (e.g., a small unit test scaffold or a sample call to `render_templates()`), tell me which section to expand.

## Common Tasks
