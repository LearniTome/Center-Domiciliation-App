import tkinter as tk
import unittest
from unittest.mock import Mock, patch

from src.forms.main_form import MainForm


def _walk_widgets(root):
    stack = [root]
    while stack:
        widget = stack.pop()
        yield widget
        try:
            stack.extend(widget.winfo_children())
        except Exception:
            pass


def _find_button_by_text(root, expected_text):
    for widget in _walk_widgets(root):
        try:
            if str(widget.winfo_class()) == "TButton" and widget.cget("text") == expected_text:
                return widget
        except Exception:
            continue
    return None


def _find_tree_by_first_column(root, first_column):
    for widget in _walk_widgets(root):
        try:
            if str(widget.winfo_class()) == "Treeview":
                columns = tuple(widget["columns"])
                if columns and columns[0] == first_column:
                    return widget
        except Exception:
            continue
    return None


def _find_toplevel_by_title(root, expected_title):
    for child in root.winfo_children():
        if isinstance(child, tk.Toplevel):
            try:
                if child.title() == expected_title:
                    return child
            except Exception:
                continue
    return None


class TestConfigurationHub(unittest.TestCase):
    def setUp(self):
        try:
            self.root = tk.Tk()
            self.root.withdraw()
        except tk.TclError as err:
            self.skipTest(f"Tk unavailable in current environment: {err}")
        self.form = MainForm(self.root, {})
        self.form.pack()
        self.root.update_idletasks()

    def tearDown(self):
        for child in list(self.root.winfo_children()):
            try:
                child.destroy()
            except Exception:
                pass
        try:
            self.root.destroy()
        except Exception:
            pass

    def test_configuration_hub_has_two_actions_and_routes_to_handlers(self):
        mock_defaults = Mock()
        mock_analyzer = Mock()
        self.form._open_defaults_dialog = mock_defaults
        self.form._open_template_values_analyzer_dialog = mock_analyzer

        self.form.open_configuration()
        self.root.update()
        self.root.update_idletasks()

        hub = _find_toplevel_by_title(self.root, "Configuration")
        self.assertIsNotNone(hub)

        defaults_btn = _find_button_by_text(hub, "⚙ Valeurs par défaut")
        analyzer_btn = _find_button_by_text(hub, "📊 Analyse des valeurs templates")
        self.assertIsNotNone(defaults_btn)
        self.assertIsNotNone(analyzer_btn)

        defaults_btn.invoke()
        self.root.update()
        self.root.update_idletasks()
        mock_defaults.assert_called_once()

        self.form.open_configuration()
        self.root.update()
        self.root.update_idletasks()
        hub = _find_toplevel_by_title(self.root, "Configuration")
        analyzer_btn = _find_button_by_text(hub, "📊 Analyse des valeurs templates")
        analyzer_btn.invoke()
        self.root.update()
        self.root.update_idletasks()
        mock_analyzer.assert_called_once()

    def test_template_analyzer_dialog_refresh_button_calls_analyze(self):
        fake_data = {
            "summary": {
                "total_templates": 1,
                "templates_with_variables": 1,
                "total_variable_occurrences": 1,
                "total_distinct_variables": 1,
                "covered_variables": 1,
                "uncovered_variables": 0,
            },
            "variables": [
                {
                    "variable": "DEN_STE",
                    "occurrences": 1,
                    "templates_count": 1,
                    "templates": ["tpl.docx"],
                    "section": "societe",
                    "coverage": "couvert",
                }
            ],
            "details": [
                {
                    "template": "tpl.docx",
                    "template_path": "C:/tmp/Models/tpl.docx",
                    "variable": "DEN_STE",
                    "occurrences": 1,
                    "section": "societe",
                    "coverage": "couvert",
                }
            ],
            "templates": ["tpl.docx"],
            "errors": [],
        }

        with patch("src.utils.template_value_analyzer.analyze_templates", return_value=fake_data) as analyze_mock:
            self.form._open_template_values_analyzer_dialog()
            self.root.update()
            self.root.update_idletasks()

            dialog = _find_toplevel_by_title(self.root, "Configuration - Analyse des valeurs templates")
            self.assertIsNotNone(dialog)
            refresh_btn = _find_button_by_text(dialog, "🔄 Actualiser")
            export_csv_btn = _find_button_by_text(dialog, "📤 Export CSV")
            export_excel_btn = _find_button_by_text(dialog, "📗 Export Excel")
            open_template_btn = _find_button_by_text(dialog, "📂 Ouvrir template")
            self.assertIsNotNone(refresh_btn)
            self.assertIsNotNone(export_csv_btn)
            self.assertIsNotNone(export_excel_btn)
            self.assertIsNotNone(open_template_btn)

            initial_calls = analyze_mock.call_count
            refresh_btn.invoke()
            self.root.update()
            self.root.update_idletasks()
            self.assertGreaterEqual(analyze_mock.call_count, initial_calls + 1)

    def test_template_analyzer_open_template_uses_system_opener(self):
        fake_data = {
            "summary": {
                "total_templates": 1,
                "templates_with_variables": 1,
                "total_variable_occurrences": 1,
                "total_distinct_variables": 1,
                "covered_variables": 1,
                "uncovered_variables": 0,
            },
            "variables": [
                {
                    "variable": "DEN_STE",
                    "occurrences": 1,
                    "templates_count": 1,
                    "templates": ["tpl.docx"],
                    "section": "societe",
                    "coverage": "couvert",
                }
            ],
            "details": [
                {
                    "template": "tpl.docx",
                    "template_path": "C:/tmp/Models/tpl.docx",
                    "variable": "DEN_STE",
                    "occurrences": 1,
                    "section": "societe",
                    "coverage": "couvert",
                }
            ],
            "templates": ["tpl.docx"],
            "errors": [],
        }

        with patch("src.utils.template_value_analyzer.analyze_templates", return_value=fake_data), \
             patch("pathlib.Path.exists", return_value=True), \
             patch.object(MainForm, "_open_path_in_system") as open_mock:
            self.form._open_template_values_analyzer_dialog()
            self.root.update()
            self.root.update_idletasks()

            dialog = _find_toplevel_by_title(self.root, "Configuration - Analyse des valeurs templates")
            self.assertIsNotNone(dialog)
            detail_tree = _find_tree_by_first_column(dialog, "template")
            self.assertIsNotNone(detail_tree)
            rows = detail_tree.get_children("")
            self.assertTrue(rows)
            detail_tree.selection_set(rows[0])
            detail_tree.focus(rows[0])

            open_template_btn = _find_button_by_text(dialog, "📂 Ouvrir template")
            self.assertIsNotNone(open_template_btn)
            open_template_btn.invoke()
            self.root.update()
            self.root.update_idletasks()

            open_mock.assert_called_once()


if __name__ == "__main__":
    unittest.main()
