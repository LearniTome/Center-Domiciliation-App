<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/env.php';

// La clé API vient de l'environnement (.env / .env.local / variable système)
return [
    'api_key' => env_var('ANTHROPIC_API_KEY', ''),
    'model' => env_var('AI_MODEL', 'claude-sonnet-4-20250514'),
    'max_tokens' => (int) env_var('AI_MAX_TOKENS', '4096'),
    'temperature' => (float) env_var('AI_TEMPERATURE', '0.7'),
    'cache_ttl' => (int) env_var('AI_CACHE_TTL', '3600'), // secondes
];
