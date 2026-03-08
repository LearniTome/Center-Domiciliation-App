#!/usr/bin/env python3
"""
Quick test launcher - Opens the GenerationSelectorDialog directly.
Use this to quickly test the manual template selection dialog.
"""

import tkinter as tk
from tkinter import ttk
from pathlib import Path
import sys

# Add src to path
sys.path.insert(0, str(Path(__file__).parent))

from src.utils.utils import ThemeManager, PathManager
from src.forms.generation_selector import GenerationSelectorDialog

def main():
    """Launch the quick test window."""
    root = tk.Tk()
    root.withdraw()  # Hide main window, we only want the dialog
    
    # Apply theme
    theme = ThemeManager(root)
    
    # Create test values
    test_values = {
        'societe': {
            'DenSte': 'Société Test',
            'FormJur': 'SARL'
        },
        'associes': [],
        'contrat': {}
    }
    
    # Open the dialog
    print("🚀 Ouverture du GenerationSelectorDialog...")
    print(f"📁 Modèles trouvés à: {PathManager.MODELS_DIR}")
    print()
    print("✅ Vérifications:")
    print("  1. Les modèles sont-ils affichés avec cases à cocher ?")
    print("  2. Les bons modèles sont-ils pré-cochés selon le type choisi ?")
    print("  3. Les boutons de gestion sont-ils sous la liste, hors du cadre des modèles ?")
    print("  4. Les boutons 'Procéder' et 'Annuler' sont-ils visibles en bas ?")
    print()
    
    dialog = GenerationSelectorDialog(root, values=test_values)
    root.wait_window(dialog)
    
    # Show results
    if dialog.result:
        print(f"\n✅ {len(dialog.selected_templates)} modèles sélectionnés")
        for tpl in dialog.selected_templates:
            print(f"   • {tpl.name}")
    else:
        print("\n❌ Dialogue annulée")
    
    root.destroy()

if __name__ == "__main__":
    main()
