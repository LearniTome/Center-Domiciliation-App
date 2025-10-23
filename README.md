# Centre de Domiciliation — Application de gestion (Desktop)

A desktop application to manage company domiciliation services (French/English). Built with Python and Tkinter. It stores data in Excel, uses Word templates for document generation, and provides a simple UI for societies, associates and contracts.

This README focuses on a robust setup and usage guide, platform notes (Windows / macOS / Linux), testing, troubleshooting and contribution steps.

## Quick facts

- Language: Python 3.10+ (works with 3.11)
- UI: Tkinter (+ tkcalendar)
- Data store: Excel files (pandas + openpyxl)
- Templates: Word (.docx) using docxtpl

## Table of contents

- Installation
- Configuration
- Running the app
- Tests and validation
- Project layout
- Troubleshooting
- Contributing
- License

## Installation

Prerequisites

- Python 3.10 or newer
- Git (to clone)
- Optional but recommended: Microsoft Office (for opening generated .docx/.pdf)

On Windows (recommended workflow)

1. Clone the repository:

```powershell
git clone https://github.com/LearniTome/Center-Domiciliation-App.git
cd Center-Domiciliation-App
```

1. Create and activate a virtual environment:

```powershell
python -m venv venv
.\venv\Scripts\Activate.ps1
```

1. Install dependencies:

```powershell
pip install -r requirements-windows.txt
```

On macOS / Linux

```bash
git clone https://github.com/LearniTome/Center-Domiciliation-App.git
cd Center-Domiciliation-App
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

Notes

- Use `requirements-windows.txt` on Windows to account for Windows-specific wheels if present.
- If you get binary build errors for packages like `python-docx`, `docxtpl` or others, ensure you have a working build toolchain (on Windows: Build Tools for Visual Studio). Many packages used here are pure-Python.

## Configuration

Default preferences are stored in `config/preferences.json`. Common changes:

- Output directory for generated documents (`tmp_out/`)
- Database path (`databases/DataBase_domiciliation.xlsx`)

Edit `config/preferences.json` or create a local copy if you want per-developer overrides.

## Running the application

From the project root, with the venv activated:

```powershell
python main.py
```

Or (module mode):

```powershell
python -m main
```

Primary screens

- Societies (Société): create and edit domiciled companies
- Associates (Associés): manage partners/members
- Contracts (Contrat): create or generate contract documents

Document generation

- Documents live in `Models/` as .docx templates. The app fills templates using `docxtpl` and writes output to `tmp_out/`.
- PDF export uses `docx2pdf` when available (Windows with Word installed) or external converters.

## Tests and validation

Run the provided smoke test to ensure basic imports and app instantiation work:

```powershell
.\venv\Scripts\Activate.ps1
python tests/smoke_test.py
```

Run the full test suite with pytest:

```powershell
.\venv\Scripts\Activate.ps1
pip install -r requirements.txt
pytest -q
```

Expected quick checks

- `tests/smoke_test.py` should print: "Smoke test: MainApp instantiated successfully"

## Project layout

Top-level files

- `main.py` — application entrypoint
- `requirements.txt`, `requirements-windows.txt` — dependencies
- `config/` — preferences
- `databases/` — Excel datastore(s)
- `Models/` — Word templates used for document generation
- `tmp_out/` — generated files output
- `src/` — application source code

Inside `src/`

- `forms/` — UI forms (main_form.py, societe_form.py, associe_form.py, contrat_form.py)
- `utils/` — utilities and small helpers (doc generation, styles, constants)

## Troubleshooting

- App won't start / ImportError: verify venv is activated and `pip install -r requirements.txt` completed.
- docxtpl or docx2pdf errors: ensure `python-docx`, `docxtpl`, and optional `docx2pdf` are installed; on Windows ensure Microsoft Word is installed if you rely on `docx2pdf`.
- Excel write errors: close the Excel file if open (Windows locks the file); ensure `openpyxl` is installed.
- GUI sizing issues: run on a larger display or change DPI scaling; minimal recommended resolution 1024x768.

If problems persist, run the smoke test and paste the traceback into an issue.

## Contributing

Guidelines

- Create a topic branch per feature or bugfix: `git checkout -b feat/your-feature`
- Keep commits small and focused
- Add/adjust tests for new logic
- Open a Pull Request describing the change and which files to review

Development workflow

```bash
git checkout -b chore/your-task
# make changes
git add .
git commit -m "chore: describe changes"
git push --set-upstream origin chore/your-task
```

Automated scripts

- `scripts/auto_commit.py` can help with staged auto-commits; review its README in `scripts/`.

## License

This project is licensed under the MIT License. See `LICENSE` for details.

## Short French summary

Application de bureau Python/Tkinter pour gérer sociétés, associés et contrats. Données dans `databases/`, modèles Word dans `Models/`, sorties dans `tmp_out/`.

---

If you'd like, I can also:

- add a minimal CONTRIBUTING.md
- add a checklist GitHub Action to run the smoke test on PRs
- or create a small script to create the recommended venv and install deps automatically
