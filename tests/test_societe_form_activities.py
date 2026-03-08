import tkinter as tk
import unittest

from src.forms.societe_form import SocieteForm
from src.utils.constants import Activities


class TestSocieteFormActivities(unittest.TestCase):
    def setUp(self):
        try:
            self.root = tk.Tk()
            self.root.withdraw()
        except tk.TclError as err:
            self.skipTest(f"Tk unavailable in current environment: {err}")
        self.form = SocieteForm(self.root)
        self.form.pack()
        self.root.update_idletasks()

    def tearDown(self):
        try:
            self.form.destroy()
        except Exception:
            pass
        try:
            self.root.destroy()
        except Exception:
            pass

    def test_default_four_activities_loaded(self):
        values = self.form.get_values()
        self.assertEqual(4, len(values.get("activites", [])))
        self.assertEqual(Activities[:4], values.get("activites", []))

    def test_add_activity_has_no_fixed_limit(self):
        initial_count = len(self.form.activites_vars)
        for _ in range(10):
            self.form.add_activity()
        self.assertEqual(initial_count + 10, len(self.form.activites_vars))


if __name__ == "__main__":
    unittest.main()
