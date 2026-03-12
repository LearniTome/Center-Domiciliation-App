#!/usr/bin/env python3
"""
Test script to verify Dashboard loads data correctly from database
"""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).parent))

from src.utils import constants as _const
from src.utils.utils import write_records_to_db
import pandas as pd

def test_dashboard_data():
    """Test that Dashboard can load data from database"""

    db_path = Path(__file__).parent / 'databases' / 'DataBase_domiciliation.xlsx'

    print("=" * 70)
    print("DASHBOARD DATA LOADING TEST")
    print("=" * 70)

    print("\n📋 Step 1: Check database exists...")
    if not db_path.exists():
        print(f"❌ Database not found: {db_path}")
        return False
    print(f"✅ Database found: {db_path.name}")

    print("\n📋 Step 2: Load data from each sheet...")

    sheets_data = {}
    for sheet_name, headers in [
        ('Societes', _const.societe_headers),
        ('Associes', _const.associe_headers),
        ('Contrats', _const.contrat_headers),
        ('Collaborateurs', _const.collaborateur_headers),
    ]:
        try:
            df = pd.read_excel(db_path, sheet_name=sheet_name, dtype=str).fillna('')
            sheets_data[sheet_name] = df
            print(f"✓ {sheet_name:15s}: {len(df)} rows, {len(df.columns)} columns")
        except Exception as e:
            print(f"✗ {sheet_name:15s}: Error - {e}")
            return False

    print("\n📋 Step 3: Verify columns match expected headers...")

    all_ok = True
    for sheet_name, expected_headers in [
        ('Societes', _const.societe_headers),
        ('Associes', _const.associe_headers),
        ('Contrats', _const.contrat_headers),
        ('Collaborateurs', _const.collaborateur_headers),
    ]:
        df = sheets_data[sheet_name]
        actual_cols = list(df.columns)

        if actual_cols == expected_headers:
            print(f"✓ {sheet_name:15s}: Headers match ✓")
        else:
            print(f"✗ {sheet_name:15s}: Headers mismatch!")
            print(f"  Expected: {expected_headers}")
            print(f"  Got:      {actual_cols}")
            all_ok = False

    if not all_ok:
        return False

    print("\n📋 Step 4: Check Dashboard would display data correctly...")

    # Check display columns (without ID_* columns)
    display_checks = [
        ('Societes', [c for c in _const.societe_headers if not c.startswith('ID_')]),
        ('Associes', [c for c in _const.associe_headers if not c.startswith('ID_')]),
        ('Contrats', [c for c in _const.contrat_headers if not c.startswith('ID_')]),
        ('Collaborateurs', [c for c in _const.collaborateur_headers if not c.startswith('ID_')]),
    ]

    for sheet_name, display_cols in display_checks:
        df = sheets_data[sheet_name]
        # Check if display columns exist in DataFrame
        missing_cols = [c for c in display_cols if c not in df.columns]
        if missing_cols:
            print(f"✗ {sheet_name:15s}: Missing columns: {missing_cols}")
            all_ok = False
        else:
            print(f"✓ {sheet_name:15s}: All display columns available")

    if not all_ok:
        return False

    print("\n📋 Step 5: Simulate Dashboard data loading...")

    try:
        # This is what Dashboard._load_data does
        from src.utils.utils import PathManager

        excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

        societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
        associes_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
        contrats_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
        collaborateurs_df = pd.read_excel(excel_path, sheet_name='Collaborateurs', dtype=str).fillna('')

        print(f"✓ Societes loaded: {len(societes_df)} rows")
        print(f"✓ Associes loaded: {len(associes_df)} rows")
        print(f"✓ Contrats loaded: {len(contrats_df)} rows")
        print(f"✓ Collaborateurs loaded: {len(collaborateurs_df)} rows")

    except Exception as e:
        print(f"✗ Error loading data: {e}")
        return False

    print("\n" + "=" * 70)
    print("✅ ALL TESTS PASSED!")
    print("   Dashboard will correctly load and display data from:")
    print("   ✓ Societes sheet")
    print("   ✓ Associes sheet")
    print("   ✓ Contrats sheet")
    print("   ✓ Collaborateurs sheet")
    print("=" * 70)

    return True

if __name__ == '__main__':
    try:
        success = test_dashboard_data()
        sys.exit(0 if success else 1)
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
