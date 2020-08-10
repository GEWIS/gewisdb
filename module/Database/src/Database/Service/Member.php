<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\Address;
use Database\Model\Member as MemberModel;
use Database\Model\MemberTemp as MemberTempModel;
use Database\Model\MailingList;

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
     * @return MemberTempModel member, null if failed.
     */
    public function subscribe($data)
    {
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

        $form = $this->getMemberForm();

        $form->bind(new MemberTempModel());

        $noiban = false;

        if (isset($data['studentAddress']) && isset($data['studentAddress']['street']) && !empty($data['studentAddress']['street'])) {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tuenumber', 'study', 'email', 'birth',
                'studentAddress', 'agreed', 'iban', 'signature'
            ));
        } else {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tuenumber', 'study', 'email', 'birth',
                'agreed', 'iban', 'signature'
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
        $memberTemp = $form->getData();

        if ($noiban) {
            $memberTemp->setIban(null);
        }

        // find if there is an earlier member with the same email or name
        if ($this->getMemberMapper()->hasMemberWith($memberTemp->getEmail())) {
            $form->get('email')->setMessages([
                'There already is a member with this email address.'
            ]);
            return null;
        }

        if (!is_numeric($memberTemp->getTuenumber())) {
            $memberTemp->setTuenumber(0);
        }

        // generation is the current year
        $memberTemp->setGeneration((int) date('Y'));

        // by default, we only add ordinary members
        $memberTemp->setType(MemberModel::TYPE_ORDINARY);

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $memberTemp->setChangedOn($date);

        // store the address
        $address = $form->get('studentAddress')->getObject();
        $memberTemp->setAddress($address);

        // check mailing lists
        foreach ($form->getLists() as $list) {
            if ($form->get('list-' . $list->getName())->isChecked()) {
                $memberTemp->addList($list);
            }
        }
        // subscribe to default mailing lists not on the form
        $mailingMapper = $this->getServiceManager()->get('database_mapper_mailinglist');
        foreach ($mailingMapper->findDefault() as $list) {
            $memberTemp->addList($list);
        }

        // handle signature
        $signature = $form->get('signature')->getValue();
        if (!is_null($signature)) {
            $path = $this->getFileStorageService()->storeUploadedData($signature, 'png');
            $memberTemp->setSignature($path);
        }

        $this->getMemberTempMapper()->persist($memberTemp);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $memberTemp));

        return $memberTemp;
    }

    /**
     * @param MemberTempModel $memberTemp
     * @return MemberModel|null
     */
    public function finalizeSubscription($memberTemp)
    {
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

        $form = $this->getMemberForm();

        $form->bind(new MemberModel());

        // Fill in the address in the form again
        $data = $memberTemp->toArray();
        unset($data['lidnr']);
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $member = $form->getData();

        // Copy all remaining information
        $member->setTuenumber($memberTemp->getTuenumber());
        $member->setGeneration($memberTemp->getGeneration());
        $member->setType($memberTemp->getType());

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // add mailing lists
        foreach ($memberTemp->getLists() as $list) {
            $member->addList($list);
        }

        $this->getMemberMapper()->persist($member);
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
     * Get temporary member info.
     *
     * @param int $id
     *
     * @return MemberTempModel
     */
    public function getMemberTemp($id)
    {
        try {
            return array(
                'member' => $this->getMemberTempMapper()->find($id),
                'simple' => false
            );
        } catch (\Doctrine\ORM\NoResultException $e) {
            return array(
                'member' => $this->getMemberTempMapper()->findSimple($id),
                'simple' => true
            );
        }
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
     * Search for a temporary member.
     *
     * @param string $query
     */
    public function searchTemp($query)
    {
        return $this->getMemberTempMapper()->search($query);
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
     * @param MemberTempModel $member
     */
    public function removeTemp(MemberTempModel $member)
    {
         $this->getMemberTempMapper()->remove($member);
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
     * @return \Database\Mapper\MemberTemp
     */
    public function getMemberTempMapper()
    {
        return $this->getServiceManager()->get('database_mapper_member_temp');
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
}
