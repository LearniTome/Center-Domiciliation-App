# 📚 Guide Utilisateur - Générateur de Documents

## 🎯 Nouvelle Fonctionnalité: Sélecteur Intelligent de Documents

### Qu'est-ce qui a changé?

Avant, vous deviez sélectionner manuellement chaque document. **Maintenant**, le système sélectionne automatiquement les bons documents en fonction de votre choix!

---

## 📋 Scénario 1: Générer les Documents de Création de Société

### Étape 1: Cliquez sur "Générer les documents"
La fenêtre du sélecteur s'ouvre automatiquement.

### Étape 2: Sélectionnez le type de société
```
☑ 📋 Générer les documents de Création de Société
    • ☑ SARL (Société à Responsabilité Limitée)
      ou
    • ☑ SARL.AU (Société Unipersonnelle)
```

### Étape 3: ✨ Magie!
**Les documents appropriés sont automatiquement sélectionnés**:
- Pour SARL: Tous les statuts, annonces, dépôts légaux pour SARL
- Pour SARL.AU: Tous les documents pour SARL unipersonnelle

### Étape 4: Format de sortie
Choisissez comment générer:
- 📄 **Word uniquement** - Fichiers .docx
- 📕 **PDF uniquement** - Fichiers .pdf
- 📊 **Word & PDF** - Les deux formats

### Étape 5: Confirmation et génération
- Sauvegardez ou non dans la base (vous serez demandé)
- Choisissez le dossier de destination
- Les documents sont générés! 🎉

---

## 🏢 Scénario 2: Générer les Documents de Domiciliation

### Étape 1: Cliquez sur "Générer les documents"

### Étape 2: Sélectionnez Domiciliation
```
☑ 🏢 Générer les documents de Domiciliation
```

### Étape 3: ✨ Deux documents seulement!
**Automatiquement sélectionnés**:
- ✅ Attestation de domiciliation
- ✅ Contrat de domiciliation

Vous pouvez les décocher si vous n'en voulez qu'un:
- ☐ Attestation
- ☑ Contrat

### Étape 4-5: Format et génération
(Même processus que Création)

---

## ⬆️ Ajouter vos Propres Modèles

### Comment charger un nouveau modèle?

1. Cliquez sur **"⬆️ Uploader un nouveau modèle"**
2. Sélectionnez un fichier `.docx` de votre ordinateur
3. Confirmez l'upload
4. Le fichier est automatiquement ajouté à la liste

### ⚠️ Fichiers en doublon?
Si un modèle existe déjà avec le même nom:
```
Le modèle 'Mon_Modele.docx' existe déjà.
Voulez-vous le remplacer?
```
- **Oui** → Le fichier est remplacé
- **Non** → L'upload est annulé

---

## 🔄 Actualiser la Liste des Modèles

Si vous avez ajouté des fichiers manuellement au dossier `Models/`:

1. Cliquez sur **"🔄 Actualiser les modèles"**
2. La liste se met à jour immédiatement

---

## 📁 Consulter les Modèles Existants

Pour voir/modifier vos modèles Word:

1. Cliquez sur **"📁 Consulter les modèles existants"**
2. Le dossier `Models` s'ouvre dans l'Explorateur Windows
3. Vous pouvez:
   - Éditer les fichiers Word
   - Supprimer les modèles inutilisés
   - Ajouter de nouveaux fichiers

---

## ✅ Sélection Manuelle des Templates

### Vous voulez générer seulement certains documents?

Même après l'auto-sélection, vous pouvez:

1. **Décocher** les documents que vous NE voulez pas
2. **Cocher** les documents supplémentaires que vous voulez

### Exemple:
Pour Création SARL, si vous ne voulez que les Statuts:
```
☑ My_Statuts_SARL.docx
☐ My_Annonce_Journal.docx
☐ My_Décl_Imm_Rc.docx
☐ My_Dépot_Légal.docx
```

---

## 🎨 Interface du Sélecteur

```
┌─────────────────────────────────────┐
│ 📄 Sélectionner les documents...     │
├─────────────────────────────────────┤
│                                     │
│ 1️⃣ Type de génération              │
│ ☑ 📋 Création de Société            │
│   • ☑ SARL                          │
│   • ☐ SARL.AU                       │
│                                     │
│ ☐ 🏢 Domiciliation                  │
│                                     │
├─────────────────────────────────────┤
│                                     │
│ 2️⃣ Modèles à générer               │
│ [🔄] [📁] [⬆️]                      │
│                                     │
│ ☑ 📄 My_Statuts_SARL                │
│ ☑ 📄 My_Annonce_Journal             │
│ ☑ 📄 My_Dépot_Légal                 │
│                                     │
│ [✅ Procéder] [❌ Annuler]          │
│                                     │
└─────────────────────────────────────┘
```

---

## 💡 Conseils et Astuces

### 📌 Nommage des fichiers
Pour que l'auto-sélection fonctionne correctement, nommez vos modèles:

**Pour Création**:
- ✅ `SARL_Statuts.docx`
- ✅ `SARL_Annexes.docx`
- ✅ `SARL_AU_Statuts.docx`
- ✅ `Annonce_Officielle.docx`

**Pour Domiciliation**:
- ✅ `Attest_domiciliation.docx`
- ✅ `Contrat_domiciliation.docx`

### ❌ À Éviter
- ❌ `Mon_Document.docx` (pas de keyword)
- ❌ `Creation_2024.docx` (pas spécifique assez)

---

## ❓ Dépannage

### "Aucun modèle sélectionné"
→ Vérifiez que votre dossier `Models/` contient des fichiers `.docx`
→ Cliquez "🔄 Actualiser les modèles"

### "Le dossier Models n'existe pas"
→ Créez un dossier `Models` à côté de votre application
→ Ajoutez vos fichiers `.docx` dedans

### "Génération échouée"
→ Vérifiez que vos fichiers .docx ne sont pas corrompus
→ Essayez avec un modèle connu fonctionnant

### Templates pas auto-sélectionnés?
→ Vérifiez que les noms de fichiers contiennent les bonnes keywords
→ Utilisez les exemples de nommage ci-dessus

---

## 🚀 Flux Complet d'Utilisation

```
1. Remplissez le formulaire de la société
   ↓
2. Cliquez "Générer les documents"
   ↓
3. Sélectionnez le type (Création ou Domiciliation)
   ↓
4. Les documents s'auto-sélectionnent ✨
   ↓
5. (Optionnel) Modifiez la sélection manuelle
   ↓
6. Cliquez "✅ Procéder à la génération"
   ↓
7. Sélectionnez le format (Word/PDF/Both)
   ↓
8. Confirmez la sauvegarde dans la base
   ↓
9. Choisissez le dossier de destination
   ↓
10. Les documents sont générés! 🎉
    Les fichiers sont dans votre dossier
```

---

## 📝 Notes Importantes

### Sauvegarde des Données
- ✅ Les données sont toujours sauvegardées avant génération (à moins que vous refusiez)
- ✅ Les documents générés sont liés à la société dans la base
- ✅ Vous pouvez retrouver les documents génér és via le Tableau de bord

### Formats de Sortie
- **Word (.docx)**: Modifiable, peut être édité après
- **PDF (.pdf)**: Immuable, idéal pour impression/archivage

### Personnalisation
Pour personnaliser les templates:
1. Ouvrez le fichier `.docx` dans Word
2. Modifiez le contenu
3. Sauvegardez
4. Le modèle personnalisé sera utilisé à la prochaine génération

---

## ✨ Résumé des Améliorations

| Avant | Après |
|-------|-------|
| Sélection manuelle de chaque document | ✨ Auto-sélection intelligente |
| Risque d'oublier un document | ✅ Tous les docs nécessaires sélectionnés |
| Pas de gestion des modèles | 📁 Upload, suppression, actualisation |
| Format fixe | 🎨 Choix Word/PDF/Both |
| Interface complexe | 🎯 Interface claire et intuitive |

---

**Questions?** Consultez votre administrateur ou la documentation technique.

**Bonne génération!** 🎉
