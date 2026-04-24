<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
        'https://grupopedregal.vercel.app',
        'https://www.grupopedregal.es',
        'http://www.grupopedregal.es',
        'https://grupopedregal.es',
        'http://grupopedregal.es',
    ],
    'allowed_origins_patterns' => ['/https:\/\/grupopedregal.*\.vercel\.app/'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
