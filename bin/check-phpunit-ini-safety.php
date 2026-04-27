#!/usr/bin/env php
<?php

/**
 * Guard against GHSA-qrr6-mg7r-m243: argument injection via newline in PHPUnit <ini> values.
 * Mirrors the validation that phpunit/phpunit 12.5.22 / 13.1.6 added in JobRunner::settingsToParameters().
 */

$root = dirname(__DIR__);

$patterns = [
    $root . '/phpunit.xml',
    $root . '/phpunit.xml.dist',
    $root . '/tests/phpunit.xml',
    $root . '/tests/phpunit.xml.dist',
];

$files = array_filter($patterns, 'file_exists');

if (empty($files)) {
    fwrite(STDERR, "[SKIP] No phpunit.xml files found — nothing to check.\n");
    exit(0);
}

$rc = 0;

foreach ($files as $file) {
    $xml = new DOMDocument();
    if (!$xml->load($file)) {
        fwrite(STDERR, "[ERROR] Could not parse {$file}\n");
        $rc = 1;
        continue;
    }

    foreach ($xml->getElementsByTagName('ini') as $node) {
        $name = $node->getAttribute('name');
        $value = $node->getAttribute('value');

        if (preg_match('/[\r\n]/', $value)) {
            fwrite(STDERR, "[FAIL] {$file}: <ini name='{$name}'> value contains CR/LF — "
                . "rejected per GHSA-qrr6-mg7r-m243\n");
            $rc = 1;
        }
    }
}

if ($rc === 0) {
    echo "[OK] All phpunit.xml <ini> values are safe.\n";
}

exit($rc);
