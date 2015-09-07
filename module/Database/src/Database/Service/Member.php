<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\Address;
use Database\Model\Member as MemberModel;
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
     * @return Member member, null if failed.
     */
    public function subscribe($data)
    {
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

        $form = $this->getMemberForm();

        $form->bind(new MemberModel());

        if (isset($data['studentAddress']) && isset($data['studentAddress']['street']) && !empty($data['studentAddress']['street'])) {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tuenumber', 'study', 'email', 'birth',
                'homeAddress', 'studentAddress', 'agreed'
            ));
        } else {
            $form->setValidationGroup(array(
                'lastName', 'middleName', 'initials', 'firstName',
                'gender', 'tuenumber', 'study', 'email', 'birth',
                'homeAddress', 'agreed'
            ));
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // set some extra data
        $member = $form->getData();

        if (!is_numeric($member->getTuenumber())) {
            $member->setTuenumber(0);
        }

        // generation is the current year
        $member->setGeneration((int) date('Y'));

        // by default, we only add ordinary members
        $member->setType(MemberModel::TYPE_ORDINARY);

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // check mailing lists
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

        $currentLists = $member->getLists();
        $member->clearLists();
        $listArr = [];
        
        foreach ($lists as $list) {
            $name = 'list-' . $list->getName();
            if (isset($data[$name]) && $data[$name]) {
                if(!$currentLists->contains($list)) {
                    // Add the member to the mailinglist
                    $command = "/usr/sbin/add_members";
                    $arguments = "-r- -n -N " . $list;
                    $this->runProcess($command, $arguments, $member->getEmail());
                }
                $listArr[] = $list->getName();
                $member->addList($list);
            }
        }
        
        $currentLists->map(
            function($entry) use ($listArr){
                if(!in_array($entry->getName(), $listArr)){
                    // Delete member from the mailinglist
                    $command = "/usr/sbin/remove_members";
                    $arguments = "-f- -n -N " . $list;
                    $this->runProcess($command, $arguments, $member->getEmail());
                }
            }
        );

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

    /**
     * Execute a command and send some info over Standard In
     *
     * @return array containing the stdOut response and the responseCode
     */
    private function runProcess($process, $stdIn)
    {

        if(!is_executable($process))
            return false;

        $descriptorspec = array(
                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                );

        $process = proc_open($process, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            fwrite($pipes[0], $stdIn."\n");
            fclose($pipes[0]);

            $stdOut = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);

            return [
                "stdOut" => $stdOut,
                "returnCode" => $return_value,
            ];
        }
    }
}
