import os
import sys

# Ensure project root is on sys.path so top-level imports work
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

# Simple smoke test: import the app and create the main window briefly
try:
    from main import MainApp
except Exception as e:
    print("Import error:", e)
    sys.exit(2)

try:
    app = MainApp()
    # don't call mainloop in tests; just update and destroy
    app.update()
    print("Smoke test: MainApp instantiated successfully")
    app.destroy()
except Exception as e:
    print("Runtime error when instantiating MainApp:", e)
    sys.exit(3)

sys.exit(0)
