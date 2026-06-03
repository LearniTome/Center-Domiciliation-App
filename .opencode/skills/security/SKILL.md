---
name: security
description: Vérifie et applique les bonnes pratiques de sécurité PHP (CSRF, XSS, injections, fichiers) sur chaque nouvelle fonctionnalité
license: MIT
compatibility: opencode
---

# Skill Sécurité

Utilise cette skill quand tu ajoutes une nouvelle page, un formulaire, ou une manipulation de fichiers.

## CSRF
- Tout formulaire POST doit avoir `<?= csrf_input() ?>` dans le HTML
- Tout handler POST doit commencer par `verify_csrf();`
- Les helpers sont dans `includes/functions.php`

## XSS (Cross-Site Scripting)
- Toute sortie utilisateur/donnée doit passer par `<?= e($var) ?>` (`htmlspecialchars`)
- Jamais de `<?= $var ?>` brut dans le HTML
- Les valeurs JS doivent être échappées : `var x = '<?= e($var) ?>';`

## SQL Injection
- Toujours des requêtes préparées PDO avec paramètres nommés (`:nom`)
- Jamais de concaténation de variables dans les requêtes SQL
- Validation des types : `intval()`, `floatval()` ou `is_numeric()` pour les entrées numériques

## Fichiers
- Chemins de fichiers : toujours utiliser `basename()` ou `realpath()` + validation
- Éviter les inclusions dynamiques de fichiers non validés
- Les uploads de templates .docx doivent être restreints à l'extension .docx

## Fichier sensible
- `config/database.php` contient les identifiants MySQL — ne jamais exposer
- Ne jamais logger/afficher les mots de passe ou identifiants de connexion

## POST/Redirect
- Toujours `redirect_to('page')` après un POST réussi — ne jamais render sur POST
- Messages flash via `set_flash('success'|'error', 'message')`
