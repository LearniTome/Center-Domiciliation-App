from pathlib import Path


def test_finish_creates_database(tmp_path, monkeypatch):
    """Test the ensure_excel_db helper creates the workbook and expected sheets.

    This avoids constructing any Tk GUI so it runs in headless CI.
    """
    # Import here to allow monkeypatching PathManager first
    from src.utils.utils import ensure_excel_db, PathManager
    from src.utils import constants as _const

    # Point database dir to tmp_path
    monkeypatch.setattr(PathManager, 'DATABASE_DIR', Path(tmp_path))

    db_file = Path(tmp_path) / 'DataBase_domiciliation.xlsx'

    # Call helper to create the workbook
    ensure_excel_db(db_file, _const.excel_sheets)

    assert db_file.exists(), f"Expected database file at {db_file}"

    import openpyxl
    wb = openpyxl.load_workbook(db_file)
    expected_sheets = set(_const.excel_sheets.keys())
    assert expected_sheets.issubset(set(wb.sheetnames))
