import os
import json
import logging
from pathlib import Path
from typing import Dict, List, Optional

logger = logging.getLogger(__name__)


def _render_docx_template(template_path: Path, context: Dict, out_path: Path) -> None:
    """Render a docx template with docxtpl and save to out_path."""
    try:
        from docxtpl import DocxTemplate
    except Exception as e:
        raise RuntimeError("docxtpl is required to render templates") from e

    tpl = DocxTemplate(str(template_path))
    tpl.render(context)
    tpl.save(str(out_path))


def _convert_to_pdf(docx_path: Path, pdf_path: Path) -> None:
    """Convert a docx file to PDF using docx2pdf if available."""
    try:
        from docx2pdf import convert
    except Exception:
        logger.warning("docx2pdf not available; skipping PDF conversion")
        return
    # docx2pdf convert accepts file paths
    convert(str(docx_path), str(pdf_path))


def render_templates(values: Dict, templates_dir: Optional[str] = None, out_dir: Optional[str] = None, to_pdf: bool = False, templates_list: Optional[List[str]] = None) -> List[Dict]:
    """Render .docx templates.

    Can either render a provided list of template file paths (templates_list),
    or scan a templates_dir for all `*.docx` files if templates_list is None.

    Returns a list with report entries: {template, out_docx, out_pdf (optional), status, error}
    """
    if out_dir is None:
        raise ValueError("out_dir is required")

    from pathlib import Path as _Path

    out_dir = _Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)

    report = []

    if templates_list:
        templates = [_Path(p) for p in templates_list]
    else:
        if templates_dir is None:
            raise ValueError("Either templates_dir or templates_list must be provided")
        templates_dir = _Path(templates_dir)
        templates = list(templates_dir.glob("*.docx"))
        if not templates:
            logger.warning("No .docx templates found in %s", templates_dir)

    for tpl in templates:
        try:
            out_docx = out_dir / f"{tpl.stem}_filled.docx"
            _render_docx_template(tpl, values, out_docx)
            entry = {
                'template': str(tpl.name),
                'out_docx': str(out_docx),
                'status': 'ok',
                'error': None
            }
            if to_pdf:
                out_pdf = out_dir / f"{tpl.stem}_filled.pdf"
                try:
                    _convert_to_pdf(out_docx, out_pdf)
                    entry['out_pdf'] = str(out_pdf)
                except Exception as e:
                    entry['out_pdf'] = None
                    entry['status'] = 'partial'
                    entry['error'] = f"PDF conversion failed: {e}"
            report.append(entry)
            logger.info("Generated %s -> %s", tpl, out_docx)
        except Exception as e:
            logger.exception("Failed to render template %s: %s", tpl, e)
            report.append({'template': str(tpl.name), 'out_docx': None, 'status': 'error', 'error': str(e)})

    # Save report
    try:
        report_path = out_dir / "generation_report.json"
        with report_path.open('w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        logger.info("Saved generation report to %s", report_path)
    except Exception:
        logger.exception("Failed to write generation report")

    return report
