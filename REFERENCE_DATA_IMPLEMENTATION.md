# Reference Data Implementation Summary

## Overview
Successfully implemented 5 reference data sheets in the Excel database to support dynamic form field population. Reference data is now stored in separate Excel sheets instead of being hardcoded in the application.

## Database Schema Changes

### New Reference Sheets (added to DataBase_domiciliation.xlsx)
1. **SteAdresses** — Company addresses
2. **Tribunaux** — Courts/Tribunals
3. **Activites** — Business activities
4. **Nationalites** — Nationalities
5. **LieuxNaissance** — Places of birth

### Sheet Headers
- `SteAdresses`: `["STE_ADRESSE"]`
- `Tribunaux`: `["TRIBUNAL"]`
- `Activites`: `["ACTIVITE"]`
- `Nationalites`: `["NATIONALITE"]`
- `LieuxNaissance`: `["LIEU_NAISSANCE"]`

## Implementation Details

### Core Functions (`src/utils/utils.py`)

#### `get_reference_data(sheet_name, path=None)` (Added)
- **Purpose**: Load reference data from Excel sheet; fallback to constants if sheet missing
- **Returns**: List of values from reference sheet column
- **Behavior**:
  - Checks if database exists; reads sheet with pandas
  - Filters out empty values
  - Falls back to constants if sheet/DB missing or empty
  - Includes exception handling with logging

**Usage Example**:
```python
from src.utils.utils import get_reference_data
addresses = get_reference_data('SteAdresses')
```

#### `initialize_reference_sheets(path)` (Added)
- **Purpose**: Populate reference sheets with default data on first DB creation
- **Behavior**:
  - Idempotent: only writes to empty sheets, does not overwrite existing data
  - Retrieves default data from constants (`SteAdresse`, `Tribunnaux`, `Activities`, `Nationalite`, `LieuxNaissance`)
  - Uses pandas ExcelWriter; fallback to openpyxl for older pandas versions
  - Includes exception handling with logging

**Usage Example**:
```python
from src.utils.utils import initialize_reference_sheets
initialize_reference_sheets(db_path)
```

### Updated Files

#### `src/utils/constants.py`
- Added 5 reference sheet header definitions
- Updated `excel_sheets` dict to include all 8 sheets (3 data + 5 reference)

#### `src/forms/main_form.py`
- Added import: `from src.utils.utils import initialize_reference_sheets`
- Added initialization call in `finish()` method after `ensure_excel_db()`
  - Ensures reference sheets are populated on first DB creation

#### `src/forms/societe_form.py`
- **Loading reference data**:
  - `initialize_variables()`: Loads Adresse and Tribunal data via `get_reference_data()`
  - `create_activities_section()`: Loads Activites via `get_reference_data()`
  
- **Using reference data**:
  - `create_address_section()`: Combobox values set to `self.ste_adresses` and `self.tribunaux`
  - `add_activity()`: Combobox values set to `self.activities_list`

#### `src/forms/associe_form.py`
- **Loading reference data** (in `__init__`):
  - Calls `get_reference_data('Nationalites')` → stored in `self.nationalites`
  - Calls `get_reference_data('LieuxNaissance')` → stored in `self.lieux_naissance`

- **Using reference data**:
  - `create_associe_vars()`: Default values use loaded reference data (first entry)
  - `create_identity_section()`: Nationalité combobox uses `self.nationalites`
  - `create_birth_section()`: Lieu de naissance is now a Combobox using `self.lieux_naissance`

## Workflow

1. **App Startup**:
   - Forms initialize and load reference data via `get_reference_data()`
   - If database doesn't exist yet, constants are used as fallback

2. **First Data Save**:
   - MainForm calls `ensure_excel_db()` to create database
   - MainForm calls `initialize_reference_sheets()` to populate reference sheets with default data
   - Reference sheets now contain initial data from constants

3. **Form Field Population**:
   - SocieteForm comboboxes (Adresse, Tribunal, Activites) display values from reference sheets
   - AssocieForm comboboxes (Nationalite, Lieu de naissance) display values from reference sheets
   - Users select from dropdown lists populated from Excel reference sheets

4. **Data Management**:
   - Users can edit reference data directly in Excel if needed
   - Reference data persists across app sessions
   - New reference entries automatically appear in form dropdowns after app restart

## Features

✅ Dynamic reference data loading from Excel sheets  
✅ Graceful fallback to constants if sheets missing/empty  
✅ Automatic reference sheet creation and population on first save  
✅ Idempotent reference sheet initialization (won't overwrite existing data)  
✅ All forms updated to use reference data  
✅ Combobox (readonly) for all reference fields to ensure data consistency  

## Future Enhancements

- Add UI for managing reference data (add/edit/delete reference items) without needing Excel
- Migration script for existing databases to populate reference sheets
- Validation to prevent duplicate entries in reference sheets
- Admin/settings page to manage reference databases

## Testing

**To verify implementation**:
1. Run the application: `uv run .\main.py`
2. Navigate through the forms and confirm comboboxes populate with reference data
3. Save a new company to create the database
4. Open Excel and verify `SteAdresses`, `Tribunaux`, `Activites`, `Nationalites`, `LieuxNaissance` sheets exist
5. Check that reference sheets contain data from constants
6. Edit or add new reference items directly in Excel
7. Restart the app and confirm new items appear in comboboxes

## Files Modified

- `src/utils/constants.py` — Added 5 reference sheet definitions
- `src/utils/utils.py` — Added `get_reference_data()` and `initialize_reference_sheets()`
- `src/forms/main_form.py` — Added reference sheet initialization
- `src/forms/societe_form.py` — Updated to load and use reference data
- `src/forms/associe_form.py` — Updated to load and use reference data

## Syntax Validation

All files have been validated for syntax errors:
- ✅ `src/utils/utils.py` — No syntax errors
- ✅ `src/forms/societe_form.py` — No syntax errors
- ✅ `src/forms/associe_form.py` — No syntax errors
