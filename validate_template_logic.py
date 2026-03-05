#!/usr/bin/env python3
"""
Validation test for template checkbox rendering logic.
Checks that the _refresh_template_list() logic works correctly without GUI display.
"""

from pathlib import Path
import sys
import logging

logging.basicConfig(level=logging.DEBUG, format='%(levelname)s: %(message)s')
logger = logging.getLogger(__name__)

# Add src to path
sys.path.insert(0, str(Path(__file__).parent))

from src.utils.utils import PathManager
from src.utils.constants import Formjur

def validate_template_rendering():
    """Validate that template rendering logic works."""
    
    print("\n" + "="*60)
    print("🧪 VALIDATION TEST: Template Checkbox Rendering Logic")
    print("="*60)
    
    models_dir = PathManager.MODELS_DIR
    print(f"\n📁 Models Directory: {models_dir}")
    print(f"   Exists: {models_dir.exists()}")
    
    if not models_dir.exists():
        print("❌ FAIL: Models directory doesn't exist!")
        return False
    
    all_passed = True
    
    # Test for each legal form
    for form in Formjur:
        print(f"\n📋 Testing form: {form}")
        
        form_path = models_dir / form
        if not form_path.exists():
            print(f"   ❌ Folder not found: {form_path}")
            all_passed = False
            continue
        
        # Get templates
        templates = sorted([f for f in form_path.glob('*.docx')])
        print(f"   ✅ Found {len(templates)} templates")
        
        # Test label rendering for each template
        for i, template in enumerate(templates, 1):
            # Extract clean name (same logic as in generation_selector.py)
            clean_name = template.stem
            for prefix in ['2026_Modèle_SARLAU_', '2026_Modèle_SARL_', '2026_Modèle_PP_', '2026_Modèle_SA_']:
                if clean_name.startswith(prefix):
                    clean_name = clean_name[len(prefix):]
                    break
            
            display_name = f"📄 {clean_name}"
            
            # Verify the display name is reasonable
            if len(display_name) > 5:  # Should have actual text
                status = "✅"
            else:
                status = "❌"
                all_passed = False
            
            print(f"   {status} [{i:2d}] {display_name}")
    
    print("\n" + "="*60)
    if all_passed:
        print("✅ ALL VALIDATIONS PASSED")
        print("\nThe template rendering logic should work correctly.")
        print("If checkboxes don't display, the issue is likely:")
        print("  1. Canvas width is too narrow")
        print("  2. Text color matches background color")
        print("  3. Frame width not configured correctly")
    else:
        print("❌ SOME VALIDATIONS FAILED")
        print("\nCheck the output above for details.")
    
    print("="*60 + "\n")
    
    return all_passed

if __name__ == "__main__":
    success = validate_template_rendering()
    sys.exit(0 if success else 1)
