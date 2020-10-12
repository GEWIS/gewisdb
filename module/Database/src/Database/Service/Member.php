<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\Address;
use Database\Model\Member as MemberModel;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Mime;
use Zend\View\Model\ViewModel;
use Zend\Mail\Message;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;

class Member extends AbstractService
{

    /**
     * List form.
     *
     * @var \Database\Form\MemberLists
     */
    protected $listForm;

    /**
     * Subscribe a member.
     *
     * @param array $data
     *
     * @return ProspectiveMemberModel member, null if failed.
     */
    public function subscribe($data)
    {
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

        $form = $this->getMemberForm();

        $form->bind(new ProspectiveMemberModel());

        $noiban = false;

        if (isset($data['studentAddress']) && isset($data['studentAddress']['street']) && !empty($data['studentAddress']['street'])) {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tuenumber', 'study', 'email', 'birth',
                'studentAddress', 'agreed', 'iban', 'signature', 'signatureLocation'
            ));
        } else {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tuenumber', 'study', 'email', 'birth',
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
        $prospectiveMember = $form->getData();

        if ($noiban) {
            $prospectiveMember->setIban(null);
        }

        // find if there is an earlier member with the same email or name
        if ($this->getMemberMapper()->hasMemberWith($prospectiveMember->getEmail())) {
            $form->get('email')->setMessages([
                'There already is a member with this email address.'
            ]);
            return null;
        }

        if (!is_numeric($prospectiveMember->getTuenumber())) {
            $prospectiveMember->setTuenumber(0);
        }

        // generation is the current year
        $prospectiveMember->setGeneration((int) date('Y'));

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
        $mailingMapper = $this->getServiceManager()->get('database_mapper_mailinglist');
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
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $prospectiveMember));

        return $prospectiveMember;
    }

    /**
     * Send an email about the newly subscribed member to the new member and the secretary
     *
     * @param ProspectiveMemberModel $member
     */
    public function sendMemberSubscriptionEmail(ProspectiveMemberModel $member)
    {
        $config = $this->getServiceManager()->get('config');
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
     * @param ProspectiveMemberModel $prospectiveMember
     * @return MemberModel|null
     */
    public function finalizeSubscription($prospectiveMember)
    {
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

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

        $member = $form->getData();

        // Copy all remaining information
        $member->setTuenumber($prospectiveMember->getTuenumber());
        $member->setGeneration($prospectiveMember->getGeneration());
        $member->setType($prospectiveMember->getType());

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // add mailing lists
        foreach ($form->getLists() as $list) {
            if ($form->get('list-' . $list->getName())->isChecked()) {
                $member->addList($list);
            }
        }
        // subscribe to default mailing lists not on the form
        $mailingMapper = $this->getServiceManager()->get('database_mapper_mailinglist');
        foreach ($mailingMapper->findDefault() as $list) {
            $member->addList($list);
        }

        // Remove prospectiveMember model
        $this->getMemberMapper()->persist($member);

        $this->removeProspective($prospectiveMember);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

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
     * @return ProspectiveMemberModel
     */
    public function getProspectiveMember($id)
    {
        return array(
            'member' => $this->getProspectiveMemberMapper()->find($id)
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
        $member->setEmail('');
        $member->setGender(MemberModel::GENDER_OTHER);
        $member->setGeneration(0);
        $member->setTuenumber(null);
        $member->setStudy(null);
        $member->setChangedOn(new \DateTime());
        $member->setBirth(new \DateTime('0001-01-01 00:00:00'));
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

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $this->getMemberMapper()->persist($member);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * Edit membership.
     *
     * @param array $data
     * @param int $lidnr member to edit
     *
     * @return MemberModel
     */
    public function membership($data, $lidnr)
    {
        $form = $this->getMemberTypeForm($lidnr)['form'];

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $member = $form->getData();

        // update changed on date, always changes the previous first of july
        $date = new \DateTime();
        $date->setTime(0, 0);
        if ($date->format('m') >= 7) {
            $year = $date->format('Y');
        } else {
            $year = $date->format('Y') - 1;
        }
        $date->setDate($year, 7, 1);
        $member->setChangedOn($date);

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $this->getMemberMapper()->persist($member);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

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

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('address' => $address));
        $this->getMemberMapper()->persistAddress($address);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('address' => $address));

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

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('address' => $address));
        $this->getMemberMapper()->persistAddress($address);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('address' => $address));

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

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('address' => $address));
        $this->getMemberMapper()->removeAddress($address);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('address' => $address));

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
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $this->getMemberMapper()->persist($member);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

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
        $form = $this->getServiceManager()->get('database_form_memberedit');
        $member = $this->getMember($lidnr);
        $form->bind($member['member']);
        return array(
            'member' => $member['member'],
            'form' => $form
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
        $form = $this->getServiceManager()->get('database_form_membertype');
        $member = $this->getMember($lidnr);
        $form->bind($member['member']);
        return array(
            'member' => $member['member'],
            'form' => $form
        );
    }

    /**
     * Get the list edit form.
     *
     * @param int $lidnr
     *
     * @return \Database\Form\MemberLists
     */
    public function getListForm($lidnr)
    {
        $member = $this->getMember($lidnr);
        $member = $member['member'];
        $lists = $this->getServiceManager()->get('database_service_mailinglist')->getAllLists();

        if (null === $this->listForm) {
            $this->listForm = new \Database\Form\MemberLists($member, $lists);
        }

        return array(
            'form' => $this->listForm,
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
     * @return \Database\Form\Address
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
        $form = $this->getServiceManager()->get('database_form_address');
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
     * @return \Database\Form\Address
     */
    public function getDeleteAddressForm($lidnr, $type)
    {
        // find the address
        $address = $this->getMemberMapper()->findMemberAddress($lidnr, $type);
        $form = $this->getServiceManager()->get('database_form_deleteaddress');
        return array(
            'form' => $form,
            'address' => $address
        );
    }

    /**
     * Get address export form.
     *
     * @return \Database\Form\AddressExport
     */
    public function getAddressExportForm()
    {
        return $this->getServiceManager()->get('database_form_addressexport');
    }

    /**
     * Get the member form.
     *
     * @return \Database\Form\Member
     */
    public function getMemberForm()
    {
        return $this->getServiceManager()->get('database_form_member');
    }

    /**
     * Get the member mapper.
     *
     * @return \Database\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('database_mapper_member');
    }

    /**
     * Get the member mapper.
     *
     * @return \Database\Mapper\ProspectiveMember
     */
    public function getProspectiveMemberMapper()
    {
        return $this->getServiceManager()->get('database_mapper_prospective_member');
    }

    /**
     * Gets the storage service.
     *
     * @return \Application\Service\FileStorage
     */
    public function getFileStorageService()
    {
        return $this->getServiceManager()->get('application_service_storage');
    }

    /**
     * Get the renderer for the email.
     *
     * @return PhpRenderer
     */
    public function getRenderer()
    {
        return $this->sm->get('view_manager')->getRenderer();
    }

    /**
     * Get the mail transport.
     *
     * @return TransportInterface
     */
    public function getMailTransport()
    {
        return $this->getServiceManager()->get('database_mail_transport');
    }
}
