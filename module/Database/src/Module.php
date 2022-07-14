<?php

namespace Database;

use Database\Form\Abolish as AbolishForm;
use Database\Form\Address as AddressForm;
use Database\Form\AddressExport as AddressExportForm;
use Database\Form\Board\Discharge as BoardDischargeForm;
use Database\Form\Board\Install as BoardInstallForm;
use Database\Form\Board\Release as BoardReleaseForm;
use Database\Form\Budget as BudgetForm;
use Database\Form\CreateMeeting as CreateMeetingForm;
use Database\Form\DeleteAddress as DeleteAddressForm;
use Database\Form\DeleteDecision as DeleteDecisionForm;
use Database\Form\DeleteList as DeleteListForm;
use Database\Form\Destroy as DestroyForm;
use Database\Form\Export as ExportForm;
use Database\Form\Fieldset\Address as AddressFieldset;
use Database\Form\Fieldset\Decision as DecisionFieldset;
use Database\Form\Fieldset\Installation as InstallationFieldset;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use Database\Form\Foundation as FoundationForm;
use Database\Form\Install as InstallForm;
use Database\Form\InstallationFunction as InstallationFunctionForm;
use Database\Form\MailingList as MailingListForm;
use Database\Form\Member as MemberForm;
use Database\Form\MemberEdit as MemberEditForm;
use Database\Form\MemberExpiration as MemberExpirationForm;
use Database\Form\MemberType as MemberTypeForm;
use Database\Form\Other as OtherForm;
use Database\Form\Query as QueryForm;
use Database\Form\QueryExport as QueryExportForm;
use Database\Form\QuerySave as QuerySaveForm;
use Database\Hydrator\Abolish as AbolishHydrator;
use Database\Hydrator\Board\Discharge as BoardDischargeHydrator;
use Database\Hydrator\Board\Install as BoardInstallHydrator;
use Database\Hydrator\Board\Release as BoardReleaseHydrator;
use Database\Hydrator\Budget as BudgetHydrator;
use Database\Hydrator\Destroy as DestroyHydrator;
use Database\Hydrator\Foundation as FoundationHydrator;
use Database\Hydrator\Install as InstallHydrator;
use Database\Hydrator\Other as OtherHydrator;
use Database\Mapper\Event as EventMapper;
use Database\Mapper\Factory\EventFactory as EventMapperFactory;
use Database\Mapper\Factory\InstallationFunctionFactory as InstallationFunctionMapperFactory;
use Database\Mapper\Factory\MailingListFactory as MailingListMapperFactory;
use Database\Mapper\Factory\MeetingFactory as MeetingMapperFactory;
use Database\Mapper\Factory\MemberFactory as MemberMapperFactory;
use Database\Mapper\Factory\OrganFactory as OrganMapperFactory;
use Database\Mapper\Factory\ProspectiveMemberFactory as ProspectiveMemberMapperFactory;
use Database\Mapper\Factory\SavedQueryFactory as SavedQueryMapperFactory;
use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\Meeting as MeetingMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\Organ as OrganMapper;
use Database\Mapper\ProspectiveMember as ProspectiveMemberMapper;
use Database\Mapper\SavedQuery as SavedQueryMapper;
use Database\Model\Address as AddressModel;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use Database\Service\Event as EventService;
use Database\Service\Factory\EventFactory as EventServiceFactory;
use Database\Service\Factory\InstallationFunctionFactory as InstallationFunctionServiceFactory;
use Database\Service\Factory\MailingListFactory as MailingListServiceFactory;
use Database\Service\Factory\MeetingFactory as MeetingServiceFactory;
use Database\Service\Factory\MemberFactory as MemberServiceFactory;
use Database\Service\Factory\QueryFactory as QueryServiceFactory;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Database\Service\MailingList as MailingListService;
use Database\Service\Meeting as MeetingService;
use Database\Service\Member as MemberService;
use Database\Service\Query as QueryService;
use Doctrine\ORM\Events;
use Interop\Container\ContainerInterface;
use Report\Listener\DatabaseDeletionListener;
use Report\Listener\DatabaseUpdateListener;
use stdClass;
use Zend\Hydrator\ObjectProperty;
use Zend\Mvc\MvcEvent;

class Module
{
    /**
     * Bootstrap event.
     *
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent$e)
    {
        $sm = $e->getApplication()->getServiceManager();

        // register event logging
        $em = $sm->get('database_doctrine_em');
        $dem = $em->getEventManager();
        $dem->addEventListener([Events::postPersist], $sm->get(DatabaseUpdateListener::class));
        $dem->addEventListener([Events::postUpdate], $sm->get(DatabaseUpdateListener::class));
        $dem->addEventListener([Events::preRemove], $sm->get(DatabaseDeletionListener::class));
    }

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
    public function getServiceConfig(): array
    {
        return array(
            'invokables' => array(
                AbolishHydrator::class => AbolishHydrator::class,
                BudgetHydrator::class => BudgetHydrator::class,
                DestroyHydrator::class => DestroyHydrator::class,
                FoundationHydrator::class => FoundationHydrator::class,
                InstallHydrator::class => InstallHydrator::class,
                OtherHydrator::class => OtherHydrator::class,
                BoardInstallHydrator::class => BoardInstallHydrator::class,
                BoardDischargeHydrator::class => BoardDischargeHydrator::class,
                BoardReleaseHydrator::class => BoardReleaseHydrator::class,
                AddressExportForm::class => AddressExportForm::class,
                DeleteAddressForm::class => DeleteAddressForm::class,
                DeleteListForm::class => DeleteListForm::class,
                MemberExpirationForm::class => MemberExpirationForm::class,
                QueryForm::class => QueryForm::class,
                QueryExportForm::class => QueryExportForm::class,
                QuerySaveForm::class => QuerySaveForm::class,
            ),
            'factories' => array(
                EventService::class => EventServiceFactory::class,
                InstallationFunctionService::class => InstallationFunctionServiceFactory::class,
                MailingListService::class => MailingListServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                QueryService::class => QueryServiceFactory::class,
                ExportForm::class => function (ContainerInterface $container) {
                    return new ExportForm(
                        $container->get(MeetingMapper::class)
                    );
                },
                AddressForm::class => function (ContainerInterface $container) {
                    $form = new AddressForm();
                    $form->setHydrator($container->get('database_hydrator_address'));
                    return $form;
                },
                MemberForm::class => function (ContainerInterface $container) {
                    $form = new MemberForm(
                        $container->get(AddressFieldset::class),
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('database_hydrator_member'));
                    $form->setLists($container->get(MailingListMapper::class)->findAllOnForm());
                    return $form;
                },
                MemberEditForm::class => function (ContainerInterface $container) {
                    $form = new MemberEditForm();
                    $form->setHydrator($container->get('database_hydrator_member'));
                    return $form;
                },
                MemberTypeForm::class => function (ContainerInterface $container) {
                    $form = new MemberTypeForm();
                    $form->setHydrator($container->get('database_hydrator_member'));
                    return $form;
                },
                CreateMeetingForm::class => function (ContainerInterface $container) {
                    $form = new CreateMeetingForm();
                    $form->setHydrator($container->get('database_hydrator_meeting'));
                    return $form;
                },
                OtherForm::class => function (ContainerInterface $container) {
                    $form = new OtherForm(
                        $container->get(MeetingFieldset::class)
                    );
                    $form->setHydrator($container->get(OtherHydrator::class));
                    return $form;
                },
                BudgetForm::class => function (ContainerInterface $container) {
                    $form = new BudgetForm(
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class)
                    );
                    $form->setHydrator($container->get(BudgetHydrator::class));
                    return $form;
                },
                MailingListForm::class => function (ContainerInterface $container) {
                    $form = new MailingListForm();
                    $form->setHydrator($container->get('database_hydrator_member'));
                    return $form;
                },
                InstallationFunctionForm::class => function (ContainerInterface $container) {
                    $form = new InstallationFunctionForm();
                    $form->setHydrator($container->get('database_hydrator_member'));
                    return $form;
                },
                InstallForm::class => function (ContainerInterface $container) {
                    $form = new InstallForm(
                        $container->get(MeetingFieldset::class),
                        $container->get(InstallationFieldset::class),
                        $container->get('database_form_fieldset_subdecision_discharge'),
                        $container->get('database_form_fieldset_subdecision_foundation')
                    );
                    $form->setHydrator($container->get(InstallHydrator::class));
                    return $form;
                },
                DeleteDecisionForm::class => function (ContainerInterface $container) {
                    $form = new DeleteDecisionForm();
                    $form->setHydrator($container->get(AbolishHydrator::class));
                    return $form;
                },
                AbolishForm::class => function (ContainerInterface $container) {
                    $form = new AbolishForm(
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_foundation')
                    );
                    $form->setHydrator($container->get(AbolishHydrator::class));
                    return $form;
                },
                DestroyForm::class => function (ContainerInterface $container) {
                    $form = new DestroyForm(
                        $container->get(MeetingFieldset::class),
                        $container->get(DecisionFieldset::class)
                    );
                    $form->setHydrator($container->get(DestroyHydrator::class));
                    return $form;
                },
                FoundationForm::class => function (ContainerInterface $container) {
                    $form = new FoundationForm(
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFunctionFieldset::class)
                    );
                    $form->setHydrator($container->get(FoundationHydrator::class));
                    return $form;
                },
                BoardInstallForm::class => function (ContainerInterface $container) {
                    $form = new BoardInstallForm(
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class)
                    );
                    $form->setHydrator($container->get(BoardInstallHydrator::class));
                    return $form;
                },
                BoardReleaseForm::class => function (ContainerInterface $container) {
                    $form = new BoardReleaseForm(
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_board_install')
                    );
                    $form->setHydrator($container->get(BoardReleaseHydrator::class));
                    return $form;
                },
                BoardDischargeForm::class => function (ContainerInterface $container) {
                    $form = new BoardDischargeForm(
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_board_install')
                    );
                    $form->setHydrator($container->get(BoardDischargeHydrator::class));
                    return $form;
                },
                'database_form_fieldset_subdecision_foundation' => function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new FoundationModel());
                    return $fieldset;
                },
                'database_form_fieldset_subdecision_discharge' => function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new InstallationModel());
                    return $fieldset;
                },
                'database_form_fieldset_subdecision_board_install' => function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new BoardInstallationModel());
                    return $fieldset;
                },
                DecisionFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new DecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_decision'));
                    $fieldset->setObject(new DecisionModel());
                    return $fieldset;
                },
                InstallationFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new InstallationFieldset(
                        $container->get(MemberFieldset::class)
                    );
                    $fieldset->setHydrator(new ObjectProperty());
                    $fieldset->setObject(new stdClass());
                    return $fieldset;
                },
                MeetingFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new MeetingFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_meeting'));
                    $fieldset->setObject(new MeetingModel());
                    return $fieldset;
                },
                MemberFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new MemberFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_member'));
                    $fieldset->setObject(new MemberModel());
                    return $fieldset;
                },
                MemberFunctionFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new MemberFunctionFieldset(
                        $container->get(MemberFieldset::class),
                        $container->get(InstallationFunctionService::class),
                        true
                    );
                    $fieldset->setHydrator(new ObjectProperty());
                    $fieldset->setObject(new stdClass());
                    return $fieldset;
                },
                'database_form_fieldset_memberfunction_nomember' => function (ContainerInterface $container) {
                    $fieldset = new MemberFunctionFieldset(
                        $container->get(MemberFieldset::class),
                        $container->get(InstallationFunctionService::class),
                        false
                    );
                    $fieldset->setHydrator(new ObjectProperty());
                    $fieldset->setObject(new stdClass());
                    return $fieldset;
                },
                AddressFieldset::class => function (ContainerInterface $container) {
                    $fs = new AddressFieldset($container->get('translator'));
                    $fs->setHydrator($container->get('database_hydrator_address'));
                    $fs->setObject(new AddressModel());
                    return $fs;
                },
                ///////////////////////////////////////////////////////////////////////////
                'database_hydrator_member' => function (ContainerInterface $container) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $container->get('database_doctrine_em')
                    );
                },
                'database_hydrator_address' => function (ContainerInterface $container) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $container->get('database_doctrine_em')
                    );
                },
                'database_hydrator_meeting' => function (ContainerInterface $container) {
                    // uses 'fixed' DoctrineObject
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $container->get('database_doctrine_em')
                    );
                },
                'database_hydrator_subdecision' => function (ContainerInterface $container) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $container->get('database_doctrine_em')
                    );
                },
                'database_hydrator_decision' => function (ContainerInterface $container) {
                    return new \Application\Doctrine\Hydrator\DoctrineObject(
                        $container->get('database_doctrine_em')
                    );
                },
                EventMapper::class => EventMapperFactory::class,
                InstallationFunctionMapper::class => InstallationFunctionMapperFactory::class,
                MailingListMapper::class => MailingListMapperFactory::class,
                MeetingMapper::class => MeetingMapperFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                OrganMapper::class => OrganMapperFactory::class,
                ProspectiveMemberMapper::class => ProspectiveMemberMapperFactory::class,
                SavedQueryMapper::class => SavedQueryMapperFactory::class,
                'database_mail_transport' => function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Zend\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Zend\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'database_doctrine_em' => function (ContainerInterface $container) {
                    return $container->get('doctrine.entitymanager.orm_default');
                }
            ),
            'shared' => array(
                // every form should get a different meeting fieldset
                MeetingFieldset::class => false,
                MemberFieldset::class => false,
                'database_form_fieldset_subdecision_foundation' => false,
            )
        );
    }
}