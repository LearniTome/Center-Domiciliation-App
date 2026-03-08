from __future__ import annotations

import json
import logging
import shutil
import time
from datetime import datetime
from html import escape
from pathlib import Path
from typing import Any, Callable, Dict, List, Optional, Tuple

from .utils import PathManager
from .doc_generator import _convert_to_pdf

logger = logging.getLogger(__name__)


def _detect_pdf_engine() -> Tuple[bool, Optional[str]]:
    """Detect a usable PDF conversion engine."""
    try:
        from docx2pdf import convert  # noqa: F401
        return True, "docx2pdf"
    except Exception:
        pass

    for candidate in ("soffice", "libreoffice", "soffice.bin"):
        if shutil.which(candidate):
            return True, candidate

    return False, None


def _find_docx_files(source_dir: Path, recursive: bool = True) -> List[Path]:
    source_dir = Path(source_dir)
    pattern = "**/*.docx" if recursive else "*.docx"
    return sorted(p for p in source_dir.glob(pattern) if p.is_file())


def _render_html_report(data: Dict[str, Any]) -> str:
    rows_html: List[str] = []
    for item in data.get("files", []):
        rows_html.append(
            "<tr>"
            f"<td>{escape(str(item.get('source_docx', '')))}</td>"
            f"<td>{escape(str(item.get('out_pdf', '')))}</td>"
            f"<td>{escape(str(item.get('status', '')))}</td>"
            f"<td>{escape(str(item.get('error', '')))}</td>"
            f"<td>{escape(str(item.get('duration_seconds', '')))}</td>"
            "</tr>"
        )

    return f"""<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Rapport conversion Word -> PDF</title>
  <style>
    body {{ font-family: Segoe UI, Arial, sans-serif; margin: 20px; }}
    h1 {{ margin-bottom: 8px; }}
    .meta {{ margin-bottom: 16px; color: #333; }}
    table {{ border-collapse: collapse; width: 100%; }}
    th, td {{ border: 1px solid #bbb; padding: 6px 8px; text-align: left; }}
    th {{ background: #f2f2f2; }}
    .ok {{ color: #137333; font-weight: 600; }}
    .error {{ color: #b00020; font-weight: 600; }}
    .skipped {{ color: #7a5c00; font-weight: 600; }}
  </style>
</head>
<body>
  <h1>Rapport conversion Word -> PDF (lot)</h1>
  <div class="meta">
    <div><strong>Date:</strong> {escape(str(data.get("generated_at", "")))}</div>
    <div><strong>Dossier source:</strong> {escape(str(data.get("source_dir", "")))}</div>
    <div><strong>Récursif:</strong> {escape(str(data.get("recursive", "")))}</div>
    <div><strong>Moteur:</strong> {escape(str(data.get("engine", "aucun")))}</div>
    <div><strong>Total:</strong> {escape(str(data.get("total_files", 0)))}</div>
    <div><strong>Succès:</strong> {escape(str(data.get("success_count", 0)))} |
         <strong>Ignorés:</strong> {escape(str(data.get("skipped_count", 0)))} |
         <strong>Erreurs:</strong> {escape(str(data.get("error_count", 0)))}</div>
    <div><strong>Durée totale (s):</strong> {escape(str(data.get("duration_seconds", 0)))}</div>
    <div><strong>Statut global:</strong> {escape(str(data.get("global_status", "")))}</div>
    <div><strong>Erreur globale:</strong> {escape(str(data.get("global_error", "")))}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>source_docx</th>
        <th>out_pdf</th>
        <th>status</th>
        <th>error</th>
        <th>duration_seconds</th>
      </tr>
    </thead>
    <tbody>
      {"".join(rows_html)}
    </tbody>
  </table>
</body>
</html>
"""


def convert_docx_batch(
    source_dir: Path,
    recursive: bool = True,
    report_root: Optional[Path] = None,
    progress_callback: Optional[Callable[[int, int, str, Dict[str, Any]], None]] = None,
) -> Dict[str, Any]:
    """Convert .docx files to PDF in batch and write HTML+JSON reports.

    Output PDFs are created in the same folder as each source .docx.
    """
    source_path = Path(source_dir).expanduser().resolve()
    if not source_path.exists() or not source_path.is_dir():
        raise ValueError(f"Dossier source invalide: {source_path}")

    start_ts = time.perf_counter()
    generated_at = datetime.now().isoformat(timespec="seconds")
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")

    reports_dir = Path(report_root) if report_root else (PathManager.BASE_DIR / "Outputs" / "Reports")
    reports_dir.mkdir(parents=True, exist_ok=True)
    json_path = reports_dir / f"conversion_word_pdf_{stamp}.json"
    html_path = reports_dir / f"conversion_word_pdf_{stamp}.html"

    files = _find_docx_files(source_path, recursive=recursive)
    has_engine, engine_name = _detect_pdf_engine()
    global_error = None if has_engine else (
        "Aucun moteur PDF disponible (docx2pdf / LibreOffice). "
        "Installez Microsoft Word ou LibreOffice puis réessayez."
    )

    report_entries: List[Dict[str, Any]] = []
    total = len(files)
    success_count = 0
    skipped_count = 0
    error_count = 0

    for idx, docx_path in enumerate(files, start=1):
        file_start = time.perf_counter()
        pdf_path = docx_path.with_suffix(".pdf")
        entry: Dict[str, Any] = {
            "source_docx": str(docx_path),
            "out_pdf": str(pdf_path),
            "status": "pending",
            "error": "",
            "duration_seconds": 0.0,
        }

        if not has_engine:
            entry["status"] = "error"
            entry["error"] = global_error or "Aucun moteur PDF disponible"
            error_count += 1
        else:
            try:
                # Skip when PDF exists and is newer/equal than DOCX.
                if pdf_path.exists() and pdf_path.stat().st_mtime >= docx_path.stat().st_mtime:
                    entry["status"] = "skipped"
                    skipped_count += 1
                else:
                    _convert_to_pdf(docx_path, pdf_path)
                    if pdf_path.exists():
                        entry["status"] = "ok"
                        success_count += 1
                    else:
                        entry["status"] = "error"
                        entry["error"] = "Conversion effectuée mais PDF introuvable en sortie."
                        error_count += 1
            except Exception as exc:
                entry["status"] = "error"
                entry["error"] = str(exc)
                error_count += 1
                logger.exception("Erreur conversion DOCX->PDF pour %s", docx_path)

        entry["duration_seconds"] = round(time.perf_counter() - file_start, 3)
        report_entries.append(entry)

        if callable(progress_callback):
            try:
                progress_callback(idx, total, docx_path.name, entry)
            except Exception:
                logger.debug("progress_callback failed", exc_info=True)

    global_status = "error" if (global_error or error_count > 0) else "ok"
    payload: Dict[str, Any] = {
        "tool": "word_pdf_batch",
        "generated_at": generated_at,
        "source_dir": str(source_path),
        "recursive": bool(recursive),
        "engine": engine_name,
        "global_status": global_status,
        "global_error": global_error,
        "total_files": total,
        "success_count": success_count,
        "skipped_count": skipped_count,
        "error_count": error_count,
        "duration_seconds": round(time.perf_counter() - start_ts, 3),
        "files": report_entries,
        "report_json": str(json_path),
        "report_html": str(html_path),
    }

    with json_path.open("w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)

    html_path.write_text(_render_html_report(payload), encoding="utf-8")
    return payload

