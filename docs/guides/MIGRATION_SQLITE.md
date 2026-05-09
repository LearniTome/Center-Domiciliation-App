# Migration vers SQLite (Projet Tkinter)

## Objectif
Migrer la base actuelle vers **SQLite** de manière progressive et sûre, sans bloquer l'application.

## 1. Pré-requis
- Python 3.10+ (module `sqlite3` inclus)
- Accès au code source du projet
- Accès en lecture à l'ancienne base

## 2. Sauvegarde obligatoire
Avant toute migration, faire une copie complète:
- Base actuelle (dump SQL ou export CSV)
- Dossier `databases/`
- Fichiers de configuration (`.env`, `config/*`)

Exemple:
```bash
cp -R databases databases_backup_$(date +%Y%m%d)
```

## 3. Créer la nouvelle base SQLite
Choisir un chemin stable, par exemple:
- `databases/app.db`

Exemple Python minimal:
```python
import sqlite3
from pathlib import Path

Path("databases").mkdir(exist_ok=True)
conn = sqlite3.connect("databases/app.db")
cur = conn.cursor()

cur.execute("""
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    email TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
""")

conn.commit()
conn.close()
```

## 4. Adapter la couche d'accès aux données
Centraliser la connexion SQLite dans un seul module (ex: `src/db/connection.py`).

Bonnes pratiques:
- Utiliser des requêtes paramétrées (`?`) pour éviter l'injection SQL.
- Activer les clés étrangères à l'ouverture:
```sql
PRAGMA foreign_keys = ON;
```
- Gérer `commit()` / `rollback()` dans des blocs `try/except`.

## 5. Migrer les données existantes
Options recommandées:
1. Exporter chaque table source en CSV.
2. Importer en SQLite via script Python.
3. Vérifier le nombre de lignes par table.

Contrôle rapide après import:
- Nombre de lignes identique (source vs SQLite)
- Échantillon de données critiques validé manuellement

## 6. Tester l'application Tkinter
Checklist:
- Création d'un enregistrement
- Modification
- Suppression
- Recherche/filtre
- Ouverture/fermeture de l'app sans erreur

Si possible, exécuter aussi les tests automatiques.

## 7. Basculer progressivement
Stratégie conseillée:
1. Ajouter un flag de config `DB_ENGINE=sqlite`.
2. Tester en local puis sur machine de recette.
3. Basculer la production après validation.

## 8. Plan de rollback
En cas de problème:
1. Revenir à l'ancienne config DB.
2. Restaurer la sauvegarde faite à l'étape 2.
3. Vérifier la reprise de service.

## 9. Points d'attention SQLite
- Très bon pour desktop/local (cas Tkinter classique).
- Éviter des écritures concurrentes lourdes.
- Prévoir une migration future vers PostgreSQL si usage multi-utilisateurs intensif.

## 10. Livrables attendus
- Fichier DB SQLite: `databases/app.db`
- Script de migration versionné
- Notes de validation (tests + comptages)
- Procédure rollback documentée
