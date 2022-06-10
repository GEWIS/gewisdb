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
                'checker_service_organ' => 'Checker\Service\Organ',
                'checker_service_installation' => 'Checker\Service\Installation',
                'checker_service_budget' => 'Checker\Service\Budget',
                'checker_service_meeting' => 'Checker\Service\Meeting',
                'checker_service_member' => 'Checker\Service\Member'
            ),
            'factories' => array(
                'checker_mapper_organ' => function ($sm) {
                    return new \Checker\Mapper\Organ(
                        $sm->get('database_doctrine_em')
                    );
                },
                'checker_mapper_installation' => function ($sm) {
                    return new \Checker\Mapper\Installation(
                        $sm->get('database_doctrine_em')
                    );
                },
                'checker_mapper_budget' => function ($sm) {
                    return new \Checker\Mapper\Budget(
                        $sm->get('database_doctrine_em')
                    );
                },
                'checker_mapper_member' => function ($sm) {
                    return new \Checker\Mapper\Member(
                        $sm->get('database_doctrine_em')
                    );
                },
                'checker_mail_transport' => function ($sm) {
                    $config = $sm->get('config');
                    $config = $config['email'];
                    $class = '\Zend\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Zend\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                }
            )
        );
    }
}
