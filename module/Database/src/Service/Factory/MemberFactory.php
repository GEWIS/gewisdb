<?php

namespace Database\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Checker\Service\Checker as CheckerService;
use Database\Form\{
    Address as AddressForm,
    AddressExport as AddressExportForm,
    DeleteAddress as DeleteAddressForm,
    Member as MemberForm,
    MemberApprove as MemberApproveForm,
    MemberEdit as MemberEditForm,
    MemberExpiration as MemberExpirationForm,
    MemberType as MemberTypeForm,
};
use Database\Mapper\{
    MailingList as MailingListMapper,
    Member as MemberMapper,
    MemberUpdate as MemberUpdateMapper,
    ProspectiveMember as ProspectiveMemberMapper,
};
use Database\Service\{
    MailingList as MailingListService,
    Member as MemberService,
};
use Laminas\Mail\Transport\TransportInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;

class MemberFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MemberService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): MemberService {
        /** @var AddressForm $addressForm */
        $addressForm = $container->get(AddressForm::class);
        /** @var AddressExportForm $addressExportForm */
        $addressExportForm = $container->get(AddressExportForm::class);
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
        /** @var MemberTypeForm $memberTypeForm */
        $memberTypeForm = $container->get(MemberTypeForm::class);
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
        /** @var PhpRenderer $viewRenderer */
        $viewRenderer = $container->get('ViewRenderer');
        /** @var TransportInterface $mailTransport */
        $mailTransport = $container->get('database_mail_transport');
        /** @var array $config */
        $config = $container->get('config');

        return new MemberService(
            $addressForm,
            $addressExportForm,
            $deleteAddressForm,
            $memberApproveForm,
            $memberForm,
            $memberEditForm,
            $memberExpirationForm,
            $memberTypeForm,
            $mailingListMapper,
            $memberMapper,
            $memberUpdateMapper,
            $prospectiveMemberMapper,
            $checkerService,
            $fileStorageService,
            $mailingListService,
            $viewRenderer,
            $mailTransport,
            $config,
        );
    }
}
