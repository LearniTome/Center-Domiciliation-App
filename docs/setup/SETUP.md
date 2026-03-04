# ⚙️ Guide d'Installation Complet

Guide complet pour installer et configurer l'environnement de développement pour **Center-Domiciliation-App**.

## 📋 Prérequis

### Système d'Exploitation
- **Windows 10/11** (recommandé)
- **Linux** (Ubuntu 20.04+, Fedora 33+)
- **macOS** (10.14+)

### Logiciels Obligatoires
- **Python 3.9+** (testé sur 3.10, 3.11, 3.12)
- **Git** (optionnel, pour le contrôle de version)
- **Editeur de code** (VS Code, PyCharm, Sublime Text, etc.)

### Logiciels Optionnels (pour PDF)
- **Microsoft Word** (pour docx2pdf sur Windows)
- **LibreOffice** (alternatif cross-platform)

## 🔍 Vérifier votre Environnement

### Vérifier Python

```bash
# Vérifier la version de Python
python --version
# Doit afficher: Python 3.9 ou supérieur

# Vérifier où est installé Python
where python              # Windows
which python             # Linux/macOS
```

### Vérifier Git (optionnel)

```bash
git --version
# Doit afficher: git version 2.x.x ou supérieur
```

## 🚀 Installation Étape par Étape

### Étape 1: Télécharger/Cloner le Projet

#### Option A: Avec Git

```bash
# Cloner le repository
git clone https://github.com/votre-organisation/center-domiciliation-app.git
cd center-domiciliation-app

# Vérifier la branche
git branch
```

#### Option B: Télécharger le ZIP

1. Allez à: https://github.com/votre-organisation/center-domiciliation-app
2. Cliquez "Code" → "Download ZIP"
3. Décompressez le fichier
4. Ouvrez un terminal dans le dossier

### Étape 2: Créer un Environnement Virtuel

#### Avec UV (Recommandé - Plus Rapide)

```bash
# 1. Installer UV si nécessaire
pip install uv

# 2. Créer l'environnement virtuel
uv venv

# 3. Activer l'environnement
# Windows (PowerShell):
.\\venv\\Scripts\\Activate.ps1

# Windows (cmd.exe):
venv\\Scripts\\activate.bat

# Linux/macOS:
source venv/bin/activate
```

#### Avec Pip Traditionnel

```bash
# 1. Créer l'environnement virtuel
python -m venv venv

# 2. Activer l'environnement
# Windows (PowerShell):
.\\venv\\Scripts\\Activate.ps1

# Windows (cmd.exe):
venv\\Scripts\\activate.bat

# Linux/macOS:
source venv/bin/activate
```

### Étape 3: Installer les Dépendances

#### Avec UV

```bash
# Un seul utilisé
uv pip install -r requirements.txt
```

#### Avec Pip

```bash
# Installer depuis le fichier requirements.txt
pip install -r requirements.txt

# Vérifier l'installation
pip list
```

### Étape 4: Vérifier l'Installation

```bash
# Tester les imports
python -c "import tkinter; import docxtpl; import pandas; print('✓ Installation réussie!')"

# Tester spécifiquement chaque module
python -c "import tkinter; print('✓ Tkinter OK')"
python -c "import docxtpl; print('✓ docxtpl OK')"
python -c "import openpyxl; print('✓ openpyxl OK')"
python -c "import pandas; print('✓ pandas OK')"
```

### Étape 5: Démarrer l'Application

```bash
# Lancer l'application
python main.py
```

**Succès!** La fenêtre principale devrait s'ouvrir.

## 🛠️ Configuration Optionnelle

### Activer la Conversion PDF

Pour convertir les fichiers DOCX générés en PDF, vous avez besoin d'un convertisseur.

#### Option 1: avec docx2pdf (Windows avec Microsoft Word)

```bash
# 1. Installer docx2pdf
pip install docx2pdf

# 2. Vérifier l'installation
python -c "import docx2pdf; print('✓ docx2pdf installé')"
```

**Prérequis:** Microsoft Word doit être installé sur le système.

#### Option 2: avec LibreOffice (Cross-platform)

**Installation:**

```bash
# Windows: https://www.libreoffice.org/download/
# Linux (Ubuntu/Debian):
sudo apt-get install libreoffice

# Linux (Fedora):
sudo dnf install libreoffice

# macOS:
brew install libreoffice
```

**Vérifier:**

```bash
# Windows
where soffice

# Linux/macOS
which soffice
```

### Configuration de VS Code (Optionnel)

Si vous utilisez VS Code:

1. **Installer l'extension Python**
   - Extension ID: `ms-python.python`
   - Cliquez "Install"

2. **Sélectionner l'interprète Python**
   - Ctrl+Shift+P → "Python: Select Interpreter"
   - Choisir `./venv/bin/python`

3. **Configurer les tests**
   - Ctrl+Shift+P → "Python: Configure Tests"
   - Sélectionner `pytest`
   - Choisir le dossier `tests/`

## 📊 Structure des Fichiers Après Installation

Après une installation réussie, vous devriez voir:

```
center-domiciliation-app/
├── venv/                              # Environnement virtuel
├── src/
│   ├── forms/                         # Formulaires UI
│   ├── utils/                         # Utilitaires
│   └── __init__.py
├── tests/                             # Tests unitaires
├── Models/                            # Modèles Word templates
├── databases/
│   └── DataBase_domiciliation.xlsx    # BD Excel (créée au démarrage)
├── config/
│   ├── preferences.json               # Préférences UI (créé au démarrage)
│   └── defaults.json                  # Valeurs par défaut (créé au démarrage)
├── tmp_out/                           # Rapports générés (JSON)
├── docs/                              # Documentation
├── main.py                            # Point d'entrée
├── requirements.txt                   # Dépendances
├── pyproject.toml                     # Métadonnées du projet
├── pytest.ini                         # Configuration pytest
├── README.md                          # Documentation principale
├── CHANGELOG.md                       # Historique des versions
└── app.log                            # Logs (créé au démarrage)
```

## 🧪 Vérifier la Configuration Développement

### Exécuter les Tests

```bash
# Tous les tests
python -m pytest -q

# Tests spécifiques
python -m pytest tests/test_doc_generator_folder_naming.py -v

# Avec couverture de code
python -m pytest --cov=src tests/
```

### Vérifier les Linters (Optionnel)

```bash
# Installer les outils de développement
pip install black pylint flake8

# Vérifier le style
black --check src/
pylint src/
flake8 src/
```

## 🔄 Mise à Jour des Dépendances

### Mettre à Jour Tous les Packages

```bash
# Avec UV
uv pip install --upgrade -r requirements.txt

# Avec Pip
pip install --upgrade -r requirements.txt
```

### Ajouter une Nouvelle Dépendance

```bash
# Avec UV
uv pip install nom_du_package

# Puis mettre à jour requirements.txt
uv pip freeze > requirements.txt

# Avec Pip
pip install nom_du_package
pip freeze > requirements.txt
```

## 🐛 Dépannage de l'Installation

### "python: command not found"

**Windows:**
```bash
# Vérifier que Python est dans le PATH
python --version
# Si erreur, ajouter Python au PATH ou utiliser le chemin complet
```

**Linux/macOS:**
```bash
# Utiliser python3 au lieu de python
python3 --version
python3 main.py
```

### "ModuleNotFoundError: No module named 'tkinter'"

**Windows:**
- Réinstallez Python
- Cochez "tcl/tk and IDLE" lors de l'installation
- Redémarrez l'ordinateur

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install python3-tk
```

**Linux (Fedora):**
```bash
sudo dnf install python3-tkinter
```

**macOS:**
```bash
# Tkinter est inclus par défaut
# Si absent:
brew install python-tk
```

### "ModuleNotFoundError: No module named 'docxtpl'"

```bash
# Réinstallez les dépendances
pip install --upgrade -r requirements.txt

# Ou spécifiquement
pip install docxtpl
```

### L'app refuse de démarrer

```bash
# Supprimez les fichiers de configuration
rm config/preferences.json
rm config/defaults.json

# Relancez
python main.py
```

### Port déjà utilisé (si applicable)

```bash
# Changer le port dans main.py ou config
# Relancer l'application
```

## 🚀 Prochaines Étapes

Après une installation réussie:

1. **Lire le Quick Start**
   - Voir [QUICKSTART.md](QUICKSTART.md)

2. **Consulter le User Guide**
   - Voir le [User Guide complet](../guides/USER_GUIDE.md)

3. **Pour les Développeurs**
   - Voir [CONTRIBUTING.md](../guides/CONTRIBUTING.md)
   - Consulter [Architecture](../architecture/ARCHITECTURE.md)

4. **Configurer les Défauts**
   - Voir [DEFAULTS_MANAGEMENT.md](../guides/DEFAULTS_MANAGEMENT.md)

## 📚 Ressources Additionnelles

- [Documentation Officielle Python](https://docs.python.org/3/)
- [Tkinter Tutorial](https://docs.python.org/3/library/tkinter.html)
- [Pytest Documentation](https://docs.pytest.org/)
- [Git Documentation](https://git-scm.com/doc)

## ✅ Checklist d'Installation

- [ ] Python 3.9+ installé
- [ ] Environnement virtuel créé et activé
- [ ] Dépendances installées (`pip list` affiche docxtpl, pandas, etc.)
- [ ] `python main.py` lance l'application sans erreur
- [ ] Tests passent: `pytest -q`
- [ ] Dossiers créés: `config/`, `databases/`, `tmp_out/`
- [ ] Fichiers créés: `app.log`, `config/preferences.json`

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026

Vous avez des problèmes? Consultez le [Troubleshooting Guide](../guides/TROUBLESHOOTING.md)!
