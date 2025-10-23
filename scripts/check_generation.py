"""Check generation outputs and optional expectations.

Usage:
  python scripts/check_generation.py [--expect-company NAME] [--expect-associe NAME]

This script:
 - Reads tmp_out/generation_report.json
 - Lists files in tmp_out
 - Optionally previews .docx content (if python-docx installed)
 - Reads last rows (up to 5) from database `databases/DataBase_domiciliation.xlsx` for Societes/Associes/Contrats
 - If --expect-company provided, checks the latest Societes row DEN_STE contains that value
 - If --expect-associe provided, checks latest Associes PRENOM or NOM contains that value

Exit code 0 = all checks passed (or no expectations given). Non-zero = one or more assertions failed.
"""
from pathlib import Path
import json
import argparse
import sys
import logging
from typing import Optional

logging.basicConfig(level=logging.INFO, format='%(message)s')
logger = logging.getLogger(__name__)

TMP = Path("./tmp_out")
DB = Path("./databases/DataBase_domiciliation.xlsx")


def load_generation_report():
    # Prefer HTML report named yyyy-mm-dd_<Company>_Raport_Docs_generer.html
    try:
        for p in TMP.glob('*_Raport_Docs_generer.html'):
            try:
                text = p.read_text(encoding='utf-8')
                # Extract JSON embedded in <pre id="genjson">...</pre>
                start_tag = '<pre id="genjson">'
                end_tag = '</pre>'
                sidx = text.find(start_tag)
                if sidx != -1:
                    sidx += len(start_tag)
                    eidx = text.find(end_tag, sidx)
                    if eidx != -1:
                        raw = text[sidx:eidx]
                        try:
                            return json.loads(raw)
                        except Exception:
                            # fall through to try plain file parse
                            pass
            except Exception:
                continue
    except Exception:
        pass

    # Fallback: old JSON file
    rp = TMP / 'generation_report.json'
    if not rp.exists():
        return None
    try:
        return json.loads(rp.read_text(encoding='utf-8'))
    except Exception as e:
        logger.error(f"Failed to read generation_report.json: {e}")
        return None


def list_tmp_out_files():
    if not TMP.exists():
        return []
    return sorted([p.name for p in TMP.iterdir()])


def preview_docx_files(limit=500):
    previews = []
    try:
        from docx import Document
    except Exception:
        return {'note': 'python-docx not installed; cannot preview .docx files'}
    for p in sorted(TMP.glob('*.docx')):
        try:
            # docx.Document prefers a str path
            doc = Document(str(p))
            text = '\n'.join([para.text for para in doc.paragraphs])
            previews.append({'file': p.name, 'preview': text[:limit]})
        except Exception as e:
            previews.append({'file': p.name, 'error': str(e)})
    return previews


def read_db_tails(n=5):
    try:
        import pandas as pd
    except Exception as e:
        return {'error': 'pandas not installed or failed to import: ' + str(e)}
    out = {}
    if not DB.exists():
        out['error'] = f"DB not found at {DB}"
        return out
    for sheet in ['Societes', 'Associes', 'Contrats']:
        try:
            df = pd.read_excel(DB, sheet_name=sheet)
            out[sheet] = df.tail(n).to_dict(orient='records')
        except Exception as e:
            out[sheet] = {'error': str(e)}
    return out


def run_checks(expect_company: Optional[str] = None, expect_associe: Optional[str] = None):
    ok = True
    report = {}

    report['generation_report'] = load_generation_report()
    report['tmp_out_files'] = list_tmp_out_files()
    report['docx_preview'] = preview_docx_files()
    report['db_tails'] = read_db_tails()

    # Simple expectations
    if expect_company:
        socs = report['db_tails'].get('Societes')
        if isinstance(socs, dict) and 'error' in socs:
            logger.error(f"Cannot validate company: {socs['error']}")
            ok = False
        else:
            if not socs:
                logger.error('No Societes rows found to validate company')
                ok = False
            else:
                latest = socs[-1]
                # latest should be a dict, but be defensive
                if isinstance(latest, dict):
                    den = str(latest.get('DEN_STE') or '')
                else:
                    den = str(latest or '')
                if expect_company.lower() in den.lower():
                    logger.info(f"Company expectation matched in DEN_STE: '{den}'")
                else:
                    logger.error(f"Company expectation NOT matched. Expected '{expect_company}' in '{den}'")
                    ok = False
    if expect_associe:
        assoc = report['db_tails'].get('Associes')
        if isinstance(assoc, dict) and 'error' in assoc:
            logger.error(f"Cannot validate associe: {assoc['error']}")
            ok = False
        else:
            if not assoc:
                logger.error('No Associes rows found to validate associe')
                ok = False
            else:
                latest = assoc[-1]
                if isinstance(latest, dict):
                    prenom = str(latest.get('PRENOM') or '')
                    nom = str(latest.get('NOM') or '')
                else:
                    prenom = ''
                    nom = str(latest or '')
                combined = (prenom + ' ' + nom).strip()
                if expect_associe.lower() in combined.lower() or expect_associe.lower() in prenom.lower() or expect_associe.lower() in nom.lower():
                    logger.info(f"Associe expectation matched in PRENOM/NOM: '{combined}'")
                else:
                    logger.error(f"Associe expectation NOT matched. Expected '{expect_associe}' in '{combined}'")
                    ok = False

    # Also try to find company name inside any generated docx (if previews available)
    if expect_company and isinstance(report['docx_preview'], list):
        found = False
        for p in report['docx_preview']:
            text = p.get('preview') or ''
            if expect_company.lower() in text.lower():
                found = True
                logger.info(f"Found company '{expect_company}' inside generated docx: {p.get('file')}")
                break
        if not found:
            logger.warning(f"Company '{expect_company}' not found in .docx previews (if any).")

    # Print JSON summary; ensure non-serializable objects (datetimes, numpy types) are converted
    def _json_serializer(obj):
        try:
            import datetime as _dt
            if isinstance(obj, (_dt.date, _dt.datetime)):
                return obj.isoformat()
        except Exception:
            pass
        try:
            import numpy as _np
            if isinstance(obj, _np.generic):
                return obj.item()
        except Exception:
            pass
        try:
            return str(obj)
        except Exception:
            return None

    print(json.dumps(report, ensure_ascii=False, indent=2, default=_json_serializer))
    return ok


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Check generation outputs and validate expectations')
    parser.add_argument('--expect-company', help='Company name expected in latest Societes entry and docx')
    parser.add_argument('--expect-associe', help='Associe name expected in latest Associes entry')
    args = parser.parse_args()

    success = run_checks(expect_company=args.expect_company, expect_associe=args.expect_associe)
    if success:
        logger.info('All checks passed (or no assertions were provided).')
        sys.exit(0)
    else:
        logger.error('One or more checks failed.')
        sys.exit(2)
