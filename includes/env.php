<?php

declare(strict_types=1);

/**
 * Chargeur de variables d'environnement minimal (sans phpdotenv).
 * Lit `.env` (et `.env.local` en surcharge) à la racine du projet.
 * Les variables déjà définies dans l'environnement réel ne sont pas écrasées.
 *
 * Usage : require_once __DIR__ . '/env.php'; // puis lire via env_var('KEY', defaut)
 */

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($value === '') {
            $value = '';
        } elseif (preg_match('/^"(.*)"$/s', $value, $m) === 1) {
            $value = $m[1];
        } elseif (preg_match("/^'(.*)'$/s", $value, $m) === 1) {
            $value = $m[1];
        } elseif (str_contains($value, ' #')) {
            $value = trim(substr($value, 0, strpos($value, ' #')));
        }

        if ($value !== '') {
            // Inline comments like KEY=value # commentaire
            $commentPos = strpos($value, ' #');
            if ($commentPos !== false && str_starts_with(substr($value, $commentPos + 1), '#')) {
                $value = trim(substr($value, 0, $commentPos));
            }
        }

        if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$projectRoot = dirname(__DIR__);
load_env_file($projectRoot . '/.env');
load_env_file($projectRoot . '/.env.local');

/**
 * Lit une variable d'environnement avec valeur par défaut.
 */
function env_var(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }
    return (string) $value;
}
