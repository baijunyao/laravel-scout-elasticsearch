<?php

return [
    'elasticsearch' => [
        'prefix' => env('ELASTICSEARCH_PREFIX', 'laravel_'),
        'host' => env('ELASTICSEARCH_HOST', '127.0.0.1'),
        'port' => env('ELASTICSEARCH_PORT', '9200'),
        'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
        'user' => env('ELASTICSEARCH_USER', null),
        'pass' => env('ELASTICSEARCH_PASS', null),
        'analyzer' => env('ELASTICSEARCH_ANALYZER', 'ik_max_word'),
        'settings' => [],
        'filter' => [
            '+',
            '-',
            '&',
            '|',
            '!',
            '(',
            ')',
            '{',
            '}',
            '[',
            ']',
            '^',
            '\\',
            '"',
            '~',
            '*',
            '?',
            ':'
        ]
    ],
];
