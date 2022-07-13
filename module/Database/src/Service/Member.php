<?php

namespace Database\Service;

use Application\Service\FileStorage as FileStorageService;
use Database\Form\Address as AddressForm;
use Database\Form\AddressExport as AddressExportForm;
use Database\Form\DeleteAddress as DeleteAddressForm;
use Database\Form\Member as MemberForm;
use Database\Form\MemberEdit as MemberEditForm;
use Database\Form\MemberExpiration as MemberExpirationForm;
use Database\Form\MemberLists as MemberListsForm;
use Database\Form\MemberType as MemberTypeForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\ProspectiveMember;
use Database\Mapper\ProspectiveMember as ProspectiveMemberMapper;
use Database\Model\Address;
use Database\Model\Member as MemberModel;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Database\Service\MailingList as MailingListService;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Mime;
use Zend\View\Model\ViewModel;
use Zend\Mail\Message;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\View\Renderer\PhpRenderer;

class Member
{
    /** @var AddressForm $addressForm */
    private $addressForm;

    /** @var AddressExportForm $addressExportForm */
    private $addressExportForm;

    /** @var DeleteAddressForm $deleteAddressForm */
    private $deleteAddressForm;

    /** @var MemberForm $memberForm */
    private $memberForm;

    /** @var MemberEditForm $memberEditForm */
    private $memberEditForm;

    /** @var MemberExpirationForm $memberExpirationForm */
    private $memberExpirationForm;

    /** @var MemberTypeForm $memberTypeForm */
    private $memberTypeForm;

    /** @var MailingListMapper $mailingListMapper */
    private $mailingListMapper;

    /** @var MemberMapper $memberMapper */
    private $memberMapper;

    /** @var ProspectiveMemberMapper $prospectiveMemberMapper */
    private $prospectiveMemberMapper;

    /** @var FileStorageService $fileStorageService */
    private $fileStorageService;

    /** @var MailingListService $mailingListService */
    private $mailingListService;

    /** @var PhpRenderer $viewRenderer */
    private $viewRenderer;

    private $mailTransport;

    /** @var array $config */
    private $config;

    public function __construct(
        AddressForm $addressForm,
        AddressExportForm $addressExportForm,
        DeleteAddressForm $deleteAddressForm,
        MemberForm $memberForm,
        MemberEditForm $memberEditForm,
        MemberExpirationForm $memberExpirationForm,
        MemberTypeForm $memberTypeForm,
        MailingListMapper $mailingListMapper,
        MemberMapper $memberMapper,
        ProspectiveMemberMapper $prospectiveMemberMapper,
        FileStorageService $fileStorageService,
        MailingListService $mailingListService,
        PhpRenderer $viewRenderer,
        $mailTransport,
        array $config
    ) {
        $this->addressForm = $addressForm;
        $this->addressExportForm = $addressExportForm;
        $this->deleteAddressForm = $deleteAddressForm;
        $this->memberForm = $memberForm;
        $this->memberEditForm = $memberEditForm;
        $this->memberExpirationForm = $memberExpirationForm;
        $this->memberTypeForm = $memberTypeForm;
        $this->mailingListMapper = $mailingListMapper;
        $this->memberMapper = $memberMapper;
        $this->prospectiveMemberMapper = $prospectiveMemberMapper;
        $this->fileStorageService = $fileStorageService;
        $this->mailingListService =  $mailingListService;
        $this->viewRenderer = $viewRenderer;
        $this->mailTransport = $mailTransport;
        $this->config = $config;
    }

    /**
     * Subscribe a member.
     *
     * @param array $data
     *
     * @return ProspectiveMemberModel member, null if failed.
     */
    public function subscribe($data)
    {
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

        $form = $this->getMemberForm();
        $form->bind(new ProspectiveMemberModel());

        $noiban = false;

        if (isset($data['studentAddress']) && isset($data['studentAddress']['street']) && !empty($data['studentAddress']['street'])) {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tueUsername', 'study', 'email', 'birth',
                'studentAddress', 'agreed', 'iban', 'signature', 'signatureLocation'
            ));
        } else {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tueUsername', 'study', 'email', 'birth',
                'agreed', 'iban', 'signature', 'signatureLocation'
            ));
        }
        if ($data['iban'] == 'noiban') {
            $noiban = true;
            $data['iban'] = 'NL20INGB0001234567';
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // set some extra data
        /** @var ProspectiveMemberModel $prospectiveMember */
        $prospectiveMember = $form->getData();

        if ($noiban) {
            $prospectiveMember->setIban(null);
        }

        // find if there is an earlier member with the same email or name
        if (
            $this->getMemberMapper()->hasMemberWith($prospectiveMember->getEmail())
            || $this->getProspectiveMemberMapper()->hasMemberWith($prospectiveMember->getEmail())
        ) {
            $form->get('email')->setMessages([
                'There already is a member with this email address.'
            ]);
            return null;
        }

        // by default, we only add ordinary members
        $prospectiveMember->setType(MemberModel::TYPE_ORDINARY);

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $prospectiveMember->setChangedOn($date);

        // store the address
        $address = $form->get('studentAddress')->getObject();
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
        $signature = $form->get('signature')->getValue();
        if (!is_null($signature)) {
            $path = $this->getFileStorageService()->storeUploadedData($signature, 'png');
            $prospectiveMember->setSignature($path);
        }

        $this->getProspectiveMemberMapper()->persist($prospectiveMember);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $prospectiveMember));

        return $prospectiveMember;
    }

    /**
     * Send an email about the newly subscribed member to the new member and the secretary
     *
     * @param ProspectiveMemberModel $member
     */
    public function sendMemberSubscriptionEmail(ProspectiveMemberModel $member)
    {
        $config = $this->config;
        $config = $config['email'];

        $renderer = $this->getRenderer();
        $model = new ViewModel(array(
            'member' => $member
        ));
        $model->setTemplate('database/member/subscribe');
        $body = $renderer->render($model);

        $html = new MimePart($body);
        $html->type = "text/html";

        // Include signature as image attachment
        $image = new MimePart(fopen($this->getFileStorageService()->getConfig()['storage_dir'] . '/' . $member->getSignature(), 'r'));
        $image->type = 'image/png';
        $image->filename = 'signature.png';
        $image->disposition = Mime::DISPOSITION_ATTACHMENT;
        $image->encoding = Mime::ENCODING_BASE64;

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html, $image]);

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

    /**
     * @param array $membershipData
     * @param ProspectiveMemberModel $prospectiveMember
     *
     * @return MemberModel|null
     */
    public function finalizeSubscription($membershipData, $prospectiveMember)
    {
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

        // Temporary IBAN to pass the form validator.
        $noiban = false;
        if ($data['iban'] == null) {
            $noiban = true;
            $data['iban'] = 'NL20INGB0001234567';
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

        /** @var MemberModel $member */
        $member = $form->getData();

        // Remove temporary IBAN.
        if ($noiban) {
            $member->setIban(null);
        }

        // Copy all remaining information
        $member->setTueUsername($prospectiveMember->getTueUsername());
        $member->setType($prospectiveMember->getType());

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // set generation (first year of the current association year), membership type and associated expiration of
        // said membership (always at the end of the current association year).
        $member->setType($membershipData['type']);
        $expiration = clone $date;

        if ($expiration->format('m') >= 7) {
            $generationYear = (int) $expiration->format('Y');
            $expirationYear = (int) $expiration->format('Y') + 1;
        } else {
            $generationYear = (int) $expiration->format('Y') - 1;
            $expirationYear = (int) $expiration->format('Y');
        }

        switch ($member->getType()) {
            case MemberModel::TYPE_ORDINARY:
                $member->setIsStudying(true);
                $member->setMembershipEndsOn(null);
                break;
            case MemberModel::TYPE_EXTERNAL:
                $member->setIsStudying(true);
                $member->setMembershipEndsOn($expiration);
                break;
            case MemberModel::TYPE_GRADUATE:
                $member->setIsStudying(false);
                // This is a weird situation, as such define the expiration of the membership to be super early. Actual
                // value will have to be edited manually.
                $membershipEndsOn = clone $expiration;
                $membershipEndsOn->setDate(1, 1, 1);
                $member->setMembershipEndsOn($membershipEndsOn);
                break;
            case MemberModel::TYPE_HONORARY:
                $member->setIsStudying(false);
                $member->setMembershipEndsOn(null);
                // infinity (1000 is close enough, right?)
                $expirationYear += 1000;
                break;
        }

        $expiration->setDate($expirationYear, 7, 1);
        $member->setExpiration($expiration);
        $member->setGeneration($generationYear);

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

        // Remove prospectiveMember model
        $this->getMemberMapper()->persist($member);

        $this->removeProspective($prospectiveMember);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * Get member info.
     *
     * @param int $id
     *
     * @return MemberModel
     */
    public function getMember($id)
    {
        try {
            return array(
                'member' => $this->getMemberMapper()->find($id),
                'simple' => false
            );
        } catch (\Doctrine\ORM\NoResultException $e) {
            return array(
                'member' => $this->getMemberMapper()->findSimple($id),
                'simple' => true
            );
        }
    }

    /**
     * Get prospective member info.
     *
     * @param int $id
     *
     * @return array
     */
    public function getProspectiveMember($id)
    {
        return array(
            'member' => $this->getProspectiveMemberMapper()->find($id),
            'form' => $this->memberTypeForm,
        );
    }

    /**
     * Toggle if a member receives the supremum.
     *
     * @param int $id
     * @param string $value
     */
    public function setSupremum($id, $value)
    {
        $member = $this->getMember($id);
        $member = $member['member'];

        $member->setSupremum($value);

        $this->getMemberMapper()->persist($member);
    }

    /**
     * Search for a member.
     *
     * @param string $query
     */
    public function search($query)
    {
        return $this->getMemberMapper()->search($query);
    }

    /**
     * Search for a prospective member.
     *
     * @param string $query
     */
    public function searchProspective($query)
    {
        return $this->getProspectiveMemberMapper()->search($query);
    }

    /**
     * Check if we can easily remove a member.
     *
     * @param MemberModel $member
     */
    public function canRemove(MemberModel $member)
    {
        return $this->getMemberMapper()->canRemove($member);
    }

    /**
     * Remove a member.
     *
     * @param MemberModel $member
     */
    public function remove(MemberModel $member)
    {
        if ($this->canRemove($member)) {
            return $this->getMemberMapper()->remove($member);
        }
        $this->clear($member);
    }

    /**
     * Remove a member.
     *
     * @param ProspectiveMemberModel $member
     */
    public function removeProspective(ProspectiveMemberModel $member)
    {
        // First destroy the signiture file
        $this->getFileStorageService()->removeFile($member->getSignature());
        $this->getProspectiveMemberMapper()->remove($member);
    }

    /**
     * Clear a member.
     *
     * @param Member $member
     */
    public function clear(MemberModel $member)
    {
        foreach ($member->getAddresses() as $address) {
            $this->getMemberMapper()->removeAddress($address);
        }

        $date = new \DateTime('0001-01-01 00:00:00');

        $member->setEmail(null);
        $member->setGender(MemberModel::GENDER_OTHER);
        $member->setGeneration(0);
        $member->setTueUsername(null);
        $member->setStudy(null);
        $member->setChangedOn(new \DateTime());
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
     *
     * @param array $data
     * @param int $lidnr member to edit
     *
     * @return MemberModel
     */
    public function edit($data, $lidnr)
    {
        $form = $this->getMemberEditForm($lidnr)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $member = $form->getData();

        // update changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $this->getMemberMapper()->persist($member);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * Edit membership.
     *
     * @param array $data
     * @param int $lidnr member to edit
     *
     * @return MemberModel|null
     */
    public function membership($data, $lidnr)
    {
        $form = $this->getMemberTypeForm($lidnr);
        // List unpacking is not allowed in PHP 5.6, so it has to be done like this.
        /** @var MemberModel $member */
        $member = $form['member'];
        $form = $form['form'];

        // It is not possible to have another membership type after being an honorary member and there does not exist a
        // good transition to a different membership type (because of the dates/expiration etc.).
        if (MemberModel::TYPE_HONORARY === $member->getType()) {
            throw new \RuntimeException('Er is geen pad waarop dit lid correct een ander lidmaatschapstype kan krijgen');
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $data = $form->getData();

        // update changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // update expiration and 'membership ends on' date (should become effective at the end of the current
        // association year).
        $expiration = clone $date;

        if ($expiration->format('m') >= 7) {
            $year = (int) $expiration->format('Y') + 2;
        } else {
            $year = (int) $expiration->format('Y') + 1;
        }

        switch ($data['type']) {
            case MemberModel::TYPE_ORDINARY:
                $member->setIsStudying(true);
                $member->setMembershipEndsOn(null);
                $member->setType(MemberModel::TYPE_ORDINARY);
                $year -= 1;
                break;
            case MemberModel::TYPE_EXTERNAL:
                $member->setIsStudying(true);
                $membershipEndsOn = clone $expiration;
                $membershipEndsOn->setDate($year - 1, 7, 1);
                $member->setMembershipEndsOn($membershipEndsOn);
                break;
            case MemberModel::TYPE_GRADUATE:
                $member->setIsStudying(false);
                $membershipEndsOn = clone $expiration;
                $membershipEndsOn->setDate($year - 1, 7, 1);
                $member->setMembershipEndsOn($membershipEndsOn);
                break;
            case MemberModel::TYPE_HONORARY:
                $member->setIsStudying(false);
                // infinity (1000 is close enough, right?)
                $year += 1000;
                $member->setMembershipEndsOn(null);
                // Directly apply the honorary membership type.
                $member->setType(MemberModel::TYPE_HONORARY);
                break;
        }

        // At the end of the current association year.
        $expiration->setDate($year, 7, 1);
        $member->setExpiration($expiration);

        $this->getMemberMapper()->persist($member);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * @param array $data
     * @param int $lidnr
     *
     * @return MemberModel|null
     */
    public function expiration($data, $lidnr)
    {
        $form = $this->getMemberExpirationForm($lidnr);
        // List unpacking is not allowed in PHP 5.6, so it has to be done like this.
        $member = $form['member'];
        $form = $form['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));

        // Make new expiration from previous expiration, but always make sure it is the end of the association year.
        $newExpiration = clone $member->getExpiration();
        $year = (int) $newExpiration->format('Y') + 1;
        $newExpiration->setDate($year, 7, 1);

        $member->setExpiration($newExpiration);

        $this->getMemberMapper()->persist($member);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * Edit address.
     *
     * @param array $data
     * @param int $lidnr
     * @param string $type Address to edit
     *
     * @return Address
     */
    public function editAddress($data, $lidnr, $type)
    {
        $form = $this->getAddressForm($lidnr, $type)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $address = $form->getData();

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('address' => $address));
        $this->getMemberMapper()->persistAddress($address);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('address' => $address));

        return $address;
    }

    /**
     * Add address.
     *
     * @param array $data
     * @param int $lidnr
     * @param string $type Type of the address to add
     *
     * @return Address
     */
    public function addAddress($data, $lidnr, $type)
    {
        $form = $this->getAddressForm($lidnr, $type, true)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $address = $form->getData();

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('address' => $address));
        $this->getMemberMapper()->persistAddress($address);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('address' => $address));

        return $address;
    }

    /**
     * Remove address.
     *
     * @param array $data
     * @param int $lidnr
     * @param string $type Address to remove
     *
     * @return MemberModel
     */
    public function removeAddress($data, $lidnr, $type)
    {
        $formData = $this->getDeleteAddressForm($lidnr, $type);
        $form = $formData['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $address = $formData['address'];
        $member = $address->getMember();

        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('address' => $address));
        $this->getMemberMapper()->removeAddress($address);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('address' => $address));

        return $member;
    }

    /**
     * Subscribe member to mailing lists.
     *
     * @param array $data
     * @param int $lidnr
     *
     * @return MemberModel
     */
    public function subscribeLists($data, $lidnr)
    {
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
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $this->getMemberMapper()->persist($member);
        // TODO: Fix global event listener.
        // $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * Get the member edit form.
     *
     * @param int $lidnr
     *
     * @return array Array with \Database\Form\MemberEdit and MemberModel
     */
    public function getMemberEditForm($lidnr)
    {
        $form = $this->memberEditForm;
        $member = $this->getMember($lidnr);
        $form->bind($member['member']);
        return array(
            'member' => $member['member'],
            'form' => $form
        );
    }

    /**
     * Get the member expiration form.
     *
     * @param int $lidnr
     *
     * @return array
     */
    public function getMemberExpirationForm($lidnr)
    {
        return array(
            'form' => $this->memberExpirationForm,
            'member' => $this->getMember($lidnr)['member']
        );
    }

    /**
     * Get the member type form.
     *
     * @param int $lidnr
     *
     * @return array Array with \Database\Form\MemberType and MemberModel
     */
    public function getMemberTypeForm($lidnr)
    {
        return array(
            'member' => $this->getMember($lidnr)['member'],
            'form' => $this->memberTypeForm,
        );
    }

    /**
     * Get the list edit form.
     *
     * @param int $lidnr
     *
     * @return array
     */
    public function getListForm($lidnr)
    {
        $member = $this->getMember($lidnr);
        $member = $member['member'];
        $lists = $this->mailingListService->getAllLists();

        return array(
            'form' => new MemberListsForm($member, $lists),
            'member' => $member,
            'lists' => $lists
        );
    }

    /**
     * Get the address form.
     *
     * @param int $lidnr
     * @param string $type address type
     * @param boolean $create
     *
     * @return AddressForm
     */
    public function getAddressForm($lidnr, $type, $create = false)
    {
        // find the address
        if ($create) {
            $address = new Address();
            $address->setMember($this->getMemberMapper()->findSimple($lidnr));
            $address->setType($type);
        } else {
            $address = $this->getMemberMapper()->findMemberAddress($lidnr, $type);
        }
        $form = $this->addressForm;
        $form->bind($address);
        return array(
            'form' => $form,
            'address' => $address
        );
    }

    /**
     * Get the delete address form.
     *
     * @param int $lidnr
     * @param string $type address type
     *
     * @return AddressForm
     */
    public function getDeleteAddressForm($lidnr, $type)
    {
        // find the address
        return array(
            'form' => $this->deleteAddressForm,
            'address' => $this->getMemberMapper()->findMemberAddress($lidnr, $type)
        );
    }

    /**
     * Get address export form.
     *
     * @return AddressExportForm
     */
    public function getAddressExportForm()
    {
        return $this->addressExportForm;
    }

    /**
     * Get the member form.
     *
     * @return MemberForm
     */
    public function getMemberForm(): MemberForm
    {
        return $this->memberForm;
    }

    /**
     * Get the member mapper.
     *
     * @return MemberMapper
     */
    public function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }

    /**
     * Get the member mapper.
     *
     * @return ProspectiveMemberMapper
     */
    public function getProspectiveMemberMapper(): ProspectiveMemberMapper
    {
        return $this->prospectiveMemberMapper;
    }

    /**
     * Gets the storage service.
     *
     * @return FileStorageService
     */
    public function getFileStorageService(): FileStorageService
    {
        return $this->fileStorageService;
    }

    /**
     * Get the renderer for the email.
     *
     * @return PhpRenderer
     */
    public function getRenderer(): PhpRenderer
    {
        return $this->viewRenderer;
    }

    /**
     * Get the mail transport.
     *
     * @return TransportInterface
     */
    public function getMailTransport(): TransportInterface
    {
        return $this->mailTransport;
    }
}
