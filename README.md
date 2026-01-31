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

## ⚙️ Configuration

Default preferences are stored in `config/preferences.json`. Common changes:

- Output directory for generated documents (`tmp_out/`)
- Database path (`databases/DataBase_domiciliation.xlsx`)

Edit `config/preferences.json` or create a local copy if you want per-developer overrides.

## ▶️ Running the application

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
.\venv\Scripts\Activate.ps1
python .\scripts\check_generation.py --expect-company ASTRAPIA --expect-associe "Abdeljalil"
```

The script prints a JSON summary and exits with code 0 on success (or when no expectations were given) and non-zero if any expectation fails.

## ✅ Tests and validation

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

- App won't start / ImportError: verify venv is activated and `pip install -r requirements.txt` completed.
- docxtpl or docx2pdf errors: ensure `python-docx`, `docxtpl`, and optional `docx2pdf` are installed; on Windows ensure Microsoft Word is installed if you rely on `docx2pdf`.
- Excel write errors: close the Excel file if open (Windows locks the file); ensure `openpyxl` is installed.
- GUI sizing issues: run on a larger display or change DPI scaling; minimal recommended resolution 1024x768.

If problems persist, run the smoke test and paste the traceback into an issue.

## 🤝 Contributing

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

If you'd like these behaviors changed (for example re-enable the legacy `generation_report.json` filename, or adjust the autofit padding), tell me which preference you prefer and I can update the code and tests accordingly.

## 📜 License

This project is licensed under the MIT License. See `LICENSE` for details.

---
If you'd like, I can also:

- add a minimal CONTRIBUTING.md
- add a checklist GitHub Action to run the smoke test on PRs
- or create a small script to create the recommended venv and install deps automatically
