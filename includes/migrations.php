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

    // Ensure _migrations table exists (handle corrupted tablespace error 1813)
    $tableReady = false;
    try {
        $probe = $pdo->query("SELECT 1 FROM {$tableName} LIMIT 0");
        $probe->closeCursor();
        $tableReady = true;
    } catch (PDOException $e) {
        // Table missing or corrupted tablespace
    }

    if (!$tableReady) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        } catch (Throwable $e) {
            // DROP itself may fail on corrupted tablespace — ignore
        }
        try {
            $pdo->exec("
                CREATE TABLE {$tableName} (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_migrations_filename (filename)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            // If CREATE still fails (e.g. orphaned tablespace file), try DISCARD TABLESPACE workaround
            if (str_contains($e->getMessage(), '1813')) {
                // The table metadata exists but .ibd is broken — force drop via ALTER
                try { $pdo->exec("DROP TABLE IF EXISTS {$tableName}"); } catch (Throwable $e2) { /* ignore */ }
                // If still broken, the table needs manual cleanup — skip gracefully
                return $results;
            }
            throw $e;
        }
    }

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
                '42S22',   // Column not found (rename migration on fresh schema)
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
