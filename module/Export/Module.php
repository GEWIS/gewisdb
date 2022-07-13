<?php

namespace Export;

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
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
                'export_service_member' => 'Export\Service\Member',
                'export_service_meeting' => 'Export\Service\Meeting',
                'export_service_organ' => 'Export\Service\Organ',
            ),
            'factories' => array(
                'export_query_meeting' => function ($sm) {
                    $q = new \Export\Query\Meeting();
                    $q->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_member' => function ($sm) {
                    $q = new \Export\Query\Member();
                    $q->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_subdecision' => function ($sm) {
                    $q = new \Export\Query\SubDecision();
                    $q->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_decision' => function ($sm) {
                    $q = new \Export\Query\Decision();
                    $q->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $q;
                },
                'export_query_organ' => function ($sm) {
                    $q = new \Export\Query\Organ();
                    $q->setConnection($sm->get('doctrine.connection.orm_import'));
                    return $q;
                },
            )
        );
    }
}
