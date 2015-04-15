<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Report\Model\Meeting as ReportMeeting;
use Report\Model\Decision as ReportDecision;

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
        $decRepo = $em->getRepository('Report\Model\Decision');

        foreach ($mapper->findAll() as $meeting) {
            $meeting = $meeting[0];

            $reportMeeting = $repo->find(array(
                'type' => $meeting->getType(),
                'number' => $meeting->getNumber()
            ));

            if ($reportMeeting === null) {
                $reportMeeting = new ReportMeeting();
            }

            $reportMeeting->setType($meeting->getType());
            $reportMeeting->setNumber($meeting->getNumber());
            $reportMeeting->setDate($meeting->getDate());

            foreach ($meeting->getDecisions() as $decision) {
                // see if decision exists
                $reportDecision = $decRepo->find(array(
                    'meeting_type' => $decision->getMeeting()->getType(),
                    'meeting_number' => $decision->getMeeting()->getNumber(),
                    'point' => $decision->getPoint(),
                    'number' => $decision->getNumber()
                ));
                if (null === $reportDecision) {
                    $reportDecision = new ReportDecision();
                    $reportDecision->setMeeting($reportMeeting);
                }
                $reportDecision->setPoint($decision->getPoint());
                $reportDecision->setNumber($decision->getNumber());
                $reportDecision->setContent("");

                // TODO: subdecisions and content

                $em->persist($reportDecision);
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
