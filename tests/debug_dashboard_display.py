#!/usr/bin/env python3
"""
Debug: Check why Dashboard doesn't display data
"""
import os
import sys
from pathlib import Path

PROJECT_ROOT = str(Path(__file__).parent)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

import pandas as pd
from src.utils import constants as const
from src.utils.utils import PathManager

# Load data
excel_path = PathManager.get_database_path('DataBase_domiciliation.xlsx')
societes_df = pd.read_excel(excel_path, sheet_name='Societes', dtype=str).fillna('')

print("=" * 80)
print("DEBUG: Data display in Dashboard")
print("=" * 80)

print("\n📋 DataFrame loaded from Excel:")
print(f"  Shape: {societes_df.shape}")
print(f"  Columns: {list(societes_df.columns)}")
print(f"\nData:")
print(societes_df)

print("\n" + "-" * 80)
print("Display columns (without ID_):")
display_cols = [c for c in const.societe_headers if not c.startswith('ID_')]
print(f"  {display_cols}")

print("\n" + "-" * 80)
print("Simulating Treeview column extraction:")

# This is what tree["columns"] returns
tree_columns_tuple = tuple(display_cols)
print(f"  tree['columns'] = {tree_columns_tuple}")
print(f"  Type: {type(tree_columns_tuple)}")

print("\n" + "-" * 80)
print("Extracting values for row 0:")

if len(societes_df) > 0:
    row = societes_df.iloc[0]
    print(f"\n  Row data (Series):")
    print(f"    {dict(row)}")

    print(f"\n  Method 1: Using tree['columns'] tuple")
    values_method1 = [str(row.get(col, '')) for col in tree_columns_tuple]
    print(f"    Values: {values_method1}")

    print(f"\n  Method 2: Using list(tree['columns'])")
    values_method2 = [str(row.get(col, '')) for col in list(tree_columns_tuple)]
    print(f"    Values: {values_method2}")

    print(f"\n  Method 3: Accessing by position")
    # This is WRONG - accessing wrong columns!
    print(f"    If accessing columns list position instead of name:")
    try:
        values_method3 = [str(row[col]) for col in range(len(tree_columns_tuple))]
        print(f"    Would fail - accessing row by index instead of column name")
    except:
        print(f"    ❌ ERROR - this would cause problems!")

    print(f"\n  ✓ CORRECT METHOD:")
    correct_values = []
    for col in display_cols:
        val = row.get(col, '')
        correct_values.append(str(val))
    print(f"    Values: {correct_values}")
else:
    print("  No data in sheet!")

print("\n" + "=" * 80)
print("Issue identified:")
print("  The problem is in _refresh_display() when getting column names from tree")
print("  It should explicitly use the display columns, not try to get them from tree")
print("=" * 80 + "\n")
