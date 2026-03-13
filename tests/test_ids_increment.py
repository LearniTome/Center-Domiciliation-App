import pandas as pd
from src.utils.utils import write_records_to_db, ensure_excel_db
from src.utils import constants as _const
from tests.excel_utils import apply_excel_aliases


def test_id_increment_behavior(tmp_path):
    db = tmp_path / "test_db_ids.xlsx"
    # ensure db exists with canonical sheets
    ensure_excel_db(db, _const.excel_sheets)

    # First save: should create id_societe == 1
    soc1 = {'denomination': 'Company Alpha', 'ice': '12345', 'date_ice': '01/01/2020'}
    write_records_to_db(db, soc1, [], {})
    df_soc = pd.read_excel(db, sheet_name='Societes', dtype=str)
    df_soc = apply_excel_aliases(df_soc, "Societes")
    assert not df_soc.empty
    # ID stored as string in our implementation
    assert df_soc['id_societe'].iloc[-1] in ('1', 1, '1.0', '01') or str(df_soc['id_societe'].iloc[-1]).startswith('1')

    # Second save: new company -> id_societe == 2
    soc2 = {'denomination': 'Company Beta', 'ice': '67890', 'date_ice': '02/02/2021'}
    write_records_to_db(db, soc2, [], {})
    df_soc = pd.read_excel(db, sheet_name='Societes', dtype=str)
    df_soc = apply_excel_aliases(df_soc, "Societes")
    assert len(df_soc) >= 2
    assert str(df_soc['id_societe'].iloc[-1]).startswith('2')
