# CHANGELOG

All notable changes to the Center-Domiciliation App are documented in this file.

## [2.2.0] - 2026-03-04

### 🎯 Dashboard UX Improvements
- Added instant search and advanced column filter (`column + value`) on all dashboard pages.
- Added sortable table headers with ascending/descending indicators.
- Added pagination controls (`10/25/50/100` rows per page, previous/next navigation).
- Added contextual empty-state messages for no data / no filter match.
- Added non-blocking toast feedback for refresh/add/edit/delete flows.
- Added CSV export of the active filtered+sorted dashboard view.

### 🔧 Technical Changes
#### src/forms/dashboard_view.py
- Introduced per-page sorting state and pagination state.
- Fixed edit/delete payload mapping to use the currently displayed row after filters/sort/page.
- Added export helper for filtered/sorted dataset to CSV.
- Improved status bar messaging with displayed vs total counts.

### ✅ Validation
- `uv run python -m py_compile src/forms/dashboard_view.py`
- `uv run python tests/test_dashboard_headless.py`
- `uv run python tests/test_dashboard_final.py`

## [2.1.0] - 2026-03-02

### 🎯 Major Features
- ✅ **CRITICAL BUG FIX:** Dashboard now correctly displays data from Excel database
- ✅ **Dashboard Data Loading:** Loads all three sheets (Societes, Associes, Contrats)
- ✅ **Dashboard Action Buttons:** Add, Edit, Delete, Refresh fully implemented
- ✅ **Database Structure:** Removed redundant DataBaseDom sheet
- ✅ **Backup System:** Automated backup with cleanup (max 5 backups)

### 🔧 Changes
#### src/forms/dashboard_view.py
- Fixed initialization order (DataFrames initialized BEFORE _build_body)
- Enhanced _load_data() to load all 3 sheets
- Implemented _show_page() routing based on page selection
- Full _action() implementation for Add/Edit/Delete/Refresh
- Added comprehensive error handling and logging
- Improved _refresh_display() with better column handling

#### src/utils/utils.py
- Added cleanup_old_backups() function (keeps max 5 backups)
- Added initialize_reference_sheets() for dropdown data
- Updated write_records_to_db() to use new sheet structure

#### databases/DataBase_domiciliation.xlsx
- Removed DataBaseDom sheet
- Updated structure: 8 sheets (3 main + 5 reference)
- All headers validated

### ✅ Testing
- Dashboard displays 3+ rows per sheet
- Page switching works correctly
- Action buttons route to correct sheet
- Data integrity validated (foreign keys)
- All action buttons functional

### 📦 Commits
```
78e13b5 - release: v2.1.0 - Dashboard data display & action buttons
46a6b79 - fix: Dashboard now correctly displays data from Excel database
9ea0193 - docs: Add Dashboard Testing Report with comprehensive test results
2f5e518 - feat: Implement Dashboard action buttons (Add/Edit/Delete/Correct sheet routing
22c1bcf - feat: Dashboard now loads data from all three sheets (Societes, Associes, Contrats)
8ad1ece - refactor: remove DataBaseDom sheet and use only canonical sheets
```

---

## [2.0.0] - 2026-02-XX

### 🔧 Changes
- Database structure refactoring
- Removed DataBaseDom sheet
- Added 5 reference sheets
- Backup system implementation
- Tests and documentation

---

## [1.0.0] - Initial Release

### 🎯 Features
- Tkinter desktop application
- Excel template filling
- Document generation (Word/PDF)
- Basic UI forms
- Excel database backend

---

## 🚀 Installation & Usage

### Requirements
- Python 3.13+
- pandas 2.3.1+
- openpyxl 3.1.5+
- tkinter (included with Python)

### Setup
```bash
# Clone repository
git clone https://github.com/LearniTome/Center-Domiciliation-App.git
cd Center-Domiciliation-App

# Create virtual environment
python -m venv .venv
.venv\Scripts\activate  # Windows
# source .venv/bin/activate  # Linux/Mac

# Install dependencies
pip install -r requirements.txt
# or
uv install

# Run application
python main.py
```

### Run Tests
```bash
uv run python test_dashboard_final.py
uv run python test_dashboard_headless.py
uv run python test_dashboard_actions.py
```

---

## 📝 Version Schema

We use [Semantic Versioning](https://semver.org/):
- **MAJOR** version (X.0.0) - Incompatible API changes
- **MINOR** version (0.X.0) - Backward-compatible new features
- **PATCH** version (0.0.X) - Backward-compatible bug fixes

---

## 🔗 Related Files

- [RELEASE_v2.1.0.md](./RELEASE_v2.1.0.md) - Detailed release notes
- [README.md](./README.md) - Project overview
- [DASHBOARD_TESTING_REPORT.md](./DASHBOARD_TESTING_REPORT.md) - Testing details

---

**Last Updated:** March 2, 2026
**Maintainer:** LearniTome
**Repository:** https://github.com/LearniTome/Center-Domiciliation-App
