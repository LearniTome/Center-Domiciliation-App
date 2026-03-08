import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from src.utils.doc_generator import render_templates


class TestDocGeneratorActivities(unittest.TestCase):
    def test_render_templates_exposes_all_activities(self):
        activities = [f"ACTIVITE_{idx}" for idx in range(1, 9)]

        with tempfile.TemporaryDirectory() as tmpdir:
            tmp_path = Path(tmpdir)
            models_dir = tmp_path / "Models"
            out_dir = tmp_path / "Output"
            models_dir.mkdir(parents=True, exist_ok=True)
            out_dir.mkdir(parents=True, exist_ok=True)

            template_path = models_dir / "dummy_template.docx"
            template_path.write_bytes(b"placeholder")

            captured_context = {}

            def _fake_render(_template_path, context, out_path):
                captured_context.update(context)
                out_path.write_bytes(b"generated")

            values = {
                "societe": {
                    "denomination": "NOUVELLE STE",
                    "forme_juridique": "SARL AU",
                    "activites": activities,
                },
                "associes": [],
                "contrat": {},
            }

            with patch("src.utils.doc_generator._render_docx_template", side_effect=_fake_render):
                report = render_templates(
                    values=values,
                    templates_dir=models_dir,
                    out_dir=out_dir,
                    to_pdf=False,
                    generation_type="creation",
                    legal_form="SARL-AU",
                )

        self.assertTrue(report)
        self.assertEqual("ok", report[0].get("status"))
        self.assertEqual("ACTIVITE_1", captured_context.get("ACTIVITY1"))
        self.assertEqual("ACTIVITE_6", captured_context.get("ACTIVITY6"))
        self.assertEqual("ACTIVITE_7", captured_context.get("ACTIVITY7"))
        self.assertEqual("ACTIVITE_8", captured_context.get("ACTIVITY8"))
        self.assertIn("ACTIVITE_8", captured_context.get("ACTIVITIES", ""))
        self.assertNotIn("•", captured_context.get("ACTIVITIES", ""))
        self.assertIn("\nACTIVITE_2", captured_context.get("ACTIVITIES", ""))
        self.assertEqual(activities, captured_context.get("ACTIVITES_LIST"))
        self.assertIn("ACTIVITE_8", captured_context.get("ACTIVITES_PLAIN", ""))
        self.assertIn("• ACTIVITE_2", captured_context.get("ACTIVITES_PUCES", ""))
        self.assertEqual(len(activities), captured_context.get("ACTIVITY_COUNT"))


if __name__ == "__main__":
    unittest.main()
