<?php

declare(strict_types=1);

namespace Database;

use Database\Form\{
    Abolish as AbolishForm,
    Address as AddressForm,
    Budget as BudgetForm,
    CreateMeeting as CreateMeetingForm,
    DeleteAddress as DeleteAddressForm,
    DeleteDecision as DeleteDecisionForm,
    DeleteList as DeleteListForm,
    Destroy as DestroyForm,
    Export as ExportForm,
    Foundation as FoundationForm,
    Install as InstallForm,
    InstallationFunction as InstallationFunctionForm,
    Key\Grant as KeyGrantForm,
    Key\Withdraw as KeyWithdrawForm,
    MailingList as MailingListForm,
    Member as MemberForm,
    MemberApprove as MemberApproveForm,
    MemberEdit as MemberEditForm,
    MemberExpiration as MemberExpirationForm,
    MemberType as MemberTypeForm,
    Other as OtherForm,
    Query as QueryForm,
    QueryExport as QueryExportForm,
    QuerySave as QuerySaveForm};
use Database\Command\{
    DeleteExpiredMembersCommand,
    GenerateAuthenticationKeysCommand,
};
use Database\Command\{
    Factory\DeleteExpiredMembersCommandFactory,
    Factory\GenerateAuthenticationKeysCommandFactory,
};
use Database\Form\Board\{
    Discharge as BoardDischargeForm,
    Install as BoardInstallForm,
    Release as BoardReleaseForm,
};
use Database\Form\Fieldset\{
    Address as AddressFieldset,
    Decision as DecisionFieldset,
    Granting as GrantingFieldset,
    Installation as InstallationFieldset,
    Meeting as MeetingFieldset,
    Member as MemberFieldset,
    MemberFunction as MemberFunctionFieldset,
    SubDecision as SubDecisionFieldset};
use Database\Hydrator\{
    Abolish as AbolishHydrator,
    Budget as BudgetHydrator,
    Destroy as DestroyHydrator,
    Foundation as FoundationHydrator,
    Install as InstallHydrator,
    Other as OtherHydrator,
};
use Database\Hydrator\Board\{
    Discharge as BoardDischargeHydrator,
    Install as BoardInstallHydrator,
    Release as BoardReleaseHydrator,
};
use Database\Hydrator\Key\{
    Grant as KeyGrantHydrator,
    Withdraw as KeyWithdrawHydrator,
};
use Database\Hydrator\Strategy\{
    AddressHydratorStrategy,
    MeetingHydratorStrategy,
    PostalRegionHydratorStrategy,
};
use Database\Mapper\Factory\{
    InstallationFunctionFactory as InstallationFunctionMapperFactory,
    MailingListFactory as MailingListMapperFactory,
    MeetingFactory as MeetingMapperFactory,
    MemberFactory as MemberMapperFactory,
    MemberUpdateFactory as MemberUpdateMapperFactory,
    OrganFactory as OrganMapperFactory,
    ProspectiveMemberFactory as ProspectiveMemberMapperFactory,
    SavedQueryFactory as SavedQueryMapperFactory,
};
use Database\Mapper\{
    InstallationFunction as InstallationFunctionMapper,
    MailingList as MailingListMapper,
    Meeting as MeetingMapper,
    Member as MemberMapper,
    MemberUpdate as MemberUpdateMapper,
    Organ as OrganMapper,
    ProspectiveMember as ProspectiveMemberMapper,
    SavedQuery as SavedQueryMapper,
};
use Database\Model\{
    Address as AddressModel,
    Decision as DecisionModel,
    Meeting as MeetingModel,
    Member as MemberModel,
};
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\{
    Foundation as FoundationModel,
    Installation as InstallationModel,
    Key\Granting as KeyGrantingModel
};
use Database\Service\Factory\{
    ApiFactory as ApiServiceFactory,
    InstallationFunctionFactory as InstallationFunctionServiceFactory,
    MailingListFactory as MailingListServiceFactory,
    MeetingFactory as MeetingServiceFactory,
    MemberFactory as MemberServiceFactory,
    QueryFactory as QueryServiceFactory,
};
use Database\Service\{
    Api as ApiService,
    InstallationFunction as InstallationFunctionService,
    MailingList as MailingListService,
    Meeting as MeetingService,
    Member as MemberService,
    Query as QueryService,
};
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Psr\Container\ContainerInterface;
use stdClass;

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
    public function getServiceConfig(): array
    {
        return [
            'invokables' => [
                AbolishHydrator::class => AbolishHydrator::class,
                BudgetHydrator::class => BudgetHydrator::class,
                DestroyHydrator::class => DestroyHydrator::class,
                FoundationHydrator::class => FoundationHydrator::class,
                InstallHydrator::class => InstallHydrator::class,
                OtherHydrator::class => OtherHydrator::class,
                BoardInstallHydrator::class => BoardInstallHydrator::class,
                BoardDischargeHydrator::class => BoardDischargeHydrator::class,
                BoardReleaseHydrator::class => BoardReleaseHydrator::class,
                KeyGrantHydrator::class => KeyGrantHydrator::class,
                KeyWithdrawHydrator::class => KeyWithdrawHydrator::class,
            ],
            'factories' => [
                DeleteExpiredMembersCommand::class => DeleteExpiredMembersCommandFactory::class,
                GenerateAuthenticationKeysCommand::class => GenerateAuthenticationKeysCommandFactory::class,
                ApiService::class => ApiServiceFactory::class,
                InstallationFunctionService::class => InstallationFunctionServiceFactory::class,
                MailingListService::class => MailingListServiceFactory::class,
                MeetingService::class => MeetingServiceFactory::class,
                MemberService::class => MemberServiceFactory::class,
                QueryService::class => QueryServiceFactory::class,
                ExportForm::class => function (ContainerInterface $container) {
                    return new ExportForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingMapper::class),
                    );
                },
                AddressForm::class => function (ContainerInterface $container) {
                    $form = new AddressForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_address'));
                    return $form;
                },
                DeleteAddressForm::class => function (ContainerInterface $container) {
                    return new DeleteAddressForm($container->get(MvcTranslator::class));
                },
                MemberForm::class => function (ContainerInterface $container) {
                    $form = new MemberForm(
                        $container->get(AddressFieldset::class),
                        $container->get(MvcTranslator::class),
                    );
                    $form->setHydrator($container->get('database_hydrator_default'));
                    $form->setLists($container->get(MailingListMapper::class)->findAllOnForm());
                    return $form;
                },
                MemberEditForm::class => function (ContainerInterface $container) {
                    $form = new MemberEditForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));
                    return $form;
                },
                MemberApproveForm::class => function (ContainerInterface $container) {
                    $form = new MemberApproveForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));
                    return $form;
                },
                MemberTypeForm::class => function (ContainerInterface $container) {
                    $form = new MemberTypeForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));
                    return $form;
                },
                MemberExpirationForm::class => function (ContainerInterface $container) {
                    return new MemberExpirationForm($container->get(MvcTranslator::class));
                },
                CreateMeetingForm::class => function (ContainerInterface $container) {
                    $form = new CreateMeetingForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_meeting'));
                    return $form;
                },
                OtherForm::class => function (ContainerInterface $container) {
                    $form = new OtherForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                    );
                    $form->setHydrator($container->get(OtherHydrator::class));
                    return $form;
                },
                BudgetForm::class => function (ContainerInterface $container) {
                    $form = new BudgetForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class),
                    );
                    $form->setHydrator($container->get(BudgetHydrator::class));
                    return $form;
                },
                MailingListForm::class => function (ContainerInterface $container) {
                    $form = new MailingListForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));
                    return $form;
                },
                DeleteListForm::class => function (ContainerInterface $container) {
                    return new DeleteListForm($container->get(MvcTranslator::class));
                },
                InstallationFunctionForm::class => function (ContainerInterface $container) {
                    $form = new InstallationFunctionForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));
                    return $form;
                },
                InstallForm::class => function (ContainerInterface $container) {
                    $form = new InstallForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(InstallationFieldset::class),
                        $container->get('database_form_fieldset_subdecision_discharge'),
                        $container->get('database_form_fieldset_subdecision_foundation'),
                    );
                    $form->setHydrator($container->get(InstallHydrator::class));
                    return $form;
                },
                DeleteDecisionForm::class => function (ContainerInterface $container) {
                    $form = new DeleteDecisionForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get(AbolishHydrator::class));
                    return $form;
                },
                AbolishForm::class => function (ContainerInterface $container) {
                    $form = new AbolishForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_foundation'),
                    );
                    $form->setHydrator($container->get(AbolishHydrator::class));
                    return $form;
                },
                DestroyForm::class => function (ContainerInterface $container) {
                    $form = new DestroyForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(DecisionFieldset::class),
                    );
                    $form->setHydrator($container->get(DestroyHydrator::class));
                    return $form;
                },
                FoundationForm::class => function (ContainerInterface $container) {
                    $form = new FoundationForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFunctionFieldset::class),
                    );
                    $form->setHydrator($container->get(FoundationHydrator::class));
                    return $form;
                },
                BoardInstallForm::class => function (ContainerInterface $container) {
                    $form = new BoardInstallForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class),
                    );
                    $form->setHydrator($container->get(BoardInstallHydrator::class));
                    return $form;
                },
                BoardReleaseForm::class => function (ContainerInterface $container) {
                    $form = new BoardReleaseForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_board_install'),
                    );
                    $form->setHydrator($container->get(BoardReleaseHydrator::class));
                    return $form;
                },
                BoardDischargeForm::class => function (ContainerInterface $container) {
                    $form = new BoardDischargeForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_board_install'),
                    );
                    $form->setHydrator($container->get(BoardDischargeHydrator::class));
                    return $form;
                },
                KeyGrantForm::class => function (ContainerInterface $container) {
                    $form = new KeyGrantForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class),
                    );
                    $form->setHydrator($container->get(KeyGrantHydrator::class));
                    return $form;
                },
                KeyWithdrawForm::class => function (ContainerInterface $container) {
                    $form = new KeyWithdrawForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_key_grant'),
                        $container->get(GrantingFieldset::class),
                    );
                    $form->setHydrator($container->get(KeyWithdrawHydrator::class));
                    return $form;
                },
                QueryForm::class => function (ContainerInterface $container) {
                    return new QueryForm($container->get(MvcTranslator::class));
                },
                QueryExportForm::class => function (ContainerInterface $container) {
                    return new QueryExportForm($container->get(MvcTranslator::class));
                },
                QuerySaveForm::class => function (ContainerInterface $container) {
                    $form = new QuerySaveForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

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
                'database_form_fieldset_subdecision_key_grant' => function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new KeyGrantingModel());
                    return $fieldset;
                },
                DecisionFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new DecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_decision'));
                    $fieldset->setObject(new DecisionModel());
                    return $fieldset;
                },
                GrantingFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new GrantingFieldset(
                        $container->get(MemberFieldset::class),
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());
                    return $fieldset;
                },
                InstallationFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new InstallationFieldset(
                        $container->get(MemberFieldset::class),
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
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
                    $fieldset->setHydrator($container->get('database_hydrator_default'));
                    $fieldset->setObject(new MemberModel());
                    return $fieldset;
                },
                MemberFunctionFieldset::class => function (ContainerInterface $container) {
                    $fieldset = new MemberFunctionFieldset(
                        $container->get(MemberFieldset::class),
                        $container->get(InstallationFunctionService::class),
                        true,
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());
                    return $fieldset;
                },
                'database_form_fieldset_memberfunction_nomember' => function (ContainerInterface $container) {
                    $fieldset = new MemberFunctionFieldset(
                        $container->get(MemberFieldset::class),
                        $container->get(InstallationFunctionService::class),
                        false,
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());
                    return $fieldset;
                },
                AddressFieldset::class => function (ContainerInterface $container) {
                    $fs = new AddressFieldset($container->get(MvcTranslator::class));
                    $fs->setHydrator($container->get('database_hydrator_address'));
                    $fs->setObject(new AddressModel());
                    return $fs;
                },
                ///////////////////////////////////////////////////////////////////////////
                'database_hydrator_default' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                },
                'database_hydrator_address' => function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('type', new AddressHydratorStrategy());
                    $hydrator->addStrategy('country', new PostalRegionHydratorStrategy());

                    return $hydrator;
                },
                'database_hydrator_meeting' => function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('type', new MeetingHydratorStrategy());

                    return $hydrator;
                },
                'database_hydrator_subdecision' => function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('meeting_type', new MeetingHydratorStrategy());

                    return $hydrator;
                },
                'database_hydrator_decision' => function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('meeting_type', new MeetingHydratorStrategy());

                    return $hydrator;
                },
                InstallationFunctionMapper::class => InstallationFunctionMapperFactory::class,
                MailingListMapper::class => MailingListMapperFactory::class,
                MeetingMapper::class => MeetingMapperFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                MemberUpdateMapper::class => MemberUpdateMapperFactory::class,
                OrganMapper::class => OrganMapperFactory::class,
                ProspectiveMemberMapper::class => ProspectiveMemberMapperFactory::class,
                SavedQueryMapper::class => SavedQueryMapperFactory::class,
                'database_mail_transport' => function (ContainerInterface $container) {
                    $config = $container->get('config');
                    $config = $config['email'];
                    $class = '\Laminas\Mail\Transport\\' . $config['transport'];
                    $optionsClass = '\Laminas\Mail\Transport\\' . $config['transport'] . 'Options';
                    $transport = new $class();
                    $transport->setOptions(new $optionsClass($config['options']));
                    return $transport;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'database_doctrine_em' => function (ContainerInterface $container) {
                    return $container->get('doctrine.entitymanager.orm_default');
                },
            ],
            'shared' => [
                // every form should get a different meeting fieldset
                MeetingFieldset::class => false,
                MemberFieldset::class => false,
                'database_form_fieldset_subdecision_foundation' => false,
            ],
        ];
    }
}
