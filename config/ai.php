<?php

declare(strict_types=1);

// Duplique ce fichier en config/ai.local.php et mets ta vraie clé
// config/ai.local.php est ignoré par git
return [
    'api_key' => getenv('ANTHROPIC_API_KEY') ?: '',
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 4096,
    'temperature' => 0.7,
    'cache_ttl' => 3600, // secondes
];
