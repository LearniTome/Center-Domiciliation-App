import os
import json
import logging
import shutil
from pathlib import Path
from typing import Dict, List, Optional, Union, Callable
import time

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
    # Prefer docx2pdf (Windows + MS Word) if available
    try:
        from docx2pdf import convert
        convert(str(docx_path), str(pdf_path))
        return
    except Exception:
        logger.debug("docx2pdf not available or failed; will try soffice fallback")

    # Fallback: try using LibreOffice (soffice) in headless mode if installed
    soffice = None
    for candidate in ("soffice", "libreoffice", "soffice.bin"):
        if shutil.which(candidate):
            soffice = candidate
            break

    if not soffice:
        logger.warning("No PDF conversion tool available (docx2pdf or LibreOffice); skipping PDF conversion")
        return

    # Use soffice to convert
    try:
        # soffice --headless --convert-to pdf --outdir <outdir> <docxfile>
        import subprocess
        outdir = str(pdf_path.parent)
        subprocess.run([soffice, "--headless", "--convert-to", "pdf", "--outdir", outdir, str(docx_path)], check=True)
        # soffice names the output with the same stem + .pdf in outdir
        if not pdf_path.exists():
            logger.debug("Post-conversion: expected PDF %s not found; checking for alternatives", pdf_path)
    except Exception as e:
        logger.exception("LibreOffice PDF conversion failed: %s", e)
        raise


def render_templates(
    values: Dict,
    templates_dir: Optional[Union[str, Path]] = None,
    out_dir: Optional[Union[str, Path]] = None,
    to_pdf: bool = False,
    templates_list: Optional[List[str]] = None,
    progress_callback: Optional[Callable[[int, int, str, Dict], None]] = None,
) -> List[Dict]:
    """Render .docx templates.

    Can either render a provided list of template file paths (templates_list),
    or scan a templates_dir for all `*.docx` files if templates_list is None.

    Returns a list with report entries: {template, out_docx, out_pdf (optional), status, error}
    """
    if out_dir is None:
        raise ValueError("out_dir is required")

    from pathlib import Path as _Path

    # Normalize out_dir/templates_dir to Path
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

    # compute total files to generate (docx + optional pdf per template)
    total_files = len(templates) * (1 + (1 if to_pdf else 0))
    processed_files = 0

    for tpl in templates:
        try:
            out_docx = out_dir / f"{tpl.stem}_filled.docx"

            # Skip if docx already exists
            if out_docx.exists():
                duration = 0.0
                size_bytes = out_docx.stat().st_size
                entry = {
                    'template': str(tpl.name),
                    'out_docx': str(out_docx),
                    'status': 'skipped',
                    'error': None,
                    'duration_seconds': round(duration, 3),
                    'out_docx_size': int(size_bytes),
                }
                processed_files += 1
                if progress_callback:
                    progress_callback(processed_files, total_files, str(tpl.name), dict(entry))
            else:
                start = time.time()
                _render_docx_template(tpl, values, out_docx)
                duration = time.time() - start
                size_bytes = out_docx.stat().st_size if out_docx.exists() else 0
                entry = {
                    'template': str(tpl.name),
                    'out_docx': str(out_docx),
                    'status': 'ok',
                    'error': None,
                    'duration_seconds': round(duration, 3),
                    'out_docx_size': int(size_bytes),
                }
                processed_files += 1
                if progress_callback:
                    progress_callback(processed_files, total_files, str(tpl.name), dict(entry))

            # PDF conversion (optional)
            if to_pdf:
                out_pdf = out_dir / f"{tpl.stem}_filled.pdf"
                # Skip if PDF exists
                if out_pdf.exists():
                    entry['out_pdf'] = str(out_pdf)
                    entry['out_pdf_size'] = int(out_pdf.stat().st_size)
                    # mark skipped only if docx was skipped earlier and pdf exists too
                    if entry.get('status') == 'skipped':
                        entry['status'] = 'skipped'
                else:
                    try:
                        _convert_to_pdf(out_docx, out_pdf)
                        entry['out_pdf'] = str(out_pdf)
                        entry['out_pdf_size'] = int(out_pdf.stat().st_size) if out_pdf.exists() else 0
                    except Exception as e:
                        entry['out_pdf'] = None
                        entry['status'] = 'partial'
                        entry['error'] = f"PDF conversion failed: {e}"
                processed_files += 1
                if progress_callback:
                    progress_callback(processed_files, total_files, str(tpl.name), dict(entry))

            report.append(entry)
            logger.info("Processed template %s -> %s", tpl, out_docx)
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
