#!/usr/bin/env python
"""Test the fixed generation selector"""

from pathlib import Path

# Test the keywords matching
CREATION_TEMPLATES_KEYWORDS = ['SARL', 'Statuts', 'Annonce', 'Décl', 'Dépot', 'AU', 'Decl']
DOMICILIATION_TEMPLATES = ['Attest', 'Contrat', 'domiciliation']

# Simulate the template files
test_files = [
    'My_Annonce_Journal.docx',
    'My_Attest_domiciliation.docx',
    'My_Contrat_domiciliation.docx',
    'My_Décl_Imm_Rc.docx',
    'My_Dépot_Légal.docx',
    'My_Statuts_SARL_AU.docx',
]

print("Testing CREATION template matching:")
print("=" * 60)
for filename in test_files:
    filename_lower = filename.lower()
    matches = [kw for kw in CREATION_TEMPLATES_KEYWORDS if kw.lower() in filename_lower]
    is_creation = any(kw.lower() in filename_lower for kw in CREATION_TEMPLATES_KEYWORDS)
    print(f"  {filename:40} → {'✓ CREATION' if is_creation else '✗ NOT creation'} {matches}")

print("\n" + "=" * 60)
print("Testing DOMICILIATION template matching:")
print("=" * 60)
for filename in test_files:
    filename_lower = filename.lower()
    matches = [kw for kw in DOMICILIATION_TEMPLATES if kw.lower() in filename_lower]
    is_domiciliation = any(kw.lower() in filename_lower for kw in DOMICILIATION_TEMPLATES)
    print(f"  {filename:40} → {'✓ DOMICILIATION' if is_domiciliation else '✗ NOT domiciliation'} {matches}")

print("\n" + "=" * 60)
print("\nExpected Results:")
print("  Creation files (should have ✓):")
print("    - My_Annonce_Journal.docx")
print("    - My_Décl_Imm_Rc.docx")
print("    - My_Dépot_Légal.docx")
print("    - My_Statuts_SARL_AU.docx")
print("\n  Domiciliation files (should have ✓):")
print("    - My_Attest_domiciliation.docx")
print("    - My_Contrat_domiciliation.docx")
