# ⚙️ Setup et Installation

Ce dossier contient les instructions d'installation et de configuration.

## 📚 Documents Setup

- **[SETUP.md](SETUP.md)** - Guide complet d'installation
- **[QUICKSTART.md](QUICKSTART.md)** - Démarrage rapide
- **[ENVIRONMENT.md](ENVIRONMENT.md)** - Configuration de l'environnement

## 🛠️ Contenu Couvert

### Installation
- Prérequis système
- Installation de Python
- Configuration de l'environnement virtuel
- Installation des dépendances (UV/Pip)

### Configuration
- Variables d'environnement
- Fichiers de configuration
- Préférences utilisateur
- Database setup

### Développement
- Setup du projet pour le développement
- Configuration des tests
- Debuggage
- Git setup

### Déploiement
- Build de l'application
- Empaquetage
- Distribution
- CI/CD

## 🚀 Démarrage Rapide

1. **Cloner le repo**
   ```bash
   git clone <repo-url>
   cd center-domiciliation-app
   ```

2. **Installer l'environnement**
   ```bash
   # Avec UV (recommandé)
   uv venv
   .\\venv\\Scripts\\Activate.ps1
   uv pip install -r requirements.txt
   
   # Ou avec pip traditionnel
   python -m venv venv
   .\\venv\\Scripts\\Activate.ps1
   pip install -r requirements.txt
   ```

3. **Lancer l'application**
   ```bash
   python main.py
   ```

4. **Exécuter les tests**
   ```bash
   python -m pytest -q
   ```

Pour plus de détails, voir [SETUP.md](SETUP.md) et [QUICKSTART.md](QUICKSTART.md).

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026
