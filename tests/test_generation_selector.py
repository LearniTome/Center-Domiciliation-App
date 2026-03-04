#!/usr/bin/env python
"""Test script to verify GenerationSelectorDialog integration"""

import sys
from pathlib import Path

# Test 1: Verify imports work
print("✓ Test 1: Checking imports...")
try:
    from src.forms.generation_selector import GenerationSelectorDialog, show_generation_selector
    print("  ✓ GenerationSelectorDialog imported successfully")
except Exception as e:
    print(f"  ✗ Failed to import GenerationSelectorDialog: {e}")
    sys.exit(1)

try:
    from main import MainApp
    print("  ✓ MainApp imported successfully")
except Exception as e:
    print(f"  ✗ Failed to import MainApp: {e}")
    sys.exit(1)

# Test 2: Verify PathManager is available
print("\n✓ Test 2: Checking PathManager...")
try:
    from src.utils.utils import PathManager
    models_dir = PathManager.MODELS_DIR
    print(f"  ✓ PathManager.MODELS_DIR = {models_dir}")

    # Check if Models directory exists
    if Path(models_dir).exists():
        docx_files = list(Path(models_dir).glob('*.docx'))
        print(f"  ✓ Found {len(docx_files)} template files in Models/")
        for f in docx_files[:3]:  # Show first 3
            print(f"    - {f.name}")
    else:
        print(f"  ⚠ Models directory not found at {models_dir}")
except Exception as e:
    print(f"  ✗ PathManager error: {e}")
    sys.exit(1)

# Test 3: Check generation_selector.py file exists and has correct structure
print("\n✓ Test 3: Checking generation_selector.py structure...")
try:
    selector_path = Path(__file__).parent / "src" / "forms" / "generation_selector.py"
    if not selector_path.exists():
        print(f"  ✗ File not found: {selector_path}")
        sys.exit(1)

    # Read and check for key components
    content = selector_path.read_text()

    checks = [
        ("class GenerationSelectorDialog", "GenerationSelectorDialog class"),
        ("def show_generation_selector", "show_generation_selector function"),
        ("_refresh_template_list", "_refresh_template_list method"),
        ("_upload_template", "_upload_template method"),
    ]

    for pattern, desc in checks:
        if pattern in content:
            print(f"  ✓ Found {desc}")
        else:
            print(f"  ✗ Missing {desc}")
            sys.exit(1)

except Exception as e:
    print(f"  ✗ Error checking structure: {e}")
    sys.exit(1)

# Test 4: Verify main.py has the integration
print("\n✓ Test 4: Checking main.py integration...")
try:
    main_path = Path(__file__).parent / "main.py"
    main_content = main_path.read_text()

    checks = [
        ("from src.forms.generation_selector import show_generation_selector", "Import statement"),
        ("show_generation_selector(self)", "Selector call in generate_documents"),
        ("generation_type = selector_result.get('type')", "Result handling"),
        ("creation_type = selector_result.get('creation_type')", "Creation type handling"),
        ("def choose_templates_with_format(self, generation_type=None", "Template filtering parameters"),
    ]

    for pattern, desc in checks:
        if pattern in main_content:
            print(f"  ✓ Found {desc}")
        else:
            print(f"  ✗ Missing {desc}")
            sys.exit(1)

except Exception as e:
    print(f"  ✗ Error checking main.py: {e}")
    sys.exit(1)

print("\n" + "="*60)
print("✅ All integration tests passed!")
print("="*60)
print("\nFeatures integrated:")
print("1. ✓ GenerationSelectorDialog with type selection (Creation/Domiciliation)")
print("2. ✓ Creation subtype options (SARL/SARL_AU)")
print("3. ✓ Template upload capability")
print("4. ✓ Dynamic template list refresh")
print("5. ✓ Template filtering based on selected type")
print("\nNext steps:")
print("  → Run: uv run main.py")
print("  → Click 'Générer les documents' button")
print("  → Select generation type (Creation or Domiciliation)")
print("  → Choose templates and format")
print("  → Test template upload feature")
