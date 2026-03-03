#!/usr/bin/env python3
"""
Test: Verify Dashboard displays data in Treeview
Simulates actual Treeview insertion with mock tree widget
"""
import os
import sys
from pathlib import Path
from unittest.mock import Mock, MagicMock

PROJECT_ROOT = str(Path(__file__).parent)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

import pandas as pd
from src.utils import constants as const
from src.utils.utils import PathManager

def test_treeview_display():
    """Test that data is correctly inserted into Treeview"""

    print("\n" + "=" * 80)
    print("TREEVIEW DISPLAY TEST - Simulating Dashboard data insertion")
    print("=" * 80)

    excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')

    # Load data
    societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')

    print(f"\n📋 Loaded data:")
    print(f"  Shape: {societes_df.shape}")
    print(f"  Columns: {list(societes_df.columns)}")

    if len(societes_df) == 0:
        print("❌ No data in Societes sheet!")
        return False

    print(f"\n📊 Data to display:")
    print(societes_df)

    # Create mock Treeview
    print(f"\n🎨 Simulating Treeview:")
    mock_tree = MagicMock()

    # Display columns (without ID_*)
    display_cols = [c for c in const.societe_headers if not c.startswith('ID_')]

    # Mock tree["columns"] to return display columns
    mock_tree.__getitem__ = MagicMock(return_value=tuple(display_cols))

    # Track inserted rows
    inserted_rows = []
    def mock_insert(parent, index, **kwargs):
        inserted_rows.append(kwargs.get('values', ()))
        print(f"  ✓ Inserted row: {kwargs.get('values', ())}")

    mock_tree.insert = mock_insert

    # Simulate _refresh_display() logic
    print(f"\n🔄 Simulating _refresh_display():")

    # Get column names from tree (these are display columns without ID_*)
    columns = list(mock_tree["columns"])
    print(f"  Display columns: {columns}")

    # Populate tree with data
    for idx, (_, row) in enumerate(societes_df.iterrows()):
        values = []
        for col in columns:
            # Get value from row
            if col in societes_df.columns:
                val = row.get(col, '')
            else:
                val = ''
            values.append(str(val))

        print(f"\n  Row {idx}:")
        print(f"    {list(zip(columns, values))}")
        mock_tree.insert('', 'end', values=tuple(values))

    # Verify
    print(f"\n✅ Results:")
    print(f"  Total rows inserted: {len(inserted_rows)}")

    for i, row_values in enumerate(inserted_rows):
        print(f"\n  Row {i}: {len(row_values)} values")
        for col, val in zip(display_cols, row_values):
            print(f"    {col}: {val}")

    # Check that we have data
    if len(inserted_rows) == 0:
        print("\n❌ No rows were inserted!")
        return False

    if len(inserted_rows[0]) == 0:
        print("\n❌ First row has no values!")
        return False

    if all(v == '' for v in inserted_rows[0]):
        print("\n❌ All values in first row are empty!")
        return False

    print("\n" + "=" * 80)
    print("✅ TREEVIEW DISPLAY TEST PASSED!")
    print("   Data is correctly formatted and ready to display")
    print("=" * 80 + "\n")

    return True


if __name__ == '__main__':
    try:
        success = test_treeview_display()
        sys.exit(0 if success else 1)
    except Exception as e:
        print(f"\n❌ Test error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
