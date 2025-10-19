# Centre de Domiciliation - Application de Gestion

Application de bureau pour la gestion des services de domiciliation d'entreprises, développée avec Python et Tkinter.

## 🌟 Fonctionnalités

- Gestion des sociétés domiciliées
- Gestion des associés
- Gestion des contrats de domiciliation
- Génération automatique de documents juridiques (Word/PDF)
- Sauvegarde des données dans Excel
- Interface utilisateur intuitive
- Thème clair/sombre

## 📋 Prérequis

- Python 3.x
- pip (gestionnaire de paquets Python)

## 🚀 Installation

1. Clonez le dépôt :

```bash
git clone https://github.com/LearniTome/center-domiciliation-app.git
cd center-domiciliation-app
```

1. Créez un environnement virtuel (recommandé) :

```bash
python -m venv venv
source venv/bin/activate  # Sur Linux/Mac
venv\Scripts\activate     # Sur Windows
```

1. Installez les dépendances :

```bash
pip install -r requirements.txt
```

## 💻 Utilisation

1. Lancez l'application :

```bash
python -m main
```

1. Interface principale :

- **Société** : Informations de la société domiciliée
- **Associés** : Détails des associés (jusqu'à 10)
- **Contrat** : Détails du contrat de domiciliation

1. Fonctions principales :

- 📄 Générer des documents Word
- 📑 Générer des documents Word et PDF
- 🆕 Créer une nouvelle fiche
- 💾 Sauvegarder dans la base de données
- ❌ Quitter l'application

## 📁 Structure du Projet

```text
center-domiciliation-app/
├── main.py                 # Point d'entrée de l'application
├── requirements.txt        # Dépendances Python
├── src/
│   ├── forms/             # Formulaires de l'interface
│   │   ├── main_form.py
│   │   ├── societe_form.py
│   │   ├── associe_form.py
│   │   └── contrat_form.py
│   └── utils/             # Utilitaires
│       ├── constants.py
│       ├── utils.py
│       └── styles.py
├── Models/                 # Modèles de documents
│   ├── My_Contrat_domiciliation.docx
│   └── ...
└── databases/             # Base de données Excel
    └── DataBase_domiciliation.xlsx
```

## 🛠 Développement

Pour contribuer au projet :

1. Créez une branche pour votre fonctionnalité
2. Committez vos changements
3. Poussez vers la branche
4. Créez une Pull Request

## 📝 Notes

- Les documents générés sont basés sur des modèles Word personnalisables
- La base de données utilise Excel pour une manipulation facile des données
- L'interface supporte jusqu'à 10 associés par société
- Les activités sont limitées à 6 par société

## ⚠️ Prérequis système

- Windows (testé sur Windows 10/11)
- Microsoft Office pour les modèles Word
- Résolution d'écran minimale : 1024x768

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

## 📜 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## ✅ Run smoke test

To quickly verify the application imports and instantiates without errors, run the provided smoke test (uses the project's virtual environment):

```powershell
# from project root (Windows PowerShell)
venv\Scripts\Activate.ps1
python tests/smoke_test.py
```

On success the script prints: `"Smoke test: MainApp instantiated successfully"`.
