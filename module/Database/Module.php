<?php
namespace Database;


class Module
{

    /**
     * Bootstrap event.
     */
    public function onBootstrap($e)
    {
        $sm = $e->getApplication()->getServiceManager();

        // register event logging
        $sm->get('database_service_event')->register();
    }

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
                'database_service_event' => 'Database\Service\Event',
                'database_service_installationfunction' => 'Database\Service\InstallationFunction',
                'database_hydrator_budget' => 'Database\Hydrator\Budget',
                'database_hydrator_abolish' => 'Database\Hydrator\Abolish',
                'database_hydrator_foundation' => 'Database\Hydrator\Foundation',
                'database_hydrator_install' => 'Database\Hydrator\Install',
                'database_hydrator_other' => 'Database\Hydrator\Other',
            ),
            'factories' => array(
                'database_form_export' => function ($sm) {
                    return new \Database\Form\Export(
                        $sm->get('database_service_meeting')
                    );
                },
                'database_form_member' => function ($sm) {
                    $form = new \Database\Form\Member(
                        $sm->get('database_form_fieldset_address')
                    );
                    $form->setHydrator($sm->get('database_hydrator_member'));
                    return $form;
                },
                'database_form_createmeeting' => function ($sm) {
                    $form = new \Database\Form\CreateMeeting();
                    $form->setHydrator($sm->get('database_hydrator_meeting'));
                    return $form;
                },
                'database_form_other' => function ($sm) {
                    $form = new \Database\Form\Other(
                        $sm->get('database_form_fieldset_meeting')
                    );
                    $form->setHydrator($sm->get('database_hydrator_other'));
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
                'database_form_installationfunction' => function ($sm) {
                    $form = new \Database\Form\InstallationFunction(
                    );
                    $form->setHydrator($sm->get('database_hydrator_member'));
                    return $form;
                },
                'database_form_install' => function ($sm) {
                    $form = new \Database\Form\Install(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_installation'),
                        $sm->get('database_form_fieldset_subdecision_discharge'),
                        $sm->get('database_form_fieldset_subdecision_foundation')
                    );
                    $form->setHydrator($sm->get('database_hydrator_install'));
                    return $form;
                },
                'database_form_deletedecision' => function ($sm) {
                    $form = new \Database\Form\DeleteDecision();
                    $form->setHydrator($sm->get('database_hydrator_abolish'));
                    return $form;
                },
                'database_form_abolish' => function ($sm) {
                    $form = new \Database\Form\Abolish(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_subdecision_foundation')
                    );
                    $form->setHydrator($sm->get('database_hydrator_abolish'));
                    return $form;
                },
                'database_form_foundation' => function ($sm) {
                    $form = new \Database\Form\Foundation(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_memberfunction')
                    );
                    $form->setHydrator($sm->get('database_hydrator_foundation'));
                    return $form;
                },
                'database_form_fieldset_subdecision_foundation' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\SubDecision();
                    $fieldset->setHydrator($sm->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new \Database\Model\SubDecision\Foundation());
                    return $fieldset;
                },
                'database_form_fieldset_subdecision_discharge' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\SubDecision();
                    $fieldset->setHydrator($sm->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new \Database\Model\SubDecision\Installation());
                    return $fieldset;
                },
                'database_form_fieldset_installation' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\Installation(
                        $sm->get('database_form_fieldset_member')
                    );
                    $fieldset->setHydrator(new \Zend\Stdlib\Hydrator\ObjectProperty());
                    $fieldset->setObject(new \stdClass());
                    return $fieldset;
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
                'database_form_fieldset_memberfunction' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\MemberFunction(
                        $sm->get('database_form_fieldset_member')
                    );
                    $fieldset->setHydrator(new \Zend\Stdlib\Hydrator\ObjectProperty());
                    $fieldset->setObject(new \stdClass());
                    return $fieldset;
                },
                'database_form_fieldset_address' => function ($sm) {
                    $fs = new \Database\Form\Fieldset\Address();
                    $fs->setHydrator($sm->get('database_hydrator_address'));
                    $fs->setObject(new \Database\Model\Address());
                    return $fs;
                },
                'database_hydrator_member' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_address' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_meeting' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_subdecision' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_mapper_organ' => function ($sm) {
                    return new \Database\Mapper\Organ(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_mapper_event' => function ($sm) {
                    return new \Database\Mapper\Event(
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
                'database_mapper_installationfunction' => function ($sm) {
                    return new \Database\Mapper\InstallationFunction(
                        $sm->get('database_doctrine_em')
                    );
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'database_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ),
            'shared' => array(
                // every form should get a different meeting fieldset
                'database_form_fieldset_meeting' => false,
                'database_form_fieldset_member' => false,
                'database_form_fieldset_subdecision_foundation' => false,
            )
        );
    }
}
