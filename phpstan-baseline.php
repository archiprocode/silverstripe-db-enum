#!/usr/bin/env php
<?php

/**
 * PHPStan Baseline Generator
 *
 * This script generates a PHPStan baseline for ignoring existing errors.
 * Run this when you want to capture the current state of PHPStan errors.
 *
 * Usage: php phpstan-baseline.php
 */

$output = [];
$returnCode = 0;

echo "Generating PHPStan baseline...\n";
exec('vendor/bin/phpstan analyse --generate-baseline', $output, $returnCode);

if ($returnCode === 0) {
    echo "✓ PHPStan baseline generated successfully!\n";
    echo "  File: phpstan-baseline.neon\n";
} else {
    echo "✗ Failed to generate PHPStan baseline\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}
