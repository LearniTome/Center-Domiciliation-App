from src.utils.doc_generator import render_templates
from pathlib import Path
import datetime


def test_render_templates_uses_societe_denomination_for_folder(tmp_path):
    out_dir = tmp_path / 'out'
    out_dir.mkdir()

    models_dir = Path('Models')

    # supply a values dict with nested 'societe' containing denomination
    company_name = 'ACME SARL'
    values = {'societe': {'denomination': company_name}}

    report = render_templates(values, templates_dir=str(models_dir), out_dir=str(out_dir), to_pdf=False)

    # generation folder should be present inside out_dir
    today = datetime.date.today().strftime('%Y-%m-%d')
    expected_folder_prefix = f"{today}_ACME_SARL_Constitution"

    folders = [p.name for p in out_dir.iterdir() if p.is_dir()]
    assert any(f.startswith(f"{today}_ACME_SARL") for f in folders), f"Expected folder with prefix {today}_ACME_SARL not found in {folders}"

    # Verify report entries point to files inside that folder and filenames are prefixed
    assert isinstance(report, list)
    assert len(report) > 0
    for entry in report:
        out_docx = entry.get('out_docx')
        assert out_docx is not None
        # ensure the path contains the expected company prefix
        assert f"{today}_ACME_SARL_" in out_docx
