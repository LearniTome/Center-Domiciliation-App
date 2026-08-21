# Architecture Dolibarr (référence)

Dolibarr ERP/CRM v23 — PHP 7.2+ sans framework, MySQL/MariaDB/PostgreSQL. ~100 modules natifs. GPL-3+.

## Structure du code (`htdocs/`)

```
htdocs/
├── main.inc.php              # Bootstrap session + contexte
├── master.inc.php            # Chargement conf + DB + constantes
├── conf/conf.php             # Config locale (hors VCS)
├── index.php                 # Login
├── {module}/                 # Un dossier par module métier
│   ├── card.php              # Fiche détail/création/édition d'un objet
│   ├── list.php              # Liste avec filtres/tri/pagination
│   ├── document.php          # Documents attachés à l'objet (GED)
│   ├── note.php              # Notes internes
│   ├── class/{objet}.class.php   # Objet métier extends CommonObject
│   ├── admin/                # Setup du module (constantes, extrafields)
│   ├── lib/{module}.lib.php  # Fonctions + préparation head des onglets
│   ├── sql/                  # DDL du module (llx_ tables)
│   └── core/
│       ├── modules/mod{Module}.class.php      # Descripteur du module
│       └── triggers/interface_99_mod{Module}_{Nom}.class.php  # Triggers
├── core/
│   ├── class/commonobject.class.php  # Classe de base objets métier
│   ├── class/hookmanager.class.php   # Gestionnaire de hooks
│   ├── class/html.form*.class.php    # Widgets formulaires réutilisables
│   ├── modules/                      # Numérotateurs (mod_facture_terre...)
│   └── triggers/                     # Triggers core
├── custom/                   # Modules externes (jamais écrasés par upgrade)
└── api/index.php             # Point d'entrée API REST (+ /explorer = Swagger)
```

## Descripteur de module `mod{Module}.class.php`

Classe chargée dynamiquement pour activer/désactiver un module. Responsabilités :

```php
class ModMonModule extends DolibarrModules {
    public function __construct($db) {
        $this->numero = 500000;              // ID unique du module
        $this->rights_class = 'monmodule';   // préfixe des permissions
        // $this->dirs : dossiers documents à créer
        // $this->config_page_url : page admin du module
        // $this->depends : modules requis ; $this->conflictwith : incompatibles
        // $this->langfiles : fichiers de traduction
        // $this->tabs : onglets ajoutés aux fiches d'autres objets
        // $this->hooks : hooks consommés
        // $this->menu : entrées de menu (leftmenu/topmenu)
        // $this->boxes : widgets dashboard
        // $this->cronjobs : tâches planifiées
        // $this->constants : constantes posées à l'activation
    }
    // insert() / remove() : gérées par la classe parente via $this->sql (DDL) et rights
}
```

## Permissions

Déclarées dans le descripteur (`$this->rights`), stockées table `llx_rights_def`, attribuées aux groupes :

| Droit | Usage |
|---|---|
| `{module}->read` | Voir les listes/fiches |
| `{module}->create` | Créer |
| `{module}->edit` (ou write) | Modifier |
| `{module}->delete` | Supprimer |
| `{module}->export` | Exporter CSV/Excel |

Vérification partout dans le code : `$user->hasRight('monmodule', 'create')` — pas seulement au niveau menu. Les boutons sont masqués ET les handlers refusent l'action.

## Hooks (points d'accroche)

Le `HookManager` parcourt les modules déclarant un hook et appelle leurs méthodes. Deux familles :

- **Hooks "add"** : retourne du contenu HTML injecté (formulaires, lignes, boutons)
- **Hooks "replace"** : remplace le rendu standard

Hooks courants :

| Hook | Moment |
|---|---|
| `formObjectOptions` | Champs supplémentaires dans un formulaire d'objet |
| `formAddObjectLine` / `printObjectLine` | Lignes de devis/facture/commande |
| `addMoreActionsButtons` | Boutons d'action supplémentaires sur une fiche |
| `doActions` | Traiter un POST custom (paramètre `action`) |
| `formConfirm` | Dialogues de confirmation (clôture, annulation...) |
| `printCommonFooter` | JS/HTML en fin de page |
| `restrictedArea` | Affiner le contrôle d'accès par tiers/utilisateur |
| `afterPDFCreation` | Post-traitement d'un PDF généré |
| `completeTabsHead` | Ajouter des onglets avec compteurs |

## Triggers (événements métier)

Fichier `core/triggers/interface_99_mod{Module}_{Nom}.class.php`, méthode `runTrigger($action, $object, $user, $langs, $conf)`.

Nomenclature des événements : `{OBJET}_{ACTION}` — exemples :
`COMPANY_CREATE`, `COMPANY_MODIFY`, `COMPANY_DELETE`, `CONTACT_CREATE`,
`PROPAL_VALIDATE`, `PROPAL_CLOSE_SIGNED`, `ORDER_VALIDATE`, `ORDER_CANCEL`,
`FACTURE_CREATE`, `FACTURE_VALIDATE`, `FACTURE_PAYED`, `FACTURE_DELETE`,
`CONTRACT_CREATE`, `CONTRACT_MODIFY`, `PRODUCT_CREATE`, `MEMBER_VALIDATE`,
`USER_CREATE`, `USER_LOGIN`, `USER_LOGOUT`, `BILL_SUPPLIER_VALIDATE`.

Usage typique : notification email, écriture comptable, mise à jour stock, log d'audit, synchronisation externe. Règle : **un trigger ne modifie jamais le résultat de l'action** — il constate et propage.

## CommonObject (objets métier)

Tous les objets (Societe, Contact, Product, Facture, Propal, Commande, Contrat...) héritent de `CommonObject` qui fournit :

- CRUD (`create/update/delete/fetch/fetchAll`) via `$this->db`
- Statuts + `setStatut()` avec validation des transitions
- Notes publiques/privées, documents attachés (`documents/`), liens entre objets
- Extrafields (`$this->array_options`), contacts liés, historique d'événements
- Validation métier centralisée avant persistance

## Workflow statuts (facture = référence)

```
Brouillon(0) → Validée(1) → Payée(2) → Classée abandonnée(3)
                    ↓
              Facturée/convertie
```

- Transitions contrôlées (`is_last_in_cycle`, vérif du statut courant)
- Chaque transition : trigger + date horodatée + utilisateur
- Re-numérotation définitive à la validation (brouillon = PROV-xxx temporaire)

## Numérotation

Classes `mod_facture_*` (terre, mars, mercure...) : masque configurable
(`{yyyy}-{nn}`), séquence par année, reset annuel, anti-collision par verrou.
Équivalent projet : `next_dossier_number()` avec format `PREFIX-YYYY-NNN`.

## Documents & templates

- Templates ODT/PDF dans `documents/doctemplates/` avec variables `{nom_objet}`
- Génération : remplissage template → fichier dans `documents/{module}/{ref}/`
- GED : chaque objet liste ses fichiers via `document.php`

## Extrafields

Champs personnalisés définis par l'admin (table `llx_extrafields`), valeurs dans
`llx_{objet}_extrafields`. Types : varchar, text, int, double, date, datetime,
boolean, select, radio, checkbox, link vers autre objet. Rendus automatiquement
dans les formulaires via hook `formObjectOptions`.

## Modules natifs par domaine (aperçu)

- **Tiers** : thirdparties (clients/prospects/fournisseurs), contacts/adresses, adhérents
- **Produits** : produits/services, stocks/entrepôts, lots/séries, variantes, BOM, MO
- **Ventes** : propositions commerciales, commandes clients, contrats/abonnements, interventions, tickets, expéditions, factures/avoirs, POS
- **Achats** : demandes de prix fournisseurs, commandes fournisseurs, réceptions, factures fournisseurs
- **Finance** : banques, prélèvements/virements SEPA, comptabilité en partie double, dons, emprunts, marges
- **Collaboration** : agenda, projets/tâches, événements, sondages
- **RH** : congés, notes de frais, recrutement, timesheets, employés
- **Support** : GED, bookmarks, reporting, import/export, LDAP, e-mailing massif, email-collector, RSS, PayPal/Stripe/Paybox, IA via API
