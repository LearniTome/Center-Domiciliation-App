# 🎛️ Gestion des Valeurs par Défaut

Cette fonctionnalité permet aux utilisateurs de configurer les valeurs par défaut pour tous les champs de l'application.

## 📋 Vue d'ensemble

La **gestion des valeurs par défaut** permet de personnaliser les valeurs utilisées automatiquement dans tous les formulaires de l'application. Vous pouvez définir les valeurs par défaut pour:

- **Entreprise**: Dénomination, Forme juridique, Capital, Parts sociales, Adresse, Tribunal
- **Associé**: Civilité, Nationalité, Qualité
- **Contrat**: Période de domiciliation

## 🚀 Accès à la Configuration

### Méthode 1: Via le Bouton Configuration

1. Cliquez sur le bouton **⚙️ Configuration** dans la barre d'outils principale
2. La fenêtre de configuration s'ouvre avec des onglets pour chaque section:
   - **🏢 Entreprise** - Parametres de société
   - **👤 Associé** - Parametres des associés
   - **📋 Contrat** - Parametres du contrat

### Méthode 2: Menu Principal (si disponible)

La configuration est accessible via le menu principal de l'application.

## ⚙️ Utilisation

### Modifier les Valeurs par Défaut

1. **Ouvrez la fenêtre de configuration**
   - Cliquez sur le bouton ⚙️ Configuration

2. **Naviguez vers l'onglet souhaité**
   - Cliquez sur l'onglet 🏢 Entreprise, 👤 Associé, ou 📋 Contrat

3. **Modifiez les valeurs dans les champs**
   - Utilisez les listes déroulantes pour sélectionner des valeurs
   - Ou saisissez directement si c'est une zone de texte

4. **Sauvegardez vos modifications**
   - Cliquez sur le bouton **💾 Enregistrer**

**Les modifications sont appliquées immédiatement:**
   - Les nouveaux formulaires affichent les valeurs par défaut
   - Les nouveaux associés sont créés avec les valeurs par défaut
   - Aucun redémarrage n'est nécessaire

### Réinitialiser aux Valeurs Initiales

Si vous souhaitez annuler vos modifications et revenir aux valeurs par défaut:

1. **Ouvrez la fenêtre de configuration**
2. **Cliquez sur le bouton 🔄 Réinitialiser**
3. **Confirmez l'action dans la boîte de dialogue**

Cela restaure toutes les valeurs aux valeurs initiales définies par l'application.

## 💾 Stockage des Données

Les valeurs par défaut sont stockées dans:

```
config/defaults.json
```

### Structure du Fichier

```json
{
  "societe": {
    "DenSte": "ASTRAPIA",
    "FormJur": "SARL AU",
    "Capital": "50 000",
    "PartsSocial": "500",
    "SteAdresse": "HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA",
    "Tribunal": "Casablanca"
  },
  "associe": {
    "Civility": "Monsieur",
    "Nationality": "Marocaine",
    "Quality": "Gérant"
  },
  "contrat": {
    "NbMois": "12"
  }
}
```

### Comment Ça Marche

- Le fichier `defaults.json` est créé automatiquement au premier lancement
- Il est sauvegardé dans le dossier `config/` avec les autres préférences
- L'application le charge à chaque démarrage
- Les modifications sont sauvegardées immédiatement

## 🔗 Intégration dans les Formulaires

### SocieteForm (Page 1 - Entreprise)

Au chargement du formulaire, les champs suivants sont pré-remplis avec les valeurs par défaut:
- **Dénomination**: Nom de l'entreprise
- **Forme juridique**: Type d'entreprise (SARL AU, SARL, SA)
- **Capital**: Montant du capital social
- **Parts sociales**: Nombre de parts
- **Adresse**: Adresse du siège social
- **Tribunal**: Tribunal compétent

### AssocieForm (Page 2 - Associés)

Lors de la création d'un nouvel associé:
- **Civilité**: Civilité (Monsieur/Madame)
- **Nationalité**: Nationalité du partenaire
- **Qualité**: Rôle (Gérant, Associé, etc.)

### ContratForm (Page 3 - Contrat)

Au chargement du contrat:
- **Période**: Durée du contrat (6, 12, 15 ou 24 mois)

## 🎯 Cas d'Usage Courants

### Cas 1: Définir une Entreprise par Défaut

**Objectif:** Chaque fois que je crée un nouveau formulaire, ma société ASTRAPIA doit être pré-sélectionnée.

**Solution:**
1. Configuration → Onglet 🏢 Entreprise
2. Dénomination: Sélectionner "ASTRAPIA"
3. Cliquez Enregistrer

**Résultat:** Tous les nouveaux formulaires auront "ASTRAPIA" pré-sélectionné.

### Cas 2: Définir l'Adresse par Défaut

**Objectif:** Mon adresse habituelle doit être utilisée par défaut.

**Solution:**
1. Configuration → Onglet 🏢 Entreprise
2. Adresse: Vérifiez que votre adresse est dans la liste
   - Si absent: Contactez un administrateur pour l'ajouter
3. Sélectionnez votre adresse
4. Cliquez Enregistrer

### Cas 3: Définir la Période de Contrat par Défaut

**Objectif:** Notre contrat standard est de 24 mois.

**Solution:**
1. Configuration → Onglet 📋 Contrat
2. Période: Sélectionner "24"
3. Cliquez Enregistrer

**Résultat:** Tous les nouveaux contrats auront 24 mois comme valeur par défaut.

## 🛠️ API du DefaultsManager

Pour les développeurs qui veulent accéder aux valeurs par défaut par programmation:

```python
from src.utils import get_defaults_manager

# Obtenir une instance du gestionnaire
defaults_mgr = get_defaults_manager()

# Obtenir une valeur par défaut spécifique
company_name = defaults_mgr.get_default('societe', 'DenSte')
period = defaults_mgr.get_default('contrat', 'NbMois')

# Définir une valeur par défaut
defaults_mgr.set_default('societe', 'DenSte', 'MA_NOUVELLE_SOCIETE')
defaults_mgr.set_default('contrat', 'NbMois', '24')

# Obtenir tous les défauts
all_defaults = defaults_mgr.get_all_defaults()

# Réinitialiser aux défauts initiaux
defaults_mgr.reset_to_initial()

# Exporter en JSON
json_export = defaults_mgr.export_defaults()

# Importer depuis JSON
defaults_mgr.import_defaults(json_export)

# Recharger depuis le fichier
defaults_mgr.load()
```

## 🔄 Mise à Jour Automatique

Quand vous mettez à jour les valeurs par défaut:

1. **Le fichier `config/defaults.json` est mis à jour** instantanément
2. **Les formulaires ouverts reçoivent une notification** et se réchargent
3. **Aucun redémarrage n'est nécessaire**

### Observation:
Si vous avez un formulaire ouvert et que vous modifiez les défauts dans la Configuration:
- Les champs du formulaire sont automatiquement mis à jour
- Vos modifications en cours dans le formulaire ne sont pas affectées

## 📊 Valeurs Disponibles

### Pour l'Entreprise (Societe)

| Champ | Options | Défaut |
|-------|---------|--------|
| DenSte | Liste d'entreprises | ASTRAPIA |
| FormJur | SARL AU, SARL, SA | SARL AU |
| Capital | 10 000, 50 000, 100 000 | 50 000 |
| PartsSocial | 100, 200, 500, 1000 | 500 |
| SteAdresse | Liste d'adresses | HAY MOULAY... (première adresse) |
| Tribunal | Casablanca, Berrechid, Mohamedia | Casablanca |

### Pour l'Associé

| Champ | Options | Défaut |
|-------|---------|--------|
| Civility | Monsieur, Madame | Monsieur |
| Nationality | Marocaine, Cameronnie | Marocaine |
| Quality | Gérant, Associé, etc. | Gérant |

### Pour le Contrat

| Champ | Options | Défaut |
|-------|---------|--------|
| NbMois | 6, 12, 15, 24 | 12 |

## 🆘 Dépannage

### Les modifications ne s'appliquent pas

**Problème:** J'ai modifié les défauts mais le formulaire affiche l'ancienne valeur.

**Solutions:**
1. Vérifiez que vous avez cliqué "Enregistrer"
2. Fermez et rouvrez le formulaire
3. Regardez `config/defaults.json` pour vérifier que la valeur a été sauvegardée

### Je ne vois pas l'option que je veux

**Problème:** La valeur que je veux n'est pas dans la liste déroulante.

**Causes possibles:**
1. La valeur n'a pas été ajoutée à la liste des constantes
2. L'application doit être redémarrée pour charger les nouvelles constantes

**Solution:** Contactez un administrateur pour ajouter la valeur.

### Réinitialiser a restauré des valeurs que je ne voulais pas

**Note:** Le bouton "Réinitialiser" restaure TOUS les défauts. Il n'y a pas d'annulation partielle.

**Solution:** Vous devrez modifier à nouveau les valeurs individuellement.

## 📝 Notes d'Utilisation

- ✅ Les défauts s'appliquent seulement **aux nouveaux formulaires/associés**
- ✅ Les données existantes ne sont **pas modifiées**
- ✅ Vous pouvez modifier un formulaire même après avoir changé le défaut
- ✅ Chaque utilisateur peut avoir ses propres défauts (stockés localement)
- ⚠️ Supprimer `config/defaults.json` réinitialise les défauts

## 🚀 Prochaines Étapes

- Consultez le [User Guide](USER_GUIDE.md) pour d'autres fonctionnalités
- Voir le [Troubleshooting Guide](TROUBLESHOOTING.md) pour les problèmes courants
- Contactez l'équipe de support si vous rencontrez des bugs

---

**Version:** 2.4.0  
**Dernière mise à jour:** Mars 2026

Vous avez des questions? Consultez le [User Guide](USER_GUIDE.md) ou le [Support](TROUBLESHOOTING.md)!
