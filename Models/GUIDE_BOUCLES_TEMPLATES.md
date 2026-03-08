# Guide boucles templates (DocxTpl)

Ce guide regroupe les boucles discutees pour les modeles Word.

## 1) Activites (reprendre la meme puce du template)

Important:
- Ne pas ecrire `•` dans le texte.
- Garder la ligne `{{ a }}` avec le style "puce" deja existant dans Word.
- Mettre les tags `{%p ... %}` sur des lignes normales (sans puce).

```jinja
{%p for a in ACTIVITES_LIST %}
{{ a }}
{%p endfor %}
```

Alias dispo: `ACTIVITES_LIST` ou `ACTIVITIES_LIST`.

## 2) Associes (bloc "Le soussigne")

```jinja
{%p for a in associes %}
{{ a.civilite }} {{ a.prenom }} {{ a.nom }} de Nationalite {{ a.nationalite }}, titulaire de la CIN N° {{ a.num_piece }}, ne le {{ a.date_naiss }} a {{ a.lieu_naiss }}, demeurant a {{ a.adresse }}.
{%p if not loop.last %}
ET
{%p endif %}
{%p endfor %}
```

La ligne `ET` reste seule entre deux associes.

## 3) Repartition des parts/capital par associe

```jinja
{%p for a in associes %}
- {{ a.civilite }} {{ a.prenom }} {{ a.nom }} ........................................ {{ a.num_parts }} Parts.
{%p endfor %}
```

Variante montant:

```jinja
{%p for a in associes %}
- {{ a.civilite }} {{ a.prenom }} {{ a.nom }} ........................................ {{ a.capital_detenu }} DHS.
{%p endfor %}
```

## 4) Gerance (1 gerant, 2 co-gerants, signature separee/conjointe)

Champ ajoute dans l'application pour SARL: `MODE_SIGNATURE_GERANCE`
- valeurs: `separee` ou `conjointe`

Exemple type:

```jinja
{% set gerants = associes | selectattr('est_gerant') | list %}

{%p if gerants|length == 1 %}
Des a present, {{ gerants[0].civilite }} {{ gerants[0].prenom }} {{ gerants[0].nom }} est nomme gerant statutaire pour une duree illimitee.
{%p endif %}

{%p if gerants|length == 2 %}
Des a present, {{ gerants[0].civilite }} {{ gerants[0].prenom }} {{ gerants[0].nom }} et {{ gerants[1].civilite }} {{ gerants[1].prenom }} {{ gerants[1].nom }} sont nommes co-gerants statutaires pour une duree illimitee.
{%p if MODE_SIGNATURE_GERANCE == 'conjointe' %}
La societe est engagee par la signature conjointe des deux co-gerants.
{%p endif %}
{%p if MODE_SIGNATURE_GERANCE != 'conjointe' %}
La societe est engagee par la signature separee de chacun des co-gerants.
{%p endif %}
{%p endif %}
```

## 5) Signatures (uniquement modele SARL multi-associes)

Dans le template `Models/SARL/SARL_2026-03_Statuts_Template.docx`, utiliser:

```jinja
{%p for a in associes %}
{{ a.civilite }} {{ a.prenom }} {{ a.nom }}
{{ a.qualite }}
{%p endfor %}
```

Ne pas appliquer cette boucle aux autres modeles si vous voulez garder la signature simple.

## 6) Rappel rapide

- Boucles de liste: utiliser `associes` et `ACTIVITES_LIST`.
- Si vous voulez garder un style Word (puce, retrait, police), appliquer ce style sur la ligne contenant `{{ ... }}`.
- Utiliser `{%p ... %}` pour eviter les lignes parasites dans Word.
