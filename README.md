# 🏢 Centre de Domiciliation — Application de gestion (Desktop)

A desktop application to manage company domiciliation services (French/English). Built with Python and Tkinter. It stores data in Excel, uses Word templates for document generation, and provides a simple UI for sociétés, associés and contrats.

This README focuses on a robust setup and usage guide, platform notes (Windows / macOS / Linux), testing, troubleshooting and contribution steps.

## ⚡ Quick facts

- Language: Python 3.10+ (works with 3.11)
- UI: Tkinter (+ tkcalendar)
- Data store: Excel files (pandas + openpyxl)
- Templates: Word (.docx) using docxtpl

## 📦 Release notes (short)

- 2025-10-29 — UX tweak: the final "Génération terminée" dialog now shows the actual generated folder (the common folder of generated files) so you can quickly open the output location. ✅

## Table of contents

- Installation
- Configuration
- Running the app
- Tests and validation
- Project layout
- Troubleshooting
- Contributing
- License

## 🛠️ Installation

### Prerequisites

- **Python 3.13+** (recommended; works with 3.10+)
- **Git** (to clone the repository)
- **`uv`** — Fast Python package installer (https://astral.sh/uv/)
- Optional: Microsoft Office (for opening generated .docx/.pdf files)

### Install `uv` (if not already installed)

**Windows:**
```powershell
pipx install uv
# or download from https://astral.sh/uv/
```

**macOS / Linux:**
```bash
curl -LsSf https://astral.sh/uv/install.sh | sh
```

### Clone and Install

1. Clone the repository:

```powershell
git clone https://github.com/LearniTome/Center-Domiciliation-App.git
cd Center-Domiciliation-App
```

2. Create virtual environment and install dependencies:

```powershell
uv venv
```

3. Run the application directly with `uv`:

```powershell
uv run python main.py
```

**That's it!** No need to manually activate the virtual environment when using `uv run`.

### Optional: Manual venv activation (if preferred)

If you prefer to activate the virtual environment manually:

```powershell
# Windows
.\venv\Scripts\Activate.ps1

# macOS / Linux
source venv/bin/activate

# Then run normally
python main.py
```

**Notes:**
- `uv` automatically installs all dependencies from `requirements.txt` when creating the venv
- `uv` handles Python version management and platform-specific wheels automatically
- On Windows, `requirements-windows.txt` is available for additional platform-specific optimization
- Most packages are pure-Python and install without build issues

## ⚙️ Configuration

Default preferences are stored in `config/preferences.json`. Common changes:

- Output directory for generated documents (`tmp_out/`)
- Database path (`databases/DataBase_domiciliation.xlsx`)

Edit `config/preferences.json` or create a local copy if you want per-developer overrides.

## ▶️ Running the Application

### Quickest way (recommended)

```powershell
uv run python main.py
```

This runs the app directly without needing to manually activate the virtual environment.

### Or with manual venv activation

If you prefer to activate the virtual environment first:

```powershell
# Windows
.\venv\Scripts\Activate.ps1

# macOS / Linux
source venv/bin/activate

# Then run
python main.py
```

You can also run as a module:

```powershell
python -m main
```

### Application Features

The app launches a Tkinter window where you can:
- **Societies (Sociétés)**: Create and edit domiciled companies
- **Associates (Associés)**: Manage partners/members
- **Contracts (Contrat)**: Create or generate contract documents

📄 Document generation

- Documents live in `Models/` as .docx templates. The app fills templates using `docxtpl` and writes output to `tmp_out/`.
- PDF export uses `docx2pdf` when available (Windows with Word installed) or external converters.

📝 Generation reports

- After a generation run the app writes a human-friendly HTML report into the generation output folder. The file name uses this pattern:

  `yyyy-mm-dd_<CompanyName>_Raport_Docs_generer.html`

  Example: `2025-10-23_ASTRAPIA_Raport_Docs_generer.html`.

- The generator writes a JSON report using the same base name as the HTML report (for example `2025-10-23_ASTRAPIA_Raport_Docs_generer.json`). The HTML report embeds the same JSON inside a `<pre id="genjson">` block so tooling can extract it easily.

Important runtime behaviors (recent updates)

- Automatic save before generation: when you click "Générer les documents" the application now automatically saves all form sections (Société, Associés, Contrat) to the Excel database before starting the document generation. If the save fails (for example the Excel file is locked by Excel), generation is cancelled and a warning is shown so you can correct the issue and retry.

- Consolidated finish/save UX: the "Terminer" action collects and saves all sections silently (no per-section popups). You will see one consolidated success or error message after the complete save operation.

- Autofit Excel columns: after saving or migrating the Excel workbook the app applies a simple "autofit" heuristic to column widths for every sheet so the resulting workbook is easier to read in Excel (width ~= max cell length + padding). This is applied programmatically via openpyxl when the workbook is written.

- Time-stamped generation reports: report file names now include a time suffix (HH-MM-SS) to avoid name collisions for multiple generations on the same day. Pattern:

  `YYYY-MM-DD_<CompanyName>_Raport_Docs_generer_HH-MM-SS.html`

  and the matching JSON:

  `YYYY-MM-DD_<CompanyName>_Raport_Docs_generer_HH-MM-SS.json`

  The HTML still contains the same JSON embedded inside `<pre id="genjson">` for tooling convenience.

- The included verification script `scripts/check_generation.py` prefers the HTML report (it will extract the embedded JSON), then looks for a named JSON that matches the HTML report naming convention, and finally falls back to `generation_report.json` only if necessary. To validate a generation and assert basic expectations run:

```powershell
uv run python .\scripts\check_generation.py --expect-company ASTRAPIA --expect-associe "Abdeljalil"
```

The script prints a JSON summary and exits with code 0 on success (or when no expectations were given) and non-zero if any expectation fails.

## ✅ Tests and Validation

### Run the smoke test

```powershell
uv run python tests/smoke_test.py
```

This ensures basic imports and app instantiation work.

### Run the full test suite

```powershell
uv run pytest -q
```

### Expected output

- `tests/smoke_test.py` should print: "Smoke test: MainApp instantiated successfully"
- `pytest -q` should complete with passing test count

## 📁 Project layout

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

## 🩺 Troubleshooting

- **App won't start / ImportError**: Verify the virtual environment is set up correctly. Run `uv venv` to create it and `uv run python main.py` to test.
- **docxtpl or docx2pdf errors**: Ensure `python-docx`, `docxtpl`, and optional `docx2pdf` are installed. On Windows, ensure Microsoft Word is installed if you rely on `docx2pdf`.
- **Excel write errors**: Close the Excel file if open (Windows locks files). Ensure `openpyxl` is installed.
- **GUI sizing issues**: Run on a larger display or change DPI scaling. Minimal recommended resolution: 1024x768.

If problems persist, run the smoke test and paste the traceback into an issue:

```powershell
uv run python tests/smoke_test.py
```

## 🤝 Contributing

### Guidelines

- Create a topic branch per feature or bugfix: `git checkout -b feat/your-feature`
- Keep commits small and focused
- Add/adjust tests for new logic
- Open a Pull Request describing the change and which files to review

### Development Workflow

```powershell
# Create a feature branch
git checkout -b feat/your-feature

# Create virtual environment
uv venv

# Activate venv (optional - you can also use uv run directly)
# Windows
.\venv\Scripts\Activate.ps1
# macOS / Linux
source venv/bin/activate

# Make your changes
# ... edit files ...

# Run tests
uv run pytest -q

# Stage and commit
git add .
git commit -m "feat: describe your changes"

# Push to GitHub
git push --set-upstream origin feat/your-feature
```

### Helper Scripts

- `scripts/auto_commit.py` can help with staged auto-commits; review its README in `scripts/`.
git push --set-upstream origin chore/your-task
```

Automated scripts

- `scripts/auto_commit.py` can help with staged auto-commits; review its README in `scripts/`.

If you'd like these behaviors changed (for example re-enable the legacy `generation_report.json` filename, or adjust the autofit padding), tell me which preference you prefer and I can update the code and tests accordingly.

## 📜 License

This project is licensed under the MIT License. See `LICENSE` for details.

---
If you'd like, I can also:

- add a minimal CONTRIBUTING.md
- add a checklist GitHub Action to run the smoke test on PRs
- or create a small script to create the recommended venv and install deps automatically
