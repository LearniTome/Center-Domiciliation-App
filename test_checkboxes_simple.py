#!/usr/bin/env python3
"""Minimal test to see if template checkboxes appear."""

import tkinter as tk
from tkinter import ttk
from pathlib import Path

# Test 1: Can we create a simple canvas with checkboxes?
root = tk.Tk()
root.geometry("600x400")

# Create a canvas with scrollbar
canvas = tk.Canvas(root, bg='#2b2b2b', highlightthickness=0)
scrollbar = ttk.Scrollbar(root, orient='vertical', command=canvas.yview)

# Create inner frame
inner_frame = ttk.Frame(canvas)
canvas.create_window((0, 0), window=inner_frame, anchor='nw')
canvas.config(yscrollcommand=scrollbar.set)

# Add some test checkboxes
for i in range(10):
    var = tk.BooleanVar()
    chk = ttk.Checkbutton(
        inner_frame,
        text=f"📄 Test Template {i+1}",
        variable=var,
        width=80
    )
    chk.pack(anchor='w', pady=5, padx=15, fill='x')

# Update scroll region
def on_configure(event=None):
    canvas.configure(scrollregion=canvas.bbox('all'))
    canvas_width = canvas.winfo_width()
    if canvas_width > 1:
        canvas.itemconfig(canvas.create_window((0, 0), window=inner_frame, anchor='nw'), width=canvas_width)

inner_frame.bind('<Configure>', on_configure)

# Pack canvas and scrollbar
canvas.pack(side='left', fill='both', expand=True, padx=10, pady=10)
scrollbar.pack(side='right', fill='y')

# Add label
label = ttk.Label(root, text="If you see checkboxes with text below, the issue is NOT with canvas/checkbutton rendering")
label.pack(side='bottom', padx=10, pady=10)

root.mainloop()
