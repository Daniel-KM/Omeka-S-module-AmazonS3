<?php declare(strict_types=1);

/**
 * Bootstrap file for module tests.
 *
 * Use Common module Bootstrap helper for test setup.
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__, 3) . '/modules/Common/tests/Bootstrap.php';

\CommonTest\Bootstrap::bootstrap(
    [
        'Common',
        'AmazonS3',
    ],
    'AmazonS3Test',
    __DIR__ . '/AmazonS3Test'
);

// Silence third-party deprecations that would otherwise be printed during
// tests and mark them risky under beStrictAboutOutputDuringTests.
error_reporting(E_ALL & ~E_DEPRECATED);
