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
                'database_service_member' => 'Database\Service\Member',
            ),
            'factories' => array(
                'database_form_createmeeting' => function ($sm) {
                    $form = new \Database\Form\CreateMeeting();
                    $form->setHydrator($sm->get('database_hydrator_meeting'));
                    return $form;
                },
                'database_form_budget' => function ($sm) {
                    $form = new \Database\Form\Budget(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_member')
                    );
                    $form->setHydrator($sm->get('database_hydrator_budget'));
                    return $form;
                },
                'database_form_install' => function ($sm) {
                    $form = new \Database\Form\Install(
                        $sm->get('database_form_fieldset_meeting')
                    );
                    return $form;
                },
                'database_form_abolish' => function ($sm) {
                    $form = new \Database\Form\Abolish(
                        $sm->get('database_form_fieldset_meeting')
                    );
                    return $form;
                },
                'database_form_foundation' => function ($sm) {
                    $form = new \Database\Form\Foundation(
                        $sm->get('database_form_fieldset_meeting')
                    );
                    return $form;
                },
                'database_form_fieldset_meeting' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\Meeting();
                    $fieldset->setHydrator($sm->get('database_hydrator_meeting'));
                    $fieldset->setObject(new \Database\Model\Meeting());
                    return $fieldset;
                },
                'database_form_fieldset_member' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\Member();
                    $fieldset->setHydrator($sm->get('database_hydrator_member'));
                    $fieldset->setObject(new \Database\Model\Member());
                    return $fieldset;
                },
                'database_hydrator_budget' => function ($sm) {
                    $hydrator = new \Database\Hydrator\Budget();
                    $hydrator->setMeetingHydrator($sm->get('database_hydrator_meeting'));
                    return $hydrator;
                },
                'database_hydrator_member' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_meeting' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_mapper_member' => function ($sm) {
                    return new \Database\Mapper\Member(
                        $sm->get('database_doctrine_em')
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
