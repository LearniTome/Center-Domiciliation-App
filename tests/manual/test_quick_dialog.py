#!/usr/bin/env python3
"""
Quick test launcher - Opens the GenerationSelectorDialog directly.
Use this to quickly test the template display fixes.
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
    print("  1. Les noms des modèles s'affichent-ils correctement?")
    print("  2. Les boutons 'Procéder' et 'Annuler' sont-ils visibles en bas?")
    print("  3. La fenêtre est-elle centrée à l'écran?")
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
