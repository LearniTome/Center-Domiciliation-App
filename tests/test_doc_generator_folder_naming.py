from src.utils.doc_generator import render_templates
from pathlib import Path
import datetime
from unittest.mock import patch
import re


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


def test_render_templates_uses_domiciliation_folder_nomenclature(tmp_path):
    out_dir = tmp_path / 'out'
    out_dir.mkdir()
    models_dir = tmp_path / 'models'
    models_dir.mkdir()
    template_path = models_dir / 'dummy_template.docx'
    template_path.write_bytes(b"placeholder")

    values = {
        'societe': {'denomination': 'ACME SARL'},
        'contrat': {
            'collaborateur': 'EXP -- Expert Comptable',
            'collaborateur_nom': 'Cabinet Atlas',
        },
    }

    def _fake_render(_template_path, _context, out_path):
        out_path.write_bytes(b"generated")

    with patch("src.utils.doc_generator._render_docx_template", side_effect=_fake_render):
        report = render_templates(
            values,
            templates_dir=str(models_dir),
            out_dir=str(out_dir),
            to_pdf=False,
            generation_type='domiciliation',
            legal_form='SARL',
        )

    assert report
    folders = [p.name for p in out_dir.iterdir() if p.is_dir()]
    assert len(folders) == 1

    year = datetime.date.today().strftime('%Y')
    expected_pattern = rf"^DOM-{year}-0001_EXP-CABINET_ATLAS_ACME_SARL$"
    assert re.match(expected_pattern, folders[0]), f"Unexpected folder name: {folders[0]}"


def test_render_templates_increments_domiciliation_sequence(tmp_path):
    out_dir = tmp_path / 'out'
    out_dir.mkdir()
    year = datetime.date.today().strftime('%Y')
    (out_dir / f"DOM-{year}-0007_CLTD-CLIENT_DIRECT_OLD_CLIENT").mkdir()

    models_dir = tmp_path / 'models'
    models_dir.mkdir()
    template_path = models_dir / 'dummy_template.docx'
    template_path.write_bytes(b"placeholder")

    values = {
        'societe': {'denomination': 'NOUVEAU CLIENT'},
        'contrat': {'collaborateur_code': 'CLTD', 'collaborateur_nom': 'Client Direct'},
    }

    def _fake_render(_template_path, _context, out_path):
        out_path.write_bytes(b"generated")

    with patch("src.utils.doc_generator._render_docx_template", side_effect=_fake_render):
        render_templates(
            values,
            templates_dir=str(models_dir),
            out_dir=str(out_dir),
            to_pdf=False,
            generation_type='domiciliation',
            legal_form='SARL',
        )

    folders = sorted([p.name for p in out_dir.iterdir() if p.is_dir()])
    assert any(name.startswith(f"DOM-{year}-0008_") for name in folders), folders
