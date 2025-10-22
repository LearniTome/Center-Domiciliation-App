import os
import sys

# Ensure project root is on sys.path so top-level imports work
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

from main import MainApp


def test_mainapp_instantiates():
    """Smoke test used by pytest: instantiate MainApp briefly and destroy it."""
    app = MainApp()
    # don't call mainloop in tests; just update and destroy
    app.update()
    app.destroy()
