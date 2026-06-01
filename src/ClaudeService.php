<?php

declare(strict_types=1);

class ClaudeService
{
    private static ?array $config = null;
    private static ?array $cache = null;

    private static function config(): array
    {
        if (self::$config === null) {
            $cfg = require __DIR__ . '/../config/ai.php';
            $local = __DIR__ . '/../config/ai.local.php';
            if (file_exists($local)) {
                $localCfg = require $local;
                $cfg = array_merge($cfg, $localCfg);
            }
            self::$config = $cfg;
        }
        return self::$config;
    }

    public static function isAvailable(): bool
    {
        return self::config()['api_key'] !== '';
    }

    public static function ask(string $prompt, string $system = '', int $maxTokens = 0): ?string
    {
        $cfg = self::config();
        if ($cfg['api_key'] === '') {
            return null;
        }

        $cacheKey = 'claude_' . md5($prompt . $system);
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $messages = [];
        if ($system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $body = json_encode([
            'model' => $cfg['model'],
            'max_tokens' => $maxTokens > 0 ? $maxTokens : $cfg['max_tokens'],
            'temperature' => $cfg['temperature'],
            'messages' => $messages,
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $cfg['api_key'],
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            error_log('ClaudeService cURL error: ' . $error);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('ClaudeService HTTP ' . $httpCode . ': ' . $response);
            return null;
        }

        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? null;

        if ($text !== null) {
            self::setCache($cacheKey, $text);
        }

        return $text;
    }

    public static function autoFill(array $data): ?array
    {
        $prompt = "Tu es un assistant expert en creation d'entreprise au Maroc. "
            . "Complete les informations suivantes avec des valeurs realistes et coherentes.\n\n"
            . "Donnees fournies :\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Retourne UNIQUEMENT un JSON avec les champs supplementaires suggestes. "
            . "Exemple: {\"adresse\": \"...\", \"capital\": 100000, \"objet_social\": \"...\"}";

        $response = self::ask($prompt, 'Tu es un assistant juridique specialise dans la creation de societes au Maroc.', 1024);
        if ($response === null) return null;

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function analyzeTemplates(array $variables): ?array
    {
        $list = array_map(fn($v) => "- {$v['variable']} ({$v['coverage']}, {$v['occurrences']} occ.)", $variables);
        $prompt = "Analyse ces variables de templates .docx et suggere des ameliorations :\n\n"
            . implode("\n", $list) . "\n\n"
            . "Retourne UNIQUEMENT un JSON: {\"suggestions\": [{\"variable\": \"...\", \"suggestion\": \"...\", \"action\": \"rename|delete|keep\"}]}";

        $response = self::ask($prompt, 'Tu es un expert en templates de documents juridiques.', 2048);
        if ($response === null) return null;

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function generateClause(array $dossierData, string $type): ?string
    {
        $typeLabels = [
            'objet_social' => "l'objet social",
            'mention_legale' => 'les mentions legales',
            'clause_siege' => "la clause de siege social",
        ];
        $label = $typeLabels[$type] ?? $type;

        $prompt = "Genere {$label} pour une societe avec les informations suivantes :\n\n"
            . json_encode($dossierData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Redige un texte juridique formel en francais.";
        return self::ask($prompt, 'Tu es un redacteur juridique specialise en droit des societes marocain.', 2048);
    }

    public static function validateDossier(array $dossierData): ?array
    {
        $prompt = "Verifie la coherence et la completude de ce dossier de creation d'entreprise :\n\n"
            . json_encode($dossierData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "Retourne UNIQUEMENT un JSON: {\"valide\": true/false, \"points\": [{\"type\": \"warning|error|info\", \"message\": \"...\"}]}";

        $response = self::ask($prompt, 'Tu es un expert juridique qui verifie les dossiers de creation de societes.', 2048);
        if ($response === null) return null;

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function chat(array $messages): ?string
    {
        $prompt = '';
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';
            $prompt .= "[{$role}]\n{$content}\n\n";
        }
        return self::ask($prompt, 'Tu es un assistant specialise dans la domiciliation et la creation d\'entreprises au Maroc. Reponds en francais de maniere concise et precise.');
    }

    private static function getCache(string $key): ?string
    {
        self::initCache();
        $entry = self::$cache[$key] ?? null;
        if ($entry !== null && $entry['expires'] > time()) {
            return $entry['value'];
        }
        return null;
    }

    private static function setCache(string $key, string $value): void
    {
        self::initCache();
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + self::config()['cache_ttl'],
        ];
        $_SESSION['_claude_cache'] = self::$cache;
    }

    private static function initCache(): void
    {
        if (self::$cache === null) {
            self::$cache = $_SESSION['_claude_cache'] ?? [];
        }
    }
}
