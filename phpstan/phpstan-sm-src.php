<?php

declare(strict_types=1);

/**
 * An empty service manager for the src/ analysis.
 *
 * slam/phpstan-laminas-framework requires a loader, but nothing under src/ touches the Laminas service manager and
 * the real loader requires bootstrap.php, which declares a global ConsoleRunner class and fatals when PHPStan has
 * already loaded it.
 */

require_once __DIR__ . '/../vendor/autoload.php';

return new Laminas\ServiceManager\ServiceManager();
