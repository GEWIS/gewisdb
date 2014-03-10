<?php
namespace Database;


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
                'database_service_meeting' => 'Database\Service\Meeting',
                'database_form_budget' => 'Database\Form\Budget'
            ),
            'factories' => array(
                'database_form_createmeeting' => function ($sm) {
                    $form = new \Database\Form\CreateMeeting();
                    $form->setHydrator($sm->get('database_hydrator_meeting'));
                    return $form;
                },
                'database_hydrator_meeting' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em'),
                        'Database\Model\Meeting'
                    );
                },
                'database_mapper_meeting' => function ($sm) {
                    return new \Database\Mapper\Meeting(
                        $sm->get('database_doctrine_em')
                    );
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'database_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            )
        );
    }
}
