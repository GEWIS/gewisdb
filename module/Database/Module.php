<?php

namespace Database;

use Report\Listener\DatabaseDeletionListener;
use Report\Listener\DatabaseUpdateListener;

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
        $em = $sm->get('database_doctrine_em');
        $dem = $em->getEventManager();
        $dem->addEventListener([\Doctrine\ORM\Events::postPersist], new DatabaseUpdateListener($sm));
        $dem->addEventListener([\Doctrine\ORM\Events::postUpdate], new DatabaseUpdateListener($sm));
        $dem->addEventListener([\Doctrine\ORM\Events::preRemove], new DatabaseDeletionListener($sm));
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
                'database_service_query' => 'Database\Service\Query',
                'database_service_installationfunction' => 'Database\Service\InstallationFunction',
                'database_service_mailinglist' => 'Database\Service\MailingList',
                'database_hydrator_abolish' => 'Database\Hydrator\Abolish',
                'database_hydrator_foundation' => 'Database\Hydrator\Foundation',
                'database_hydrator_install' => 'Database\Hydrator\Install',
                'database_hydrator_other' => 'Database\Hydrator\Other',
                'database_hydrator_destroy' => 'Database\Hydrator\Destroy',
                'database_hydrator_budget' => 'Database\Hydrator\Budget',
                'database_hydrator_board_install' => 'Database\Hydrator\Board\Install',
                'database_hydrator_board_discharge' => 'Database\Hydrator\Board\Discharge',
                'database_hydrator_board_release' => 'Database\Hydrator\Board\Release',
                'database_form_query' => 'Database\Form\Query',
                'database_form_queryexport' => 'Database\Form\QueryExport',
                'database_form_deleteaddress' => 'Database\Form\DeleteAddress',
                'database_form_deletelist' => 'Database\Form\DeleteList',
            ),
            'factories' => array(
                'database_form_addressexport' => function ($sm) {
                    return new \Database\Form\AddressExport();
                },
                'database_form_export' => function ($sm) {
                    return new \Database\Form\Export(
                        $sm->get('database_service_meeting')
                    );
                },
                'database_form_querysave' => function ($sm) {
                    $form = new \Database\Form\QuerySave();
                    $form->setHydrator($sm->get('database_hydrator_meeting'));
                    return $form;
                },
                'database_form_address' => function ($sm) {
                    $form = new \Database\Form\Address();
                    $form->setHydrator($sm->get('database_hydrator_address'));
                    return $form;
                },
                'database_form_member' => function ($sm) {
                    $form = new \Database\Form\Member(
                        $sm->get('database_form_fieldset_address'),
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('database_hydrator_member'));
                    $form->setLists($sm->get('database_mapper_mailinglist')->findAllOnForm());
                    return $form;
                },
                'database_form_memberedit' => function ($sm) {
                    $form = new \Database\Form\MemberEdit();
                    $form->setHydrator($sm->get('database_hydrator_member'));
                    return $form;
                },
                'database_form_memberexpiration' => function ($sm) {
                    return new \Database\Form\MemberExpiration();
                },
                'database_form_membertype' => function ($sm) {
                    $form = new \Database\Form\MemberType();
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
                'database_form_mailinglist' => function ($sm) {
                    $form = new \Database\Form\MailingList();
                    $form->setHydrator($sm->get('database_hydrator_member'));
                    return $form;
                },
                'database_form_installationfunction' => function ($sm) {
                    $form = new \Database\Form\InstallationFunction();
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
                'database_form_destroy' => function ($sm) {
                    $form = new \Database\Form\Destroy(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_decision')
                    );
                    $form->setHydrator($sm->get('database_hydrator_destroy'));
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
                'database_form_board_install' => function ($sm) {
                    $form = new \Database\Form\Board\Install(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_member')
                    );
                    $form->setHydrator($sm->get('database_hydrator_board_install'));
                    return $form;
                },
                'database_form_board_release' => function ($sm) {
                    $form = new \Database\Form\Board\Release(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_subdecision_board_install'),
                        $sm->get('database_service_meeting')
                    );
                    $form->setHydrator($sm->get('database_hydrator_board_release'));
                    return $form;
                },
                'database_form_board_discharge' => function ($sm) {
                    $form = new \Database\Form\Board\Discharge(
                        $sm->get('database_form_fieldset_meeting'),
                        $sm->get('database_form_fieldset_subdecision_board_install'),
                        $sm->get('database_service_meeting')
                    );
                    $form->setHydrator($sm->get('database_hydrator_board_discharge'));
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
                'database_form_fieldset_subdecision_board_install' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\SubDecision();
                    $fieldset->setHydrator($sm->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new \Database\Model\SubDecision\Board\Installation());
                    return $fieldset;
                },
                'database_form_fieldset_decision' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\Decision();
                    $fieldset->setHydrator($sm->get('database_hydrator_decision'));
                    $fieldset->setObject(new \Database\Model\Decision());
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
                        $sm->get('database_form_fieldset_member'),
                        $sm->get('database_service_installationfunction'),
                        true
                    );
                    $fieldset->setHydrator(new \Zend\Stdlib\Hydrator\ObjectProperty());
                    $fieldset->setObject(new \stdClass());
                    return $fieldset;
                },
                'database_form_fieldset_memberfunction_nomember' => function ($sm) {
                    $fieldset = new \Database\Form\Fieldset\MemberFunction(
                        $sm->get('database_form_fieldset_member'),
                        $sm->get('database_service_installationfunction'),
                        false
                    );
                    $fieldset->setHydrator(new \Zend\Stdlib\Hydrator\ObjectProperty());
                    $fieldset->setObject(new \stdClass());
                    return $fieldset;
                },
                'database_form_fieldset_address' => function ($sm) {
                    $fs = new \Database\Form\Fieldset\Address($sm->get('translator'));
                    $fs->setHydrator($sm->get('database_hydrator_address'));
                    $fs->setObject(new \Database\Model\Address());
                    return $fs;
                },
                'database_hydrator_member' => function ($sm) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_address' => function ($sm) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_meeting' => function ($sm) {
                    // uses 'fixed' DoctrineObject
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_subdecision' => function ($sm) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_hydrator_decision' => function ($sm) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
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
                'database_mapper_prospective_member' => function ($sm) {
                    return new \Database\Mapper\ProspectiveMember(
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
                'database_mapper_mailinglist' => function ($sm) {
                    return new \Database\Mapper\MailingList(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_mapper_savedquery' => function ($sm) {
                    return new \Database\Mapper\SavedQuery(
                        $sm->get('database_doctrine_em')
                    );
                },
                'database_mail_transport' => function ($sm) {
                    $config = $sm->get('config');
                    $config = $config['email'];
                    $class = '\Zend\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Zend\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
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
