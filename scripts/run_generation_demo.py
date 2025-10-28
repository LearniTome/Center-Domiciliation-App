import tkinter as tk
from pathlib import Path
import json
import sys

try:
    # Some utility modules import pandas at import-time. The dev environment
    # used for these quick checks may not have pandas installed. To avoid
    # import errors while we only need form defaults, inject a lightweight
    # fake `pandas` module into sys.modules before importing project code.
    import types
    if 'pandas' not in sys.modules:
        sys.modules['pandas'] = types.ModuleType('pandas')

    # Import forms and generator
    from src.forms.societe_form import SocieteForm
    from src.forms.associe_form import AssocieForm
    from src.forms.contrat_form import ContratForm
    from src.utils.doc_generator import render_templates
    from src.utils.utils import PathManager
except Exception as e:
    print('Import error:', e)
    raise


def main():
    root = tk.Tk()
    root.withdraw()

    # instantiate forms attached to the hidden root to get default values
    soc = SocieteForm(root)
    assoc = AssocieForm(root)
    # ensure at least one associe exists
    try:
        if len(assoc.associe_vars) == 0:
            assoc.add_associe()
    except Exception:
        try:
            assoc.add_associe()
        except Exception:
            pass
    contr = ContratForm(root)

    values = {
        'societe': soc.get_values(),
        'associes': assoc.get_values(),
        'contrat': contr.get_values()
    }

    out_dir = Path('tmp_out')
    models_dir = Path(PathManager.MODELS_DIR)
    out_dir.mkdir(parents=True, exist_ok=True)

    print('Using models_dir=', models_dir)
    print('Using out_dir=', out_dir)
    print('Values snapshot:')
    print(json.dumps(values, ensure_ascii=False, indent=2))

    try:
        report = render_templates(values, models_dir, out_dir, to_pdf=False)
        print('\nGeneration report:')
        print(json.dumps(report, ensure_ascii=False, indent=2))
    except Exception as e:
        print('Generation error:', e)
        raise
    finally:
        try:
            root.destroy()
        except Exception:
            pass


if __name__ == '__main__':
    main()
