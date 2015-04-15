<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Report\Model\Meeting as ReportMeeting;

class Meeting extends AbstractService
{

    /**
     * Export meetings.
     */
    public function generate()
    {
        $mapper = $this->getMeetingMapper();

        // simply export every meeting
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Meeting');

        foreach ($mapper->findAll() as $meeting) {
            $meeting = $meeting[0];

            $reportMeeting = $repo->find(array(
                'type' => $meeting->getType(),
                'number' => $meeting->getNumber()
            ));

            if ($reportMeeting == null) {
                $reportMeeting = new ReportMeeting();
            }

            $reportMeeting->setType($meeting->getType());
            $reportMeeting->setNumber($meeting->getNumber());
            $reportMeeting->setDate($meeting->getDate());

            foreach ($meeting->getDecisions() as $decision) {
                var_dump($decision);
            }

            $em->persist($reportMeeting);
        }
        $em->flush();
    }

    /**
     * Get the meeting mapper.
     *
     * @return \Database\Mapper\Meeting
     */
    public function getMeetingMapper()
    {
        return $this->getServiceManager()->get('database_mapper_meeting');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
