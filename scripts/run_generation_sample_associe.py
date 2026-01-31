import tkinter as tk
from pathlib import Path
import json
import sys
import types

# lightweight pandas shim (as in the demo script)
if 'pandas' not in sys.modules:
    sys.modules['pandas'] = types.ModuleType('pandas')

from src.utils.doc_generator import render_templates
from src.utils.utils import PathManager

# Build a sample values dict with a populated associe
values = {
    'societe': {
        'denomination': 'ASTRAPIA',
        'forme_juridique': 'SARL AU',
        'ice': '',
        'date_ice': '28/10/2025',
        'capital': '10 000',
        'parts_social': '100',
        'adresse': '46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA',
        'tribunal': 'Casablanca',
        'activites': []
    },
    'associes': [
        {
            'civilite': 'M.',
            'nom': 'DOE',
            'prenom': 'John',
            'date_naiss': '01/01/1980',
            'lieu_naiss': 'Casablanca',
            'nationalite': 'Marocaine',
            'num_piece': 'AB123456',
            'validite_piece': '01/01/2030',
            'adresse': '1 Rue Exemple, Casablanca',
            'telephone': '0666123456',
            'email': 'john.doe@example.com',
            'est_gerant': True,
            'qualite': 'Gérant',
            'capital_detenu': '50000',
            'num_parts': '500'
        }
    ],
    'contrat': {
        'date_contrat': '28/10/2025',
        'period': '12',
        'prix_mensuel': '1000',
        'prix_inter': '1500',
        'date_debut': '28/10/2025',
        'date_fin': '28/10/2026'
    }
}

out_dir = Path('tmp_out')
models_dir = Path(PathManager.MODELS_DIR)
out_dir.mkdir(parents=True, exist_ok=True)

print('Values snapshot:')
print(json.dumps(values, ensure_ascii=False, indent=2))

# Remove previous outputs to force regeneration
import shutil
try:
    shutil.rmtree(out_dir)
except Exception:
    pass
out_dir.mkdir(parents=True, exist_ok=True)

report = render_templates(values, models_dir, out_dir, to_pdf=False)
print('\nGeneration report:')
print(json.dumps(report, ensure_ascii=False, indent=2))
