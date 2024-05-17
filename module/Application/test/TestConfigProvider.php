<?php

declare(strict_types=1);

namespace ApplicationTest;

use Doctrine\DBAL\Driver\PDO\SQLite\Driver as SQLiteDriver;
use Laminas\Stdlib\ArrayUtils;

class TestConfigProvider
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function getConfig(): array
    {
        return include './config/application.config.php';
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function overrideConfig(array $config): array
    {
        $testConfig = [
            'doctrine' => [
                'connection' => [
                    'orm_default' => [
                        'driverClass' => SQLiteDriver::class,
                        'params' => [
                            'user' => 'phpunit',
                            'password' => 'phpunit',
                            'memory' => true,
                            'charset' => 'utf8mb4',
                            'collate' => 'utf8mb4_unicode_ci',
                        ],
                    ],
                    'orm_report' => [
                        'driverClass' => SQLiteDriver::class,
                        'params' => [
                            'user' => 'phpunit',
                            'password' => 'phpunit',
                            'memory' => true,
                            'charset' => 'utf8mb4',
                            'collate' => 'utf8mb4_unicode_ci',
                        ],
                    ],
                ],
            ],
        ];

        return ArrayUtils::merge($config, $testConfig);
    }
}
