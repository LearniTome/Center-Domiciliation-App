
# Charte de Nommage des Fichiers & Dossiers (Version Professionnelle 3)

---
# 🔹 1. Objectif
Standardiser les noms de fichiers, la structure des dossiers et l'organisation documentaire pour :
- éviter les erreurs
- faciliter la recherche
- assurer une conformité juridique
- permettre l’automatisation future

Cette version combine **Version 1** + **Version 2**, avec :
✔ Préfixes par forme juridique (PP, SARL, SARL-AU, SA)  
✔ Exemples clients complets  
✔ Arborescences et templates pour chaque forme juridique  
✔ Règles pro de nommage  

---
# 🔹 2. Principes Généraux de Nommage

- Aucun accent, aucun espace → utiliser `-`
- Style *Titre* : majuscule à chaque mot
- Date au format : `YYYY-MM`
- Préfixe obligatoire selon la forme juridique
- Type du fichier en suffixe : `-Template`, `-Brouillon`, `-Final`, `-Signe`
- Format final :
```
[FORME]_YYYY-MM_Nom-Document_Type[-Version].ext
```

**Exemples** :
```
PP_2026-03_Attestation-Domiciliation-Initiale-Template.docx
SARL_2026-03_PV-Nomination-Gerant-Final.docx
SA_2026-03_PV-AG-Constitutive-Signe.pdf
```

---
# 🔹 3. Préfixes Officiels

| Forme Juridique | Préfixe |
|-----------------|----------|
| Personne Physique | **PP** |
| SARL | **SARL** |
| SARL AU | **SARL-AU** |
| SA | **SA** |

---
# 🔹 4. Structure de Dossiers – Racine
```
Dossiers/
├── Personne-Physique/
├── SARL/
├── SARL-AU/
└── SA/
```
Tous contiennent un dossier **99_Archive**.

---
# 🔹 5. Sous-dossiers & Templates par Forme Juridique

## 5.1 Personne Physique (PP)

### Arborescence
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

### Templates
```
PP_2026-03_Attestation-Domiciliation-Initiale-Template.docx
PP_2026-03_Contrat-Domiciliation-Template.docx
PP_2026-03_Declaration-Immatriculation-RC-Template.docx
PP_2026-03_Annonce-Legale-Template.docx
PP_2026-03_Depot-Legal-Template.docx
PP_2026-03_Dossier-Fiscal-PP-Template.docx
```

---
## 5.2 SARL

### Arborescence
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

### Templates
```
SARL_2026-03_Statuts-Societe-Template.docx
SARL_2026-03_Contrat-Domiciliation-Template.docx
SARL_2026-03_Attestation-Domiciliation-Initiale-Template.docx
SARL_2026-03_Declaration-Immatriculation-RC-Template.docx
SARL_2026-03_Annonce-Legale-Template.docx
SARL_2026-03_Depot-Legal-Constitution-Template.docx
SARL_2026-03_PV-Nomination-Gerant-Template.docx
SARL_2026-03_Lettre-Souscription-Liberation-Parts-Template.docx
SARL_2026-03_Liste-Associes-Template.xlsx
```

---
## 5.3 SARL AU

### Arborescence
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

### Templates
```
SARL-AU_2026-03_Statuts-SARL-AU-Template.docx
SARL-AU_2026-03_Contrat-Domiciliation-Template.docx
SARL-AU_2026-03_Attestation-Domiciliation-Initiale-Template.docx
SARL-AU_2026-03_Declaration-Immatriculation-RC-Template.docx
SARL-AU_2026-03_Annonce-Legale-Template.docx
SARL-AU_2026-03_Depot-Legal-Constitution-Template.docx
SARL-AU_2026-03_PV-Nomination-Gerant-Associe-Unique-Template.docx
SARL-AU_2026-03_Decision-Associe-Unique-Template.docx
```

---
## 5.4 SA

### Arborescence
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

### Templates
```
SA_2026-03_Statuts-SA-Template.docx
SA_2026-03_Contrat-Domiciliation-Template.docx
SA_2026-03_Attestation-Domiciliation-Initiale-Template.docx
SA_2026-03_Declaration-Immatriculation-RC-Template.docx
SA_2026-03_Annonce-Legale-Template.docx
SA_2026-03_Depot-Legal-Constitution-Template.docx
SA_2026-03_PV-Conseil-Administration-Template.docx
SA_2026-03_PV-AG-Constitutive-Template.docx
SA_2026-03_Lettre-Acceptation-Administrateur-Template.docx
```

---
# 🔹 6. Exemples Clients (Pro)

## Exemple 1 — Personne Physique
```
Dossier : 2026-03_Client_Mohamed-Amrani_PP/
│
├── PP_2026-03_Attestation-Domiciliation-Initiale-Final.docx
├── PP_2026-03_Contrat-Domiciliation-Signe.pdf
├── PP_2026-03_Declaration-Immatriculation-RC-Final.docx
├── PP_2026-03_Annonce-Legale-Final.pdf
└── PP_2026-03_Depot-Legal-Final.pdf
```

## Exemple 2 — SARL
```
Dossier : 2026-03_Client_TechnoPlus-SARL/
│
├── SARL_2026-03_Statuts-Societe-Signe.pdf
├── SARL_2026-03_PV-Nomination-Gerant-Final.docx
├── SARL_2026-03_Depot-Legal-Constitution-Final.pdf
├── SARL_2026-03_Attestation-Domiciliation-Initiale-Final.docx
├── SARL_2026-03_Contrat-Domiciliation-Signe.pdf
└── SARL_2026-03_Annonce-Legale-Final.pdf
```

## Exemple 3 — SARL AU
```
Dossier : 2026-03_Client_GreenEnergy-SARL-AU/
│
├── SARL-AU_2026-03_Statuts-SARL-AU-Signe.pdf
├── SARL-AU_2026-03_Decision-Associe-Unique-Final.docx
├── SARL-AU_2026-03_Depot-Legal-Constitution-Final.pdf
├── SARL-AU_2026-03_Contrat-Domiciliation-Signe.pdf
└── SARL-AU_2026-03_Annonce-Legale-Final.pdf
```

## Exemple 4 — SA
```
Dossier : 2026-03_Client_AtlasCorp-SA/
│
├── SA_2026-03_Statuts-SA-Signe.pdf
├── SA_2026-03_PV-AG-Constitutive-Final.docx
├── SA_2026-03_PV-Conseil-Administration-Final.docx
├── SA_2026-03_Depot-Legal-Constitution-Final.pdf
├── SA_2026-03_Contrat-Domiciliation-Signe.pdf
└── SA_2026-03_Annonce-Legale-Final.pdf
```

---
# 🔹 7. Archivage Professionnel

- Utiliser **99_Archive** pour toutes les anciennes versions
- Format recommandé :
```
[FORME]_YYYY-MM_Nom-Document_Archive_YYYY-MM-DD.ext
```
- Les fichiers signés doivent être en PDF

---
# 🔹 8. Variables Types (à utiliser dans les templates)
```
{{Nom-Societe}}
{{Forme-Juridique}}
{{Capital}}
{{Siege}}
{{RC}}
{{ICE}}
{{IF}}
{{CNSS}}
{{TP}}
{{Date}}
{{Gerant}}
{{Objet}}
```

---
# 🔹 9. Glossaire des Abréviations
- RC : Registre du Commerce
- ICE : Identifiant Commun de l’Entreprise
- IF : Identifiant Fiscal
- CNSS : Caisse Nationale de Sécurité Sociale
- TP : Taxe Professionnelle

---
# 🔹 VERSION PROFESSIONNELLE 3 — 07/03/2026
