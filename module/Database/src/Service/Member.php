<?php

namespace Database\Service;

use Application\Model\Enums\{
    AddressTypes,
    MembershipTypes,
};
use Application\Service\FileStorage as FileStorageService;
use Checker\Model\Exception\LookupException;
use Checker\Model\TueData;
use Checker\Service\Checker as CheckerService;
use Database\Form\{
    Address as AddressForm,
    AddressExport as AddressExportForm,
    DeleteAddress as DeleteAddressForm,
    Member as MemberForm,
    MemberApprove as MemberApproveForm,
    MemberEdit as MemberEditForm,
    MemberExpiration as MemberExpirationForm,
    MemberLists as MemberListsForm,
    MemberType as MemberTypeForm,
};
use Database\Mapper\{
    MailingList as MailingListMapper,
    Member as MemberMapper,
    ProspectiveMember,
    ProspectiveMember as ProspectiveMemberMapper,
};
use Database\Model\{
    Address as AddressModel,
    Member as MemberModel,
    ProspectiveMember as ProspectiveMemberModel,
};
use Database\Service\MailingList as MailingListService;
use DateTime;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mail\Message;
use Laminas\Mime\{
    Mime,
    Part as MimePart,
    Message as MimeMessage,
};
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use RuntimeException;

class Member
{
    public function __construct(
        private readonly AddressForm $addressForm,
        private readonly AddressExportForm $addressExportForm,
        private readonly DeleteAddressForm $deleteAddressForm,
        private readonly MemberApproveForm $memberApproveForm,
        private readonly MemberForm $memberForm,
        private readonly MemberEditForm $memberEditForm,
        private readonly MemberExpirationForm $memberExpirationForm,
        private readonly MemberTypeForm $memberTypeForm,
        private readonly MailingListMapper $mailingListMapper,
        private readonly MemberMapper $memberMapper,
        private readonly ProspectiveMemberMapper $prospectiveMemberMapper,
        private readonly CheckerService $checkerService,
        private readonly FileStorageService $fileStorageService,
        private readonly MailingListService $mailingListService,
        private readonly PhpRenderer $viewRenderer,
        private readonly TransportInterface $mailTransport,
        private readonly array $config,
    ) {
    }

    /**
     * Subscribe a member.
     */
    public function subscribe(array $data): ?ProspectiveMemberModel
    {
        $form = $this->getMemberForm();
        $form->bind(new ProspectiveMemberModel());

        if (
            isset($data['address'])
            && isset($data['address']['street'])
            && !empty($data['address']['street'])
        ) {
            $form->setValidationGroup([
                'lastName', 'middleName', 'initials', 'firstName',
                'tueUsername', 'study', 'email', 'birth',
                'address', 'agreed', 'iban', 'signature', 'signatureLocation',
            ]);
        } else {
            $form->setValidationGroup([
                'lastName', 'middleName', 'initials', 'firstName',
                'tueUsername', 'study', 'email', 'birth',
                'agreed', 'iban', 'signature', 'signatureLocation',
            ]);
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // set some extra data
        /** @var ProspectiveMemberModel $prospectiveMember */
        $prospectiveMember = $form->getData();

        // find if there is an earlier member with the same email or name
        if (
            $this->getMemberMapper()->hasMemberWith($prospectiveMember->getEmail())
            || $this->getProspectiveMemberMapper()->hasMemberWith($prospectiveMember->getEmail())
        ) {
            $form->get('email')->setMessages([
                'There already is a member with this email address.',
            ]);
            return null;
        }

        // changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $prospectiveMember->setChangedOn($date);

        // store the address
        $address = $form->get('address')->getObject();
        $prospectiveMember->setAddress($address);

        // check mailing lists
        foreach ($form->getLists() as $list) {
            if ($form->get('list-' . $list->getName())->isChecked()) {
                $prospectiveMember->addList($list);
            }
        }
        // subscribe to default mailing lists not on the form
        $mailingMapper = $this->mailingListMapper;
        foreach ($mailingMapper->findDefault() as $list) {
            $prospectiveMember->addList($list);
        }

        // handle signature
        if (null !== $prospectiveMember->getIban()) {
            $signature = $form->get('signature')->getValue();

            if (null !== $signature) {
                $path = $this->getFileStorageService()->storeUploadedData($signature, 'png');
                $prospectiveMember->setSignature($path);
            }
        }

        $this->getProspectiveMemberMapper()->persist($prospectiveMember);

        return $prospectiveMember;
    }

    /**
     * Send an email about the newly subscribed member to the new member and the secretary
     */
    public function sendMemberSubscriptionEmail(ProspectiveMemberModel $member): void
    {
        $config = $this->config;
        $config = $config['email'];

        $renderer = $this->getRenderer();
        $model = new ViewModel([
            'member' => $member,
        ]);
        $model->setTemplate('database/member/subscribe');
        $body = $renderer->render($model);

        $html = new MimePart($body);
        $html->type = "text/html";

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        // Include signature as image attachment
        if (null !== $member->getIban()) {
            $image = new MimePart(fopen(
                $this->getFileStorageService()->getConfig()['storage_dir'] . '/' . $member->getSignature(),
                'r',
            ));
            $image->type = 'image/png';
            $image->filename = 'signature.png';
            $image->disposition = Mime::DISPOSITION_ATTACHMENT;
            $image->encoding = Mime::ENCODING_BASE64;
            $mimeMessage->addPart($image);
        }

        $message = new Message();
        $message->setBody($mimeMessage);
        $message->setFrom($config['from']);
        $message->addTo($config['to']['subscription']);
        $message->setSubject('New member subscription: ' . $member->getFullName());
        $this->getMailTransport()->send($message);

        $message = new Message();
        $message->setBody($mimeMessage);
        $message->setFrom($config['from']);
        $message->addTo($member->getEmail());
        $message->setSubject('GEWIS Subscription');
        $this->getMailTransport()->send($message);
    }

    public function finalizeSubscription(
        array $membershipData,
        ProspectiveMemberModel $prospectiveMember,
    ): ?MemberModel {
        // If no membership type has been submitted it does not make sense to do anything else.
        if (!isset($membershipData['type'])) {
            return null;
        }

        $form = $this->getMemberForm();
        $form->bind(new MemberModel());

        // Fill in the address in the form again
        $data = $prospectiveMember->toArray();

        // add list data to the form
        foreach ($form->getLists() as $list) {
            $result = '0';
            foreach ($prospectiveMember->getLists() as $l) {
                if ($list->getName() == $l->getName()) {
                    $result = '1';
                }
            }
            $data['list-' . $list->getName()] = $result;
        }

        unset($data['lidnr']);

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var MemberModel $member */
        $member = $form->getData();

        // Copy all remaining information
        $member->setTueUsername($prospectiveMember->getTueUsername());

        // changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // set generation (first year of the current association year), membership type and associated expiration of
        // said membership (always at the end of the current association year).
        $member->setType(MembershipTypes::from($membershipData['type']));
        $expiration = clone $date;

        if ($expiration->format('m') >= 7) {
            $generationYear = (int) $expiration->format('Y');
            $expirationYear = (int) $expiration->format('Y') + 1;
        } else {
            $generationYear = (int) $expiration->format('Y') - 1;
            $expirationYear = (int) $expiration->format('Y');
        }

        switch ($member->getType()) {
            case MembershipTypes::Ordinary:
                $member->setIsStudying(true);
                $member->setMembershipEndsOn(null);
                break;
            case MembershipTypes::External:
                $member->setIsStudying(true);
                $member->setMembershipEndsOn($expiration);
                break;
            case MembershipTypes::Graduate:
                $member->setIsStudying(false);
                // This is a weird situation, as such define the expiration of the membership to be super early. Actual
                // value will have to be edited manually.
                $membershipEndsOn = clone $expiration;
                $membershipEndsOn->setDate(1, 1, 1);
                $member->setMembershipEndsOn($membershipEndsOn);
                break;
            case MembershipTypes::Honorary:
                $member->setIsStudying(false);
                $member->setMembershipEndsOn(null);
                // infinity (1000 is close enough, right?)
                $expirationYear += 1000;
                break;
        }

        $expiration->setDate($expirationYear, 7, 1);
        $member->setExpiration($expiration);
        $member->setGeneration($generationYear);

        // add address
        $member->addAddresses($prospectiveMember->getAddresses());

        // add mailing lists
        foreach ($form->getLists() as $list) {
            if ($form->get('list-' . $list->getName())->isChecked()) {
                $member->addList($list);
            }
        }
        // subscribe to default mailing lists not on the form
        $mailingMapper = $this->mailingListMapper;
        foreach ($mailingMapper->findDefault() as $list) {
            $member->addList($list);
        }

        // If this was requested, update the data with the TU/e data
        // Assume that this checkbox is only set if the data can be retrieved correctly
        // so we don't catch any errors
        if (isset($membershipData['updatedata'])) {
            $tuedata = $this->getCheckerService()->tueDataObject();
            $tuedata->setUser($member->getTueUsername());
            $member->setInitials($tuedata->getInitials());
            $member->setFirstName($tuedata->getFirstName());
            $member->setMiddleName($tuedata->computedPrefixName());
            $member->setLastName($tuedata->computedLastName());
        }

        // Remove prospectiveMember model
        $this->getMemberMapper()->persist($member);

        $this->removeProspective($prospectiveMember);

        return $member;
    }

    /**
     * Get member info.
     */
    public function getMember(int $id): array
    {
        $member = $this->getMemberMapper()->find($id);
        $simple = false;

        if (null === $member) {
            $member = $this->getMemberMapper()->findSimple($id);
            $simple = true;
        }

        return [
            'member' => $member,
            'simple' => $simple,
        ];
    }

    /**
     * Get prospective member info
     *
     * @return array member, form, tuedata
     */
    public function getProspectiveMember(int $id): array
    {
        $member = $this->getProspectiveMemberMapper()->find($id);
        $tueData = $this->getCheckerService()->tueDataObject();
        $tueStatus = [];

        try {
            $tueData->setUser($member->getTueUsername());

            if (!$tueData->isValid()) {
                $tueStatus[] = [
                    'info',
                    'No data was returned.',
                ];
            } else {
                $similar = $tueData->compareData(
                    firstName: $member->getFirstName(),
                    prefixName: $member->getMiddleName(),
                    lastName: $member->getLastName(),
                    initials: $member->getInitials(),
                );

                if ($similar > 3) {
                    // phpcs:ignore -- user-visible strings should not be split
                    $tueStatus[] = [
                        'danger',
                        '<b>Warning:</b> Data is not likely to be similar. Requires ' . $similar . ' edits. Please check if the TU/e data matches the data entered by the member before approving membership.',
                    ];
                } elseif ($similar > 0) {
                    $tueStatus[] = [
                        'info',
                        '<b>Info:</b> ' . $similar . ' edits needed to correct name. Data likely correct.',
                    ];
                }

                if ($tueData->studiesAtDepartment()) {
                    // phpcs:ignore -- user-visible strings should not be split
                    $tueStatus[] = [
                        'success',
                        '<b>Info:</b> Member studies at department. Recommended membership type: <strong>Gewoon lid</strong>.',
                    ];
                } else {
                    $tueStatus[] = [
                        'danger',
                        '<b>Warning:</b> Member does not study at department.',
                    ];
                }
            }
        } catch (LookupException $e) {
            $tueStatus[] = $e->getMessage();
        }

        return [
            'member' => $member,
            'form' => $this->memberApproveForm,
            'tueData' => $tueData,
            'tueStatus' => $tueStatus,
        ];
    }

    /**
     * Get TU/e data of a member
     *
     * @return TueData|null for member or null if no such data is available
     */
    public function getTueData(int $id): ?TueData
    {
        /** @var MemberModel $member */
        $member = $this->getMember($id)['member'];

        if (null !== ($tueUsername = $member->getTueUsername())) {
            $tuedata = $this->getCheckerService()->tueDataObject();

            try {
                $tuedata->setUser($tueUsername);
            } catch (LookupException $e) {
                return null;
            }

            if ($tuedata->isValid()) {
                return $tuedata;
            }
        }

        return null;
    }

    /**
     * Toggle if a member receives the supremum.
     */
    public function setSupremum(
        int $id,
        string $value,
    ): void {
        $member = $this->getMember($id);
        $member = $member['member'];

        $member->setSupremum($value);

        $this->getMemberMapper()->persist($member);
    }

    /**
     * Search for a member.
     *
     * @return array<array-key, MemberModel>
     */
    public function search(string $query): array
    {
        return $this->getMemberMapper()->search($query);
    }

    /**
     * Search for a prospective member.
     *
     * @return array<array-key, ProspectiveMemberModel>
     */
    public function searchProspective(string $query): array
    {
        return $this->getProspectiveMemberMapper()->search($query);
    }

    /**
     * Check if we can easily remove a member.
     */
    public function canRemove(MemberModel $member): bool
    {
        return $this->getMemberMapper()->canRemove($member);
    }

    /**
     * Remove a member.
     */
    public function remove(MemberModel $member): void
    {
        if ($this->canRemove($member)) {
            $this->getMemberMapper()->remove($member);
        } else {
            $this->clear($member);
        }
    }

    /**
     * Remove a member.
     */
    public function removeProspective(ProspectiveMemberModel $member): void
    {
        // First destroy the signature file
        if (null !== ($signature = $member->getSignature())) {
            $this->getFileStorageService()->removeFile($signature);
        }

        $this->getProspectiveMemberMapper()->remove($member);
    }

    /**
     * Clear a member.
     */
    public function clear(MemberModel $member): void
    {
        foreach ($member->getAddresses() as $address) {
            $this->getMemberMapper()->removeAddress($address);
        }

        $date = new DateTime('0001-01-01 00:00:00');

        $member->setEmail(null);
        $member->setGeneration(0);
        $member->setTueUsername(null);
        $member->setStudy(null);
        $member->setChangedOn(new DateTime());
        $member->setMembershipEndsOn($date);
        $member->setExpiration($date);
        $member->setBirth($date);
        $member->setPaid(0);
        $member->setIban(null);
        $member->setSupremum('optout');
        $member->clearLists();

        $this->getMemberMapper()->persist($member);
    }

    /**
     * Edit a member.
     */
    public function edit(
        array $data,
        int $lidnr,
    ): ?MemberModel {
        $form = $this->getMemberEditForm($lidnr)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $member = $form->getData();

        // update changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Edit membership.
     */
    public function membership(
        array $data,
        int $lidnr,
    ): ?MemberModel {
        $form = $this->getMemberTypeForm($lidnr);
        // List unpacking is not allowed in PHP 5.6, so it has to be done like this.
        /** @var MemberModel $member */
        $member = $form['member'];
        $form = $form['form'];

        // It is not possible to have another membership type after being an honorary member and there does not exist a
        // good transition to a different membership type (because of the dates/expiration etc.).
        if (MembershipTypes::Honorary === $member->getType()) {
            throw new RuntimeException('Er is geen pad waarop dit lid correct een ander lidmaatschapstype kan krijgen');
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        // update changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // update expiration and 'membership ends on' date (should become effective at the end of the previous
        // association year).
        $expiration = clone $date;

        if ($expiration->format('m') >= 7) {
            $year = (int) $expiration->format('Y') + 1;
        } else {
            $year = (int) $expiration->format('Y');
        }

        switch (MembershipTypes::from($data['type'])) {
            case MembershipTypes::Ordinary:
                $member->setIsStudying(true);
                $member->setMembershipEndsOn(null);
                $member->setType(MembershipTypes::Ordinary);
                break;
            case MembershipTypes::External:
                $member->setIsStudying(true);
                $membershipEndsOn = clone $expiration;
                $membershipEndsOn->setDate($year - 1, 7, 1);
                $member->setMembershipEndsOn($membershipEndsOn);
                $member->setType(MembershipTypes::External);
                break;
            case MembershipTypes::Graduate:
                $member->setIsStudying(false);
                $membershipEndsOn = clone $expiration;
                $membershipEndsOn->setDate($year - 1, 7, 1);
                $member->setMembershipEndsOn($membershipEndsOn);
                $member->setType(MembershipTypes::Graduate);
                break;
            case MembershipTypes::Honorary:
                $member->setIsStudying(false);
                // infinity (1000 is close enough, right?)
                $year += 1000;
                $member->setMembershipEndsOn(null);
                // Directly apply the honorary membership type.
                $member->setType(MembershipTypes::Honorary);
                break;
        }

        // At the end of the current association year.
        $expiration->setDate($year, 7, 1);
        $member->setExpiration($expiration);

        $this->getMemberMapper()->persist($member);

        return $member;
    }

    public function expiration(
        array $data,
        int $lidnr,
    ): ?MemberModel {
        $form = $this->getMemberExpirationForm($lidnr);
        // List unpacking is not allowed in PHP 5.6, so it has to be done like this.
        $member = $form['member'];
        $form = $form['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // Make new expiration from previous expiration, but always make sure it is the end of the association year.
        $newExpiration = clone $member->getExpiration();
        $year = (int) $newExpiration->format('Y') + 1;
        $newExpiration->setDate($year, 7, 1);

        $member->setExpiration($newExpiration);

        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Edit address.
     */
    public function editAddress(
        array $data,
        int $lidnr,
        AddressTypes $type,
    ): ?AddressModel {
        $form = $this->getAddressForm($lidnr, $type)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var AddressModel $address */
        $address = $form->getData();

        $this->getMemberMapper()->persistAddress($address);

        return $address;
    }

    /**
     * Add address.
     */
    public function addAddress(
        array $data,
        int $lidnr,
        AddressTypes $type,
    ): ?AddressModel {
        $form = $this->getAddressForm($lidnr, $type, true)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var AddressModel $address */
        $address = $form->getData();

        $this->getMemberMapper()->persistAddress($address);

        return $address;
    }

    /**
     * Remove address.
     */
    public function removeAddress(
        array $data,
        int $lidnr,
        AddressTypes $type,
    ): ?MemberModel {
        $formData = $this->getDeleteAddressForm($lidnr, $type);
        $form = $formData['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $address = $formData['address'];
        $member = $address->getMember();

        $this->getMemberMapper()->removeAddress($address);

        return $member;
    }

    /**
     * Subscribe member to mailing lists.
     */
    public function subscribeLists(
        array $data,
        int $lidnr,
    ): ?MemberModel {
        $formData = $this->getListForm($lidnr);
        $form = $formData['form'];
        $lists = $formData['lists'];
        $member = $formData['member'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        $member->clearLists();

        foreach ($lists as $list) {
            $name = 'list-' . $list->getName();
            if (isset($data[$name]) && $data[$name]) {
                $member->addList($list);
            }
        }

        // simply persist through member
        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Get the member edit form.
     */
    public function getMemberEditForm(int $lidnr): array
    {
        $form = $this->memberEditForm;
        $member = $this->getMember($lidnr);
        $form->bind($member['member']);

        return [
            'member' => $member['member'],
            'form' => $form,
            'tueData' => $this->getTueData($lidnr),
        ];
    }

    /**
     * Get the member expiration form.
     */
    public function getMemberExpirationForm(int $lidnr): array
    {
        return [
            'form' => $this->memberExpirationForm,
            'member' => $this->getMember($lidnr)['member'],
        ];
    }

    /**
     * Get the member type form.
     */
    public function getMemberTypeForm(int $lidnr): array
    {
        return [
            'member' => $this->getMember($lidnr)['member'],
            'form' => $this->memberTypeForm,
        ];
    }

    /**
     * Get the list edit form.
     */
    public function getListForm(int $lidnr): array
    {
        $member = $this->getMember($lidnr);
        $member = $member['member'];
        $lists = $this->mailingListService->getAllLists();

        return [
            'form' => new MemberListsForm($member, $lists),
            'member' => $member,
            'lists' => $lists,
        ];
    }

    /**
     * Get the address form.
     */
    public function getAddressForm(
        int $lidnr,
        AddressTypes $type,
        bool $create = false,
    ): array {
        // find the address
        if ($create) {
            $address = new AddressModel();
            $address->setMember($this->getMemberMapper()->findSimple($lidnr));
            $address->setType($type);
        } else {
            $address = $this->getMemberMapper()->findMemberAddress($lidnr, $type);
        }

        $form = $this->addressForm;
        $form->bind($address);

        return [
            'form' => $form,
            'address' => $address,
        ];
    }

    /**
     * Get the delete address form.
     */
    public function getDeleteAddressForm(
        int $lidnr,
        AddressTypes $type,
    ): array {
        // find the address
        return [
            'form' => $this->deleteAddressForm,
            'address' => $this->getMemberMapper()->findMemberAddress($lidnr, $type),
        ];
    }

    /**
     * Get address export form.
     */
    public function getAddressExportForm(): AddressExportForm
    {
        return $this->addressExportForm;
    }

    /**
     * Get the member form.
     */
    public function getMemberForm(): MemberForm
    {
        return $this->memberForm;
    }

    /**
     * Get the member mapper.
     */
    public function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }

    /**
     * Get the member mapper.
     */
    public function getProspectiveMemberMapper(): ProspectiveMemberMapper
    {
        return $this->prospectiveMemberMapper;
    }

    /**
     * Gets the storage service.
     */
    public function getFileStorageService(): FileStorageService
    {
        return $this->fileStorageService;
    }

    /**
     * Get the renderer for the email.
     */
    public function getRenderer(): PhpRenderer
    {
        return $this->viewRenderer;
    }

    /**
     * Get the mail transport.
     */
    public function getMailTransport(): TransportInterface
    {
        return $this->mailTransport;
    }

    /**
     * Get the checker service.
     */
    public function getCheckerService(): CheckerService
    {
        return $this->checkerService;
    }
}
