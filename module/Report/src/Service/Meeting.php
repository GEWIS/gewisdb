<?php

declare(strict_types=1);

namespace Report\Service;

use Database\Mapper\Meeting as MeetingMapper;
use Database\Model\Decision as DatabaseDecisionModel;
use Database\Model\Meeting as DatabaseMeetingModel;
use Database\Model\Member as DatabaseMemberModel;
use Database\Model\SubDecision as DatabaseSubDecisionModel;
use Doctrine\ORM\EntityManager;
use Exception;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use LogicException;
use Report\Model\Decision as ReportDecisionModel;
use Report\Model\Meeting as ReportMeetingModel;
use Report\Model\Member as ReportMemberModel;
use Report\Model\SubDecision as ReportSubDecisionModel;
use Throwable;

use function array_reverse;
use function count;
use function implode;
use function preg_replace;

class Meeting
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly MeetingMapper $meetingMapper,
        private readonly EntityManager $emReport,
        private readonly array $config,
        private readonly TransportInterface $mailTransport,
    ) {
    }

    /**
     * Export meetings.
     */
    public function generate(): void
    {
        // simply export every meeting
        $meetings = $this->meetingMapper->findAll(true, true);

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($meetings));

        $num = 0;
        foreach ($meetings as $meeting) {
            $this->generateMeeting($meeting[0]);
            $this->emReport->flush();
            $this->emReport->clear();
            $progress->update(++$num);
        }

        $this->emReport->flush();
        $progress->finish();
    }

    public function generateMeeting(DatabaseMeetingModel $meeting): void
    {
        $repo = $this->emReport->getRepository(ReportMeetingModel::class);

        $reportMeeting = $repo->find([
            'type' => $meeting->getType(),
            'number' => $meeting->getNumber(),
        ]);

        if (null === $reportMeeting) {
            $reportMeeting = new ReportMeetingModel();
        }

        $reportMeeting->setType($meeting->getType());
        $reportMeeting->setNumber($meeting->getNumber());
        $reportMeeting->setDate($meeting->getDate());

        foreach ($meeting->getDecisions() as $decision) {
            try {
                $this->generateDecision($decision, $reportMeeting);
            } catch (Throwable $e) {
                // send email, something went wrong
                $this->sendDecisionExceptionMail($e, $decision);
                continue;
            }
        }

        $this->emReport->persist($reportMeeting);
    }

    public function generateDecision(
        DatabaseDecisionModel $decision,
        ?ReportMeetingModel $reportMeeting = null,
    ): void {
        $decRepo = $this->emReport->getRepository(ReportDecisionModel::class);

        if (null === $reportMeeting) {
            $reportMeeting = $this->emReport->getRepository(ReportMeetingModel::class)->find([
                'type' => $decision->getMeeting()->getType(),
                'number' => $decision->getMeeting()->getNumber(),
            ]);

            if (null === $reportMeeting) {
                throw new LogicException('Decision without meeting');
            }
        }

        // see if decision exists
        $reportDecision = $decRepo->find([
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'point' => $decision->getPoint(),
            'number' => $decision->getNumber(),
        ]);

        if (null === $reportDecision) {
            $reportDecision = new ReportDecisionModel();
            $reportDecision->setMeeting($reportMeeting);
        }

        $reportDecision->setPoint($decision->getPoint());
        $reportDecision->setNumber($decision->getNumber());
        $content = [];

        foreach ($decision->getSubdecisions() as $subdecision) {
            $this->generateSubDecision($subdecision, $reportDecision);
            $content[] = $subdecision->getContent();
        }

        if (empty($content)) {
            $content[] = '';
        }

        $reportDecision->setContent(implode(' ', $content));

        $this->emReport->persist($reportDecision);
    }

    /**
     * @psalm-template T of ReportSubDecisionModel
     *
     * @psalm-return T
     */
    public function generateSubDecision(
        DatabaseSubDecisionModel $subdecision,
        ?ReportDecisionModel $reportDecision = null,
    ): ReportSubDecisionModel {
        $decRepo = $this->emReport->getRepository(ReportDecisionModel::class);
        $subdecRepo = $this->emReport->getRepository(ReportSubDecisionModel::class);

        if (null === $reportDecision) {
            $reportDecision = $decRepo->find([
                'meeting_type' => $subdecision->getMeetingType(),
                'meeting_number' => $subdecision->getMeetingNumber(),
                'point' => $subdecision->getDecisionPoint(),
                'number' => $subdecision->getDecisionNumber(),
            ]);

            if (null === $reportDecision) {
                throw new LogicException('Decision without meeting');
            }
        }

        /** @var T|null $reportSubDecision */
        $reportSubDecision = $subdecRepo->find([
            'meeting_type' => $subdecision->getMeetingType(),
            'meeting_number' => $subdecision->getMeetingNumber(),
            'decision_point' => $subdecision->getDecisionPoint(),
            'decision_number' => $subdecision->getDecisionNumber(),
            'number' => $subdecision->getNumber(),
        ]);

        if (null === $reportSubDecision) {
            // determine type and create
            $class = $subdecision::class;
            /** @var class-string<T> $class */
            $class = preg_replace('/^Database/', 'Report', $class);
            /** @var T $reportSubDecision */
            $reportSubDecision = new $class();
            $reportSubDecision->setDecision($reportDecision);
            $reportSubDecision->setNumber($subdecision->getNumber());
        }

        if ($subdecision instanceof DatabaseSubDecisionModel\FoundationReference) {
            $ref = $subdecision->getFoundation();
            $foundation = $subdecRepo->find([
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setFoundation($foundation);
        }

        // transfer specific data
        if ($subdecision instanceof DatabaseSubDecisionModel\Installation) {
            // installation
            $reportSubDecision->setFunction($subdecision->getFunction());
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
        } elseif (
            $subdecision instanceof DatabaseSubDecisionModel\Reappointment
            || $subdecision instanceof DatabaseSubDecisionModel\Discharge
        ) {
            // reappointment and discharge
            $ref = $subdecision->getInstallation();
            $installation = $subdecRepo->find([
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setInstallation($installation);
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Foundation) {
            // foundation
            $reportSubDecision->setAbbr($subdecision->getAbbr());
            $reportSubDecision->setName($subdecision->getName());
            $reportSubDecision->setOrganType($subdecision->getOrganType());
        } elseif (
            $subdecision instanceof DatabaseSubDecisionModel\Reckoning
            || $subdecision instanceof DatabaseSubDecisionModel\Budget
            || $subdecision instanceof DatabaseSubDecisionModel\OrganRegulation
        ) {
            // budget and reckoning
            if (null !== $subdecision->getMember()) {
                $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            }

            $reportSubDecision->setName($subdecision->getName());
            $reportSubDecision->setVersion($subdecision->getVersion());
            $reportSubDecision->setDate($subdecision->getDate());
            $reportSubDecision->setApproval($subdecision->getApproval());
            $reportSubDecision->setChanges($subdecision->getChanges());

            // Specific to the `OrganRegulation`s, set the type of organ
            if ($subdecision instanceof DatabaseSubDecisionModel\OrganRegulation) {
                $reportSubDecision->setOrganType($subdecision->getOrganType());
            }
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Board\Installation) {
            // board installation
            $reportSubDecision->setFunction($subdecision->getFunction());
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            $reportSubDecision->setDate($subdecision->getDate());
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Board\Release) {
            // board release
            $ref = $subdecision->getInstallation();
            $installation = $subdecRepo->find([
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setInstallation($installation);
            $reportSubDecision->setDate($subdecision->getDate());
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Board\Discharge) {
            $ref = $subdecision->getInstallation();
            $installation = $subdecRepo->find([
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setInstallation($installation);
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Key\Granting) {
            // key code granting
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            $reportSubDecision->setUntil($subdecision->getUntil());
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Key\Withdrawal) {
            // key code withdrawal
            $ref = $subdecision->getGranting();
            $granting = $subdecRepo->find([
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setGranting($granting);
            $reportSubDecision->setWithdrawnOn($subdecision->getWithdrawnOn());
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Destroy) {
            $ref = $subdecision->getTarget();
            $target = $decRepo->find([
                'meeting_type' => $ref->getMeeting()->getType(),
                'meeting_number' => $ref->getMeeting()->getNumber(),
                'point' => $ref->getPoint(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setTarget($target);
        }

        // Abolish decisions are handled by foundationreference
        // Other decisions don't need special handling

        // for any decision, make sure the content is filled
        $reportSubDecision->setContent($subdecision->getContent());
        $this->emReport->persist($reportSubDecision);

        return $reportSubDecision;
    }

    public function deleteDecision(DatabaseDecisionModel $decision): void
    {
        $reportDecision = $this->emReport->getRepository(ReportDecisionModel::class)->find([
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'point' => $decision->getPoint(),
            'number' => $decision->getNumber(),
        ]);

        foreach (array_reverse($reportDecision->getSubdecisions()->toArray()) as $subDecision) {
            $this->deleteSubDecision($subDecision);
        }

        $this->emReport->remove($reportDecision);
    }

    public function deleteSubDecision(ReportSubDecisionModel $subDecision): void
    {
        switch (true) {
            case $subDecision instanceof ReportSubDecisionModel\Destroy:
                throw new Exception('Deletion of destroy decisions not implemented');

            case $subDecision instanceof ReportSubDecisionModel\Reappointment:
                $installation = $subDecision->getInstallation();
                $installation->removeReappointment($subDecision);

                break;
            case $subDecision instanceof ReportSubDecisionModel\Discharge:
                $installation = $subDecision->getInstallation();
                $installation->clearDischarge();
                $organMember = $subDecision->getInstallation()->getOrganMember();
                $organMember->setDischargeDate(null);

                break;
            case $subDecision instanceof ReportSubDecisionModel\Foundation:
                $organ = $subDecision->getOrgan();
                $this->emReport->remove($organ);
                break;
            case $subDecision instanceof ReportSubDecisionModel\Installation:
                $organMember = $subDecision->getOrganMember();

                if (null !== $organMember) {
                    $this->emReport->remove($organMember);
                }

                break;
            case $subDecision instanceof ReportSubDecisionModel\Board\Installation:
                $boardMember = $subDecision->getBoardMember();
                $this->emReport->remove($boardMember);
                break;
            case $subDecision instanceof ReportSubDecisionModel\Board\Release:
                $installation = $subDecision->getInstallation();
                $installation->clearRelease();

                $boardMember = $installation->getBoardMember();
                $boardMember->setReleaseDate(null);
                break;
            case $subDecision instanceof ReportSubDecisionModel\Board\Discharge:
                $installation = $subDecision->getInstallation();
                $installation->clearDischarge();

                $boardMember = $installation->getBoardMember();
                $boardMember->setDischargeDate(null);
                break;
            case $subDecision instanceof ReportSubDecisionModel\Key\Granting:
                $keyholder = $subDecision->getKeyholder();
                $this->emReport->remove($keyholder);
                break;
            case $subDecision instanceof ReportSubDecisionModel\Key\Withdrawal:
                $granting = $subDecision->getGranting();
                $granting->clearWithdrawal();

                $keyholder = $granting->getKeyholder();
                $keyholder->setWithdrawnDate(null);
                break;
        }

        $this->emReport->remove($subDecision);
    }

    /**
     * Obtain the correct member, given a database member. Because these members are generated based on what happens in
     * the `Database` module, this cannot return `null`.
     *
     * @psalm-ignore-nullable-return
     */
    public function findMember(DatabaseMemberModel $member): ReportMemberModel
    {
        $repo = $this->emReport->getRepository(ReportMemberModel::class);

        return $repo->find($member->getLidnr());
    }

    /**
     * Send an email about that something went wrong.
     */
    public function sendDecisionExceptionMail(
        Throwable $e,
        DatabaseDecisionModel $decision,
    ): void {
        $config = $this->config['email'];

        $meeting = $decision->getMeeting();
        $body = <<<BODYTEXT
            Hallo Belangrijke Database Mensen,

            Ik ben een fout tegen gekomen tijdens het processen:

            {$e->getMessage()}

            Dit gebeurde tijdens het processen van onderstaand besluit:
            {$meeting->getType()->value} {$meeting->getNumber()}.{$decision->getNumber()}.{$decision->getPoint()}.

            Met vriendelijke groet,

            De GEWIS Database

            PS: extra info over de fout:

            {$e->getTraceAsString()}
            BODYTEXT;

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setBody($body);
        $message->setFrom($config['from']['address'], $config['from']['name']);
        $message->setTo($config['to']['report_error']['address'], $config['to']['report_error']['name']);
        $message->setSubject('Database fout');

        $this->mailTransport->send($message);
    }
}
