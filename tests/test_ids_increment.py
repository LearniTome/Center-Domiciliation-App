import pandas as pd
from src.utils.utils import write_records_to_db, ensure_excel_db
from src.utils import constants as _const


def test_id_increment_behavior(tmp_path):
    db = tmp_path / "test_db_ids.xlsx"
    # ensure db exists with canonical sheets
    ensure_excel_db(db, _const.excel_sheets)

    # First save: should create ID_SOCIETE == 1
    soc1 = {'denomination': 'Company Alpha', 'ice': '12345', 'date_ice': '01/01/2020'}
    write_records_to_db(db, soc1, [], {})
    df_soc = pd.read_excel(db, sheet_name='Societes', dtype=str)
    assert not df_soc.empty
    # ID stored as string in our implementation
    assert df_soc['ID_SOCIETE'].iloc[-1] in ('1', 1, '1.0', '01') or str(df_soc['ID_SOCIETE'].iloc[-1]).startswith('1')

    # Second save: new company -> ID_SOCIETE == 2
    soc2 = {'denomination': 'Company Beta', 'ice': '67890', 'date_ice': '02/02/2021'}
    write_records_to_db(db, soc2, [], {})
    df_soc = pd.read_excel(db, sheet_name='Societes', dtype=str)
    assert len(df_soc) >= 2
    assert str(df_soc['ID_SOCIETE'].iloc[-1]).startswith('2')
