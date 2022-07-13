<?php

namespace Checker\Service;

use Application\Service\AbstractService;
use Checker\Mapper\Installation as InstallationMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Installation as InstallationModel;

class Installation extends AbstractService
{
    /**
     * Fetch all the existing organs after $meeting
     *
     * @param MeetingModel $meeting
     *
     * @return array \Database\Model\SubDecision\Installation
     */
    public function getAllInstallations(MeetingModel $meeting)
    {
        /** @var InstallationMapper $mapper */
        $mapper = $this->getServiceManager()->get('checker_mapper_installation');

        $createdMembers = $mapper->getAllInstallationsInstalled($meeting);
        $deletedMembers = $mapper->getAllInstallationsDischarged($meeting);

        $members = array();
        foreach ($createdMembers as $cm) {
            $members[$this->getHash($cm)] = $cm;
        }

        foreach ($deletedMembers as $dm) {
            $creation = $dm->getInstallation();
            $hash = $this->getHash($creation);

            if (isset($members[$hash])) {
                unset($members[$hash]);
            }
        }

        return $members;
    }

    /**
     * Returns the different roles for each user in each organ
     * @param MeetingModel $meeting
     * @return array \Database\Model\SubDecision\Installation in the form:
     * [
     *      'organName' => [
     *          'memberId' => [
     *              'function' => Installation
     *          ]
     *      ]
     * ]
     */
    public function getCurrentRolesPerOrgan(MeetingModel $meeting)
    {
        $installations = $this->getAllInstallations($meeting);

        $roles = [];

        foreach ($installations as $installation) {
            $memberId = $installation->getMember()->getLidNr();
            $function = $installation->getFunction();
            $organName = $installation->getFoundation()->getAbbr();

            $roles[$organName][$memberId][$function] = $installation;
        }

        return $roles;
    }

    /**
     * Get all members who are currently installed in an organ.
     *
     * @param MeetingModel|null $meeting
     *
     * @return array
     */
    public function getActiveMembers($meeting)
    {
        if (null === $meeting) {
            return [];
        }

        $installations = $this->getAllInstallations($meeting);

        $members = [];
        foreach ($installations as $installation) {
            $member = $installation->getMember()->getLidnr();

            // Doing checks against the keys is a lot faster, and we do not need a lot of information.
            if (!array_key_exists($member, $members)) {
                $members[$member] = '';
            }
        }

        return $members;
    }

    /**
     * Returns a unique hash for a subdecision (Needed for matching subdecisions)
     *
     * @param InstallationModel $installation Decision to hash for
     * @return string Unique hash for $d
     */
    private function getHash(InstallationModel $installation)
    {
        return sprintf(
            '%s%d%d%d%d',
            $installation->getMeetingType(),
            $installation->getMeetingNumber(),
            $installation->getDecisionPoint(),
            $installation->getDecisionNumber(),
            $installation->getNumber()
        );
    }
}
