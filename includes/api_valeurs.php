<?php

declare(strict_types=1);

/**
 * Normalisation des valeurs POST selon le type reel des colonnes.
 *
 * Isolé dans un fichier includable (et non dans api.php) pour rester
 * testable : api.php s'exécute dès son inclusion et ne peut pas être chargé
 * par PHPUnit.
 *
 * Origine du besoin : les listes d'options des modales sont parfois indexées
 * par un identifiant (par exemple [8 => 'Comptable agréé']). Une heuristique
 * based on is_int() sur la clef confondait cet identifiant avec un index de
 * liste et soumettait le libellé dans une colonne entière, provoquant une
 * erreur SQL "Incorrect integer value" chez l'utilisateur.
 *
 * Ce fichier ne couvre que la conversion cote serveur. Le rendu des <option>
 * dans les modales teste array_is_list() de son cote.
 */

/**
 * Convertit une valeur POST selon le type reel de la colonne, lue par
 * SHOW COLUMNS.
 *
 * Sans cette conversion, un texte envoyé dans une colonne entière (par exemple
 * un libellé de rôle là où MySQL attend un id) remonte au client sous la forme
 * d'une exception SQL brute. On la remplace par un refus explicite, ou l'on
 * convertit ce qui est convertible, comme "8" pour un entier.
 *
 * Une chaîne vide devient NULL sur les colonnes numériques et temporelles,
 * où elle n'a pas de sens ; elle reste une chaîne légitime pour du texte.
 *
 * @return array{ok: bool, value: mixed, message: string}
 */
function api_normaliser_valeur(mixed $value, string $type): array
{
    if ($value === null) {
        return ['ok' => true, 'value' => null, 'message' => ''];
    }

    if (!is_string($value)) {
        return ['ok' => true, 'value' => $value, 'message' => ''];
    }

    $estNumeriqueOuTemporelle = (bool) preg_match('/^(date|datetime|timestamp|decimal|double|float|int|bigint|tinyint|smallint|mediumint)\b/i', $type);

    if ($estNumeriqueOuTemporelle) {
        $brut = trim($value);

        if ($brut === '') {
            return ['ok' => true, 'value' => null, 'message' => ''];
        }

        if (preg_match('/^(date|datetime|timestamp)\b/i', $type)) {
            $normalise = (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $brut, $m))
                ? "{$m[3]}-{$m[2]}-{$m[1]}"
                : $brut;

            $estHorodatee = str_contains(strtolower($type), 'timestamp') || str_contains(strtolower($type), 'datetime');

            if ($estHorodatee) {
                // La forme seule ne suffit pas : MySQL refuse 2026-02-31, on
                // verifie donc aussi l'existence reelle de la date, et les
                // plages de l'heure. Une date nue reste acceptee (MySQL y
                // applique 00:00:00).
                [$jour, $reste] = array_pad(explode(' ', $normalise, 2), 2, '');
                $valide = date_iso_valide($jour) && api_heure_valide($reste);
            } else {
                $valide = date_iso_valide($normalise);
            }

            if (!$valide) {
                return [
                    'ok' => false,
                    'value' => null,
                    'message' => 'Date invalide : "' . $brut . '". Format attendu : JJ/MM/AAAA.',
                ];
            }

            return ['ok' => true, 'value' => $normalise, 'message' => ''];
        }

        if (preg_match('/^(int|bigint|tinyint|smallint|mediumint)\b/i', $type)) {
            if (!preg_match('/^-?\d+$/', $brut)) {
                return [
                    'ok' => false,
                    'value' => null,
                    'message' => 'Valeur numerique attendue, recu : "' . $brut . '".',
                ];
            }

            return ['ok' => true, 'value' => (int) $brut, 'message' => ''];
        }

        if (preg_match('/^(decimal|double|float)\b/i', $type)) {
            $point = str_replace(',', '.', $brut);
            if (!is_numeric($point)) {
                return [
                    'ok' => false,
                    'value' => null,
                    'message' => 'Nombre attendu, recu : "' . $brut . '".',
                ];
            }

            return ['ok' => true, 'value' => $point + 0, 'message' => ''];
        }
    }

    return ['ok' => true, 'value' => $value, 'message' => ''];
}

/**
 * Verifie la partie horaire d'un horodatage : vide (date nue, que MySQL
 * complete par 00:00:00), HH:MM ou HH:MM:SS, avec des plages reelles.
 */
function api_heure_valide(string $heure): bool
{
    if ($heure === '') {
        return true;
    }

    if (!preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $heure, $m)) {
        return false;
    }

    $h = (int) $m[1];
    $min = (int) $m[2];
    $sec = isset($m[3]) ? (int) $m[3] : 0;

    return $h <= 23 && $min <= 59 && $sec <= 59;
}

/**
 * Types des colonnes d'une table, indexes par nom de colonne.
 *
 * @return array<string, string>
 */
function api_types_colonnes(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table}"); // nosemgrep: tainted-sql-string -- $table appelee apres validation de la whitelist $allowedTables
    $stmt->execute();

    $types = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $types[(string) $row['Field']] = (string) $row['Type'];
    }

    return $types;
}
