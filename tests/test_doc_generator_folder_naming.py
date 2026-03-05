from src.utils.doc_generator import render_templates
from pathlib import Path
import datetime


def test_render_templates_uses_societe_denomination_for_folder(tmp_path):
    out_dir = tmp_path / 'out'
    out_dir.mkdir()

    models_dir = Path('Models')

    # supply a values dict with nested 'societe' containing denomination
    company_name = 'ACME SARL'
    values = {'societe': {'denomination': company_name, 'forme_juridique': 'SARL AU'}}

    report = render_templates(
        values,
        templates_dir=str(models_dir),
        out_dir=str(out_dir),
        to_pdf=False,
        generation_type='creation',
        legal_form='SARL AU',
    )

    # generation folder should be present inside out_dir
    today = datetime.date.today().strftime('%Y-%m-%d')
    expected_folder_prefix = f"{today}_DosCré_SARLAU_ACME_SARL"

    folders = [p.name for p in out_dir.iterdir() if p.is_dir()]
    assert any(
        f.startswith(expected_folder_prefix) for f in folders
    ), f"Expected folder with prefix {expected_folder_prefix} not found in {folders}"

    # Verify report entries point to files inside that folder and filenames are prefixed
    assert isinstance(report, list)
    assert len(report) > 0
    for entry in report:
        out_docx = entry.get('out_docx')
        assert out_docx is not None
        filename = Path(out_docx).name
        assert filename.startswith(f"{today}_SARLAU_")
        assert filename.endswith("_ACME_SARL.docx")
