<?php

namespace Export;

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
    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'export_service_member' => 'Export\Service\Member',
                'export_service_meeting' => 'Export\Service\Meeting',
                'export_service_organ' => 'Export\Service\Organ',
            ),
            'factories' => array(
                'export_query_meeting' => function (ContainerInterface $container) {
                    $q = new \Export\Query\Meeting();
                    $q->setConnection($container->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_member' => function (ContainerInterface $container) {
                    $q = new \Export\Query\Member();
                    $q->setConnection($container->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_subdecision' => function (ContainerInterface $container) {
                    $q = new \Export\Query\SubDecision();
                    $q->setConnection($container->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_decision' => function (ContainerInterface $container) {
                    $q = new \Export\Query\Decision();
                    $q->setConnection($container->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_organ' => function (ContainerInterface $container) {
                    $q = new \Export\Query\Organ();
                    $q->setConnection($container->get('doctrine.connection.orm_import'));
                    return $q;
                },
            )
        );
    }
}
