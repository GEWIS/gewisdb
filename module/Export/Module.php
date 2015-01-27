<?php
namespace Export;

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
                'export_service_member' => 'Export\Service\Member'
            ),
            'factories' => array(
                'export_query_member' => function ($sm) {
                    $q = new \Export\Query\Member();
                    $q->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $q;
                }
            )
        );
    }
}
