<?php

declare(strict_types=1);

/**
 * Resolve Symfony preload script across deployment layouts.
 *
 * - Docker Compose bind mount: /app/config/preload.php
 * - Built image repo layout:   /app/app/config/preload.php
 * - Kubernetes copied app:     /var/www/html/config/preload.php
 */
$preloadCandidates = [
    '/app/config/preload.php',
    '/app/app/config/preload.php',
    '/var/www/html/config/preload.php',
];

foreach ($preloadCandidates as $candidate) {
    if (!is_file($candidate)) {
        continue;
    }

    require $candidate;
    return;
}
