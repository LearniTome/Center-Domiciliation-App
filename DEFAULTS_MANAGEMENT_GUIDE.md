"""
Gestion des Valeurs par Défaut - Guide d'Utilisation

Cette fonctionnalité permet aux utilisateurs de configurer les valeurs par défaut
pour tous les champs de l'application.
"""

## Overview

La nouvelle fonctionnalité de **gestion des valeurs par défaut** remplace le switch
mode Sombre/Light et offre une interface complète pour personnaliser les valeurs
par défaut utilisées dans toute l'application.

## Accès à la Configuration

1. Cliquez sur le bouton **⚙ Configuration** dans la barre d'outils
2. La fenêtre de configuration s'ouvre avec des onglets pour chaque section:
   - **🏢 Entreprise** - Dénomination, Forme juridique, Capital, Parts sociales
   - **👤 Associé** - Civilité, Nationalité
   - **📋 Contrat** - Période de contrat

## Utilisation

### Modifier les Défauts

1. Ouvrez la fenêtre de configuration
2. Naviguez vers l'onglet souhaité
3. Modifiez les valeurs dans les champs
4. Cliquez sur **💾 Enregistrer**

Les valeurs seront appliquées immédiatement à tous les nouveaux formulaires ouverts
ainsi qu'aux nouveaux associés créés.

### Réinitialiser aux Défauts Initiaux

1. Ouvrez la fenêtre de configuration
2. Cliquez sur **🔄 Réinitialiser**
3. Confirmez l'action

Cela restaure toutes les valeurs aux valeurs initiales définies par l'application.

## Stockage

Les valeurs par défaut sont stockées dans:
```
config/defaults.json
```

Le fichier contient une structure JSON organisée par section:
```json
{
  "societe": {
    "DenSte": "ASTRAPIA",
    "FormJur": "SARL AU",
    "Capital": "50 000",
    "PartsSocial": "500"
  },
  "associe": {
    "Civility": "Monsieur",
    "Nationality": "Marocaine"
  },
  "contrat": {
    "NbMois": "12"
  }
}
```

## Intégration dans les Formulaires

Les formulaires utilisent automatiquement les défauts stockés:

1. **SocieteForm** - Charge les défauts pour DenSte, FormJur, Capital, PartsSocial
2. **AssocieForm** - Charge les défauts pour Civility et Nationality lors de la création des associés
3. **ContratForm** - Charge le défaut pour la période de contrat

## API du DefaultsManager

```python
from src.utils import get_defaults_manager

# Obtenir une instance
defaults_mgr = get_defaults_manager()

# Obtenir une valeur par défaut
value = defaults_mgr.get_default('societe', 'DenSte')

# Définir une valeur par défaut
defaults_mgr.set_default('societe', 'DenSte', 'NewValue')

# Obtenir tous les défauts
all_defaults = defaults_mgr.get_all_defaults()

# Réinitialiser aux défauts initiaux
defaults_mgr.reset_to_initial()

# Exporter/Importer
json_str = defaults_mgr.export_defaults()
defaults_mgr.import_defaults(json_str)
```

## Changements Apportés

- ✅ Suppression du switch mode Sombre/Light dans la configuration
- ✅ Nouvelle fenêtre de configuration avec onglets pour gérer les défauts
- ✅ Création du gestionnaire centralisé `DefaultsManager`
- ✅ Intégration avec SocieteForm, AssocieForm et ContratForm
- ✅ Stockage persistant dans `config/defaults.json`
