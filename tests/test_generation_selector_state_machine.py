import unittest
import sys
import types

# Test environment may not have all runtime deps; stub pandas for import-time only.
if 'pandas' not in sys.modules:
    sys.modules['pandas'] = types.ModuleType('pandas')
if 'openpyxl' not in sys.modules:
    openpyxl_stub = types.ModuleType('openpyxl')
    openpyxl_stub.load_workbook = lambda *args, **kwargs: None
    openpyxl_stub.Workbook = object
    sys.modules['openpyxl'] = openpyxl_stub
if 'openpyxl.utils' not in sys.modules:
    openpyxl_utils_stub = types.ModuleType('openpyxl.utils')
    openpyxl_utils_stub.get_column_letter = lambda *_args, **_kwargs: "A"
    sys.modules['openpyxl.utils'] = openpyxl_utils_stub

from src.forms.generation_selector import compute_selection_feedback, template_matches_generation_type
from src.utils.utils import PathManager, WidgetFactory


class TestGenerationSelectorStateMachine(unittest.TestCase):
    def test_not_ready_when_everything_missing(self):
        feedback = compute_selection_feedback("", "", 0)
        self.assertFalse(feedback["is_ready"])
        self.assertIn("Forme: —", feedback["summary"])
        self.assertIn("Nb modèles: 0", feedback["summary"])
        self.assertIn("sélectionner une forme juridique", feedback["tooltip"])

    def test_not_ready_when_partial_selection(self):
        feedback = compute_selection_feedback("SARL", "creation", 0, 4)
        self.assertFalse(feedback["is_ready"])
        self.assertIn("Forme: SARL", feedback["summary"])
        self.assertIn("Type: Création", feedback["summary"])
        self.assertIn("cocher au moins un modèle", feedback["tooltip"])

    def test_ready_when_selection_complete(self):
        feedback = compute_selection_feedback("SARL AU", "domiciliation", 3)
        self.assertTrue(feedback["is_ready"])
        self.assertIn("Forme: SARL AU", feedback["summary"])
        self.assertIn("Type: Domiciliation", feedback["summary"])
        self.assertIn("Nb modèles: 3", feedback["summary"])
        self.assertEqual("Prêt à générer.", feedback["tooltip"])

    def test_generation_type_filter_selects_all_for_creation_and_only_domiciliation_for_domiciliation(self):
        self.assertTrue(template_matches_generation_type("SARL_2026-03_Statuts_Template.docx", "creation"))
        self.assertTrue(template_matches_generation_type("SARL_2026-03_Contrat-Domiciliation_Template.docx", "creation"))
        self.assertTrue(template_matches_generation_type("SARL_2026-03_Attestation-Domiciliation_Template.docx", "domiciliation"))
        self.assertFalse(template_matches_generation_type("SARL_2026-03_Statuts_Template.docx", "domiciliation"))

    def test_all_declared_icon_files_exist(self):
        registry = WidgetFactory.get_icon_registry()
        self.assertTrue(registry, "Icon registry should not be empty")
        missing = [
            name for name in registry.values()
            if not (PathManager.ICONS_DIR / name).exists()
        ]
        self.assertEqual([], missing, f"Missing icon files: {missing}")


if __name__ == "__main__":
    unittest.main()
