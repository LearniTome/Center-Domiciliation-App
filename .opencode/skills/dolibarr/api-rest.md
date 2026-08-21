# API REST Dolibarr + intégration entre apps PHP

## API REST Dolibarr

### Authentification
- Token API généré par utilisateur : carte utilisateur → onglet « Interface API » (ou `DOL_API_TOKEN`)
- Envoi du token : header `DOLAPIKEY: <token>` (ou `?api_key=` en GET, déconseillé)
- Explorateur interactif (Swagger) : `https://<dolibarr>/api/index.php/explorer`
- Base URL des endpoints : `https://<dolibarr>/api/index.php/`

### Endpoints principaux

| Objet | Endpoints |
|---|---|
| Tiers | `GET/POST /thirdparties`, `GET /thirdparties/{id}`, `PUT /thirdparties/{id}`, `DELETE /thirdparties/{id}`, `GET /thirdparties/email/{email}` |
| Contacts | `/contacts` (+ `GET /contacts/email/{email}`) |
| Produits | `/products`, `/products/{id}`, `/products/ref/{ref}` |
| Devis | `/proposals` (+ `POST /proposals/{id}/validate`, `/close`) |
| Commandes | `/orders` (+ validate/close) |
| Factures | `/invoices` (+ `POST /invoices/{id}/payments`, `/validate`, `/settopaid`) |
| Contrats | `/contracts` |
| Projets/Tâches | `/projects`, `/tasks` |
| Agenda | `/agendaevents` |
| Documents | `/documents/download?modulepart=...&original_file=...`, `/documents/upload` |
| Utilisateurs | `/users`, `/users/info` (info du token courant) |

Conventions : filtres via query params (`sortfield`, `sortorder`, `limit`, `sqlfilters`), réponses JSON, codes HTTP standards (200/201/401/403/404/500).

### Client PHP minimal (cURL, sans dépendance)

```php
function dolibarr_api(string $method, string $path, ?array $body = null): array
{
    $base = rtrim(env_var('DOLIBARR_URL', ''), '/');
    $token = env_var('DOLIBARR_API_TOKEN', '');
    if ($base === '' || $token === '') {
        throw new RuntimeException('Dolibarr non configuré (.env : DOLIBARR_URL, DOLIBARR_API_TOKEN)');
    }
    $ch = curl_init($base . '/api/index.php/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'DOLAPIKEY: ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($res === false) {
        throw new RuntimeException('Dolibarr injoignable');
    }
    $data = json_decode($res, true);
    if ($code >= 400) {
        throw new RuntimeException('Erreur Dolibarr ' . $code . ' : ' . substr($res, 0, 300));
    }
    return is_array($data) ? $data : [];
}
```

- Placer les clés dans `.env` (jamais en dur) : `DOLIBARR_URL`, `DOLIBARR_API_TOKEN`
- Toujours vérifier la disponibilité avant d'afficher une UI dépendante (mode dégradé)
- Logger chaque appel échoué (log_activity type erreur) — pas de fuite du token dans les logs

## Pattern d'intégration entre deux apps PHP (A = cette app, B = Dolibarr)

### Principes
1. **Un seul système maître par donnée** (ex. sociétés créées dans A → poussées vers B ; factures gérées dans B → lues depuis A). Jamais d'édition bidirectionnelle de la même donnée.
2. **Synchronisation par triggers** : après un événement métier dans A (création société), appeler B en asynchrone ou via file d'attente simple (table `_sync_queue` + cron), jamais bloquer le POST utilisateur.
3. **Correspondance stockée** : table de mapping `sync_links(id_local, remote_type, remote_id, synced_at)` pour retrouver l'ID distant sans re-chercher.
4. **Idempotence** : avant création distante, chercher par clé naturelle (email/SIRET/référence) — si trouvé, mettre à jour au lieu de dupliquer.

### Mapping entités Center-Domiciliation ↔ Dolibarr

| App A (center_domiciliation) | Dolibarr | Sens suggéré |
|---|---|---|
| `societes` | `thirdparties` (client) | A → B |
| `collaborateurs` / `associes` | `contacts` liés au tiers | A → B |
| `contrats` | `contrats` (+ lignes abonnement) | A → B |
| Facturation domiciliation | `invoices` | B maître ou A → B |
| `uploaded_docs` | `documents` (ECM) | A → B |
| `activity_logs` | `agendaevents` | A → B (optionnel) |

### Exemple de trigger de synchronisation (côté A)

```php
// Après INSERT societes réussi :
function sync_societe_to_dolibarr(?PDO $pdo, int $societeId): void
{
    try {
        $soc = fetch_record($pdo, 'societes', $societeId);
        if ($soc === null) return;
        // Idempotence : lien existant ?
        $q = $pdo->prepare("SELECT remote_id FROM sync_links WHERE id_local = :id AND remote_type = 'thirdparty'");
        $q->execute([':id' => $societeId]);
        $remoteId = $q->fetchColumn();
        $payload = [
            'name'   => $soc['raison_sociale'],
            'email'  => $soc['email'] ?? '',
            'phone'  => $soc['telephone'] ?? '',
            'client' => 1,
            'array_options' => ['options_dossier_dom' => $soc['societe_dossier_domiciliation_number'] ?? ''],
        ];
        if ($remoteId) {
            dolibarr_api('PUT', 'thirdparties/' . (int) $remoteId, $payload);
        } else {
            $created = dolibarr_api('POST', 'thirdparties', $payload);
            $ins = $pdo->prepare("INSERT INTO sync_links (id_local, remote_type, remote_id, synced_at) VALUES (:l, 'thirdparty', :r, NOW())");
            $ins->execute([':l' => $societeId, ':r' => (int) $created['id']]);
        }
    } catch (Throwable $e) {
        // Ne jamais casser le flux utilisateur pour une synchro :
        error_log('[dolibarr-sync] ' . $e->getMessage());
    }
}
```

### Table de queue (si synchro différée)

```sql
CREATE TABLE IF NOT EXISTS _sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(30) NOT NULL,          -- create|update|delete
    entity VARCHAR(40) NOT NULL,          -- thirdparty|contact|contract...
    local_id INT NOT NULL,
    payload JSON NULL,
    attempts TINYINT DEFAULT 0 NOT NULL,
    last_error TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL
) ENGINE=InnoDB;
```

Cron (ou tâche planifiée Windows) qui rejoue la queue avec backoff ; abandon après N tentatives avec notification.

## Sécurité intégration

- Token API = secret : `.env` gitignoré, jamais dans le code ni les commits
- Restreindre le token à un utilisateur dédié avec permissions minimales dans Dolibarr
- Valider/échapper toute donnée reçue de B avant affichage dans A (`e()`)
- Timeout cURL court (10–15 s) + mode dégradé si B indisponible
