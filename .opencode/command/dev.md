---
description: Lance le serveur de dev PHP intégré (dev-server.ps1) pour ce projet sur un port donné
agent: build
---

Démarre le serveur de développement de ce projet avec la nouvelle approche multi-projets (PHP intégré, un port par projet, sans Apache).

1. **Port** : si `$ARGUMENTS` contient un nombre, utilise-le ; sinon `8000`. Vérifie qu'il est libre (`Get-NetTCPConnection -LocalPort <port> -State Listen`).
2. **Lancement détaché** (le serveur doit rester actif après la fin du tool call) :
   ```
   Start-Process -FilePath "C:\xampp\php\php.exe" -ArgumentList "-S localhost:<port> -t <docroot>" -WindowStyle Hidden
   ```
   Docroot : la racine du projet, ou `public/` si le projet a un `public/index.php`. Utilise plutôt `dev-server.ps1` avec `-NoBrowser` si tu préfères :
   ```
   powershell -NoProfile -ExecutionPolicy Bypass -File .\dev-server.ps1 -Project . -Port <port> -NoBrowser
   ```
3. **Vérification** : attends ~2s puis teste avec `curl.exe -s -o NUL -w "%{http_code}" http://localhost:<port>/` — un code 200 ou 302 confirme que l'app répond.
4. Confirme à l'utilisateur l'URL `http://localhost:<port>/` et le port utilisé.

Ne bloque pas sur le serveur — il tourne en arrière-plan. Précise à l'utilisateur comment l'arrêter : `Get-NetTCPConnection -LocalPort <port> -State Listen | % { Stop-Process -Id $_.OwningProcess -Force }`.
