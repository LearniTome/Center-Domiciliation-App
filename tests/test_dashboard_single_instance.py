import os
import sys
from pathlib import Path

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

from main import MainApp
from src.forms import dashboard_view as dashboard_view_module


class _FakeDashboard:
    created_count = 0

    def __init__(self, parent, action_handler=None, start_fullscreen=False):
        type(self).created_count += 1
        self.parent = parent
        self.action_handler = action_handler
        self.start_fullscreen = start_fullscreen
        self._exists = True

    def winfo_exists(self):
        return self._exists

    def deiconify(self):
        return None

    def lift(self):
        return None

    def focus_force(self):
        return None

    def _enter_fullscreen(self):
        return None

    def destroy(self):
        self._exists = False


def test_dashboard_single_instance(monkeypatch):
    monkeypatch.setenv("APP_START_DASHBOARD", "0")
    monkeypatch.setattr(dashboard_view_module, "DashboardView", _FakeDashboard)
    _FakeDashboard.created_count = 0

    app = MainApp()
    app.update()
    try:
        first = app.main_form.show_dashboard(start_fullscreen=True)
        second = app.main_form.show_dashboard(start_fullscreen=True)

        assert first is not None
        assert second is first
        assert _FakeDashboard.created_count == 1
    finally:
        try:
            if getattr(app.main_form, "_dashboard_window", None) is not None:
                app.main_form._dashboard_window.destroy()
        except Exception:
            pass
        app.destroy()


def test_startup_open_dashboard_idempotent(monkeypatch):
    monkeypatch.setenv("APP_START_DASHBOARD", "0")
    monkeypatch.setattr(dashboard_view_module, "DashboardView", _FakeDashboard)
    _FakeDashboard.created_count = 0

    app = MainApp()
    app.update()
    try:
        app._open_dashboard_on_startup()
        app._open_dashboard_on_startup()
        assert _FakeDashboard.created_count == 1
    finally:
        try:
            if getattr(app.main_form, "_dashboard_window", None) is not None:
                app.main_form._dashboard_window.destroy()
        except Exception:
            pass
        app.destroy()
