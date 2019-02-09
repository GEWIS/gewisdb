<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Database\Model\Member as MemberModel;
use Database\Model\SubDecision;
use Database\Model\Decision;

use Report\Model\Meeting as ReportMeeting;
use Report\Model\Decision as ReportDecision;

use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Message;
use Zend\ProgressBar\Adapter\Console;
use Zend\ProgressBar\ProgressBar;

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

        $meetings = $mapper->findAll(true, true);

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($meetings));

        $num = 0;
        foreach ($meetings as $meeting) {
            $this->generateMeeting($meeting[0]);
            $progress->update(++$num);
            $em->flush();
            $em->clear();
        }
        $em->flush();
    }

    public function generateMeeting($meeting)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Meeting');

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
            try {
                $this->generateDecision($decision, $reportMeeting);
            } catch (\Exception $e) {
                // send email, something went wrong
                $this->sendDecisionExceptionMail($e, $decision);
                continue;
            }
        }

        $em->persist($reportMeeting);
    }

    public function generateDecision($decision, $reportMeeting = null)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $decRepo = $em->getRepository('Report\Model\Decision');
        if ($reportMeeting === null) {
            $reportMeeting = $em->getRepository('Report\Model\Meeting')->find([
                'type' => $decision->getMeeting()->getType(),
                'number' => $decision->getMeeting()->getNumber()
            ]);
            if ($reportMeeting === null) {
                throw new \LogicException('Decision without meeting');
            }
        }
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
        $content = array();

        foreach ($decision->getSubdecisions() as $subdecision) {
            $this->generateSubDecision($subdecision, $reportDecision);
            $content[] = $subdecision->getContent();
        }

        if (empty($content)) {
            $content[] = '';
        }
        $reportDecision->setContent(implode(' ', $content));

        $em->persist($reportDecision);

    }

    public function generateSubDecision($subdecision, $reportDecision = null) {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $decRepo = $em->getRepository('Report\Model\Decision');
        $subdecRepo = $em->getRepository('Report\Model\SubDecision');
        if ($reportDecision === null) {
            $reportDecision = $decRepo->find(array(
                'meeting_type' => $subdecision->getMeetingType(),
                'meeting_number' => $subdecision->getMeetingNumber(),
                'point' => $subdecision->getDecisionPoint(),
                'number' => $subdecision->getDecisionNumber()
            ));
            if ($reportDecision === null) {
                throw new \LogicException('Decision without meeting');
            }
        }
        $reportSubDecision = $subdecRepo->find(array(
            'meeting_type' => $subdecision->getMeetingType(),
            'meeting_number' => $subdecision->getMeetingNumber(),
            'decision_point' => $subdecision->getDecisionPoint(),
            'decision_number' => $subdecision->getDecisionNumber(),
            'number' => $subdecision->getNumber()
        ));
        if (null === $reportSubDecision) {
            // determine type and create
            $class = get_class($subdecision);
            $class = preg_replace('/^Database/', 'Report', $class);
            $reportSubDecision = new $class();
            $reportSubDecision->setDecision($reportDecision);
            $reportSubDecision->setNumber($subdecision->getNumber());
        }

        if ($subdecision instanceof SubDecision\FoundationReference) {
            $ref = $subdecision->getFoundation();
            $foundation = $subdecRepo->find(array(
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'number' => $ref->getNumber()
            ));
            $reportSubDecision->setFoundation($foundation);
        }

        // transfer specific data
        if ($subdecision instanceof SubDecision\Installation) {
            // installation
            $reportSubDecision->setFunction($subdecision->getFunction());
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
        } else {
            if ($subdecision instanceof SubDecision\Discharge) {
                // discharge
                $ref = $subdecision->getInstallation();
                $installation = $subdecRepo->find(array(
                    'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                    'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                    'decision_point' => $ref->getDecision()->getPoint(),
                    'decision_number' => $ref->getDecision()->getNumber(),
                    'number' => $ref->getNumber()
                ));
                $reportSubDecision->setInstallation($installation);
            } else {
                if ($subdecision instanceof SubDecision\Foundation) {
                    // foundation
                    $reportSubDecision->setAbbr($subdecision->getAbbr());
                    $reportSubDecision->setName($subdecision->getName());
                    $reportSubDecision->setOrganType($subdecision->getOrganType());
                } else {
                    if ($subdecision instanceof SubDecision\Reckoning || $subdecision instanceof SubDecision\Budget) {
                        // budget and reckoning
                        if (null !== $subdecision->getAuthor()) {
                            $reportSubDecision->setAuthor($this->findMember($subdecision->getAuthor()));
                        }
                        $reportSubDecision->setName($subdecision->getName());
                        $reportSubDecision->setVersion($subdecision->getVersion());
                        $reportSubDecision->setDate($subdecision->getDate());
                        $reportSubDecision->setApproval($subdecision->getApproval());
                        $reportSubDecision->setChanges($subdecision->getChanges());
                    } else {
                        if ($subdecision instanceof SubDecision\Board\Installation) {
                            // board installation
                            $reportSubDecision->setFunction($subdecision->getFunction());
                            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
                            $reportSubDecision->setDate($subdecision->getDate());
                        } else {
                            if ($subdecision instanceof SubDecision\Board\Release) {
                                // board release
                                $ref = $subdecision->getInstallation();
                                $installation = $subdecRepo->find(array(
                                    'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                                    'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                                    'decision_point' => $ref->getDecision()->getPoint(),
                                    'decision_number' => $ref->getDecision()->getNumber(),
                                    'number' => $ref->getNumber()
                                ));
                                $reportSubDecision->setInstallation($installation);
                                $reportSubDecision->setDate($subdecision->getDate());
                            } else {
                                if ($subdecision instanceof SubDecision\Board\Discharge) {
                                    $ref = $subdecision->getInstallation();
                                    $installation = $subdecRepo->find(array(
                                        'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                                        'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                                        'decision_point' => $ref->getDecision()->getPoint(),
                                        'decision_number' => $ref->getDecision()->getNumber(),
                                        'number' => $ref->getNumber()
                                    ));
                                    $reportSubDecision->setInstallation($installation);
                                } else {
                                    if ($subdecision instanceof SubDecision\Destroy) {
                                        $ref = $subdecision->getTarget();
                                        $target = $decRepo->find(array(
                                            'meeting_type' => $ref->getMeeting()->getType(),
                                            'meeting_number' => $ref->getMeeting()->getNumber(),
                                            'point' => $ref->getPoint(),
                                            'number' => $ref->getNumber()
                                        ));
                                        $reportSubDecision->setTarget($target);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Abolish decisions are handled by foundationreference
        // Other decisions don't need special handling

        // for any decision, make sure the content is filled
        $cnt = $subdecision->getContent();
        if (null === $cnt) {
            $cnt = '';
        }
        $reportSubDecision->setContent($cnt);
        $em->persist($reportSubDecision);
        return $reportSubDecision;
    }

    public function deleteDecision($decision)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $reportDecision = $em->getRepository('Report\Model\Decision')->find(array(
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'point' => $decision->getPoint(),
            'number' => $decision->getNumber()
        ));
        $em->remove($reportDecision);
    }
    /**
     * Obtain the correct member, given a database member.
     *
     * @param MemberModel $member
     *
     * @return \Report\Model\Member
     */
    public function findMember(MemberModel $member)
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\Member');

        return $repo->find($member->getLidnr());
    }

    /**
     * Send an email about that something went wrong.
     *
     * @param Exception $e
     * @param Decision $decision
     */
    public function sendDecisionExceptionMail(\Exception $e, Decision $decision)
    {
        $config = $this->getServiceManager()->get('config');
        $config = $config['email'];

        $meeting = $decision->getMeeting();
        $body = <<<BODYTEXT
Hallo Belangrijke Database Mensen,

Ik ben een fout tegen gekomen tijdens het processen:

{$e->getMessage()}

Dit gebeurde tijdens het processen van besluit {$meeting->getType()} {$meeting->getNumber()}.{$decision->getNumber()}.{$decision->getPoint()}.

Met vriendelijke groet,

De GEWIS Database

PS: extra info over de fout:

{$e->getTraceAsString()}
BODYTEXT;

        $message = new Message();
        $message->setBody($body);
        $message->setFrom($config['from']);
        $message->addTo($config['to']['report_error']);
        $message->setSubject('Database fout');

        $this->getMailTransport()->send($message);
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
