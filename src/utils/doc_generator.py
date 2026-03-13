import os
import json
import logging
import re
import shutil
from pathlib import Path
from typing import Dict, List, Optional, Union, Callable
import time

logger = logging.getLogger(__name__)

RENAMED_CONTEXT_ALIASES = {
    "DEN_STE": "DENOMINATION_SOCIALE",
    "FORME_JUR": "FORME_JURIDIQUE",
    "ICE": "NUMERO_ICE",
    "DATE_ICE": "DATE_IMMATRICULATION_ICE",
    "CAPITAL": "CAPITAL_SOCIAL",
    "PART_SOCIAL": "NOMBRE_PARTS_SOCIALES",
    "STE_ADRESS": "ADRESSE_SIEGE_SOCIAL",
    "TRIBUNAL": "TRIBUNAL_COMPETENT",
    "CIVIL": "CIVILITE_ASSOCIE",
    "PRENOM": "PRENOM_ASSOCIE",
    "NOM": "NOM_ASSOCIE",
    "NATIONALITY": "NATIONALITE_ASSOCIE",
    "CIN_NUM": "NUMERO_CIN_ASSOCIE",
    "CIN_VALIDATY": "DATE_VALIDITE_CIN_ASSOCIE",
    "DATE_NAISS": "DATE_NAISSANCE_ASSOCIE",
    "LIEU_NAISS": "LIEU_NAISSANCE_ASSOCIE",
    "ADRESSE": "ADRESSE_ASSOCIE",
    "PHONE": "TELEPHONE_ASSOCIE",
    "EMAIL": "EMAIL_ASSOCIE",
    "PARTS": "NOMBRE_PARTS_ASSOCIE",
    "CAPITAL_DETENU": "CAPITAL_DETENU_ASSOCIE",
    "IS_GERANT": "EST_GERANT",
    "QUALITY": "QUALITE_ASSOCIE",
    "PERIOD_DOMCIL": "DUREE_CONTRAT_MOIS",
    "PRIX_CONTRAT": "LOYER_MENSUEL_TTC",
    "PRIX_INTERMEDIARE_CONTRAT": "FRAIS_INTERMEDIAIRE_CONTRAT",
    "DOM_DATEDEB": "DATE_DEBUT_CONTRAT",
    "DOM_DATEFIN": "DATE_FIN_CONTRAT",
    "PACK_DEMARRAGE_MONTANT_TTC": "MONTANT_PACK_DEMARRAGE_TTC",
    "PACK_DEMARRAGE_LOYER_MENSUEL_TTC": "LOYER_MENSUEL_PACK_DEMARRAGE_TTC",
    "LOYER_RENOUVELLEMENT_MENSUEL_TTC": "LOYER_MENSUEL_RENOUVELLEMENT_TTC",
    "LOYER_RENOUVELLEMENT_ANNUEL_TTC": "LOYER_ANNUEL_RENOUVELLEMENT_TTC",
    "TVA": "TAUX_TVA_POURCENT",
    "DH_HT": "LOYER_MENSUEL_HT",
    "MONTANT_HT": "MONTANT_TOTAL_HT_CONTRAT",
}


def _build_expected_context_key_sections() -> Dict[str, str]:
    """Return supported template keys mapped to their logical section."""
    section_map: Dict[str, str] = {}

    def _add(section: str, *keys: str) -> None:
        for key in keys:
            if key:
                section_map[str(key)] = section

    # Nested roots available in rendering context.
    _add("societe", "societe")
    _add("associe", "associes", "associe")
    _add("contrat", "contrat")
    _add("autre", "values")

    # Société-oriented keys
    _add(
        "societe",
        "DEN_STE", "denomination", "denomination_sociale", "den_ste", "name",
        "FORME_JUR", "forme_juridique",
        "ICE", "ice",
        "DATE_ICE", "date_ice", "date_certificat_negatif",
        "DATE_EXP_CERT_NEG", "date_expiration_certificat_negatif",
        "CAPITAL", "capital",
        "PART_SOCIAL", "parts_social",
        "VALEUR_NOMINALE", "valeur_nominale",
        "STE_ADRESS", "adresse",
        "TRIBUNAL", "tribunal",
        "MODE_SIGNATURE_GERANCE", "mode_signature_gerance",
    )

    # Associé/gérant-oriented keys
    _add(
        "associe",
        "CIVIL", "civilite",
        "PRENOM", "prenom",
        "NOM", "nom",
        "NATIONALITY", "nationalite",
        "CIN_NUM", "num_piece",
        "CIN_VALIDATY", "validite_piece",
        "DATE_NAISS", "date_naiss",
        "LIEU_NAISS", "lieu_naiss",
        "ADRESSE", "PHONE", "EMAIL", "QUALITY",
        "adresse", "telephone", "email", "qualite",
        "PARTS", "parts", "num_parts",
        "CAPITAL_DETENU", "capital_detenu",
        "IS_GERANT", "est_gerant",
    )
    for base in ("ADRESSE", "PHONE", "EMAIL", "QUALITY", "NOM", "PRENOM"):
        _add("associe", f"ASSOCIE_{base}", f"{base}_ASSOCIE", base.lower())
        camel = base[0].lower() + base[1:].lower()
        _add("associe", f"{camel}Associe")

    # Contrat-oriented keys
    _add(
        "contrat",
        "DATE_CONTRAT", "date_contrat", "Date_Contrat", "DateContrat", "dateContrat", "DTAE_CONTRAT",
        "PERIOD_DOMCIL", "period", "period_domcil",
        "PRIX_CONTRAT", "prix_mensuel", "prix_contrat",
        "PRIX_INTERMEDIARE_CONTRAT", "prix_inter", "prix_intermediare",
        "DOM_DATEDEB", "date_debut", "dom_datedeb",
        "DOM_DATEFIN", "date_fin", "dom_datefin",
        "TYPE_CONTRAT_DOMICILIATION", "type_contrat_domiciliation",
        "TYPE_CONTRAT_DOMICILIATION_AUTRE", "type_contrat_domiciliation_autre",
        "CONTRAT_FORME_JURIDIQUE", "contrat_forme_juridique",
    )

    # Activity aliases
    for idx in range(1, 21):
        _add("societe", f"ACTIVITY{idx}", f"activity{idx}")
    _add(
        "societe",
        "ACTIVITIES",
        "ACTIVITES",
        "ACTIVITIES_LIST",
        "ACTIVITES_LIST",
        "LISTE_ACTIVITES",
        "ACTIVITIES_PLAIN",
        "ACTIVITES_PLAIN",
        "ACTIVITIES_BULLETS",
        "ACTIVITES_PUCES",
        "ACTIVITIES_CONTINUATION_BULLETS",
        "ACTIVITES_CONTINUATION_PUCES",
        "ACTIVITIES_INLINE",
        "ACTIVITES_INLINE",
        "ACTIVITY_COUNT",
        "activities",
        "activites",
    )

    # Add aliases explicitly, preserving existing section inference if possible.
    for old_key, new_key in RENAMED_CONTEXT_ALIASES.items():
        inferred = section_map.get(old_key) or section_map.get(new_key) or "autre"
        _add(inferred, old_key, new_key)

    return section_map


_EXPECTED_CONTEXT_KEY_SECTIONS = _build_expected_context_key_sections()


def get_expected_context_key_sections() -> Dict[str, str]:
    """Public API: keys the renderer can expose, grouped by section."""
    return dict(_EXPECTED_CONTEXT_KEY_SECTIONS)


def get_expected_context_keys() -> set[str]:
    """Public API: keys potentially available in rendered template context."""
    return set(_EXPECTED_CONTEXT_KEY_SECTIONS.keys())


def _extract_activities_list(raw_activities) -> List[str]:
    """Normalize activities from list/string payloads into a clean list."""
    activities: List[str] = []
    if isinstance(raw_activities, (list, tuple)):
        activities = [str(a).strip() for a in raw_activities if str(a).strip()]
    elif isinstance(raw_activities, str):
        activities = [a.strip() for a in re.split(r"[\n;]+", raw_activities) if a.strip()]
    return activities


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
    generation_type: Optional[str] = None,
    legal_form: Optional[str] = None,
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

    def _extract_legal_form(vals: Dict, provided: Optional[str]) -> str:
        if provided and str(provided).strip():
            return str(provided).strip()
        if not isinstance(vals, dict):
            return "UNKNOWN"
        soc = vals.get('societe', {}) if isinstance(vals.get('societe'), dict) else {}
        for key in ('forme_juridique', 'FormJur', 'FORME_JUR', 'legal_form'):
            v = soc.get(key) if isinstance(soc, dict) else None
            if v:
                return str(v).strip()
            v2 = vals.get(key)
            if v2:
                return str(v2).strip()
        return "UNKNOWN"

    def _normalize_legal_form_token(raw_form: str) -> str:
        norm = (raw_form or "").strip().upper()
        compact = re.sub(r"[\s_\-]+", "", norm)
        if compact in ("SARLAU",):
            return "SARLAU"
        if compact in ("SARL",):
            return "SARL"
        if compact in ("PP", "PERSONNEPHYSIQUE", "PERSONNEPHYSIQUES"):
            return "PP"
        if compact in ("SA",):
            return "SA"
        return compact or "UNKNOWN"

    def _normalize_generation_folder_token(raw_type: Optional[str], templates: Optional[List[Path]]) -> str:
        t = (raw_type or "").strip().lower()
        if t in ("creation", "création", "create"):
            return "DosCré"
        if t in ("domiciliation", "domicile", "dom"):
            return "DosDom"
        # Fallback by template names when generation_type isn't provided.
        for tpl in (templates or []):
            n = tpl.name.lower()
            if any(k in n for k in ("domiciliation", "contrat", "attest")):
                return "DosDom"
        return "DosCré"

    def _next_domiciliation_sequence(base_out_dir: Path, year_token: str) -> int:
        pattern = re.compile(rf"^DOM-{re.escape(year_token)}-(\d{{4}})_")
        max_seq = 0
        try:
            for existing in base_out_dir.iterdir():
                if not existing.is_dir():
                    continue
                match = pattern.match(existing.name)
                if not match:
                    continue
                try:
                    seq = int(match.group(1))
                except Exception:
                    seq = 0
                if seq > max_seq:
                    max_seq = seq
        except Exception:
            pass
        return max_seq + 1

    def _derive_doc_label(template_stem: str) -> str:
        stem = template_stem
        if stem.startswith('My_'):
            stem = stem[3:]
        stem = re.sub(r"^\d{4}_Mod[èe]le_[^_]+_", "", stem, flags=re.IGNORECASE)
        s_lower = stem.lower()
        if "statut" in s_lower:
            return "Statuts"
        if "contrat" in s_lower:
            return "Contrat"
        if "attest" in s_lower:
            return "Attestation"
        if "annonce" in s_lower:
            return "Annonce"
        if "décl" in s_lower or "decl" in s_lower:
            return "Declaration"
        if "dépot" in s_lower or "depot" in s_lower:
            return "Depot"
        # Generic fallback from stem
        generic = re.sub(r"[^A-Za-z0-9]+", "_", stem).strip("_")
        generic = re.sub(r"__+", "_", generic)
        return generic or "Document"

    company_raw = _extract_company_name(values or {})
    company_clean = _sanitize_name(company_raw)
    legal_form_token = _normalize_legal_form_token(_extract_legal_form(values or {}, legal_form))

    # Resolve template list early to infer generation type when needed.
    pre_templates: Optional[List[_Path]] = [_Path(p) for p in templates_list] if templates_list else None
    folder_kind_token = _normalize_generation_folder_token(generation_type, pre_templates)

    if folder_kind_token == "DosDom":
        year_token = gen_date[:4]
        client_name = company_clean.upper() or "UNKNOWNCOMPANY"
        seq = _next_domiciliation_sequence(out_dir, year_token)
        while True:
            generation_folder_name = f"DOM-{year_token}-{seq:04d}_{client_name}"
            out_subdir = out_dir / generation_folder_name
            if not out_subdir.exists():
                break
            seq += 1
    else:
        generation_folder_name = f"{gen_date}_{folder_kind_token}_{legal_form_token}_{company_clean}"
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
            'forme_juridique': 'FORME_JUR', 'ice': 'ICE', 'date_ice': 'DATE_ICE',
            'date_certificat_negatif': 'DATE_ICE',
            'date_expiration_certificat_negatif': 'DATE_EXP_CERT_NEG',
            'capital': 'CAPITAL', 'parts_social': 'PART_SOCIAL', 'valeur_nominale': 'VALEUR_NOMINALE',
            'adresse': 'STE_ADRESS', 'tribunal': 'TRIBUNAL',
            'mode_signature_gerance': 'MODE_SIGNATURE_GERANCE',
            'type_generation': 'TYPE_GENERATION',
            'generation_type': 'TYPE_GENERATION',
            'procedure_creation': 'PROCEDURE_CREATION',
            'creation_procedure': 'PROCEDURE_CREATION',
            'mode_depot_creation': 'MODE_DEPOT_CREATION',
            'creation_depot_mode': 'MODE_DEPOT_CREATION',
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

        # Contrat mappings
        c = ctx['contrat']
        contrat_map = {
            'date_contrat': 'DATE_CONTRAT', 'period': 'PERIOD_DOMCIL', 'period_domcil': 'PERIOD_DOMCIL',
            'prix_mensuel': 'PRIX_CONTRAT', 'prix_inter': 'PRIX_INTERMEDIARE_CONTRAT',
            'prix_contrat': 'PRIX_CONTRAT', 'prix_intermediare': 'PRIX_INTERMEDIARE_CONTRAT',
            'date_debut': 'DOM_DATEDEB', 'date_fin': 'DOM_DATEFIN', 'dom_datedeb': 'DOM_DATEDEB', 'dom_datefin': 'DOM_DATEFIN',
            'type_contrat_domiciliation': 'TYPE_CONTRAT_DOMICILIATION',
            'type_contrat_domiciliation_autre': 'TYPE_CONTRAT_DOMICILIATION_AUTRE',
            # Compatibility input key used in some older payloads/templates.
            'contrat_forme_juridique': 'CONTRAT_FORME_JURIDIQUE',
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

        # Activities — keep backward compatibility (1..6) while supporting all provided entries.
        try:
            activities = _extract_activities_list(soc.get('activites', None))
            max_slots = max(6, len(activities))
            for i in range(max_slots):
                key = f'ACTIVITY{i+1}'
                val = activities[i] if i < len(activities) else ''
                ctx[key] = val
                ctx[key.lower()] = val
                # camelCase (activity1) for template compatibility.
                ctx[f'activity{i+1}'] = val

            activities_multiline = "\n".join(activities)
            activities_bullets = "\n".join(f"• {item}" for item in activities)
            activities_continuation_bullets = activities_multiline
            activities_inline = "; ".join(activities)
            # Default aliases are plain multi-line (no bullets) so templates can reuse their own bullet style.
            ctx['ACTIVITIES'] = activities_multiline
            ctx['ACTIVITES'] = activities_multiline
            ctx['ACTIVITIES_LIST'] = list(activities)
            ctx['ACTIVITES_LIST'] = list(activities)
            ctx['LISTE_ACTIVITES'] = activities_multiline
            # Plain multiline aliases.
            ctx['ACTIVITIES_PLAIN'] = activities_multiline
            ctx['ACTIVITES_PLAIN'] = activities_multiline
            # Optional explicit bullet aliases for templates that need generated bullets.
            ctx['ACTIVITIES_BULLETS'] = activities_bullets
            ctx['ACTIVITES_PUCES'] = activities_bullets
            ctx['ACTIVITIES_CONTINUATION_BULLETS'] = activities_continuation_bullets
            ctx['ACTIVITES_CONTINUATION_PUCES'] = activities_continuation_bullets
            ctx['ACTIVITIES_INLINE'] = activities_inline
            ctx['ACTIVITES_INLINE'] = activities_inline
            ctx['ACTIVITY_COUNT'] = len(activities)
            ctx['activities'] = activities_multiline
            ctx['activites'] = activities_multiline
        except Exception:
            # non-fatal
            pass
        for old_key, new_key in RENAMED_CONTEXT_ALIASES.items():
            if old_key in ctx and ctx.get(new_key) in (None, ''):
                ctx[new_key] = ctx.get(old_key)
        # Backward compatibility for legacy templates still using this variable name.
        if ctx.get('TYPE_CONTRAT_DOMICILIATION') and not ctx.get('CONTRAT_FORME_JURIDIQUE'):
            ctx['CONTRAT_FORME_JURIDIQUE'] = ctx.get('TYPE_CONTRAT_DOMICILIATION')

        return ctx

    if templates_list:
        templates = [_Path(p) for p in templates_list]
    else:
        if templates_dir is None:
            raise ValueError("Either templates_dir or templates_list must be provided")
        templates_dir = _Path(templates_dir)
        
        # Scan root Models folder for templates
        templates = list(templates_dir.glob("*.docx"))
        
        # Also scan subdirectories organized by legal form
        # (SARL AU, SARL, Personne Physique, SA, etc.)
        for subdir in templates_dir.iterdir():
            if subdir.is_dir() and not subdir.name.startswith('_'):
                templates.extend(subdir.glob("*.docx"))
        
        templates = sorted(templates)  # Ensure consistent order
        if not templates:
            logger.warning("No .docx templates found in %s or subdirectories", templates_dir)

    # compute total files to generate (docx + optional pdf per template)
    total_files = len(templates) * (1 + (1 if to_pdf else 0))
    processed_files = 0

    # Use out_subdir for generated files and report
    used_file_stems = set()
    for tpl in templates:
        try:
            doc_label = _derive_doc_label(tpl.stem)
            file_stem = f"{gen_date}_{legal_form_token}_{doc_label}_{company_clean}"
            if file_stem in used_file_stems:
                idx = 2
                candidate = f"{file_stem}_{idx}"
                while candidate in used_file_stems:
                    idx += 1
                    candidate = f"{file_stem}_{idx}"
                file_stem = candidate
            used_file_stems.add(file_stem)
            out_docx = out_subdir / f"{file_stem}.docx"

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
                out_pdf = out_subdir / f"{file_stem}.pdf"
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
    json_name = f"{gen_date}_{legal_form_token}_Rapport_Docs_Generes_{company_clean}_{gen_time}.json"
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
    # yyyy-mm-dd_Forme_Rapport_Docs_Generes_DenSte_HH-MM-SS.html
    try:
        html_name = f"{gen_date}_{legal_form_token}_Rapport_Docs_Generes_{company_clean}_{gen_time}.html"
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
    body{{font-family:Segoe UI,Arial,Helvetica,sans-serif;margin:18px;background:#1f1f1f;color:#f3f3f3}}
    header{{display:flex;align-items:center;justify-content:space-between}}
    h1{{margin:0;font-size:20px}}
    .meta{{color:#d0d0d0}}
    .summary{{display:flex;gap:12px;margin-top:12px;flex-wrap:wrap}}
    .card{{background:#2b2b2b;padding:10px;border-radius:6px;border:1px solid #3a3a3a}}
    table{{border-collapse:collapse;width:100%;margin-top:12px}}
    th,td{{border:1px solid #3a3a3a;padding:8px;text-align:left}}
    th{{background:#2b2b2b;color:#f3f3f3}}
    pre#genjson{{background:#1e1e1e;color:#e6e6e6;padding:12px;overflow:auto;max-height:420px;border:1px solid #3a3a3a}}
    a.filelink{{color:#9cc9ff;text-decoration:none}}
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
