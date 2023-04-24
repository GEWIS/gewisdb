<?php

declare(strict_types=1);

namespace ApplicationTest;

class TestConfigProvider
{
    public static function getConfig(): array
    {
        return include './config/test.config.php';
    }
}
