<?php

namespace Import;

use DoctrineORMModule\Service\DBALConnectionFactory;
use Import\Database\Query;
use Interop\Container\ContainerInterface;

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return array(
            'invokables' => array(
                'import_service_meeting' => 'Import\Service\Meeting',
                'import_service_member' => 'Import\Service\Member'
            ),
            'factories' => array(
                'doctrine.connection.orm_import' => new DBALConnectionFactory('orm_import'),
                'import_database_query' => function (ContainerInterface $container) {
                    $queries = new Query();
                    $queries->setConnection($container->get('doctrine.connection.orm_import'));
                    return $queries;
                }
            )
        );
    }
}
