import tempfile
import unittest
from pathlib import Path

import pandas as pd

from src.utils import constants as _const
from src.utils.utils import ensure_excel_db, write_records_to_db
from tests.excel_utils import apply_excel_aliases


class TestSocieteGenerationDbFields(unittest.TestCase):
    def test_write_records_persists_generation_fields(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            db = Path(tmp_dir) / "db_generation_fields.xlsx"
            ensure_excel_db(db, _const.excel_sheets)

            soc = {
                "denomination": "TEST STE",
                "forme_juridique": "SARL AU",
                "type_generation": "creation",
                "procedure_creation": "acceleree",
                "mode_depot_creation": "depot_en_ligne",
            }
            write_records_to_db(db, soc, [], {})

            df = pd.read_excel(db, sheet_name="Societes", dtype=str).fillna("")
            df = apply_excel_aliases(df, "Societes")
            self.assertIn("type_generation", df.columns)
            self.assertIn("procedure_creation", df.columns)
            self.assertIn("mode_depot_creation", df.columns)

            row = df.iloc[-1]
            self.assertEqual("creation", str(row.get("type_generation", "")).strip())
            self.assertEqual("acceleree", str(row.get("procedure_creation", "")).strip())
            self.assertEqual("depot_en_ligne", str(row.get("mode_depot_creation", "")).strip())

    def test_ensure_excel_db_upgrades_legacy_societe_columns(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            db = Path(tmp_dir) / "db_legacy_societe.xlsx"
            legacy_societe_headers = [
                "ID_SOCIETE", "DEN_STE", "FORME_JUR", "ICE", "DATE_ICE", "CAPITAL",
                "PART_SOCIAL", "VALEUR_NOMINALE", "DATE_EXP_CERT_NEG", "STE_ADRESS", "TRIBUNAL",
            ]
            legacy_row = {
                "ID_SOCIETE": "1",
                "DEN_STE": "LEGACY STE",
                "FORME_JUR": "SARL",
                "ICE": "123",
                "DATE_ICE": "01/01/2026",
                "CAPITAL": "10000",
                "PART_SOCIAL": "100",
                "VALEUR_NOMINALE": "100",
                "DATE_EXP_CERT_NEG": "01/01/2027",
                "STE_ADRESS": "CASABLANCA",
                "TRIBUNAL": "Casablanca",
            }

            with pd.ExcelWriter(db, engine="openpyxl") as writer:
                pd.DataFrame([legacy_row], columns=legacy_societe_headers).to_excel(
                    writer, sheet_name="Societes", index=False
                )
                pd.DataFrame(columns=_const.associe_headers).to_excel(writer, sheet_name="Associes", index=False)
                pd.DataFrame(columns=_const.contrat_headers).to_excel(writer, sheet_name="Contrats", index=False)

            ensure_excel_db(db, _const.excel_sheets)

            upgraded = pd.read_excel(db, sheet_name="Societes", dtype=str).fillna("")
            upgraded = apply_excel_aliases(upgraded, "Societes")
            self.assertIn("type_generation", upgraded.columns)
            self.assertIn("procedure_creation", upgraded.columns)
            self.assertIn("mode_depot_creation", upgraded.columns)
            self.assertEqual("LEGACY STE", str(upgraded.iloc[0].get("den_ste", "")).strip())


if __name__ == "__main__":
    unittest.main()
