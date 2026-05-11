
# Charte de Génération Automatique des Dossiers Clients – Version 4 (Sans Sous‑Dossiers)

## 1. Objectif
Cette charte définit les standards professionnels de création automatique :
- des **dossiers clients**,
- des **fichiers générés**,
- des **règles de nommage**,
- des **exemples concrets** selon les formes juridiques courantes au Maroc.

Date de référence : **2026-03-07**.

---
## 2. Format du dossier client (sans sous-dossiers)
Format officiel :
```
{{Dom-0000}}_{{Client-Nom}}_{{Forme-Juridique}}
```

### Exemples concrets :
```
DOM-0001_Mohamed-Amrani_PP
DOM-0155_TechnoPlus_SARL
DOM-0233_GreenEnergy_SARL-AU
DOM-0400_AtlasCorp_SA
```

---
## 3. Format du fichier généré
Format officiel :
```
[FORME]_YYYY-MM-DD_Nom-Document_Type.ext
```

### Règles :
- `FORME` ∈ {PP, SARL, SARL-AU, SA}
- Première génération → **Type = Brouillon**
- Après validation → **Type = Final**
- Documents signés → **Type = Signe** (PDF recommandé)
- Pas d’accents, pas d’espaces → utilisation du “-”

### Exemple général :
```
SARL_2026-03-07_Contrat-Domiciliation_Brouillon.docx
```

---
## 4. Exemples complets par forme juridique

# 4.1 Personne Physique (PP)
Dossier :
```
DOM-0001_Mohamed-Amrani_PP
```
Fichiers générés :
```
PP_2026-03-07_Attestation-Domiciliation-Initiale_Brouillon.docx
PP_2026-03-07_Contrat-Domiciliation_Brouillon.docx
PP_2026-03-07_Declaration-Immatriculation-RC_Brouillon.docx
PP_2026-03-07_Annonce-Legale_Brouillon.docx
PP_2026-03-07_Depot-Legal_Brouillon.docx
PP_2026-03-07_Dossier-Fiscal_Brouillon.docx
```

---
# 4.2 SARL
Dossier :
```
DOM-0155_TechnoPlus_SARL
```
Fichiers générés :
```
SARL_2026-03-07_Statuts-Societe_Brouillon.docx
SARL_2026-03-07_Attestation-Domiciliation-Initiale_Brouillon.docx
SARL_2026-03-07_Contrat-Domiciliation_Brouillon.docx
SARL_2026-03-07_Declaration-Immatriculation-RC_Brouillon.docx
SARL_2026-03-07_Lettre-Souscription-Liberation-Parts_Brouillon.docx
SARL_2026-03-07_PV-Nomination-Gerant_Brouillon.docx
SARL_2026-03-07_Liste-Associes_Brouillon.xlsx
SARL_2026-03-07_Depot-Legal-Constitution_Brouillon.docx
SARL_2026-03-07_Annonce-Legale_Brouillon.docx
```

---
# 4.3 SARL-AU
Dossier :
```
DOM-0233_GreenEnergy_SARL-AU
```
Fichiers générés :
```
SARL-AU_2026-03-07_Statuts-SARL-AU_Brouillon.docx
SARL-AU_2026-03-07_Attestation-Domiciliation-Initiale_Brouillon.docx
SARL-AU_2026-03-07_Contrat-Domiciliation_Brouillon.docx
SARL-AU_2026-03-07_Declaration-Immatriculation-RC_Brouillon.docx
SARL-AU_2026-03-07_PV-Nomination-Gerant-Associe-Unique_Brouillon.docx
SARL-AU_2026-03-07_Decision-Associe-Unique_Brouillon.docx
SARL-AU_2026-03-07_Depot-Legal-Constitution_Brouillon.docx
SARL-AU_2026-03-07_Annonce-Legale_Brouillon.docx
```

---
# 4.4 SA
Dossier :
```
DOM-0400_AtlasCorp_SA
```
Fichiers générés :
```
SA_2026-03-07_Statuts-SA_Brouillon.docx
SA_2026-03-07_Attestation-Domiciliation-Initiale_Brouillon.docx
SA_2026-03-07_Contrat-Domiciliation_Brouillon.docx
SA_2026-03-07_Declaration-Immatriculation-RC_Brouillon.docx
SA_2026-03-07_PV-Conseil-Administration_Brouillon.docx
SA_2026-03-07_PV-AG-Constitutive_Brouillon.docx
SA_2026-03-07_Depot-Legal-Constitution_Brouillon.docx
SA_2026-03-07_Lettre-Acceptation-Administrateur_Brouillon.docx
SA_2026-03-07_Annonce-Legale_Brouillon.docx
```

---
## 5. Variables de génération
À remplacer automatiquement :
```
{{Dom-0000}}
{{Client-Nom}}
{{Forme-Juridique}}
{{DateYYYY-MM-DD}}
{{RC}}, {{ICE}}, {{IF}}, {{CNSS}}, {{TP}}, {{Siege}}, {{Capital}}, {{Gerant}}, {{Objet}}
```

---
## 6. États des fichiers (cycle de vie)
- Première génération → **Brouillon**
- Après vérification → **Final**
- Lorsque signé → **Signe** (PDF)

Format :
```
[FORME]_YYYY-MM-DD_Nom-Document_Final.docx
[FORME]_YYYY-MM-DD_Nom-Document_Signe.pdf
```

---
## 7. Conclusion
Cette structure simple et professionnelle garantit :
- un fonctionnement optimal pour une application automatisée,
- une organisation claire,
- aucun conflit de dossiers,
- un flux de production fluide et contrôlable.

Version 4 – 07/03/2026
