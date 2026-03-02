# 📊 Center-Domiciliation App - Project Summary

## 🎯 Project Overview

**Center-Domiciliation App** est une application desktop Tkinter qui simplifie les processus administratifs pour un centre de domiciliation. L'application:

1. **Gère les données d'entreprises** (Sociétés, Associés, Contrats)
2. **Génère des documents** (modèles Word → PDF)
3. **Stocke les enregistrements** dans une base de données Excel
4. **Fournit un tableau de bord** pour visualiser et gérer les données

---

## 📦 Version Actuelle

**v2.1.0** (March 2, 2026)

### ✅ Fonctionnalités Principales

| Feature | Status | Details |
|---------|--------|---------|
| **Dashboard** | ✅ Complet | Affiche Societes, Associes, Contrats avec données |
| **Action Buttons** | ✅ Complet | Add, Edit, Delete, Refresh |
| **Database** | ✅ Complet | 8 sheets (3 main + 5 reference) |
| **Backup System** | ✅ Complet | Auto-backup with cleanup (max 5) |
| **Forms** | ✅ Complet | Societe, Associe, Contrat entry forms |
| **Document Generation** | ✅ Complet | Word templates → PDF |
| **Excel Templates** | ✅ Complet | 7 templates disponibles |

---

## 🏗️ Architecture

### File Structure

```
center-domiciliation-app/
├── main.py                          # Entry point
├── src/
│   ├── forms/                       # UI Forms
│   │   ├── main_form.py            # Main form (Societe/Associe/Contrat)
│   │   ├── dashboard_view.py       # Dashboard modal
│   │   └── ...
│   └── utils/
│       ├── utils.py                # Utilities (PathManager, ThemeManager, etc)
│       ├── constants.py            # Constants (headers, lists)
│       └── doc_generator.py        # Document generation
├── databases/
│   └── DataBase_domiciliation.xlsx  # Main database (8 sheets)
├── Models/                          # Word templates (7 files)
├── tests/                           # Test suite
├── RELEASE_v2.1.0.md               # Release notes
├── CHANGELOG.md                     # Version history
└── README.md                        # Project documentation
```

### Database Schema

**8 Excel Sheets:**

| Sheet | Type | Columns | Purpose |
|-------|------|---------|---------|
| **Societes** | Main | 9 | Company data |
| **Associes** | Main | 17 | Associate/partner data |
| **Contrats** | Main | 8 | Contract data |
| **SteAdresses** | Reference | 1 | Company address dropdown |
| **Tribunaux** | Reference | 1 | Court location dropdown |
| **Activites** | Reference | 1 | Business activity dropdown |
| **Nationalites** | Reference | 1 | Nationality dropdown |
| **LieuxNaissance** | Reference | 1 | Birth place dropdown |

---

## 🚀 Key Improvements (v2.1.0)

### ✅ Critical Bug Fix
**Dashboard Display Issue Fixed**
- Problem: Dashboard wouldn't show data even though it loaded correctly
- Root Cause: Initialization order (widgets created before DataFrames)
- Solution: Reordered initialization sequence
- Result: All data now displays correctly ✅

### ✅ Enhanced Features
- Dashboard loads all 3 sheets (not just Societes)
- Page switching with correct data routing
- Full action button implementation (Add/Edit/Delete/Refresh)
- Better error handling and logging

### ✅ Improved Testing
- Comprehensive test suite (5 test files)
- Headless testing capability
- 100% test pass rate

---

## 📊 Current Data

Database contains:
- **3 Societes** (companies)
- **3 Associes** (associates)
- **3 Contrats** (contracts)

All data properly organized in Excel sheets with foreign key relationships.

---

## 🧪 Testing

### Test Suite

| Test | Command | Purpose |
|------|---------|---------|
| **Headless** | `uv run python test_dashboard_headless.py` | Dashboard loads data correctly |
| **Final** | `uv run python test_dashboard_final.py` | All 3 pages display correctly |
| **Actions** | `uv run python test_dashboard_actions.py` | Action buttons route correctly |
| **Treeview** | `uv run python test_treeview_display.py` | Data formats correctly |

### Test Results
```
✅ Dashboard loads 3+ rows per sheet
✅ Page switching works correctly
✅ Action buttons route to correct sheet
✅ Data integrity validated
✅ Foreign key relationships valid
```

---

## 💻 Installation

### Requirements
- Python 3.13+
- pandas 2.3.1+
- openpyxl 3.1.5+
- Tkinter (included)

### Setup
```bash
git clone https://github.com/LearniTome/Center-Domiciliation-App.git
cd Center-Domiciliation-App
uv install  # or pip install -r requirements.txt
```

### Run
```bash
uv run python main.py
```

---

## 🔧 Main Classes & Methods

### DashboardView (src/forms/dashboard_view.py)
- `_load_data()` - Load all 3 sheets from database
- `_show_page(page_key)` - Switch between pages with correct data
- `_action(action)` - Handle Add/Edit/Delete/Refresh buttons
- `_refresh_display()` - Update Treeview with current data

### PathManager (src/utils/utils.py)
- `get_database_path()` - Get Excel database path
- `get_models_dir()` - Get templates directory
- `ensure_directories()` - Create needed folders

### ThemeManager (src/utils/utils.py)
- `set_dark_mode()` - Apply dark theme
- `get_color()` - Get theme color

---

## 📝 Git Commits (v2.1.0)

```
78e13b5 - release: v2.1.0 - Dashboard data display & action buttons
46a6b79 - fix: Dashboard now correctly displays data from Excel database
9ea0193 - docs: Add Dashboard Testing Report
2f5e518 - feat: Implement Dashboard action buttons
22c1bcf - feat: Dashboard now loads data from all three sheets
8ad1ece - refactor: remove DataBaseDom sheet
```

---

## 🔮 Future Enhancements

### Optional Features
1. **Search/Filter** in Dashboard
2. **Sorting** by column
3. **Export** to CSV/Excel
4. **Performance Testing** (100+ records)
5. **Advanced Validation** in forms
6. **Logging Dashboard** activity
7. **User Preferences** persistence
8. **Batch Operations** (import/export)

### Known Limitations
- Single-user (no multi-user sync)
- Local Excel backend (no cloud sync)
- No user authentication
- Limited to Windows/Linux/Mac with Python 3.13

---

## 📄 Documentation Files

| File | Purpose |
|------|---------|
| [README.md](./README.md) | Project overview & features |
| [RELEASE_v2.1.0.md](./RELEASE_v2.1.0.md) | Detailed release notes |
| [CHANGELOG.md](./CHANGELOG.md) | Version history |
| [DASHBOARD_TESTING_REPORT.md](./DASHBOARD_TESTING_REPORT.md) | Dashboard test details |

---

## 🎓 Code Quality

- ✅ Type hints used throughout
- ✅ Error handling with logging
- ✅ Docstrings for all major functions
- ✅ Centralized constants (no magic values)
- ✅ Helper functions (no code duplication)
- ✅ Tests for critical features

---

## 📊 Metrics

| Metric | Value |
|--------|-------|
| **Python Files** | 20+ |
| **Test Files** | 5+ |
| **Lines of Code** | 5000+ |
| **Excel Sheets** | 8 |
| **Word Templates** | 7 |
| **Test Pass Rate** | 100% ✅ |

---

## 🤝 Contributing

To contribute improvements:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/description`)
3. Make your changes
4. Run tests to verify
5. Commit with clear messages
6. Push to your fork
7. Create a Pull Request

---

## 📞 Support

For issues or questions:
- Check [DASHBOARD_TESTING_REPORT.md](./DASHBOARD_TESTING_REPORT.md) for common issues
- Review test files for usage examples
- Check git history for implementation details

---

## 📜 License

This project is maintained by **LearniTome**.

---

**Last Updated:** March 2, 2026  
**Current Version:** 2.1.0  
**Repository:** https://github.com/LearniTome/Center-Domiciliation-App
