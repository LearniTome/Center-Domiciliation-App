# 🔧 Guide de Dépannage

Ce guide aide à résoudre les problèmes courants de l'application **Center-Domiciliation-App**.

## 🚨 Problèmes d'Exécution

### L'application n'a pas accès à l'affichage
**Symptôme:** Erreur `_tkinter.TclError`  
**Cause:** Problème d'initialisation Tkinter  
**Solution:**
```bash
# Windows
python main.py

# Linux (besoin du serveur d'affichage)
export DISPLAY=:0
python main.py

# Docker/WSL
# Installer XServer et configurer DISPLAY
```

### L'application se ferme immédiatement
**Symptômes:** 
- Application démarre puis s'arrête sans message
- Pas d'erreur visible

**Causes possibles:**
1. **Fichier de configuration corrompu**
   ```bash
   # Solution: Supprimer les fichiers de config
   rm config/preferences.json
   rm config/defaults.json
   ```

2. **Base de données corrompue**
   ```bash
   # Solution: Supprimer et régénérer
   rm databases/DataBase_domiciliation.xlsx
   # Relancer l'application (sera recréée)
   ```

3. **Dépendance manquante**
   ```bash
   # Solution: Réinstaller les dépendances
   pip install --upgrade -r requirements.txt
   ```

**Vérification:**
- Consultez `app.log` pour le message d'erreur réel
- Exécutez `python -c "from main import MainApp; print('OK')"`

---

## 📦 Problèmes d'Installation

### `ModuleNotFoundError: No module named 'tkinter'`

**Windows:**
- Réinstallez Python
- Cochez "tcl/tk and IDLE" pendant l'installation
- Redémarrez votre ordinateur

**Linux:**
```bash
# Ubuntu/Debian
sudo apt-get install python3-tk

# Fedora
sudo dnf install python3-tkinter

# Arch
sudo pacman -S tk
```

**macOS:**
- Tkinter est inclus avec Python d'Apple
- Si absent: utilisez `brew install python-tk`

### `ModuleNotFoundError: No module named 'docxtpl'`

```bash
# Réinstallez les dépendances
pip install --upgrade -r requirements.txt

# Ou spécifiquement
pip install docxtpl
```

### `ImportError: DLL load failed while importing`

**Windows uniquement**
- Installez Visual C++ Redistributable: https://support.microsoft.com/en-us/help/2977003
- Redémarrez l'ordinateur

---

## 🎨 Problèmes d'Interface

### Le mode sombre ne s'applique pas

**Symptôme:** Interface reste en colors par défaut

**Solutions:**
1. **Supprimer la configuration**
   ```bash
   rm config/preferences.json
   # Relancer l'application
   ```

2. **Vérifier ThemeManager**
   ```python
   # Dans Python REPL:
   from main import MainApp
   app = MainApp()
   print(app.theme_manager.is_dark_mode)
   ```

### Les polices sont mauvaises

**Cause:** Polices système manquantes

**Solution:**
- Windows: Installer Arial, Courier New (inclus par défaut)
- Linux: `sudo apt-get install fonts-liberation`
- macOS: Inclus par défaut

### Les éléments ne s'affichent pas correctement

**Solutions:**
1. **Réduire la zone de travail**
   - Réduisez la résolution d'écran temporairement
   - Utilisez le zoom du système opérateur (125% ou moins)

2. **Réinitialiser la configuration de fenêtre**
   ```bash
   # Supprimer les préférences
   rm config/preferences.json
   ```

---

## 📊 Problèmes de Données

### Le tableau de bord ne montre pas de données

**Causes possibles:**

1. **Base de données principale vide**
   ```bash
   # Vérifier le fichier existe
   ls databases/DataBase_domiciliation.xlsx
   
   # Si absent, relancer l'application pour le créer
   python main.py
   ```

2. **Permissions de fichier insuffisantes**
   - Vérifiez que le dossier `databases/` est accessible en écriture
   - Windows: Clic droit → Propriétés → Sécurité

3. **Fichier Excel verrouillé**
   ```bash
   # Fermer tous les programmes utilisant le fichier
   # (Excel, LibreOffice, etc.)
   # Relancer l'application
   ```

### Les modifications du tableau de bord ne se sauvegardent pas

**Causes:**

1. **Excel verrouillé**
   - Fermer Excel complètement (Task Manager)
   - Relancer l'application
   - Modifier et sauvegarder

2. **Permissions insuffisantes**
   - Vérifiez que vous pouvez éditer le fichier
   ```bash
   # Windows: Vérifiez les propriétés du fichier
   # non marqué comme "read-only"
   ```

3. **Fichier corrompu**
   ```bash
   # Sauvegarder et supprimer
   mv databases/DataBase_domiciliation.xlsx databases/DataBase_old.xlsx
   # Relancer l'application (recréé automatiquement)
   ```

---

## 📄 Problèmes de Génération

### La génération de documents échoue

**Solutions:**

1. **Vérifier les modèles**
   ```bash
   # Vérifier que les modèles existent
   ls Models/
   # Doit voir les fichiers .docx
   ```

2. **Vérifier les données de formulaire**
   ```python
   # Dans le code, ajouter un debug:
   values = self.collect_values()
   print(values)  # Voir la structure
   ```

3. **Variables manquantes du formulaire**
   - Remplir tous les champs obligatoires
   - Regarder le message d'erreur pour la variable manquante

### Les fichiers PDF ne sont pas générés

**Cause:** Convertisseur manquant

**Solutions:**

1. **Installer docx2pdf (Windows)**
   ```bash
   # Requiert: MS Word installé
   pip install docx2pdf
   ```

2. **Installer LibreOffice (cross-platform)**
   ```bash
   # Windows: https://www.libreoffice.org/download/
   # Linux: sudo apt-get install libreoffice
   # macOS: brew install libreoffice
   
   # Vérifier l'installation
   where soffice          # Windows
   which soffice          # Linux/Mac
   ```

### Les chemins de sortie sont incorrect

**Cause:** Caractères spéciaux dans le nom de l'entreprise

**Solution:** C'est normal, les caractères spéciaux sont automatiquement remplacés:
- Espaces → underscores
- Caractères spéciaux → supprimés
- Dossier créé avec nom valide

---

## 📝 Problèmes de Logs

### Où trouver les logs?

Les logs sont sauvegardés dans `app.log` à la racine du projet:

```bash
# Voir les 50 dernières lignes
tail -50 app.log

# Chercher une erreur
grep ERROR app.log
grep "Exception" app.log
```

### Les logs sont trop verbeux

Modifiez le niveau de logging dans `main.py`:

```python
# Changer le niveau
logging.basicConfig(level=logging.WARNING)  # Plus silencieux
```

### Les logs ne s'affichent pas

- Vérifiez que `app.log` existe: `ls app.log`
- Vérifiez les permissions: le dossier doit être inscriptible

---

## 🔗 Problèmes de Référence

### Les adresses/tribunaux ne s'affichent pas

**Cause:** Données manquantes dans Excel

**Solution:**
```python
# Vérifier les données
from src.utils.constants import SteAdresse, Tribunaux
print(SteAdresse)
print(Tribunaux)

# Si vides, consulter constants.py et ajouter les données
```

### Les nationalités manquent une option

Modifier `src/utils/constants.py`:

```python
# Ajouter dans la liste Nationalite
Nationalite = ["Marocaine", "Cameronnie", "Nouvelle Nationalite"]
```

---

## 🆘 Problèmes Persistants

### J'ai essayé tout ça et ça ne marche pas

**Démarche:**

1. **Collectez les informations:**
   - Qu'avez-vous tenté?
   - Quelle est l'erreur exacte? (`app.log`)
   - Sur quel système (OS, Python version)?
   - Captures d'écran de la fenêtre d'erreur

2. **Ouvrez une Issue GitHub:**
   - Titre: Problème concis
   - Description: Étapes à reproduire
   - Logs: Contenu de `app.log`
   - Environnement: OS, Python version

3. **Attendez la réponse** (généralement 24-48h)

---

## 📚 Ressources Supplémentaires

- [Copilot Instructions](../../.github/copilot-instructions.md) - Guide complet pour développeurs
- [Architecture Documentation](../architecture/ARCHITECTURE.md) - Vue d'ensemble technique
- [User Guide](USER_GUIDE.md) - Guide d'utilisation complet

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026

Le problème n'est pas ici? Consultez le [User Guide](USER_GUIDE.md) pour plus de détails!
