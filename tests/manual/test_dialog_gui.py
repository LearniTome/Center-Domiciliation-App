#!/usr/bin/env python3
"""Debug script to test the GenerationSelectorDialog directly."""

import tkinter as tk
from pathlib import Path
import sys

# Add src to path so we can import modules
sys.path.insert(0, str(Path(__file__).parent))

from src.forms.generation_selector import GenerationSelectorDialog

# Create minimal main window
root = tk.Tk()
root.title("Debug - Main App")
root.geometry("400x300")

# Add a button to open the dialog
def open_dialog():
    dialog = GenerationSelectorDialog(root)
    root.wait_window(dialog)

btn = tk.Button(root, text="Ouvrir GenerationSelectorDialog", command=open_dialog)
btn.pack(pady=20)

# Add debug info
info = tk.Text(root, height=10, width=50, state='disabled')
info.pack(fill='both', expand=True, padx=10, pady=10)

def update_debug():
    info.config(state='normal')
    info.delete('1.0', 'end')
    
    from src.utils.utils import PathManager
    models_dir = PathManager.MODELS_DIR
    
    # Count templates per directory
    info.insert('end', f"Models Directory: {models_dir}\n\n")
    
    if models_dir.exists():
        for form in ['SARL AU', 'SARL', 'Personne Physique', 'SA']:
            form_path = models_dir / form
            if form_path.exists():
                count = len(list(form_path.glob('*.docx')))
                info.insert('end', f"✅ {form}: {count} templates\n")
            else:
                info.insert('end', f"❌ {form}: NOT FOUND\n")
    
    info.config(state='disabled')

update_debug()

root.mainloop()
