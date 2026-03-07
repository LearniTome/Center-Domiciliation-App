#!/usr/bin/env python3
"""
Test script to launch the GenerationSelectorDialog and verify fixes.
"""

import tkinter as tk
from tkinter import ttk, messagebox
from pathlib import Path
import sys
import logging

# Configure logging
logging.basicConfig(level=logging.DEBUG)
logger = logging.getLogger(__name__)

# Add src to path
sys.path.insert(0, str(Path(__file__).parent))

from src.utils.utils import PathManager, ThemeManager
from src.forms.generation_selector import GenerationSelectorDialog

def main():
    """Launch the main test window."""
    root = tk.Tk()
    root.title("🧪 Test - GenerationSelectorDialog")
    root.geometry("500x300")
    
    # Apply theme
    ThemeManager(root)
    
    # Title
    title = ttk.Label(root, text="Test GenerationSelectorDialog", font=('Segoe UI', 14, 'bold'))
    title.pack(pady=20)
    
    # Info frame
    info_frame = ttk.LabelFrame(root, text="Info", padding=10)
    info_frame.pack(fill='both', expand=True, padx=20, pady=20)
    
    # Models info
    try:
        models_dir = PathManager.MODELS_DIR
        info_text = f"✅ Models Directory: {models_dir.name}\n"
        info_text += f"   Exists: {models_dir.exists()}\n\n"
        
        info_text += "📂 Templates per form:\n"
        for form in ['SARL AU', 'SARL', 'Personne Physique', 'SA']:
            form_path = models_dir / form
            if form_path.exists():
                count = len(list(form_path.glob('*.docx')))
                info_text += f"   ✅ {form}: {count} templates\n"
            else:
                info_text += f"   ❌ {form}: NOT FOUND\n"
    except Exception as e:
        info_text = f"❌ Error: {e}"
        logger.exception("Error checking models")
    
    info_label = ttk.Label(info_frame, text=info_text, justify='left')
    info_label.pack(anchor='w')
    
    # Test button
    def open_dialog():
        logger.info("Opening GenerationSelectorDialog...")
        try:
            # Sample values
            test_values = {
                'societe': {'DenSte': 'Test Company', 'FormJur': 'SARL AU'},
                'associes': [],
                'contrat': {}
            }
            
            dialog = GenerationSelectorDialog(root, values=test_values, output_format='docx')
            root.wait_window(dialog)
            
            # Show results
            if dialog.result:
                messagebox.showinfo("Success", f"Selected {len(dialog.selected_templates)} templates")
                logger.info(f"Selected templates: {dialog.selected_templates}")
            else:
                messagebox.showinfo("Cancelled", "Dialog was cancelled")
        except Exception as e:
            logger.exception(f"Error opening dialog: {e}")
            messagebox.showerror("Error", f"Error opening dialog:\n{e}")
    
    btn = ttk.Button(root, text="📄 Open GenerationSelectorDialog", command=open_dialog)
    btn.pack(pady=20)
    
    root.mainloop()

if __name__ == "__main__":
    main()
