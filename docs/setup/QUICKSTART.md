# 🎯 Quick Start Guide

Bienvenue! Ce guide vous aidera à démarrer rapidement avec l'application **Center-Domiciliation-App**.

## 📋 Prérequis

- **Windows 10+** (ou Linux/Mac avec adaptations mineures)
- **Python 3.9+** (testé sur 3.10, 3.11, 3.12)
- **Git** (optionnel, pour le développement)

## 🚀 Installation (3 minutes)

### Option 1: Avec UV (recommandé)

```bash
# 1. Cloner/télécharger le projet
git clone <repo-url>
cd center-domiciliation-app

# 2. Créer un environnement virtuel
uv venv

# 3. Activer l'environnement (PowerShell Windows)
.\\venv\\Scripts\\Activate.ps1

# 4. Installer les dépendances
uv pip install -r requirements.txt

# 5. Lancer l'application
python main.py
```

### Option 2: Avec Pip traditionnel

```bash
git clone <repo-url>
cd center-domiciliation-app

python -m venv venv
.\\venv\\Scripts\\Activate.ps1
pip install -r requirements.txt

python main.py
```

## ✅ Vérifier l'Installation

```bash
# Tester si les dépendances sont correctes
python -c "import tkinter; import docxtpl; import pandas; print('✓ All imports OK')"

# Lancer les tests (optionnel)
python -m pytest -q
```

## 📱 Première Utilisation

1. **Lancer l'application**
   ```bash
   python main.py
   ```

2. **Fenêtre principale**
   - **Page 1 (Entreprise)**: Entrez les informations de la société
   - **Page 2 (Associés)**: Ajoutez les associés/partenaires
   - **Page 3 (Contrat)**: Spécifiez les détails du contrat

3. **Générer les documents**
   - Cliquez "Générer les documents"
   - Sélectionnez le format (Word/PDF/Both)
   - Choisissez les modèles à utiliser
   - Les fichiers sont générés dans un dossier de sortie

4. **Tableau de bord**
   - Cliquez "Tableau de bord" pour gérer les données
   - Visualisez, modifiez, ajoutez ou supprimez des enregistrements
   - Les modifications sont sauvegardées dans Excel

## 📚 Prochaines Étapes

- Consultez [USER_GUIDE.md](USER_GUIDE.md) pour une documentation complète
- Consultez [DEFAULTS_MANAGEMENT.md](DEFAULTS_MANAGEMENT.md) pour personnaliser les valeurs par défaut
- Pour le développement, voir [CONTRIBUTING.md](CONTRIBUTING.md)

## 🆘 Problèmes?

### L'application ne démarre pas
```bash
# Vérifiez que Python est installé
python --version

# Vérifiez que les dépendances sont installées
pip list | findstr docx

# Relancez l'installation
pip install --upgrade -r requirements.txt
```

### ImportError: No module named 'tkinter'
- Sur Linux: `sudo apt-get install python3-tk`
- Sur Mac: Tkinter est inclus avec Python
- Sur Windows: Réinstallez Python en cochant "tcl/tk and IDLE"

### Le mode sombre ne s'applique pas
- Supprimez `config/preferences.json`
- Relancez l'application

### Les données ne s'enregistrent pas
- Fermez Excel si ouvert (bloque le fichier)
- Vérifiez que vous avez les permissions d'écriture

## 📞 Support

Pour plus d'aide:
1. Consultez la [documentation complète](../README.md)
2. Vérifiez les [problèmes connus](TROUBLESHOOTING.md)
3. Contactez l'équipe de développement

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026
