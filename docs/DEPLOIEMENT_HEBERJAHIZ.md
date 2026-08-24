# Deploiement Heberjahiz (cPanel mutualise)

Deploiement automatique de la branche `php-haja` vers l'hebergement mutualise
Heberjahiz via GitHub Actions (FTPS). Le `vendor/` etant versionne dans git,
aucun Composer n'est requis sur le serveur.

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
Dans **File Manager** → `public_html/` → creer `.env` :

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
git push origin php-haja
```

- Le workflow `.github/workflows/deploy-heberjahiz.yml` se declenche et pousse
  les fichiers vers `public_html/` en FTPS.
- Premier deploiement lent (~50 Mo avec `vendor/`), ensuite **sync incremental** :
  seuls les fichiers modifies sont transferes.
- Suivi : onglet **Actions** du repo GitHub.
- Deploiement manuel possible : onglet Actions → *Deploy Heberjahiz* → **Run workflow**.

## 4. Rollback

```bash
git revert <commit> && git push origin php-haja
```
Le revert est redeploye automatiquement.

> La base de donnees n'est **pas** synchronisee par le deploiement : les
> changements de schema passent par `database/migrations/` (auto), les donnees
> de test via phpMyAdmin si besoin.

## 5. Depannage

| Symptome | Cause probable / solution |
|---|---|
| Echec FTP dans Actions | Verifier les secrets ; si le port FTPS 21 explicite est refuse, tester `protocol: ftp` (depannage) ou demander a Heberjahiz |
| Erreur 500 au chargement | Verifier version PHP ≥ 8.1 et extensions (1.1) |
| Page "Erreur de connexion BD" | Verifier `.env` : nom/user BDD prefixes par le compte cPanel |
| Migrations echouent au premier chargement | Message affiche en haut du tableau de bord ; reimporter schema.sql puis vider `_migrations` dans phpMyAdmin |
| PDF non genere | Normal sans LibreOffice/Word : le fallback PHPWord→Dompdf est utilise automatiquement (rendu simplifie) |
| Fichier supprime du repo toujours present sur le serveur | Le sync incremental conserve l'historique ; pour un nettoyage complet mettre temporairement `dangerous-clean-slate: true` dans le workflow |
