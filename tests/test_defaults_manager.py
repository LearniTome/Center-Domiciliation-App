import unittest

from src.utils.defaults_manager import DefaultsManager


class TestDefaultsManager(unittest.TestCase):
    def test_initial_defaults_include_extended_fields(self):
        manager = DefaultsManager()
        defaults = manager._get_initial_defaults()

        self.assertIn("Nom", defaults["associe"])
        self.assertIn("Prenom", defaults["associe"])
        self.assertIn("Adresse", defaults["associe"])
        self.assertIn("TypeContratDomiciliation", defaults["contrat"])
        self.assertIn("DateIce", defaults["societe"])

    def test_merge_with_initial_defaults_backfills_missing_keys(self):
        manager = DefaultsManager()
        base = manager._get_initial_defaults()
        loaded = {
            "associe": {"Civility": "Madame"},
            "contrat": {"NbMois": "24"},
        }

        merged = manager._merge_with_initial_defaults(base, loaded)

        self.assertEqual("Madame", merged["associe"]["Civility"])
        self.assertIn("Nom", merged["associe"])
        self.assertEqual("24", merged["contrat"]["NbMois"])
        self.assertIn("TypeRenouvellement", merged["contrat"])


if __name__ == "__main__":
    unittest.main()
