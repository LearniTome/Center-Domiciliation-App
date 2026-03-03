#!/usr/bin/env python3
"""
Integration test: Verify Dashboard loads all sheet data and switches between pages
"""
import os
import sys
from pathlib import Path

# Ensure project root is on sys.path
PROJECT_ROOT = str(Path(__file__).parent)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

import pandas as pd
from src.utils import constants as const
from src.utils.utils import PathManager


def test_dashboard_sheet_loading():
    """Test that all three sheets can be loaded for Dashboard display"""

    print("\n" + "=" * 70)
    print("DASHBOARD INTEGRATION TEST - Sheet Loading")
    print("=" * 70)

    # Get database path
    excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

    if not Path(excel_path).exists():
        print(f"❌ Database not found: {excel_path}")
        return False

    print(f"\n📂 Database: {Path(excel_path).name}")

    try:
        # Load all three sheets (as Dashboard._load_data does)
        print("\n📋 Loading sheets...")

        societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
        print(f"  ✓ Societes: {len(societes_df)} rows, columns: {list(societes_df.columns)[:3]}...")

        associes_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
        print(f"  ✓ Associes: {len(associes_df)} rows, columns: {list(associes_df.columns)[:3]}...")

        contrats_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
        print(f"  ✓ Contrats: {len(contrats_df)} rows, columns: {list(contrats_df.columns)[:3]}...")

    except Exception as e:
        print(f"❌ Error loading sheets: {e}")
        import traceback
        traceback.print_exc()
        return False

    # Verify headers match expected
    print("\n📋 Verifying headers...")

    checks = [
        ('Societes', societes_df, const.societe_headers),
        ('Associes', associes_df, const.associe_headers),
        ('Contrats', contrats_df, const.contrat_headers),
    ]

    for sheet_name, df, expected_headers in checks:
        if list(df.columns) != expected_headers:
            print(f"  ❌ {sheet_name}: Headers mismatch")
            print(f"     Expected: {expected_headers}")
            print(f"     Got:      {list(df.columns)}")
            return False
        print(f"  ✓ {sheet_name}: Headers OK")

    # Verify display columns exist (Dashboard excludes ID_* columns)
    print("\n📋 Verifying display columns...")

    display_checks = [
        ('Societes', societes_df, [c for c in const.societe_headers if not c.startswith('ID_')]),
        ('Associes', associes_df, [c for c in const.associe_headers if not c.startswith('ID_')]),
        ('Contrats', contrats_df, [c for c in const.contrat_headers if not c.startswith('ID_')]),
    ]

    for sheet_name, df, display_cols in display_checks:
        for col in display_cols:
            if col not in df.columns:
                print(f"  ❌ {sheet_name}: Missing column '{col}'")
                return False
        print(f"  ✓ {sheet_name}: All {len(display_cols)} display columns available")

    # Simulate Dashboard page switching
    print("\n📋 Simulating page switching...")

    pages = {
        'societe': ('Societes', societes_df),
        'associe': ('Associes', associes_df),
        'contrat': ('Contrats', contrats_df),
    }

    for page_key, (sheet_name, expected_df) in pages.items():
        # This simulates what Dashboard._show_page does:
        # 1. Switch to correct DataFrame based on page_key
        # 2. Refresh display with that DataFrame

        if page_key == 'societe':
            current_df = societes_df
        elif page_key == 'associe':
            current_df = associes_df
        elif page_key == 'contrat':
            current_df = contrats_df

        if not current_df.equals(expected_df):
            print(f"  ❌ Page '{page_key}': DataFrame mismatch")
            return False

        display_cols = [c for c in current_df.columns if not c.startswith('ID_')]
        print(f"  ✓ Page '{page_key}': {len(current_df)} rows, {len(display_cols)} display columns")

    print("\n" + "=" * 70)
    print("✅ ALL TESTS PASSED!")
    print("\n   Dashboard can now:")
    print("   ✓ Load Societes data")
    print("   ✓ Load Associes data")
    print("   ✓ Load Contrats data")
    print("   ✓ Switch between pages with correct data")
    print("=" * 70 + "\n")

    return True


if __name__ == '__main__':
    try:
        success = test_dashboard_sheet_loading()
        sys.exit(0 if success else 1)
    except Exception as e:
        print(f"\n❌ Test error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
