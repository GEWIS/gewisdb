<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\Address;
use Database\Model\Member as MemberModel;

class Member extends AbstractService
{

    /**
     * Subscribe a member.
     *
     * @param array $data
     *
     * @return Member member, null if failed.
     */
    public function subscribe($data)
    {
        $form = $this->getMemberForm();

        $form->bind(new MemberModel());

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // set some extra data
        $member = $form->getData();

        // generation is the current year
        $member->setGeneration((int) date('Y'));

        // by default, we only add ordinary members
        $member->setType(MemberModel::TYPE_ORDINARY);

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
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
        return $this->getMemberMapper()->find($id);
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

        // update changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
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
        $form->bind($member);
        return array(
            'member' => $member,
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
        $form->bind($member);
        return array(
            'member' => $member,
            'form' => $form
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
            $address->setMember($this->getMemberMapper()->find($lidnr));
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
}
