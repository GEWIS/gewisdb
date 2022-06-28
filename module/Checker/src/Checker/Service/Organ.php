<?php
namespace Checker\Service;

use Application\Service\AbstractService;
use Checker\Mapper\Organ as OrganMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Abrogation as AbrogationModel;
use Database\Model\SubDecision\Foundation as FoundationModel;

class Organ extends AbstractService
{
    /**
     * Get the names of all the organs after $meeting
     *
     * @param MeetingModel $meeting
     * @return array string
     */
    public function getAllOrgans(MeetingModel $meeting)
    {
        /** @var OrganMapper $mapper */
        $mapper = $this->getServiceManager()->get('checker_mapper_organ');
        $organFoundations = $mapper->getAllOrganFoundations($meeting);
        $organAbrogations = $mapper->getAllOrganAbrogations($meeting);

        $hashedOrganFoundations = array_map(
        /** @var FoundationModel $organ */
            function ($organ) {
                return $this->getHash($organ);
            },
            $organFoundations
        );

        $hashedOrganAbrogations = array_map(
            /** @var AbrogationModel $organ */
            function ($organ) {
                return $this->getHash($organ->getFoundation());
            },
            $organAbrogations
        );

        return array_diff($hashedOrganFoundations, $hashedOrganAbrogations);
    }

    public function getOrgansCreatedAtMeeting(MeetingModel $meeting)
    {
        /** @var OrganMapper $mapper */
        $mapper = $this->getServiceManager()->get('checker_mapper_organ');

        return $mapper->getOrgansCreatedAtMeeting($meeting);
    }

    /**
     * @param FoundationModel $foundation
     *
     * @return string
     */
    public function getHash(FoundationModel $foundation)
    {
        return sprintf(
            '%s%d%d%d%d',
            $foundation->getMeetingType(),
            $foundation->getMeetingNumber(),
            $foundation->getDecisionPoint(),
            $foundation->getDecisionNumber(),
            $foundation->getNumber()
        );
    }
}
