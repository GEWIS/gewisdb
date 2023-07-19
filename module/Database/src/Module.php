<?php

declare(strict_types=1);

namespace Database;

use Database\Command\DeleteExpiredMembersCommand;
use Database\Command\Factory\DeleteExpiredMembersCommandFactory;
use Database\Command\Factory\GenerateAuthenticationKeysCommandFactory;
use Database\Command\GenerateAuthenticationKeysCommand;
use Database\Form\Abolish as AbolishForm;
use Database\Form\Address as AddressForm;
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
use Database\Form\Fieldset\Granting as GrantingFieldset;
use Database\Form\Fieldset\Installation as InstallationFieldset;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use Database\Form\Foundation as FoundationForm;
use Database\Form\Install as InstallForm;
use Database\Form\InstallationFunction as InstallationFunctionForm;
use Database\Form\Key\Grant as KeyGrantForm;
use Database\Form\Key\Withdraw as KeyWithdrawForm;
use Database\Form\MailingList as MailingListForm;
use Database\Form\Member as MemberForm;
use Database\Form\MemberApprove as MemberApproveForm;
use Database\Form\MemberEdit as MemberEditForm;
use Database\Form\MemberExpiration as MemberExpirationForm;
use Database\Form\MemberRenewal as MemberRenewalForm;
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
use Database\Hydrator\Key\Grant as KeyGrantHydrator;
use Database\Hydrator\Key\Withdraw as KeyWithdrawHydrator;
use Database\Hydrator\Other as OtherHydrator;
use Database\Hydrator\Strategy\AddressHydratorStrategy;
use Database\Hydrator\Strategy\MeetingHydratorStrategy;
use Database\Hydrator\Strategy\PostalRegionHydratorStrategy;
use Database\Mapper\ActionLink as ActionLinkMapper;
use Database\Mapper\CheckoutSession as CheckoutSessionMapper;
use Database\Mapper\Factory\ActionLinkFactory as ActionLinkMapperFactory;
use Database\Mapper\Factory\CheckoutSessionFactory as CheckoutSessionMapperFactory;
use Database\Mapper\Factory\InstallationFunctionFactory as InstallationFunctionMapperFactory;
use Database\Mapper\Factory\MailingListFactory as MailingListMapperFactory;
use Database\Mapper\Factory\MeetingFactory as MeetingMapperFactory;
use Database\Mapper\Factory\MemberFactory as MemberMapperFactory;
use Database\Mapper\Factory\MemberUpdateFactory as MemberUpdateMapperFactory;
use Database\Mapper\Factory\OrganFactory as OrganMapperFactory;
use Database\Mapper\Factory\ProspectiveMemberFactory as ProspectiveMemberMapperFactory;
use Database\Mapper\Factory\SavedQueryFactory as SavedQueryMapperFactory;
use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\Meeting as MeetingMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\MemberUpdate as MemberUpdateMapper;
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
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;
use Database\Service\Api as ApiService;
use Database\Service\Factory\ApiFactory as ApiServiceFactory;
use Database\Service\Factory\InstallationFunctionFactory as InstallationFunctionServiceFactory;
use Database\Service\Factory\MailingListFactory as MailingListServiceFactory;
use Database\Service\Factory\MeetingFactory as MeetingServiceFactory;
use Database\Service\Factory\MemberFactory as MemberServiceFactory;
use Database\Service\Factory\PaymentFactory as PaymentServiceFactory;
use Database\Service\Factory\QueryFactory as QueryServiceFactory;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Database\Service\MailingList as MailingListService;
use Database\Service\Meeting as MeetingService;
use Database\Service\Member as MemberService;
use Database\Service\Payment as PaymentService;
use Database\Service\Query as QueryService;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Psr\Container\ContainerInterface;
use stdClass;

class Module
{
    /**
     * Get the configuration for this module.
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
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
                PaymentService::class => PaymentServiceFactory::class,
                QueryService::class => QueryServiceFactory::class,
                ExportForm::class => static function (ContainerInterface $container) {
                    return new ExportForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingMapper::class),
                    );
                },
                AddressForm::class => static function (ContainerInterface $container) {
                    $form = new AddressForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_address'));

                    return $form;
                },
                DeleteAddressForm::class => static function (ContainerInterface $container) {
                    return new DeleteAddressForm($container->get(MvcTranslator::class));
                },
                MemberForm::class => static function (ContainerInterface $container) {
                    $form = new MemberForm(
                        $container->get(AddressFieldset::class),
                        $container->get(MvcTranslator::class),
                    );
                    $form->setHydrator($container->get('database_hydrator_default'));
                    $form->setLists($container->get(MailingListMapper::class)->findAllOnForm());

                    return $form;
                },
                MemberEditForm::class => static function (ContainerInterface $container) {
                    $form = new MemberEditForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                MemberApproveForm::class => static function (ContainerInterface $container) {
                    $form = new MemberApproveForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                MemberRenewalForm::class => static function (ContainerInterface $container) {
                    $form = new MemberRenewalForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                MemberTypeForm::class => static function (ContainerInterface $container) {
                    $form = new MemberTypeForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                MemberExpirationForm::class => static function (ContainerInterface $container) {
                    return new MemberExpirationForm($container->get(MvcTranslator::class));
                },
                CreateMeetingForm::class => static function (ContainerInterface $container) {
                    $form = new CreateMeetingForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_meeting'));

                    return $form;
                },
                OtherForm::class => static function (ContainerInterface $container) {
                    $form = new OtherForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                    );
                    $form->setHydrator($container->get(OtherHydrator::class));

                    return $form;
                },
                BudgetForm::class => static function (ContainerInterface $container) {
                    $form = new BudgetForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class),
                    );
                    $form->setHydrator($container->get(BudgetHydrator::class));

                    return $form;
                },
                MailingListForm::class => static function (ContainerInterface $container) {
                    $form = new MailingListForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                DeleteListForm::class => static function (ContainerInterface $container) {
                    return new DeleteListForm($container->get(MvcTranslator::class));
                },
                InstallationFunctionForm::class => static function (ContainerInterface $container) {
                    $form = new InstallationFunctionForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                InstallForm::class => static function (ContainerInterface $container) {
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
                DeleteDecisionForm::class => static function (ContainerInterface $container) {
                    $form = new DeleteDecisionForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get(AbolishHydrator::class));

                    return $form;
                },
                AbolishForm::class => static function (ContainerInterface $container) {
                    $form = new AbolishForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_foundation'),
                    );
                    $form->setHydrator($container->get(AbolishHydrator::class));

                    return $form;
                },
                DestroyForm::class => static function (ContainerInterface $container) {
                    $form = new DestroyForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(DecisionFieldset::class),
                    );
                    $form->setHydrator($container->get(DestroyHydrator::class));

                    return $form;
                },
                FoundationForm::class => static function (ContainerInterface $container) {
                    $form = new FoundationForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFunctionFieldset::class),
                    );
                    $form->setHydrator($container->get(FoundationHydrator::class));

                    return $form;
                },
                BoardInstallForm::class => static function (ContainerInterface $container) {
                    $form = new BoardInstallForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class),
                    );
                    $form->setHydrator($container->get(BoardInstallHydrator::class));

                    return $form;
                },
                BoardReleaseForm::class => static function (ContainerInterface $container) {
                    $form = new BoardReleaseForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_board_install'),
                    );
                    $form->setHydrator($container->get(BoardReleaseHydrator::class));

                    return $form;
                },
                BoardDischargeForm::class => static function (ContainerInterface $container) {
                    $form = new BoardDischargeForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_board_install'),
                    );
                    $form->setHydrator($container->get(BoardDischargeHydrator::class));

                    return $form;
                },
                KeyGrantForm::class => static function (ContainerInterface $container) {
                    $form = new KeyGrantForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get(MemberFieldset::class),
                    );
                    $form->setHydrator($container->get(KeyGrantHydrator::class));

                    return $form;
                },
                KeyWithdrawForm::class => static function (ContainerInterface $container) {
                    $form = new KeyWithdrawForm(
                        $container->get(MvcTranslator::class),
                        $container->get(MeetingFieldset::class),
                        $container->get('database_form_fieldset_subdecision_key_grant'),
                        $container->get(GrantingFieldset::class),
                    );
                    $form->setHydrator($container->get(KeyWithdrawHydrator::class));

                    return $form;
                },
                QueryForm::class => static function (ContainerInterface $container) {
                    return new QueryForm($container->get(MvcTranslator::class));
                },
                QueryExportForm::class => static function (ContainerInterface $container) {
                    return new QueryExportForm($container->get(MvcTranslator::class));
                },
                QuerySaveForm::class => static function (ContainerInterface $container) {
                    $form = new QuerySaveForm($container->get(MvcTranslator::class));
                    $form->setHydrator($container->get('database_hydrator_default'));

                    return $form;
                },
                'database_form_fieldset_subdecision_foundation' => static function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new FoundationModel());

                    return $fieldset;
                },
                'database_form_fieldset_subdecision_discharge' => static function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new InstallationModel());

                    return $fieldset;
                },
                'database_form_fieldset_subdecision_board_install' => static function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new BoardInstallationModel());

                    return $fieldset;
                },
                'database_form_fieldset_subdecision_key_grant' => static function (ContainerInterface $container) {
                    $fieldset = new SubDecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_subdecision'));
                    $fieldset->setObject(new KeyGrantingModel());

                    return $fieldset;
                },
                DecisionFieldset::class => static function (ContainerInterface $container) {
                    $fieldset = new DecisionFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_decision'));
                    $fieldset->setObject(new DecisionModel());

                    return $fieldset;
                },
                GrantingFieldset::class => static function (ContainerInterface $container) {
                    $fieldset = new GrantingFieldset(
                        $container->get(MemberFieldset::class),
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());

                    return $fieldset;
                },
                InstallationFieldset::class => static function (ContainerInterface $container) {
                    $fieldset = new InstallationFieldset(
                        $container->get(MemberFieldset::class),
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());

                    return $fieldset;
                },
                MeetingFieldset::class => static function (ContainerInterface $container) {
                    $fieldset = new MeetingFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_meeting'));
                    $fieldset->setObject(new MeetingModel());

                    return $fieldset;
                },
                MemberFieldset::class => static function (ContainerInterface $container) {
                    $fieldset = new MemberFieldset();
                    $fieldset->setHydrator($container->get('database_hydrator_default'));
                    $fieldset->setObject(new MemberModel());

                    return $fieldset;
                },
                MemberFunctionFieldset::class => static function (ContainerInterface $container) {
                    $fieldset = new MemberFunctionFieldset(
                        $container->get(MemberFieldset::class),
                        $container->get(InstallationFunctionService::class),
                        true,
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());

                    return $fieldset;
                },
                'database_form_fieldset_memberfunction_nomember' => static function (ContainerInterface $container) {
                    $fieldset = new MemberFunctionFieldset(
                        $container->get(MemberFieldset::class),
                        $container->get(InstallationFunctionService::class),
                        false,
                    );
                    $fieldset->setHydrator(new ObjectPropertyHydrator());
                    $fieldset->setObject(new stdClass());

                    return $fieldset;
                },
                AddressFieldset::class => static function (ContainerInterface $container) {
                    $fs = new AddressFieldset($container->get(MvcTranslator::class));
                    $fs->setHydrator($container->get('database_hydrator_address'));
                    $fs->setObject(new AddressModel());

                    return $fs;
                },
                ///////////////////////////////////////////////////////////////////////////
                'database_hydrator_default' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                },
                'database_hydrator_address' => static function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('type', new AddressHydratorStrategy());
                    $hydrator->addStrategy('country', new PostalRegionHydratorStrategy());

                    return $hydrator;
                },
                'database_hydrator_meeting' => static function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('type', new MeetingHydratorStrategy());

                    return $hydrator;
                },
                'database_hydrator_subdecision' => static function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('meeting_type', new MeetingHydratorStrategy());

                    return $hydrator;
                },
                'database_hydrator_decision' => static function (ContainerInterface $container) {
                    $hydrator = new DoctrineObject(
                        $container->get('database_doctrine_em'),
                        false,
                    );
                    $hydrator->addStrategy('meeting_type', new MeetingHydratorStrategy());

                    return $hydrator;
                },
                ActionLinkMapper::class => ActionLinkMapperFactory::class,
                InstallationFunctionMapper::class => InstallationFunctionMapperFactory::class,
                MailingListMapper::class => MailingListMapperFactory::class,
                MeetingMapper::class => MeetingMapperFactory::class,
                MemberMapper::class => MemberMapperFactory::class,
                MemberUpdateMapper::class => MemberUpdateMapperFactory::class,
                OrganMapper::class => OrganMapperFactory::class,
                CheckoutSessionMapper::class => CheckoutSessionMapperFactory::class,
                ProspectiveMemberMapper::class => ProspectiveMemberMapperFactory::class,
                SavedQueryMapper::class => SavedQueryMapperFactory::class,
                'database_mail_transport' => static function (ContainerInterface $container) {
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
                'database_doctrine_em' => static function (ContainerInterface $container) {
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
