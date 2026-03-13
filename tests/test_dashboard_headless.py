#!/usr/bin/env python3
"""
Test: Run Dashboard in headless mode and capture what's displayed
"""
import os
import sys
from pathlib import Path
from unittest.mock import patch, MagicMock
import tkinter as tk

PROJECT_ROOT = str(Path(__file__).parent)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

def test_dashboard_headless():
    """Test Dashboard without showing GUI"""

    print("\n" + "=" * 80)
    print("HEADLESS DASHBOARD TEST")
    print("=" * 80)

    try:
        # Import after path is set
        from src.forms.dashboard_view import DashboardView
        from src.utils.utils import PathManager
        import pandas as pd
        from tests.excel_utils import apply_excel_aliases

        # Create a headless root window
        root = tk.Tk()
        root.withdraw()  # Hide window

        print("\n✓ Created Tk root window")

        # Verify database exists
        excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')
        if not Path(excel_path).exists():
            print(f"❌ Database not found: {excel_path}")
            return False

        print(f"✓ Database found: {excel_path.name}")

        # Load expected data
        societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')
        societes_df = apply_excel_aliases(societes_df, "Societes")
        print(f"✓ Expected Societes data: {len(societes_df)} rows")

        # Create Dashboard
        print("\n📋 Creating Dashboard...")
        dashboard = DashboardView(root)
        root.update()  # Process events

        print("✓ Dashboard created successfully")

        # Check what data the dashboard loaded
        print("\n📊 Dashboard internal state:")
        print(f"  self._df shape: {dashboard._df.shape if dashboard._df is not None else 'None'}")
        print(f"  self._societes_df shape: {dashboard._societes_df.shape if dashboard._societes_df is not None else 'None'}")

        if dashboard._df is not None and len(dashboard._df) > 0:
            print(f"  ✓ DataFrame has {len(dashboard._df)} rows")
        else:
            print(f"  ❌ DataFrame is empty or None")
            return False

        # Check Treeview contents
        print("\n🎨 Treeview state:")
        societe_tree = dashboard.trees.get('societe')
        if societe_tree is not None:
            items = societe_tree.get_children()
            print(f"  Societe tree children: {len(items)} items")

            if len(items) > 0:
                first_item = items[0]
                values = societe_tree.item(first_item)['values']
                print(f"  ✓ First row has {len(values)} values:")
                for i, val in enumerate(values[:3]):
                    print(f"    [{i}] {val}")
                if len(values) > 3:
                    print(f"    ... and {len(values) - 3} more")
            else:
                print(f"  ❌ Treeview has no items!")
                return False
        else:
            print(f"  ❌ Societe tree not found!")
            return False

        # Cleanup
        dashboard._on_close(call_parent=False)
        root.destroy()

        print("\n" + "=" * 80)
        print("✅ HEADLESS TEST PASSED!")
        print("   Dashboard loaded data and populated Treeview")
        print("=" * 80 + "\n")

        return True

    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        return False


if __name__ == '__main__':
    success = test_dashboard_headless()
    sys.exit(0 if success else 1)
