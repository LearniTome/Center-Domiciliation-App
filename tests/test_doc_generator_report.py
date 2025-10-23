from src.utils.doc_generator import render_templates
from pathlib import Path


def test_render_templates_report_contains_timing_and_size(tmp_path):
    out_dir = tmp_path / 'out'
    out_dir.mkdir()

    # Use the repository Models directory which contains sample .docx templates
    models_dir = Path('Models')
    report = render_templates({'test': 'value'}, templates_dir=str(models_dir), out_dir=str(out_dir), to_pdf=False)

    assert isinstance(report, list)
    # At least one template should be processed in this repo
    assert len(report) > 0

    for entry in report:
        assert 'template' in entry
        assert 'out_docx' in entry
        assert 'duration_seconds' in entry
        assert isinstance(entry['duration_seconds'], (int, float))
        assert 'out_docx_size' in entry
        assert isinstance(entry['out_docx_size'], int)
        # Size may be zero if rendering failed, but duration should be present
        assert entry['duration_seconds'] >= 0
