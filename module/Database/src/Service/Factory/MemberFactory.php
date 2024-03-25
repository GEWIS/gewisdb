<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Checker\Service\Checker as CheckerService;
use Checker\Service\Renewal as RenewalService;
use Database\Form\Address as AddressForm;
use Database\Form\AuditEntry\AuditNote as AuditNoteForm;
use Database\Form\DeleteAddress as DeleteAddressForm;
use Database\Form\Member as MemberForm;
use Database\Form\MemberApprove as MemberApproveForm;
use Database\Form\MemberEdit as MemberEditForm;
use Database\Form\MemberExpiration as MemberExpirationForm;
use Database\Form\MemberRenewal as MemberRenewalForm;
use Database\Form\MemberType as MemberTypeForm;
use Database\Mapper\ActionLink as ActionLinkMapper;
use Database\Mapper\Audit as AuditMapper;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\MemberUpdate as MemberUpdateMapper;
use Database\Mapper\ProspectiveMember as ProspectiveMemberMapper;
use Database\Service\MailingList as MailingListService;
use Database\Service\Member as MemberService;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;
use User\Service\UserService;

class MemberFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberService {
        /** @var MvcTranslator $translator */
        $translator = $container->get(MvcTranslator::class);
        /** @var AddressForm $addressForm */
        $addressForm = $container->get(AddressForm::class);
        /** @var AuditNoteForm $auditNoteForm */
        $auditNoteForm = $container->get(AuditNoteForm::class);
        /** @var DeleteAddressForm $deleteAddressForm */
        $deleteAddressForm = $container->get(DeleteAddressForm::class);
        /** @var MemberApproveForm $memberApproveForm */
        $memberApproveForm = $container->get(MemberApproveForm::class);
        /** @var MemberForm $memberForm */
        $memberForm = $container->get(MemberForm::class);
        /** @var MemberEditForm $memberEditForm */
        $memberEditForm = $container->get(MemberEditForm::class);
        /** @var MemberExpirationForm $memberExpirationForm */
        $memberExpirationForm = $container->get(MemberExpirationForm::class);
        /** @var MemberRenewalForm $memberRenewalForm */
        $memberRenewalForm = $container->get(MemberRenewalForm::class);
        /** @var MemberTypeForm $memberTypeForm */
        $memberTypeForm = $container->get(MemberTypeForm::class);
        /** @var ActionLinkMapper $actionLinkMapper */
        $actionLinkMapper = $container->get(ActionLinkMapper::class);
        /** @var AuditMapper $auditMapper */
        $auditMapper = $container->get(AuditMapper::class);
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);
        /** @var MemberMapper $memberMapper */
        $memberMapper = $container->get(MemberMapper::class);
        /** @var MemberUpdateMapper $memberUpdateMapper */
        $memberUpdateMapper = $container->get(MemberUpdateMapper::class);
        /** @var ProspectiveMemberMapper $prospectiveMemberMapper */
        $prospectiveMemberMapper = $container->get(ProspectiveMemberMapper::class);
        /** @var CheckerService $checkerService */
        $checkerService = $container->get(CheckerService::class);
        /** @var FileStorageService $fileStorageService */
        $fileStorageService = $container->get(FileStorageService::class);
        /** @var MailingListService $mailingListService */
        $mailingListService = $container->get(MailingListService::class);
        /** @var RenewalService $renewalService */
        $renewalService = $container->get(RenewalService::class);
        /** @var UserService $userService */
        $userService = $container->get(UserService::class);
        /** @var PhpRenderer $viewRenderer */
        $viewRenderer = $container->get('ViewRenderer');
        /** @var TransportInterface $mailTransport */
        $mailTransport = $container->get('database_mail_transport');
        /** @var array $config */
        $config = $container->get('config');

        return new MemberService(
            $translator,
            $addressForm,
            $auditNoteForm,
            $deleteAddressForm,
            $memberApproveForm,
            $memberForm,
            $memberEditForm,
            $memberExpirationForm,
            $memberRenewalForm,
            $memberTypeForm,
            $mailingListMapper,
            $actionLinkMapper,
            $auditMapper,
            $memberMapper,
            $memberUpdateMapper,
            $prospectiveMemberMapper,
            $checkerService,
            $fileStorageService,
            $mailingListService,
            $renewalService,
            $userService,
            $viewRenderer,
            $mailTransport,
            $config,
        );
    }
}
