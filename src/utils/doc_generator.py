import os
import json
import logging
import re
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
    # capture per-generation time so filenames can include precise timestamp
    try:
        from datetime import datetime as _datetime
        gen_time = _datetime.now().strftime("%H-%M-%S")
    except Exception:
        gen_time = time.strftime("%H-%M-%S")

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

    def _build_context(vals: Dict) -> Dict:
        """Build a flat context dict for docxtpl from nested values.

        Keeps the original nested structure under keys 'societe', 'associes', 'contrat'
        but also injects many alternative keys (uppercase canonical headers and
        common form keys) to maximize chance of matching the variables used in
        the .docx templates.
        """
        ctx: Dict = {}
        if not isinstance(vals, dict):
            return ctx

        # keep nested
        ctx['societe'] = vals.get('societe', {}) or {}
        ctx['associes'] = vals.get('associes', []) or []
        ctx['contrat'] = vals.get('contrat', {}) or {}

        soc = ctx['societe']
        # Societe mappings
        soc_map = {
            'denomination': 'DEN_STE', 'denomination_sociale': 'DEN_STE', 'den_ste': 'DEN_STE', 'name': 'DEN_STE',
            'forme_juridique': 'FORME_JUR', 'ice': 'ICE', 'date_ice': 'DATE_ICE', 'capital': 'CAPITAL',
            'parts_social': 'PART_SOCIAL', 'adresse': 'STE_ADRESS', 'tribunal': 'TRIBUNAL'
        }
        for fk, hk in soc_map.items():
            v = None
            try:
                v = soc.get(fk)
            except Exception:
                v = None
            if v:
                ctx[hk] = v
                # also lowercase friendly name
                ctx[fk] = v
            # (DATE_CONTRAT will be ensured after mapping the contrat dict below)

        # If no DEN_STE found, try any string in soc
        if 'DEN_STE' not in ctx:
            for k, v in soc.items():
                if isinstance(v, str) and v.strip():
                    ctx['DEN_STE'] = v
                    break

        # Associe: prefer first associe for single-value templates
        assoc_list = ctx['associes']
        if assoc_list and isinstance(assoc_list, list) and len(assoc_list) > 0:
            a = assoc_list[0] or {}
            assoc_map = {
                'civilite': 'CIVIL', 'prenom': 'PRENOM', 'nom': 'NOM', 'nationalite': 'NATIONALITY',
                'num_piece': 'CIN_NUM', 'validite_piece': 'CIN_VALIDATY', 'date_naiss': 'DATE_NAISS',
                'lieu_naiss': 'LIEU_NAISS', 'adresse': 'ADRESSE', 'telephone': 'PHONE', 'email': 'EMAIL',
                'parts': 'PARTS', 'num_parts': 'PARTS', 'capital_detenu': 'CAPITAL_DETENU',
                'est_gerant': 'IS_GERANT', 'qualite': 'QUALITY'
            }
            for fk, hk in assoc_map.items():
                v = None
                try:
                    v = a.get(fk)
                except Exception:
                    v = None
                if v is not None and v != '':
                    ctx[hk] = v
                    ctx[fk] = v

            # Provide additional alias keys for templates that expect associe-prefixed
            # or suffixed variable names. This helps catch documents using patterns
            # like {{ASSOCIE_ADRESSE}} or {{ADRESSE_ASSOCIE}} or camelCase variants.
            for base in ('ADRESSE', 'PHONE', 'EMAIL', 'QUALITY', 'NOM', 'PRENOM'):
                if base in ctx:
                    try:
                        ctx[f'ASSOCIE_{base}'] = ctx[base]
                        ctx[f'{base}_ASSOCIE'] = ctx[base]
                        # also provide lowercase/camel variants
                        ctx[base.lower()] = ctx[base]
                        # camelCase (e.g., adresseAssocie)
                        camel = base[0].lower() + base[1:].lower()
                        ctx[f'{camel}Associe'] = ctx[base]
                    except Exception:
                        pass

            # If this associe is marked as the gérant, provide GERANT_* aliases
            try:
                is_gerant = a.get('est_gerant') or a.get('est_gerant') == True or ctx.get('IS_GERANT')
            except Exception:
                is_gerant = False
            if is_gerant:
                try:
                    # prefer already-normalized keys (from above) then fallback to raw a dict
                    ger_nom = ctx.get('NOM') or a.get('nom')
                    ger_prenom = ctx.get('PRENOM') or a.get('prenom')
                    ger_adress = ctx.get('ADRESSE') or a.get('adresse')
                    ger_phone = ctx.get('PHONE') or a.get('telephone')
                    ger_email = ctx.get('EMAIL') or a.get('email')
                    ger_quality = ctx.get('QUALITY') or a.get('qualite')
                    ger_cin = ctx.get('CIN_NUM') or a.get('num_piece')

                    if ger_adress:
                        ctx['GERANT_ADRESS'] = ger_adress
                    if ger_quality:
                        ctx['GERANT_QUALITY'] = ger_quality
                    if ger_nom:
                        ctx['GERANT_NOM'] = ger_nom
                    if ger_prenom:
                        ctx['GERANT_PRENOM'] = ger_prenom
                    if ger_phone:
                        ctx['GERANT_PHONE'] = ger_phone
                    if ger_email:
                        ctx['GERANT_EMAIL'] = ger_email
                    if ger_cin:
                        ctx['GERANT_CIN'] = ger_cin
                except Exception:
                    pass

        # Contrat mappings
        c = ctx['contrat']
        contrat_map = {
            'date_contrat': 'DATE_CONTRAT', 'period': 'PERIOD_DOMCIL', 'period_domcil': 'PERIOD_DOMCIL',
            'prix_mensuel': 'PRIX_CONTRAT', 'prix_inter': 'PRIX_INTERMEDIARE_CONTRAT',
            'prix_contrat': 'PRIX_CONTRAT', 'prix_intermediare': 'PRIX_INTERMEDIARE_CONTRAT',
            'date_debut': 'DOM_DATEDEB', 'date_fin': 'DOM_DATEFIN', 'dom_datedeb': 'DOM_DATEDEB', 'dom_datefin': 'DOM_DATEFIN'
        }
        for fk, hk in contrat_map.items():
            v = None
            try:
                v = c.get(fk)
            except Exception:
                v = None
            if v is not None and v != '':
                ctx[hk] = v
                ctx[fk] = v

        # Ensure DATE_CONTRAT exists (may be empty string) so templates can always
        # reference it without KeyError
        try:
            if 'DATE_CONTRAT' not in ctx:
                ctx['DATE_CONTRAT'] = c.get('date_contrat', '') if c else ''
        except Exception:
            ctx['DATE_CONTRAT'] = ''

        # Provide alternate keys for contract date variables commonly used in
        # templates (different naming conventions). e.g., Date_Contrat, DateContrat.
        if 'DATE_CONTRAT' in ctx:
            try:
                ctx['Date_Contrat'] = ctx['DATE_CONTRAT']
                ctx['DateContrat'] = ctx['DATE_CONTRAT']
                ctx['dateContrat'] = ctx['DATE_CONTRAT']
                ctx['date_contrat'] = ctx['DATE_CONTRAT']
            except Exception:
                pass
        # Some templates contain a typo or alternate spelling: DTAE_CONTRAT
        if 'DATE_CONTRAT' in ctx and 'DTAE_CONTRAT' not in ctx:
            try:
                ctx['DTAE_CONTRAT'] = ctx['DATE_CONTRAT']
            except Exception:
                pass

            # Activities — many templates expect ACTIVITY1..ACTIVITY6 (or similar)
            try:
                activities = []
                if isinstance(soc.get('activites', None), (list, tuple)):
                    activities = list(soc.get('activites', []))
                elif isinstance(soc.get('activites', None), str):
                    # If stored as a single string, split on newlines or ';'
                    activities = [a.strip() for a in re.split(r"[\n;]+", soc.get('activites', '')) if a.strip()]
                # Populate ACTIVITY1..ACTIVITY6 and fallback lower/camel variants
                for i in range(6):
                    key = f'ACTIVITY{i+1}'
                    val = activities[i] if i < len(activities) else ''
                    ctx[key] = val
                    ctx[key.lower()] = val
                    # camelCase (activity1) isn't commonly used but harmless to add
                    ctx[f'activity{i+1}'] = val
            except Exception:
                # non-fatal
                pass
        return ctx

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
                # Build a forgiving context for templates (flat + nested)
                context = _build_context(values or {})
                # Also keep the original values under 'values' key for templates that expect it
                context['values'] = values or {}
                _render_docx_template(tpl, context, out_docx)
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

    # Save report (write both a human-named JSON matching the HTML report,
    # and keep the legacy `generation_report.json` for backward compatibility)
    json_name = f"{gen_date}_{company_clean}_Raport_Docs_generer_{gen_time}.json"
    report_path = out_subdir / json_name
    try:
        with report_path.open('w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        logger.info("Saved generation report (JSON) to %s", report_path)
    except Exception:
        logger.exception("Failed to write generation report (JSON)")

    # NOTE: legacy `generation_report.json` is intentionally no longer written
    # to avoid duplicate files and confusion. Existing tools should be updated
    # to consume the named JSON report written above which matches the HTML
    # report filename. If you absolutely need the legacy file for compatibility,
    # re-enable the block below.

    # Also write a human-friendly HTML report with the requested name format:
    # yyyy-mm-dd_DenSte_Raport_Docs_generer.html
    try:
        html_name = f"{gen_date}_{company_clean}_Raport_Docs_generer_{gen_time}.html"
        html_path = out_subdir / html_name
        # Build a simple HTML page: header + table of report entries + embedded JSON for tools
        def _escape(s: str) -> str:
            import html as _html
            return _html.escape(str(s) if s is not None else '')

        # compute summary stats
        total = len(report)
        counts = {'ok': 0, 'skipped': 0, 'partial': 0, 'error': 0}
        total_duration = 0.0
        for e in report:
            st = (e.get('status') or 'unknown')
            if st in counts:
                counts[st] += 1
            try:
                total_duration += float(e.get('duration_seconds') or 0.0)
            except Exception:
                pass

        rows_html = []
        for e in report:
            rows_html.append('<tr>' +
                             ''.join(f"<td>{_escape(e.get(k,''))}</td>" for k in ('template', 'out_docx', 'out_pdf', 'status', 'error', 'duration_seconds', 'out_docx_size', 'out_pdf_size')) +
                             '</tr>')

        table_header = ''.join(f"<th>{_escape(h)}</th>" for h in ('template', 'out_docx', 'out_pdf', 'status', 'error', 'duration_seconds', 'out_docx_size', 'out_pdf_size'))

        # Enhanced HTML with summary and links
        html_content = f"""<!doctype html>
<html lang=\"fr\">
<head>
  <meta charset=\"utf-8\">
  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
  <title>Rapport de génération - {_escape(company_raw)} - {gen_date} {gen_time}</title>
  <style>
    body{{font-family:Segoe UI,Arial,Helvetica,sans-serif;margin:18px}}
    header{{display:flex;align-items:center;justify-content:space-between}}
    h1{{margin:0;font-size:20px}}
    .meta{{color:#555}}
    .summary{{display:flex;gap:12px;margin-top:12px}}
    .card{{background:#f8f9fb;padding:10px;border-radius:6px;border:1px solid #e6e9ef}}
    table{{border-collapse:collapse;width:100%;margin-top:12px}}
    th,td{{border:1px solid #ddd;padding:8px;text-align:left}}
    th{{background:#f2f2f2}}
    pre#genjson{{background:#1e1e1e;color:#e6e6e6;padding:12px;overflow:auto;max-height:420px}}
    a.filelink{{color:#1a73e8;text-decoration:none}}
  </style>
</head>
<body>
<header>
  <div>
    <h1>Rapport de génération — {_escape(company_raw)}</h1>
    <div class=\"meta\">Généré le: {gen_date} {gen_time}</div>
  </div>
  <div class=\"meta\">Total modèles: {total}</div>
</header>

<section class=\"summary\">
  <div class=\"card\"><strong>Succès</strong><div>{counts['ok']}</div></div>
  <div class=\"card\"><strong>Sautés</strong><div>{counts['skipped']}</div></div>
  <div class=\"card\"><strong>Partiels</strong><div>{counts['partial']}</div></div>
  <div class=\"card\"><strong>Erreurs</strong><div>{counts['error']}</div></div>
  <div class=\"card\"><strong>Durée totale (s)</strong><div>{round(total_duration,3)}</div></div>
</section>

<h2>Fichiers générés</h2>
<table>
<thead><tr>{table_header}</tr></thead>
<tbody>
{''.join(rows_html)}
</tbody>
</table>

<h2>Données brutes (JSON)</h2>
<pre id=\"genjson\">{_escape(json.dumps(report, ensure_ascii=False, indent=2))}</pre>
</body>
</html>"""

        with html_path.open('w', encoding='utf-8') as hf:
            hf.write(html_content)
        logger.info("Saved generation report (HTML) to %s", html_path)
    except Exception:
        logger.exception("Failed to write generation report (HTML)")

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
