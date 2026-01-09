# Backup & Database Management System

## Overview
The application now has an intelligent backup system that automatically manages database backups to prevent disk clutter while maintaining data safety.

## How It Works

### Automatic Backup Creation
When you save data (via the "Générer les documents" button or any save operation):
1. The application automatically creates a timestamped backup of the current database
2. Backup filename format: `DataBase_domiciliation_backup_YYYYMMDD_HHMMSS.xlsx`
3. Backups are stored in the `databases/` folder

### Automatic Cleanup
After each backup is created:
1. The system automatically checks how many backups exist
2. If there are more than 5 backups, the oldest ones are deleted
3. This keeps disk usage manageable while maintaining recent recovery points

## Database Structure

```
databases/
├── DataBase_domiciliation.xlsx          (Main database - current data)
├── DataBase_domiciliation_backup_*.xlsx (Up to 5 recent backups)
└── (No old test files or unnecessary backups)
```

## Usage

### Saving Data
The application saves data through several buttons:

**Main Save Button:**
- Located in the main form wizard
- Button: "Générer les documents & Sauvegarder"
- Action: Collects all form data → Saves to Excel → Creates backup → Cleans old backups

**Save Flow:**
```
User clicks "Générer"
    ↓
Collect values from all forms (Societe, Associes, Contrat)
    ↓
Check for duplicate company name (prevents conflicts)
    ↓
Save to Excel database
    ↓
Run workbook migration (handles old/renamed sheets)
    ↓
Create timestamped backup
    ↓
Cleanup: Keep only 5 most recent backups
    ↓
Show success message with database path
```

### Backup Recovery
If you need to restore a backup:
1. Close the application
2. Navigate to `databases/` folder
3. Rename the backup file you want to restore to `DataBase_domiciliation.xlsx`
4. Reopen the application

Example:
```
Before:  DataBase_domiciliation_backup_20250109_103000.xlsx
After:   DataBase_domiciliation.xlsx (rename the backup)
```

## Key Features

✅ **Automatic Backups** - Created before every database modification
✅ **Smart Cleanup** - Keeps only 5 most recent backups
✅ **No Data Loss** - Backup created before any changes are made
✅ **Conflict Prevention** - Cannot save duplicate company names
✅ **Idempotent** - Safe to call multiple times
✅ **Error Handling** - Graceful fallback if cleanup fails

## Technical Details

### Backup Cleanup Function
```python
def cleanup_old_backups(db_path, max_backups=5):
    """Keep only the most recent N backups, delete older ones."""
```

**Location:** `src/utils/utils.py` (lines 1074-1102)
**Called from:** `migrate_excel_workbook()` after backup creation
**Max backups kept:** 5 (configurable)

### Save to Database Function
```python
def save_to_db(self):
    """Saves form data to Excel database"""
```

**Location:** `main.py` (lines 426-470)
**Steps:**
1. Collects values from all forms
2. Ensures Excel database exists with correct sheets
3. Checks for duplicate company names
4. Writes records to database
5. Runs workbook migration
6. Returns database path on success

## Configuration

To change the number of backups kept:

**File:** `src/utils/utils.py`
**Line:** ~1116

Change:
```python
cleanup_old_backups(path, max_backups=5)  # Change 5 to desired number
```

## Troubleshooting

### Issue: "Le fichier Excel est ouvert"
**Solution:** Close the Excel file and try again

### Issue: "La société existe déjà"
**Solution:** This is intentional to prevent duplicates. Use Dashboard to edit existing company.

### Issue: Backup file corrupted
**Solution:** Delete the corrupted backup and use a working one

## File Cleanup Results

**Before:**
- 40+ backup files cluttering the databases/ folder
- Disk space wasted on old backups

**After:**
- Only main database file visible
- Up to 5 recent backups created on-demand
- Automatic cleanup after each save
- Clean, organized folder structure

## Testing

Test file was created and verified that:
1. ✅ 7 test backups created
2. ✅ Cleanup ran successfully
3. ✅ Exactly 5 backups retained (most recent ones)
4. ✅ 2 oldest backups deleted
5. ✅ All remaining backups properly timestamped

## Next Steps

1. **Dashboard Integration** - Button presses in Dashboard can trigger saves
2. **User Settings** - Allow users to configure backup retention via settings
3. **Backup Restore UI** - Add menu to view and restore from backups
4. **Archive** - Move old backups to archive instead of deleting them

