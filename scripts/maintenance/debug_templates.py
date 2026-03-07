#!/usr/bin/env python3
"""Debug script to check template organization and display."""

from pathlib import Path
from src.utils.utils import PathManager
from src.utils.constants import Formjur

models_dir = PathManager.MODELS_DIR
print(f"📁 Models Directory: {models_dir}")
print(f"✅ Exists: {models_dir.exists()}")
print()

print("📋 Legal Forms from constants.Formjur:")
for form in Formjur:
    print(f"  - {form}")
print()

print("📂 Templates by Legal Form:")
for form in Formjur:
    form_path = models_dir / form
    if form_path.exists():
        templates = list(form_path.glob('*.docx'))
        print(f"\n✅ {form}/ ({len(templates)} templates)")
        for tpl in sorted(templates):
            print(f"   📄 {tpl.name}")
    else:
        print(f"\n❌ {form}/ - FOLDER NOT FOUND")

# Check root Models folder
print(f"\n📂 Root Models/ directory:")
root_templates = list(models_dir.glob('*.docx'))
if root_templates:
    print(f"✅ {len(root_templates)} templates in root")
    for tpl in sorted(root_templates):
        print(f"   📄 {tpl.name}")
else:
    print(f"📌 No templates in root Models/ folder")

print("\n═════════════════════════════════════")
print("✅ Debug complete")
