# 📚 Documentation - Center-Domiciliation-App

Bienvenue dans la documentation du projet **Center-Domiciliation-App**. Ce projet est une application Tkinter qui remplit des modèles Word, gère des données Excel et fournit un tableau de bord pour la génération de documents juridiques.

## 📖 Table des matières

### 🚀 Démarrage Rapide
- [**Setup et Installation**](setup/SETUP.md) - Instructions pour configurer l'environnement de développement
- [**Quick Start**](setup/QUICKSTART.md) - Démarrer rapidement avec l'application

### 👨‍💼 Guides Utilisateur
- [**Guide de l'Utilisateur**](guides/USER_GUIDE.md) - Comment utiliser l'application
- [**Gestion des Valeurs par Défaut**](guides/DEFAULTS_MANAGEMENT.md) - Configurer les valeurs par défaut

### 👨‍💻 Guides Développeur
- [**Architecture**](architecture/ARCHITECTURE.md) - Vue d'ensemble de l'architecture
- [**API Documentation**](architecture/API.md) - Documentation de l'API interne
- [**Contribution Guide**](guides/CONTRIBUTING.md) - Comment contribuer au projet

### 📝 Version et Historique
- [**CHANGELOG**](../CHANGELOG.md) - Historique complet des versions
- [**Releases**](architecture/RELEASES.md) - Notes de version détaillées

### 📋 Information du Projet
- [**Copilot Instructions**](../.github/copilot-instructions.md) - Instructions pour GitHub Copilot

### 🗂️ Autres Ressources
- [**Archive**](archive/README.md) - Documentation archivée/historique

---

## 🎯 Vue d'ensemble rapide

### Structure du Projet

```
center-domiciliation-app/
├── main.py                         # Application entry point
├── src/
│   ├── forms/                      # UI forms
│   └── utils/                      # Utilities and helpers
├── Models/                         # Word templates
├── databases/                      # Excel data storage
├── tests/                          # Unit tests
├── docs/                           # Documentation
│   ├── guides/                     # User & dev guides
│   ├── architecture/               # Technical docs
│   ├── setup/                      # Installation & setup
│   └── archive/                    # Archived docs
├── README.md                       # Main documentation
└── CHANGELOG.md                    # Version history
```

### Fonctionnalités Principales

✅ **Génération de Documents Juridiques**
- Remplissage automatique de modèles Word via Jinja2
- Conversion optionnelle en PDF

✅ **Gestion de Données**
- Tableau de bord pour visualiser et modifier les données Excel
- Support complet CRUD (Create, Read, Update, Delete)

✅ **Configuration**
- Gestion centralisée des valeurs par défaut
- Mode sombre/clair automatique
- Préférences persistantes

✅ **Architecture Moderne**
- Code modulaire et maintenable
- Patterns design reconnus
- Gestion cohérente des erreurs

## 🛠️ Technology Stack

- **Framework:** Tkinter (GUI)
- **Backend:** Python 3.9+
- **Template:** Jinja2 + python-docx
- **Data:** Pandas + OpenPyXL (Excel)
- **Package Manager:** UV (recommandé) ou Pip
- **Testing:** Pytest

## 📞 Support & Contribution

Pour contribuer au projet, consultez [CONTRIBUTING.md](guides/CONTRIBUTING.md).

Pour signaler des bugs ou demander des fonctionnalités, utilisez le système GitHub Issues.

---

**Dernière mise à jour:** Mars 2026  
**Version:** 2.4.0
