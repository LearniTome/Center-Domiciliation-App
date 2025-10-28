from pathlib import Path
from docx import Document

for p in sorted(Path('tmp_out').rglob('*.docx')):
    print('---', p)
    try:
        text='\n'.join([para.text for para in Document(str(p)).paragraphs])
        print(text[:1200])
    except Exception as e:
        print('ERROR reading', e)
