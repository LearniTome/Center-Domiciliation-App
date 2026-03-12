import logging
import re
import csv
from collections import Counter, defaultdict
from pathlib import Path
from typing import Dict, Iterable, List, Optional, Set, Union
from zipfile import ZipFile

from jinja2 import Environment, meta

from .doc_generator import get_expected_context_key_sections

logger = logging.getLogger(__name__)

_EXPR_PATTERN = re.compile(r"\{\{\s*(.*?)\s*\}\}", re.DOTALL)


def _extract_jinja_expressions_from_docx(template_path: Path) -> List[str]:
    expressions: List[str] = []
    with ZipFile(template_path, "r") as zf:
        for member in zf.namelist():
            if not member.startswith("word/") or not member.endswith(".xml"):
                continue
            try:
                xml_data = zf.read(member).decode("utf-8", errors="ignore")
            except Exception:
                continue
            expressions.extend(_EXPR_PATTERN.findall(xml_data))
    return expressions


def _variables_from_expression(expression: str) -> Set[str]:
    expr = (expression or "").strip()
    if not expr:
        return set()
    env = Environment()
    try:
        parsed = env.parse(f"{{{{ {expr} }}}}")
    except Exception:
        # Keep analyzer robust against malformed template snippets.
        return set()
    return set(meta.find_undeclared_variables(parsed))


def extract_template_variables(template_path: Union[str, Path]) -> Counter:
    """Extract variable occurrence counts from one .docx template."""
    path = Path(template_path)
    counts: Counter = Counter()
    for expr in _extract_jinja_expressions_from_docx(path):
        variables = _variables_from_expression(expr)
        for var_name in variables:
            counts[var_name] += 1
    return counts


def _infer_section(variable_name: str, key_sections: Dict[str, str]) -> str:
    if variable_name in key_sections:
        return key_sections[variable_name]

    name = (variable_name or "").upper()
    if not name:
        return "autre"

    if "COLLAB" in name:
        return "collaborateur"
    if any(token in name for token in ("ASSOC", "GERANT", "CIN", "NAISS", "CIVIL", "NATIONAL", "QUALITE", "PHONE", "EMAIL")):
        return "associe"
    if any(token in name for token in ("CONTRAT", "LOYER", "RENOUV", "TVA", "MONTANT", "PACK", "DOM_", "DATE_CONTRAT")):
        return "contrat"
    if any(token in name for token in ("STE", "SOCIETE", "DEN", "FORME", "ICE", "TRIBUNAL", "CAPITAL", "PART", "ACTIVITY")):
        return "societe"
    return "autre"


def filter_analysis_rows(
    rows: Iterable[Dict],
    search_text: str = "",
    template_name: str = "Tous",
    section: str = "Tous",
    coverage: str = "Tous",
) -> List[Dict]:
    """Apply common filters used by global/detail analyzer views."""
    search = (search_text or "").strip().lower()
    template_filter = (template_name or "Tous").strip()
    section_filter = (section or "Tous").strip().lower()
    coverage_filter = (coverage or "Tous").strip().lower()

    filtered: List[Dict] = []
    for row in rows:
        variable = str(row.get("variable", ""))
        tpl = str(row.get("template", ""))
        templates = [str(t) for t in row.get("templates", [])]
        row_section = str(row.get("section", "")).strip().lower()
        row_coverage = str(row.get("coverage", "")).strip().lower()

        if search:
            haystack_parts = [variable, tpl] + templates
            if not any(search in part.lower() for part in haystack_parts if part):
                continue

        if template_filter != "Tous":
            if tpl:
                if tpl != template_filter:
                    continue
            elif template_filter not in templates:
                continue

        if section_filter != "tous" and row_section != section_filter:
            continue

        if coverage_filter != "tous" and row_coverage != coverage_filter:
            continue

        filtered.append(row)
    return filtered


def analyze_templates(
    templates_dir: Union[str, Path],
    context_keys: Optional[Set[str]] = None,
) -> Dict:
    """Analyze all .docx templates and return global and detailed stats."""
    root = Path(templates_dir)
    template_files = sorted(p for p in root.rglob("*.docx") if p.is_file())

    key_sections = get_expected_context_key_sections()
    expected_keys = set(context_keys or key_sections.keys())

    global_counts: Counter = Counter()
    variable_templates: Dict[str, Set[str]] = defaultdict(set)
    variable_template_paths: Dict[str, Set[str]] = defaultdict(set)
    details: List[Dict] = []
    errors: List[Dict] = []
    templates_with_variables = 0

    for template_path in template_files:
        try:
            counts = extract_template_variables(template_path)
        except Exception as exc:
            logger.exception("Template analysis failed: %s", template_path)
            errors.append({"template": str(template_path), "error": str(exc)})
            continue

        if counts:
            templates_with_variables += 1

        for variable_name, occurrence_count in counts.items():
            global_counts[variable_name] += int(occurrence_count)
            variable_templates[variable_name].add(template_path.name)
            variable_template_paths[variable_name].add(str(template_path))
            details.append(
                {
                    "template": template_path.name,
                    "template_path": str(template_path),
                    "variable": variable_name,
                    "occurrences": int(occurrence_count),
                    "section": _infer_section(variable_name, key_sections),
                    "coverage": "couvert" if variable_name in expected_keys else "non couvert",
                }
            )

    variable_rows: List[Dict] = []
    for variable_name, occurrence_count in global_counts.items():
        templates = sorted(variable_templates.get(variable_name, set()))
        paths_for_variable = sorted(variable_template_paths.get(variable_name, set()))
        variable_rows.append(
            {
                "variable": variable_name,
                "occurrences": int(occurrence_count),
                "templates_count": len(paths_for_variable),
                "templates": templates,
                "template_paths": paths_for_variable,
                "section": _infer_section(variable_name, key_sections),
                "coverage": "couvert" if variable_name in expected_keys else "non couvert",
            }
        )

    variable_rows.sort(key=lambda row: row["variable"].lower())
    details.sort(key=lambda row: (row["template"].lower(), row["variable"].lower()))

    covered_count = sum(1 for row in variable_rows if row["coverage"] == "couvert")
    uncovered_count = len(variable_rows) - covered_count

    return {
        "summary": {
            "total_templates": len(template_files),
            "templates_with_variables": templates_with_variables,
            "total_variable_occurrences": int(sum(global_counts.values())),
            "total_distinct_variables": len(variable_rows),
            "covered_variables": covered_count,
            "uncovered_variables": uncovered_count,
        },
        "variables": variable_rows,
        "details": details,
        "templates": [p.name for p in template_files],
        "errors": errors,
    }


def export_analysis_rows(rows: Iterable[Dict], out_path: Union[str, Path], columns: Optional[Iterable[str]] = None) -> Path:
    """Export analysis rows to CSV or XLSX."""
    path = Path(out_path)
    row_list = list(rows)

    if columns is None:
        if row_list:
            columns = list(row_list[0].keys())
        else:
            columns = []
    columns = list(columns)

    def _serialize(value):
        if isinstance(value, (list, tuple, set)):
            return "; ".join(str(v) for v in value)
        return value

    suffix = path.suffix.lower()
    if suffix == ".csv":
        path.parent.mkdir(parents=True, exist_ok=True)
        with path.open("w", encoding="utf-8-sig", newline="") as fh:
            writer = csv.DictWriter(fh, fieldnames=columns, extrasaction="ignore")
            writer.writeheader()
            for row in row_list:
                writer.writerow({col: _serialize(row.get(col, "")) for col in columns})
        return path

    if suffix == ".xlsx":
        try:
            from openpyxl import Workbook
        except Exception as exc:
            raise RuntimeError("openpyxl est requis pour exporter en Excel") from exc

        wb = Workbook()
        ws = wb.active
        ws.title = "Analyse"
        ws.append(columns)
        for row in row_list:
            ws.append([_serialize(row.get(col, "")) for col in columns])
        path.parent.mkdir(parents=True, exist_ok=True)
        wb.save(path)
        return path

    raise ValueError("Format non supporté. Utilisez .csv ou .xlsx")
