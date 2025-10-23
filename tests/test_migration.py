import pandas as pd
from src.utils.utils import migrate_excel_workbook, ensure_excel_db
from src.utils import constants as _const


def test_migration_moves_old_sheets(tmp_path):
    db = tmp_path / "test_db_mig.xlsx"
    # Create an old sheet that looks like Associes but has a different name
    old_cols = ['PRENOM', 'NOM', 'CIN_NUM', 'DATE_NAISS']
    df_old = pd.DataFrame([{'PRENOM': 'Jean', 'NOM': 'Dupont', 'CIN_NUM': 'A123', 'DATE_NAISS': '01/01/1980'}])

    # Create initial canonical Associes sheet with headers only
    ensure_excel_db(db, _const.excel_sheets)

    # Write the old sheet data
    with pd.ExcelWriter(db, engine='openpyxl', mode='a', if_sheet_exists='overlay') as writer:
        df_old.to_excel(writer, sheet_name='OldAssoc', index=False)

    # Run migration
    migrate_excel_workbook(db)

    # After migration, OldAssoc should be removed and rows appended to 'Associes'
    df_assoc = pd.read_excel(db, sheet_name='Associes', dtype=str)
    assert not df_assoc.empty
    # Find migrated row by matching PRENOM/NOM
    found = ((df_assoc['PRENOM'] == 'Jean') & (df_assoc['NOM'] == 'Dupont')).any()
    assert found

    # Also ensure old sheet no longer exists
    import openpyxl
    wb = openpyxl.load_workbook(db)
    assert 'OldAssoc' not in wb.sheetnames
