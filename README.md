# 🏢 Centre de Domiciliation

> A desktop application to manage company domiciliation services. Built with **Python 3.13+** and **Tkinter**. Stores data in **Excel**, generates **Word documents**, and provides a clean UI for companies, associates, and contracts.

---

## ✨ Quick Start (3 steps)

```powershell
# 1️⃣ Clone the repository
git clone https://github.com/LearniTome/Center-Domiciliation-App.git
cd Center-Domiciliation-App

# 2️⃣ Sync dependencies with uv
uv sync

# 3️⃣ Run the application
uv run python main.py
```

Done! 🎉

---

## � Requirements

| Requirement | Details |
|---|---|
| **Python** | 3.13+ (or 3.10+) |
| **Git** | For cloning the repository |
| **`uv`** | Fast Python package manager |

### Install `uv` (one-time setup)

**Windows (PowerShell):**
```powershell
pipx install uv
```

**macOS / Linux:**
```bash
curl -LsSf https://astral.sh/uv/install.sh | sh
```

> More info: https://astral.sh/uv/

---

## 🎮 Using the Application

Once running, you can:

- 🏢 **Manage Companies** (Sociétés): Create and edit domiciled companies
- 👥 **Manage Associates** (Associés): Handle partners and members
- 📄 **Generate Contracts** (Contrat): Create contract documents
- 📊 **Export Data**: Save everything to Excel database

All generated documents go to `tmp_out/` folder.

---

## ⚙️ Configuration

Default settings are in `config/preferences.json`:

- Output directory for generated files (`tmp_out/`)
- Database location (`databases/DataBase_domiciliation.xlsx`)

Edit or create a local copy to customize per-developer.

---

## ✅ Testing

### Run the smoke test
```powershell
uv run python tests/smoke_test.py
```

### Run all tests
```powershell
uv run pytest -q
```

---

## 🛠️ Development

**Create a feature branch:**
```powershell
git checkout -b feat/your-feature
uv sync
uv run pytest -q
# ... make changes ...
git add . && git commit -m "feat: describe changes"
git push --set-upstream origin feat/your-feature
```

---

## 🩺 Troubleshooting

| Problem | Solution |
|---|---|
| **App won't start** | Run `uv sync` to ensure all dependencies are installed |
| **ImportError** | Check Python version: `python --version` should be 3.10+ |
| **Excel locked** | Close the file in Excel before running generation |
| **PDF export fails** | Ensure Microsoft Word is installed (Windows) |
| **GUI too small** | Run on larger display or adjust DPI scaling |

---

## 📁 Project Structure

```
├── main.py                 # Application entrypoint
├── requirements.txt        # Python dependencies
├── config/                 # Configuration files
├── databases/              # Excel data storage
├── Models/                 # Word templates for generation
├── src/
│   ├── forms/              # UI forms and dashboard
│   └── utils/              # Utilities, styles, helpers
├── tests/                  # Test files
└── tmp_out/                # Generated output files
```

---

## 📊 Key Features

- ✅ **Simple Tkinter UI** — Clean, dark-mode friendly interface
- ✅ **Excel Database** — Store companies, associates, contracts
- ✅ **Word Templates** — Auto-generate constitutive documents
- ✅ **PDF Export** — Convert generated documents to PDF
- ✅ **Generation Reports** — HTML + JSON reports for each generation
- ✅ **Dark Mode** — Professional dark theme support

---

## 📝 Implementation Details

### Document Generation
- Documents live in `Models/` as .docx templates
- App fills templates using `docxtpl` and writes to `tmp_out/`
- PDF export uses `docx2pdf` (requires Microsoft Word on Windows)
- Reports include HTML (user-friendly) + JSON (for tooling)

### Runtime Features
- **Auto-save**: Automatically saves form data before document generation
- **Excel autofit**: Column widths auto-adjust after save
- **Time-stamped reports**: Avoids name collisions for multiple generations per day

### Verification Script
Validate generation with expectations:
```powershell
uv run python .\scripts\check_generation.py --expect-company "ASTRAPIA" --expect-associe "Abdeljalil"
```

---

## 🤝 Contributing

1. Create a branch: `git checkout -b feat/your-feature`
2. Make changes and test: `uv run pytest -q`
3. Commit: `git commit -m "feat: description"`
4. Push: `git push origin feat/your-feature`
5. Open a Pull Request on GitHub

---

## 📝 Notes

- **Python**: Recommended 3.13+, works with 3.10+
- **Platform**: Windows, macOS, Linux
- **Package Manager**: Uses `uv sync` for fast, reliable dependency management
- **Testing**: Full test suite with pytest

---

## 📜 License

MIT License — See `LICENSE` file for details.

---

**Questions?** Open an issue on GitHub or check project documentation.
