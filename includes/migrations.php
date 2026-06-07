<?php

declare(strict_types=1);

function run_migrations(PDO $pdo): array
{
    $results = [];
    $tableName = '_migrations';
    $dir = __DIR__ . '/../database/migrations';

    if (!is_dir($dir)) {
        return $results;
    }

    // Create _migrations table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_migrations_filename (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Get already applied migrations
    $applied = $pdo->query("SELECT filename FROM {$tableName}")
        ->fetchAll(PDO::FETCH_COLUMN);

    // Scan migration files
    $files = glob($dir . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);

        if (in_array($filename, $applied, true)) {
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            $results[$filename] = 'EMPTY';
            continue;
        }

        try {
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT IGNORE INTO {$tableName} (filename) VALUES (:f)");
            $stmt->execute(['f' => $filename]);
            $results[$filename] = 'OK';
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            // Ignore "duplicate column" / "duplicate key" / "already exists" errors
            // since the schema may already be up-to-date
            $duplicatePatterns = [
                '42S21',   // Column already exists
                '42S01',   // Table already exists
                '42000',   // Duplicate key (MariaDB specific)
            ];
            $isDuplicate = false;
            foreach ($duplicatePatterns as $code) {
                if (str_contains($msg, "SQLSTATE[$code]") || str_contains($msg, "($code)")) {
                    $isDuplicate = true;
                    break;
                }
            }

            if ($isDuplicate) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$tableName} (filename) VALUES (:f)");
                $stmt->execute(['f' => $filename]);
                $results[$filename] = 'OK (deja applique)';
            } else {
                $results[$filename] = 'ERROR: ' . $msg;
            }
        }
    }

    return $results;
}
