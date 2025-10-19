﻿# Centre de Domiciliation - Application de Gestion

Application de bureau pour la gestion des services de domiciliation d'entreprises, dÃ©veloppÃ©e avec Python et Tkinter.

## ðŸŒŸ FonctionnalitÃ©s

- Gestion des sociÃ©tÃ©s domiciliÃ©es
- Gestion des associÃ©s
- Gestion des contrats de domiciliation
- GÃ©nÃ©ration automatique de documents juridiques (Word/PDF)
- Sauvegarde des donnÃ©es dans Excel
- Interface utilisateur intuitive
- ThÃ¨me clair/sombre

## ðŸ“‹ PrÃ©requis

- Python 3.x
- pip (gestionnaire de paquets Python)

## ðŸš€ Installation

1. Clonez le dÃ©pÃ´t :

```bash
git clone https://github.com/LearniTome/center-domiciliation-app.git
cd center-domiciliation-app
```

1. CrÃ©ez un environnement virtuel (recommandÃ©) :

```bash
python -m venv venv
source venv/bin/activate  # Sur Linux/Mac
venv\Scripts\activate     # Sur Windows
```

1. Installez les dÃ©pendances :

```bash
pip install -r requirements.txt
```

## ðŸ’» Utilisation

1. Lancez l'application :

```bash
python -m main
```

1. Interface principale :

- **SociÃ©tÃ©** : Informations de la sociÃ©tÃ© domiciliÃ©e
- **AssociÃ©s** : DÃ©tails des associÃ©s (jusqu'Ã  10)
- **Contrat** : DÃ©tails du contrat de domiciliation

1. Fonctions principales :

- ðŸ“„ GÃ©nÃ©rer des documents Word
- ðŸ“‘ GÃ©nÃ©rer des documents Word et PDF
- ðŸ†• CrÃ©er une nouvelle fiche
- ðŸ’¾ Sauvegarder dans la base de donnÃ©es
- âŒ Quitter l'application

## ðŸ“ Structure du Projet

```text
center-domiciliation-app/
â”œâ”€â”€ main.py                 # Point d'entrÃ©e de l'application
â”œâ”€â”€ requirements.txt        # DÃ©pendances Python
â”œâ”€â”€ src/
â”‚   â”œâ”€â”€ forms/             # Formulaires de l'interface
â”‚   â”‚   â”œâ”€â”€ main_form.py
â”‚   â”‚   â”œâ”€â”€ societe_form.py
â”‚   â”‚   â”œâ”€â”€ associe_form.py
â”‚   â”‚   â””â”€â”€ contrat_form.py
â”‚   â””â”€â”€ utils/             # Utilitaires
â”‚       â”œâ”€â”€ constants.py
â”‚       â”œâ”€â”€ utils.py
â”‚       â””â”€â”€ styles.py
â”œâ”€â”€ Models/                 # ModÃ¨les de documents
â”‚   â”œâ”€â”€ My_Contrat_domiciliation.docx
â”‚   â””â”€â”€ ...
â””â”€â”€ databases/             # Base de donnÃ©es Excel
    â””â”€â”€ DataBase_domiciliation.xlsx
```

## ðŸ›  DÃ©veloppement

Pour contribuer au projet :

1. CrÃ©ez une branche pour votre fonctionnalitÃ©
2. Committez vos changements
3. Poussez vers la branche
4. CrÃ©ez une Pull Request

## ðŸ“ Notes

- Les documents gÃ©nÃ©rÃ©s sont basÃ©s sur des modÃ¨les Word personnalisables
- La base de donnÃ©es utilise Excel pour une manipulation facile des donnÃ©es
- L'interface supporte jusqu'Ã  10 associÃ©s par sociÃ©tÃ©
- Les activitÃ©s sont limitÃ©es Ã  6 par sociÃ©tÃ©

## âš ï¸ PrÃ©requis systÃ¨me

- Windows (testÃ© sur Windows 10/11)
- Microsoft Office pour les modÃ¨les Word
- RÃ©solution d'Ã©cran minimale : 1024x768

## ðŸ¤ Contribution

Les contributions sont les bienvenues ! N'hÃ©sitez pas Ã  ouvrir une issue ou une pull request.

## ðŸ“œ Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de dÃ©tails.

## âœ… Run smoke test

To quickly verify the application imports and instantiates without errors, run the provided smoke test (uses the project's virtual environment):

```powershell
# from project root (Windows PowerShell)
venv\Scripts\Activate.ps1
python tests/smoke_test.py
```

On success the script prints: `"Smoke test: MainApp instantiated successfully"`.

<!-- CI retrigger -->
