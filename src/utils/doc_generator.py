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
    cleanup_tmp: bool = False,
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

    # Prepare generation prefix and per-generation folder using company name
    try:
        from datetime import date as _date
    except Exception:
        _date = None

    gen_date = _date.today().strftime("%Y-%m-%d") if _date else time.strftime("%Y-%m-%d")

    # Determine company name from values (try several common keys, search nested 'societe')
    def _extract_company_name(vals: Dict) -> str:
        if not isinstance(vals, dict):
            return "UnknownCompany"

        # prefer nested societe dict when available
        candidates = []
        soc = vals.get('societe')
        if isinstance(soc, dict):
            candidates.append(soc)
        # also try top-level values dict
        candidates.append(vals)

        keys_priority = (
            'denomination', 'denomination_sociale', 'den_ste', 'DenSte', 'DEN_STE',
            'nom_societe', 'nom', 'societe', 'name'
        )

        for c in candidates:
            for k in keys_priority:
                try:
                    v = c.get(k)
                except Exception:
                    v = None
                if v:
                    return str(v)

        # last resort: try to find any reasonable string value in societe dict
        if isinstance(soc, dict):
            for k, v in soc.items():
                if isinstance(v, str) and v.strip():
                    return v.strip()

        return "UnknownCompany"

    def _sanitize_name(name: str) -> str:
        # replace spaces with underscore, remove path-unfriendly chars
        import re
        s = name.strip()
        s = s.replace(' ', '_')
        # keep alnum, underscore and dash
        s = re.sub(r"[^A-Za-z0-9_\-]", '', s)
        # collapse multiple underscores
        s = re.sub(r"__+", '_', s)
        return s or 'UnknownCompany'

    company_raw = _extract_company_name(values or {})
    company_clean = _sanitize_name(company_raw)

    generation_folder_name = f"{gen_date}_{company_clean}_Constitution"
    out_subdir = out_dir / generation_folder_name
    out_subdir.mkdir(parents=True, exist_ok=True)

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

    # Use out_subdir for generated files and report
    for tpl in templates:
        try:
            # Prefix filenames with date and sanitized company name
            prefix = f"{gen_date}_{company_clean}_"
            # Clean the template stem: remove leading 'My_' and trailing '_filled' if present
            stem = tpl.stem
            if stem.startswith('My_'):
                stem = stem[3:]
            if stem.endswith('_filled'):
                stem = stem[:-7]
            out_docx = out_subdir / f"{prefix}{stem}.docx"

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
                out_pdf = out_subdir / f"{prefix}{stem}.pdf"
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

    # Save report (ensure report_path is defined even on failure)
    report_path = out_subdir / "generation_report.json"
    try:
        with report_path.open('w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        logger.info("Saved generation report to %s", report_path)
    except Exception:
        logger.exception("Failed to write generation report")

    # Optionally remove generated files in out_dir after saving the report.
    # Keep the generation_report.json file but delete other files (docx/pdf) when cleanup_tmp is True.
    if cleanup_tmp:
        try:
            for child in out_dir.iterdir():
                # keep the report file
                if child.name == report_path.name:
                    continue
                # only remove files (avoid removing directories unintentionally)
                if child.is_file():
                    try:
                        child.unlink()
                    except Exception:
                        logger.debug("Failed to remove temporary file %s", child, exc_info=True)
            logger.info("Cleaned up temporary output files in %s", out_dir)
        except Exception:
            logger.exception("Failed to cleanup temporary output directory %s", out_dir)

    return report
