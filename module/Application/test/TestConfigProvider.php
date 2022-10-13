<?php

namespace ApplicationTest;

class TestConfigProvider
{
    public static function getConfig(): array
    {
        return include './config/test.config.php';
    }
}
