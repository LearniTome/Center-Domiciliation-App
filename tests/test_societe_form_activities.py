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

    def test_clear_activities_completely_empties_table(self):
        self.assertGreater(len(self.form.activites_vars), 0)
        self.form.clear_activities_completely()
        values = self.form.get_values()
        self.assertEqual([], values.get("activites", []))

    def test_generation_options_visibility_and_values(self):
        self.form.type_generation_var.set("creation")
        self.form.procedure_creation_var.set("normal")
        self.form._update_generation_options_visibility()
        self.root.update_idletasks()

        self.assertEqual("grid", self.form.creation_procedure_frame.winfo_manager())
        self.assertEqual("", self.form.creation_depot_frame.winfo_manager())

        values = self.form.get_values()
        self.assertEqual("creation", values.get("type_generation"))
        self.assertEqual("normal", values.get("procedure_creation"))
        self.assertEqual("", values.get("mode_depot_creation"))

        self.form.procedure_creation_var.set("acceleree")
        self.form.mode_depot_creation_var.set("depot_en_ligne")
        self.form._update_generation_options_visibility()
        self.root.update_idletasks()

        self.assertEqual("grid", self.form.creation_depot_frame.winfo_manager())
        values = self.form.get_values()
        self.assertEqual("acceleree", values.get("procedure_creation"))
        self.assertEqual("depot_en_ligne", values.get("mode_depot_creation"))

        self.form.type_generation_var.set("domiciliation")
        self.form._update_generation_options_visibility()
        self.root.update_idletasks()

        self.assertEqual("", self.form.creation_procedure_frame.winfo_manager())
        self.assertEqual("", self.form.creation_depot_frame.winfo_manager())
        values = self.form.get_values()
        self.assertEqual("domiciliation", values.get("type_generation"))
        self.assertEqual("", values.get("procedure_creation"))
        self.assertEqual("", values.get("mode_depot_creation"))


if __name__ == "__main__":
    unittest.main()
