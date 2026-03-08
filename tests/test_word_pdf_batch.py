import unittest
from pathlib import Path
from tempfile import TemporaryDirectory
from unittest.mock import patch

from src.utils.word_pdf_batch import convert_docx_batch


def _make_fake_docx(path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(b"fake-docx-content")


class TestWordPdfBatch(unittest.TestCase):
    def test_recursive_scan_and_same_folder_output(self):
        with TemporaryDirectory() as tmp:
            tmp_path = Path(tmp)
            source = tmp_path / "source"
            nested = source / "nested"
            _make_fake_docx(source / "a.docx")
            _make_fake_docx(nested / "b.docx")

            def _fake_convert(_docx_path: Path, pdf_path: Path):
                pdf_path.write_bytes(b"%PDF-1.4 fake")

            with patch("src.utils.word_pdf_batch._detect_pdf_engine", return_value=(True, "fake")), \
                 patch("src.utils.word_pdf_batch._convert_to_pdf", side_effect=_fake_convert):
                result = convert_docx_batch(source_dir=source, recursive=True, report_root=tmp_path / "reports")

            self.assertEqual(2, result["total_files"])
            self.assertEqual(2, result["success_count"])
            self.assertEqual(0, result["error_count"])

            for item in result["files"]:
                src = Path(item["source_docx"])
                out = Path(item["out_pdf"])
                self.assertEqual(out.parent, src.parent)
                self.assertEqual(".pdf", out.suffix.lower())
                self.assertTrue(out.exists())
                self.assertEqual("ok", item["status"])

    def test_reports_are_written_with_expected_keys(self):
        with TemporaryDirectory() as tmp:
            tmp_path = Path(tmp)
            source = tmp_path / "source"
            _make_fake_docx(source / "single.docx")

            with patch("src.utils.word_pdf_batch._detect_pdf_engine", return_value=(True, "fake")), \
                 patch("src.utils.word_pdf_batch._convert_to_pdf", side_effect=lambda _docx, pdf: pdf.write_bytes(b"%PDF-1.4 fake")):
                result = convert_docx_batch(source_dir=source, recursive=True, report_root=tmp_path / "reports")

            json_report = Path(result["report_json"])
            html_report = Path(result["report_html"])
            self.assertTrue(json_report.exists())
            self.assertTrue(html_report.exists())
            self.assertEqual("ok", result["global_status"])
            self.assertTrue(result["files"])
            self.assertTrue(
                {"source_docx", "out_pdf", "status", "error", "duration_seconds"} <= set(result["files"][0].keys())
            )

    def test_global_error_when_no_engine_is_available(self):
        with TemporaryDirectory() as tmp:
            tmp_path = Path(tmp)
            source = tmp_path / "source"
            _make_fake_docx(source / "single.docx")

            with patch("src.utils.word_pdf_batch._detect_pdf_engine", return_value=(False, None)):
                result = convert_docx_batch(source_dir=source, recursive=True, report_root=tmp_path / "reports")

            self.assertEqual("error", result["global_status"])
            self.assertTrue(result["global_error"])
            self.assertEqual(1, result["total_files"])
            self.assertEqual(1, result["error_count"])
            self.assertEqual("error", result["files"][0]["status"])


if __name__ == "__main__":
    unittest.main()
