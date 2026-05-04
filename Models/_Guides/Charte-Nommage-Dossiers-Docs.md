
# Charte de Nommage des Fichiers & Dossiers (Templates Inclus)

> **Objectif** : Standardiser les noms de fichiers et la structure des dossiers pour l’activité de domiciliation et de constitution de sociétés, afin d’assurer une recherche rapide, une collaboration fluide et une conformité documentaire.

---

## 1) Principes Généraux de Nommage

- **Encodage** : pas d’accents, pas d’espaces, pas de caractères spéciaux. Utiliser `-` comme séparateur.
- **Casse** : style *Titre* (Chaque mot en majuscule initiale).
- **Date** : préfixe `YYYY-MM` (ex. `2026-03`).
- **Type** : suffixe indiquant la nature (`-Template`, `-Brouillon`, `-Final`, `-Signe`).
- **Version** : ajouter `-v1`, `-v1.1`, etc. si plusieurs itérations.
- **Format** : `YYYY-MM_Nom-Document_Type[-Version].ext`

**Exemples** :
- `2026-03_Contrat-Domiciliation-Template.docx`
- `2026-03_Attestation-Domiciliation-Initiale-Final.docx`
- `2026-03_Statuts-Societe-Signe_v2.pdf`

---

## 2) Règles Spécifiques

- **Langue** : privilégier le français sans accents ; termes juridiques standard.
- **Constance** : appliquer les mêmes segments et l’ordre des mots dans toute la bibliothèque.
- **Abréviations** : éviter, sauf standards reconnus : `RC` (Registre du Commerce), `ICE`, `IF`, `CNSS`, `TP`.
- **Confidentialité** : pour les documents contenant des données sensibles, ajouter un tag : `-Confidentiel`.

---

## 3) Statuts de Cycle de Vie (suffixe)

- `-Template` : modèle vierge
- `-Brouillon` : en cours de rédaction
- `-Final` : validé juridiquement
- `-Signe` : signé par les parties

**Exemples** :
- `2026-03_Contrat-Domiciliation-Final.docx`
- `2026-03_Contrat-Domiciliation-Signe.pdf`

---

## 4) Structure de Dossiers – Racine

```
Racine/
├── Personne-Physique/
├── SARL/
├── SARL-AU/
└── SA/
```

Chaque dossier **Forme Juridique** contient des sous-dossiers standardisés + un sous-dossier `Archive`.

---

## 5) Templates de Sous-Dossiers par Forme Juridique

> Les sous-dossiers et modèles ci-dessous sont communs, avec quelques variations selon la forme.

### 5.1 Personne Physique

**Arborescence**
```
Personne-Physique/
├── 01_Identification/
├── 02_Domiciliation/
├── 03_Immatriculation-RC/
├── 04_Annonce-Legale/
├── 05_Depot-Legal/
├── 06_Fiscalite/
└── 99_Archive/
```

**Templates recommandés**
```
2026-03_Attestation-Domiciliation-Initiale-Template.docx
2026-03_Contrat-Domiciliation-Template.docx
2026-03_Declaration-Immatriculation-RC-Template.docx
2026-03_Annonce-Legale-Template.docx
2026-03_Depot-Legal-Template.docx
2026-03_Dossier-Fiscal-PP-Template.docx
```

---

### 5.2 SARL

**Arborescence**
```
SARL/
├── 01_Statuts/
├── 02_Domiciliation/
├── 03_Immatriculation-RC/
├── 04_Annonce-Legale/
├── 05_Depot-Legal/
├── 06_Fiscalite/
├── 07_Associes-Gérance/
└── 99_Archive/
```

**Templates recommandés**
```
2026-03_Statuts-Societe-Template.docx
2026-03_Contrat-Domiciliation-Template.docx
2026-03_Attestation-Domiciliation-Initiale-Template.docx
2026-03_Declaration-Immatriculation-RC-Template.docx
2026-03_Annonce-Legale-Template.docx
2026-03_Depot-Legal-Constitution-Template.docx
2026-03_PV-Nomination-Gerant-Template.docx
2026-03_Lettre-Souscription-Liberation-Parts-Template.docx
2026-03_Liste-Associes-Template.xlsx
```

---

### 5.3 SARL AU

**Arborescence**
```
SARL-AU/
├── 01_Statuts/
├── 02_Domiciliation/
├── 03_Immatriculation-RC/
├── 04_Annonce-Legale/
├── 05_Depot-Legal/
├── 06_Fiscalite/
├── 07_Associe-Unique/
└── 99_Archive/
```

**Templates recommandés**
```
2026-03_Statuts-SARL-AU-Template.docx
2026-03_Contrat-Domiciliation-Template.docx
2026-03_Attestation-Domiciliation-Initiale-Template.docx
2026-03_Declaration-Immatriculation-RC-Template.docx
2026-03_Annonce-Legale-Template.docx
2026-03_Depot-Legal-Constitution-Template.docx
2026-03_PV-Nomination-Gerant-Associe-Unique-Template.docx
2026-03_Decision-Associe-Unique-Template.docx
```

---

### 5.4 SA

**Arborescence**
```
SA/
├── 01_Statuts/
├── 02_Domiciliation/
├── 03_Immatriculation-RC/
├── 04_Annonce-Legale/
├── 05_Depot-Legal/
├── 06_Fiscalite/
├── 07_Conseil-Administration/
├── 08_Commissaires-Aux-Comptes/
└── 99_Archive/
```

**Templates recommandés**
```
2026-03_Statuts-SA-Template.docx
2026-03_Contrat-Domiciliation-Template.docx
2026-03_Attestation-Domiciliation-Initiale-Template.docx
2026-03_Declaration-Immatriculation-RC-Template.docx
2026-03_Annonce-Legale-Template.docx
2026-03_Depot-Legal-Constitution-Template.docx
2026-03_PV-Conseil-Administration-Template.docx
2026-03_PV-AG-Constitutive-Template.docx
2026-03_Lettre-Acceptation-Administrateur-Template.docx
```

---

## 6) Règles de Classement & Archivage

- **Index numérique** : préfixer les dossiers par `01_`, `02_` pour garder l’ordre logique.
- **Archivage** : déplacer tous les documents *obsolètes* dans `99_Archive/` avec suffixe `-Archive` + date.
- **Documents signés** : conserver en **PDF** dans le même dossier que leur version `-Final.docx` ; nommer `-Signe`.
- **Traçabilité** :
  - brouillon → `-Brouillon`
  - validé → `-Final`
  - signé → `-Signe`

---

## 7) Métadonnées & Mentions Légales (facultatif mais recommandé)

- **Propriétaire** (entreprise), **auteur**, **version**, **mots‑clés** dans les propriétés du fichier.
- **Mentions en pied de page** des templates : raison sociale, RC, ICE, IF, CNSS, TP, adresse, contact.

---

## 8) Bonnes Pratiques Opérationnelles

- Créer d’abord le **Template**, puis dupliquer pour chaque client/dossier.
- Ne jamais modifier un `-Template` directement. Utiliser une copie et renommer en `-Brouillon` ou `-Final`.
- Toujours supprimer les informations sensibles des `-Template` (variables à remplir).
- Centraliser les `-Template` dans un dossier *Templates-Maitres* en lecture seule.

---

## 9) Variables Types à Utiliser dans les Templates

Utilisez des placeholders entre doubles accolades : `{{Nom-Societe}}`, `{{Forme-Juridique}}`, `{{Capital}}`, `{{Siege}}`, `{{RC}}`, `{{ICE}}`, `{{IF}}`, `{{CNSS}}`, `{{TP}}`, `{{Date}}`, `{{Gerant}}`, `{{Objet}}`.

**Exemple de nommage spécifique dossier client** :

```
2026-03_Dossier-Client_{{Nom-Societe}}_{{Forme-Juridique}}
```

---

## 10) Glossaire (abréviations autorisées)

- **RC** : Registre du Commerce
- **ICE** : Identifiant Commun de l’Entreprise
- **IF** : Identifiant Fiscal
- **CNSS** : Caisse Nationale de Sécurité Sociale
- **TP** : Taxe Professionnelle

---

*Dernière mise à jour : 2026-03-07*
