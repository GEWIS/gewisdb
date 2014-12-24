<?php
namespace Checker;

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
                'checker_service_checker' => 'Checker\Service\Checker',
                'checker_service_organ' => 'Checker\Service\Organ'
            ),
            'factories' => array(
                'checker_mapper_organ' => function ($sm) {
                    return new \Checker\Mapper\Organ(
                        $sm->get('database_doctrine_em')
                    );
                },
            )
        );
    }
}
