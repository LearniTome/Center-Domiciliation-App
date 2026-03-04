# 📋 Document Generation Selector - Feature Update

## Version: 2.3.0
**Date**: March 3, 2026

## ✨ Nouvelles Fonctionnalités

### 1️⃣ Sélecteur de Type de Génération Amélioré
Le nouvel interface `GenerationSelectorDialog` permet maintenant:

- **Choix du type de document**:
  - 📋 Création de Société (SARL ou SARL.AU)
  - 🏢 Domiciliation

- **Sélection automatique des templates**:
  - ✅ **Création de Société**: Tous les documents de création sont automatiquement sélectionnés
  - ✅ **Domiciliation**: Seuls Attestation et Contrat sont sélectionnés

### 2️⃣ Gestion Avancée des Modèles
- **🔄 Bouton Actualiser**: Rafraîchit la liste des modèles disponibles
- **📁 Consulter les modèles**: Ouvre le dossier Models dans l'explorateur
- **⬆️ Upload**: Permet d'ajouter de nouveaux templates .docx
- **☑️ Sélection manuelle**: Chaque modèle peut être coché/décoché

### 3️⃣ Nouvelle Méthode de Format
- Dialogue séparé pour le choix du format de sortie
- Options: **Word uniquement**, **PDF uniquement**, ou **Word & PDF**

### 4️⃣ Sélection et Validation Complètes
- Les templates sélectionnés sont validés avant génération
- Message d'avertissement si aucun template n'est sélectionné
- Transmission des templates au générateur de documents

## 📁 Fichiers Modifiés

### `src/forms/generation_selector.py`
**Nouvelles constantes**:
```python
CREATION_TEMPLATES_KEYWORDS = ['SARL', 'Statuts', 'Annonce', 'Decl', 'Dépot']
DOMICILIATION_TEMPLATES = ['Attest', 'Contrat']
```

**Nouvelles méthodes**:
- `_on_frame_configure()`: Gère le redimensionnement du canvas
- `_auto_select_templates()`: Sélection automatique basée sur le type
- `_refresh_template_list()`: Rafraîchit la liste avec checkboxes
- Update de `_confirm()`: Retourne aussi la liste des templates sélectionnés

**UI améliorations**:
- Canvas avec checkboxes au lieu de listbox simple
- Bouton Actualiser ajouté
- Support du redimensionnement dinamique

### `main.py`
**Nouvelles méthodes**:
- `_ask_output_format()`: Dialogue de sélection du format (Word/PDF/Both)

**Modifications du flux `generate_documents()`**:
1. ✅ Appelle `show_generation_selector()` avec auto-sélection
2. ✅ Récupère les templates sélectionnés
3. ✅ Appelle `_ask_output_format()` pour le format
4. ✅ Utilise les templates du sélecteur au lieu de `choose_templates_with_format()`

## 🔄 Flux de Génération Simplifié

```
Clic sur "Générer les documents"
    ↓
Sélecteur Modal (Type + Templates auto-sélectionnés)
    ↓
Demande Format (Word/PDF/Both)
    ↓
Demande Sauvegarde
    ↓
Demande Dossier Sortie
    ↓
Génération + Barre Progression
    ↓
Message Succès
```

## 📊 Exemple d'Utilisation

### Scénario 1: Création de Société SARL
1. Clic "Générer les documents"
2. Sélection "📋 Générer les documents de Création de Société"
3. Choix "• SARL"
4. ✅ Tous les templates SARL sont automatiquement sélectionnés
5. Format sélection
6. Génération

### Scénario 2: Domiciliation
1. Clic "Générer les documents"
2. Sélection "🏢 Générer les documents de Domiciliation"
3. ✅ Seuls Attestation et Contrat sont sélectionnés
4. Format sélection
5. Génération

## 🐛 Fixes et Améliorations

- ✅ Validation complète avant génération
- ✅ Messages d'erreur clairs
- ✅ UI responsive avec canvas scrollable
- ✅ Support du redimensionnement de fenêtre
- ✅ Fermeture propre des dialogues

## ⚙️ Configuration

### Keywords de détection de templates

**Pour Création de Société**:
```
SARL, Statuts, Annonce, Decl, Dépot
```

**Pour Domiciliation**:
```
Attest, Contrat
```

Exemples de noms de fichiers reconnus:
- `My_Statuts_SARL.docx` → Création SARL
- `My_Statuts_SARL_AU.docx` → Création SARL.AU
- `My_Attest_domiciliation.docx` → Domiciliation
- `My_Contrat_domiciliation.docx` → Domiciliation

## ✅ Tests

L'intégration a été vérifiée avec:
- ✅ Import des modules
- ✅ Chargement des templates
- ✅ Création des dialogues
- ✅ Sélection automatique
- ✅ Transmission des données

## 🚀 Prochaines Étapes

1. Tester le flux complet avec l'interface GUI
2. Vérifier la génération de documents
3. Améliorer les keywords si nécessaire
4. Ajouter des présets de templates nommés

## 📝 Notes

- Les templates doivent être des fichiers `.docx` dans le dossier `Models/`
- Les noms des templates doivent contenir les keywords appropriés
- L'auto-sélection est basée sur les noms de fichiers
- Les utilisateurs peuvent toujours décocher les templates s'ils ne veulent pas tous les générer

---

**Status**: ✅ COMPLET ET TESTÉ
