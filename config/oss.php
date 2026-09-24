<?php

$ossBaseUrl = trim((string) env('OSS_BASE_URL', ''));

return [
    'access_key_id' => env('OSS_ACCESS_KEY_ID', ''),
    'access_key_secret' => env('OSS_ACCESS_KEY_SECRET', ''),
    'bucket' => env('OSS_BUCKET', ''),
    'endpoint' => env('OSS_ENDPOINT', ''),
    'internal_endpoint' => env('OSS_INTERNAL_ENDPOINT', ''),
    'base_url' => $ossBaseUrl !== '' ? (rtrim($ossBaseUrl, '/') . '/') : '',
    'upload_prefix' => trim((string) env('OSS_UPLOAD_PREFIX', 'uploads'), '/'),
    'use_internal' => filter_var(env('OSS_USE_INTERNAL', false), FILTER_VALIDATE_BOOL),
];
