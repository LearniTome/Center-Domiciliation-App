import sys
import types
import unittest
from unittest.mock import patch

import tkinter as tk

# Keep imports resilient in environments where optional runtime deps are missing.
if "pandas" not in sys.modules:
    sys.modules["pandas"] = types.ModuleType("pandas")
if "openpyxl" not in sys.modules:
    openpyxl_stub = types.ModuleType("openpyxl")
    openpyxl_stub.load_workbook = lambda *args, **kwargs: None
    openpyxl_stub.Workbook = object
    sys.modules["openpyxl"] = openpyxl_stub
if "openpyxl.utils" not in sys.modules:
    openpyxl_utils_stub = types.ModuleType("openpyxl.utils")
    openpyxl_utils_stub.get_column_letter = lambda *_args, **_kwargs: "A"
    sys.modules["openpyxl.utils"] = openpyxl_utils_stub

from src.forms.associe_form import AssocieForm


class TestAssocieFormLogic(unittest.TestCase):
    def setUp(self):
        try:
            self.root = tk.Tk()
            self.root.withdraw()
        except tk.TclError as err:
            self.skipTest(f"Tk unavailable in current environment: {err}")

        self.form = AssocieForm(
            self.root,
            get_societe_totals=lambda: ("100000", "1000"),
        )
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

    def test_civility_normalization(self):
        self.assertEqual("Monsieur", AssocieForm.normalize_civility("M."))
        self.assertEqual("Madame", AssocieForm.normalize_civility("mme"))
        self.assertEqual("Monsieur", AssocieForm.normalize_civility("Monsieur"))

    def test_legacy_bool_parsing_in_set_values(self):
        self.form.set_values(
            [
                {
                    "civilite": "M.",
                    "nom": "DOE",
                    "prenom": "John",
                    "percentage": "100",
                    "est_gerant": "0",
                }
            ]
        )
        values = self.form.get_values()
        self.assertEqual("Monsieur", values[0]["civilite"])
        self.assertFalse(values[0]["est_gerant"])

    def test_add_associe_preserves_existing_distribution(self):
        self.form.add_associe()
        self.form.associe_vars[0]["percentage"].set("60")
        self.form.add_associe()
        self.assertEqual("60", self.form.associe_vars[0]["percentage"].get())
        self.assertEqual("40", self.form.associe_vars[1]["percentage"].get())

    def test_remove_associe_does_not_force_equal_split(self):
        self.form.set_values(
            [
                {"civilite": "Monsieur", "nom": "A", "prenom": "A", "percentage": "70"},
                {"civilite": "Madame", "nom": "B", "prenom": "B", "percentage": "20"},
                {"civilite": "Madame", "nom": "C", "prenom": "C", "percentage": "10"},
            ]
        )
        with patch("src.forms.associe_form.messagebox.askyesno", return_value=True):
            first_frame = self.form.associes_frame.winfo_children()[0]
            self.form.remove_associe(first_frame, self.form.associe_vars[0])

        self.assertEqual(2, len(self.form.associe_vars))
        self.assertEqual("20", self.form.associe_vars[0]["percentage"].get())
        self.assertEqual("10", self.form.associe_vars[1]["percentage"].get())

    def test_equalize_button_repartit_equitablement(self):
        self.form.set_values(
            [
                {"civilite": "Monsieur", "nom": "A", "prenom": "A", "percentage": "80"},
                {"civilite": "Madame", "nom": "B", "prenom": "B", "percentage": "20"},
            ]
        )
        self.form.equalize_button.invoke()
        self.assertEqual("50", self.form.associe_vars[0]["percentage"].get())
        self.assertEqual("50", self.form.associe_vars[1]["percentage"].get())

    def test_validate_for_submit_nom_prenom_and_percentage_rules(self):
        self.form.set_values(
            [
                {"civilite": "Monsieur", "nom": "", "prenom": "A", "percentage": "70"},
                {"civilite": "Madame", "nom": "B", "prenom": "", "percentage": "40"},
            ]
        )
        ok, errors = self.form.validate_for_submit(show_dialog=False)
        self.assertFalse(ok)
        self.assertTrue(any("nom est obligatoire" in err for err in errors))
        self.assertTrue(any("prénom est obligatoire" in err for err in errors))
        self.assertTrue(any("dépasse 100%" in err for err in errors))

    def test_validate_for_submit_accepts_total_less_than_100(self):
        self.form.set_values(
            [
                {"civilite": "Monsieur", "nom": "A", "prenom": "A", "percentage": "40"},
                {"civilite": "Madame", "nom": "B", "prenom": "B", "percentage": "30"},
            ]
        )
        ok, errors = self.form.validate_for_submit(show_dialog=False)
        self.assertTrue(ok)
        self.assertEqual([], errors)

    def test_computed_fields_are_readonly(self):
        self.form.add_associe()
        vars_dict = self.form.associe_vars[0]
        capital_entry, parts_entry = self.form._capital_entry_widgets[id(vars_dict)]
        self.assertEqual("readonly", str(capital_entry.cget("state")))
        self.assertEqual("readonly", str(parts_entry.cget("state")))


if __name__ == "__main__":
    unittest.main()
