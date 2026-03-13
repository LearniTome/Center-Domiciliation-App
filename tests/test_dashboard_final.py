#!/usr/bin/env python3
"""
Final test: Verify Dashboard displays all three sheets with data
"""
import os
import sys
from pathlib import Path
import tkinter as tk

PROJECT_ROOT = str(Path(__file__).parent)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

from src.forms.dashboard_view import DashboardView
from src.utils.utils import PathManager
import pandas as pd
from tests.excel_utils import apply_excel_aliases

def test_all_pages():
    """Test that all three pages display their data correctly"""

    print("\n" + "=" * 80)
    print("DASHBOARD FINAL TEST - All Three Pages")
    print("=" * 80)

    try:
        # Load expected data
        excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')
        societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
        societes_df = apply_excel_aliases(societes_df, "Societes")
        associes_df = pd.read_excel(excel_path, sheet_name='Associes', dtype=str).fillna('')
        associes_df = apply_excel_aliases(associes_df, "Associes")
        contrats_df = pd.read_excel(excel_path, sheet_name='Contrats', dtype=str).fillna('')
        contrats_df = apply_excel_aliases(contrats_df, "Contrats")

        print(f"\n📂 Database found with:")
        print(f"  - Societes: {len(societes_df)} rows")
        print(f"  - Associes: {len(associes_df)} rows")
        print(f"  - Contrats: {len(contrats_df)} rows")

        # Create headless root
        root = tk.Tk()
        root.withdraw()

        # Create Dashboard
        print(f"\n📋 Creating Dashboard...")
        dashboard = DashboardView(root)
        root.update()
        print(f"✓ Dashboard created")

        # Test each page
        pages = [
            ('societe', 'Sociétés', societes_df),
            ('associe', 'Associés', associes_df),
            ('contrat', 'Contrats', contrats_df),
        ]

        all_ok = True
        for page_key, page_title, expected_df in pages:
            print(f"\n🔄 Testing page: '{page_key}' ({page_title})")

            # Switch to page
            dashboard._show_page(page_key)
            root.update()

            # Check tree
            tree = dashboard.trees.get(page_key)
            if tree is None:
                print(f"  ❌ Tree not found!")
                all_ok = False
                continue

            items = tree.get_children()
            expected_count = len(expected_df)

            print(f"  Expected: {expected_count} rows")
            print(f"  Displayed: {len(items)} rows")

            if len(items) == expected_count:
                print(f"  ✓ Row count matches!")

                # Check first row values
                if len(items) > 0:
                    first_item = items[0]
                    values = tree.item(first_item)['values']
                    print(f"  ✓ First row has {len(values)} values")

                    # Show first few values
                    for i, val in enumerate(values[:2]):
                        print(f"    - {val}")
            else:
                print(f"  ❌ Row count mismatch!")
                all_ok = False

        # Cleanup
        dashboard._on_close(call_parent=False)
        root.destroy()

        if all_ok:
            print("\n" + "=" * 80)
            print("✅ FINAL TEST PASSED!")
            print("   All three pages display data correctly from database")
            print("=" * 80 + "\n")
        else:
            print("\n" + "=" * 80)
            print("❌ FINAL TEST FAILED!")
            print("=" * 80 + "\n")

        return all_ok

    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        return False


if __name__ == '__main__':
    success = test_all_pages()
    sys.exit(0 if success else 1)
