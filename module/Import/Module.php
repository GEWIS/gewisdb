<?php
namespace Import;

class Module
{

    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                )
            )
        );
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'import_service_meeting' => 'Import\Service\Meeting'
            ),
            'factories' => array(
                'doctrine.connection.orm_import' => new \DoctrineORMModule\Service\DBALConnectionFactory('orm_import'),
                'import_database_query' => function ($sm) {
                    $queries = new \Import\Database\Query();
                    $queries->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $queries;
                }
            )
        );
    }
}
