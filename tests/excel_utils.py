from __future__ import annotations

from typing import Optional

import pandas as pd

from src.utils import constants as _const


def apply_excel_aliases(df: pd.DataFrame, sheet_name: Optional[str] = None) -> pd.DataFrame:
    """Normalize Excel headers to canonical snake_case columns for tests."""
    if df is None or df.empty:
        return df

    alias_map = {}
    if sheet_name == "Societes":
        alias_map = getattr(_const, "societe_header_aliases", {}) or {}
    elif sheet_name == "Associes":
        alias_map = getattr(_const, "associe_header_aliases", {}) or {}
    elif sheet_name == "Contrats":
        alias_map = getattr(_const, "contrat_header_aliases", {}) or {}
    elif sheet_name == "Collaborateurs":
        alias_map = getattr(_const, "collaborateur_header_aliases", {}) or {}
    elif sheet_name:
        alias_map = getattr(_const, "reference_header_aliases", {}).get(sheet_name, {}) or {}

    if not alias_map:
        return df

    out = df.copy()
    for old_col, new_col in alias_map.items():
        if old_col not in out.columns:
            continue
        if new_col not in out.columns:
            out[new_col] = out[old_col]
        else:
            try:
                old_vals = out[old_col].fillna("").astype(str).str.strip()
                new_vals = out[new_col].fillna("").astype(str).str.strip()
                mask = (new_vals == "") & (old_vals != "")
                out.loc[mask, new_col] = out.loc[mask, old_col]
            except Exception:
                pass
        try:
            out.drop(columns=[old_col], inplace=True)
        except Exception:
            pass
    return out
