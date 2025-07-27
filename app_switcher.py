import tkinter as tk
from tkinter import ttk
from Main_app import DomiciliationApp
from dashboard import DomiciliationDashboard

class AppSwitcher:
    def __init__(self, root):
        self.root = root
        self.root.title("Centre de Domiciliation")
        self.current_frame = None
        self.setup_gui()

    def setup_gui(self):
        # Frame pour les boutons de navigation
        self.nav_frame = ttk.Frame(self.root)
        self.nav_frame.pack(fill="x", pady=5)

        # Boutons de navigation
        ttk.Button(self.nav_frame, text="Application Principale",
                  command=self.show_main_app).pack(side="left", padx=5)
        ttk.Button(self.nav_frame, text="Dashboard",
                  command=self.show_dashboard).pack(side="left", padx=5)

        # Frame principal pour le contenu
        self.content_frame = ttk.Frame(self.root)
        self.content_frame.pack(fill="both", expand=True)

        # Afficher l'application principale par défaut
        self.show_main_app()

    def clear_content(self):
        if self.current_frame:
            self.current_frame.destroy()

    def show_main_app(self):
        self.clear_content()
        self.current_frame = ttk.Frame(self.content_frame)
        self.current_frame.pack(fill="both", expand=True)
        app = DomiciliationApp(self.current_frame)

    def show_dashboard(self):
        self.clear_content()
        self.current_frame = ttk.Frame(self.content_frame)
        self.current_frame.pack(fill="both", expand=True)
        dashboard = DomiciliationDashboard(self.current_frame)

if __name__ == "__main__":
    root = tk.Tk()
    root.geometry("1200x800")
    app = AppSwitcher(root)
    root.mainloop()
