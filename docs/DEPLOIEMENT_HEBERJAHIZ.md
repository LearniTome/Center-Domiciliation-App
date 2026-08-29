# Deploiement Heberjahiz (cPanel mutualise)

Deploiement automatique de la branche `main` (production) vers l'hebergement mutualise
Heberjahiz via GitHub Actions (FTPS). Le `vendor/` etant versionne dans git,
aucun Composer n'est requis sur le serveur.

> Travail de developpement sur `php-haja` : pour mettre en production, copier
> `php-haja` vers `main` (`git checkout main && git reset --hard php-haja && git push --force origin main`).

## 1. Setup cPanel (une seule fois)

### 1.1 PHP et extensions
- **MultiPHP Manager** : PHP **8.2** (ou 8.1 minimum)
- **Select PHP Version** → extensions actives :
  `pdo_mysql`, `zip`, `mbstring`, `xml`, `gd`, `curl`

### 1.2 Base de donnees
- **MySQL Databases** : creer une base (nom impose avec prefixe cPanel, ex.
  `monuser_center`) + un utilisateur avec privileges **ALL**
- **phpMyAdmin** → selectionner la base → Importer :
  1. `database/schema.sql`
  2. `database/seed.sql`

Les migrations (`database/migrations/`) s'appliquent automatiquement au premier
chargement de la page — rien a faire ensuite.

### 1.3 Fichier .env
Dans **File Manager** → `app.centirio.ma/` (document root du sous-domaine) → creer `.env` :

```env
APP_NAME=Center Domiciliation App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tondomaine.ma
DB_HOST=localhost
DB_PORT=3306
DB_NAME=monuser_center
DB_USERNAME=monuser_user
DB_PASSWORD=********
DB_CHARSET=utf8mb4

ANTHROPIC_API_KEY=
AI_MODEL=claude-sonnet-4-20250514
AI_MAX_TOKENS=4096
AI_TEMPERATURE=0.7
AI_CACHE_TTL=3600
```

> `DB_HOST=localhost` est la valeur standard cPanel. Adapter les identifiants DB
> a ceux crees en 1.2.

### 1.4 SSL
**SSL/TLS Status** → Run AutoSSL (Let's Encrypt) pour le domaine.

## 2. Secrets GitHub (une seule fois)

Repo GitHub → **Settings → Secrets and variables → Actions → New repository secret** :

| Secret          | Valeur                                        |
|-----------------|-----------------------------------------------|
| `FTP_HOST`      | hote FTP fourni par Heberjahiz (ex. `ftp.tondomaine.ma`) |
| `FTP_USER`      | utilisateur FTP (= utilisateur cPanel)        |
| `FTP_PASSWORD`  | mot de passe FTP                              |

## 3. Deploiement

```bash
git push origin main
```

- Le workflow `.github/workflows/deploy-heberjahiz.yml` :
  1. **Zippe** le repo (hors `.git`, docs, scripts, tests, dossiers_generer, backups…)
  2. **Upload** un seul fichier `app.zip` vers `app.centirio.ma/` en FTPS
     (un seul transfert : les sessions FTPS longues sont coupees par le serveur)
  3. Declenche `_deploy_extract.php` en HTTPS (token protege) qui **decompresse**
     sur place puis supprime le zip et s'auto-supprime
- Duree : **2 a 4 minutes** par deploiement (tout est retransfere a chaque push).
- Suivi : onglet **Actions** du repo GitHub.
- Deploiement manuel possible : onglet Actions → *Deploy Heberjahiz* → **Run workflow**.

> L'extraction ajoute/met a jour les fichiers mais n'efface pas les anciens
> fichiers devenus inutiles — nettoyage ponctuel via File Manager si besoin.

## 4. Rollback

```bash
git revert <commit> && git push origin main
```
Le revert est redeploye automatiquement.

> La base de donnees n'est **pas** synchronisee par le deploiement : les
> changements de schema passent par `database/migrations/` (auto), les donnees
> de test via phpMyAdmin si besoin.

## 5. Depannage

| Symptome | Cause probable / solution |
|---|---|
| Echec upload FTP dans Actions | Verifier les secrets ; le port FTPS explicite 21 doit etre ouvert |
| Etape "Extraction" en echec (pas de reponse HTTP) | Le domaine app.centirio.ma ne pointe pas encore ou SSL absent — verifier DNS/AutoSSL puis relancer le workflow (le zip est deja sur le serveur) |
| `EXTRACT_FAIL_*` | Zip corrompu ou droits insuffisants sur `app.centirio.ma/` (passer en 0755 via File Manager) |
| Erreur 500 au chargement | Verifier version PHP ≥ 8.1 et extensions (1.1) |
| Page "Erreur de connexion BD" | Verifier `.env` : nom/user BDD prefixes par le compte cPanel, `DB_HOST=localhost` |
| Migrations echouent au premier chargement | Message affiche en haut du tableau de bord ; reimporter schema.sql puis vider `_migrations` dans phpMyAdmin |
| PDF non genere | Normal sans LibreOffice/Word : le fallback PHPWord→Dompdf est utilise automatiquement (rendu simplifie) |
| Fichiers obsolètes sur le serveur | L'extraction n'efface pas — supprimer a la main dans File Manager si necessaire |
