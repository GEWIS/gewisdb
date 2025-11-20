<?php

declare(strict_types=1);

namespace Report\Service;

use Application\Model\Enums\AppLanguages;
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
use Laminas\Mvc\I18n\Translator;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use LogicException;
use Report\Model\Decision as ReportDecisionModel;
use Report\Model\Meeting as ReportMeetingModel;
use Report\Model\Member as ReportMemberModel;
use Report\Model\SubDecision as ReportSubDecisionModel;
use RuntimeException;
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
        private readonly Translator $translator,
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
        $contentNL = [];
        $contentEN = [];

        foreach ($decision->getSubdecisions() as $subdecision) {
            $this->generateSubDecision($subdecision, $reportDecision);
            $contentNL[] = $subdecision->getTranslatedContent($this->translator, AppLanguages::Dutch);
            $contentEN[] = $subdecision->getTranslatedContent($this->translator, AppLanguages::English);
        }

        if (empty($contentNL)) {
            $contentNL[] = '';
            $contentEN[] = '';
        }

        $reportDecision->setContentNL(implode(' ', $contentNL));
        $reportDecision->setContentEN(implode(' ', $contentEN));

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
        $meetingRepo = $this->emReport->getRepository(ReportMeetingModel::class);

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
            'sequence' => $subdecision->getSequence(),
        ]);

        if (null === $reportSubDecision) {
            // determine type and create
            $class = $subdecision::class;
            /** @var class-string<T> $class */
            $class = preg_replace('/^Database/', 'Report', $class);
            /** @var T $reportSubDecision */
            $reportSubDecision = new $class();
            $reportSubDecision->setDecision($reportDecision);
            $reportSubDecision->setSequence($subdecision->getSequence());
        }

        if ($subdecision instanceof DatabaseSubDecisionModel\FoundationReference) {
            $ref = $subdecision->getFoundation();
            $foundation = $subdecRepo->find([
                'meeting_type' => $ref->getDecision()->getMeeting()->getType(),
                'meeting_number' => $ref->getDecision()->getMeeting()->getNumber(),
                'decision_point' => $ref->getDecision()->getPoint(),
                'decision_number' => $ref->getDecision()->getNumber(),
                'sequence' => $ref->getSequence(),
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
                'sequence' => $ref->getSequence(),
            ]);

            $reportSubDecision->setInstallation($installation);
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Foundation) {
            // foundation
            $reportSubDecision->setAbbr($subdecision->getAbbr());
            $reportSubDecision->setName($subdecision->getName());
            $reportSubDecision->setOrganType($subdecision->getOrganType());
        } elseif (
            $subdecision instanceof DatabaseSubDecisionModel\Financial\Statement
            || $subdecision instanceof DatabaseSubDecisionModel\Financial\Budget
            || $subdecision instanceof DatabaseSubDecisionModel\OrganRegulation
        ) {
            // financial budgets and statements
            if (null !== $subdecision->getMember()) {
                $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            }

            // Specific to the `OrganRegulation`s, set the abbr and type of organ
            if ($subdecision instanceof DatabaseSubDecisionModel\OrganRegulation) {
                $reportSubDecision->setAbbr($subdecision->getAbbr());
                $reportSubDecision->setOrganType($subdecision->getOrganType());
            } else {
                $reportSubDecision->setName($subdecision->getName());
            }

            $reportSubDecision->setVersion($subdecision->getVersion());
            $reportSubDecision->setDate($subdecision->getDate());
            $reportSubDecision->setApproval($subdecision->getApproval());
            $reportSubDecision->setChanges($subdecision->getChanges());
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Minutes) {
            $ref = $subdecision->getTarget();
            $meeting = $meetingRepo->find([
                'type' => $ref->getType(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setMeeting($meeting);
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            $reportSubDecision->setApproval($subdecision->getApproval());
            $reportSubDecision->setChanges($subdecision->getChanges());
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
                'sequence' => $ref->getSequence(),
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
                'sequence' => $ref->getSequence(),
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
                'sequence' => $ref->getSequence(),
            ]);

            $reportSubDecision->setGranting($granting);
            $reportSubDecision->setWithdrawnOn($subdecision->getWithdrawnOn());
        } elseif ($subdecision instanceof DatabaseSubDecisionModel\Annulment) {
            $ref = $subdecision->getTarget();
            $target = $decRepo->find([
                'meeting_type' => $ref->getMeeting()->getType(),
                'meeting_number' => $ref->getMeeting()->getNumber(),
                'point' => $ref->getPoint(),
                'number' => $ref->getNumber(),
            ]);

            $reportSubDecision->setTarget($target);

            // Annulment must be handled here, because it cannot be part of the process{X}Updates because the
            // subdecision is the annulment, not the target subdecision(s).
            $this->annulDecision($target);
        }

        // Abolish decisions are handled by foundationreference
        // Other decisions don't need special handling

        // for any decision, make sure the content is filled for Dutch and English
        $reportSubDecision->setContentNL($subdecision->getTranslatedContent($this->translator, AppLanguages::Dutch));
        $reportSubDecision->setContentEN($subdecision->getTranslatedContent($this->translator, AppLanguages::English));
        $this->emReport->persist($reportSubDecision);

        return $reportSubDecision;
    }

    /**
     * Annuls a previously recorded decision and its subdecisions in GEWISDB.
     *
     * This function reverts the effects of a target decision by undoing or removing its associated subdecisions. Each
     * subdecision type is handled explicitly to ensure that the data remains consistent and auditable.
     *
     * GEWISDB operates as a ledger, meaning the chronological order of decisions must be preserved. A target decision
     * made at point X may be annulled at point Z, but any relevant decisions that influence the target (at points Y)
     * must lie strictly between X and Z. Annulments cannot be applied retroactively or out of sequence. Violating this
     * breaks the ledger assumption and will result in an inconsistent and potentially irrecoverable state.
     *
     * This ordering is what allows us to perform the annulment at point Z, because at that time all points Y will be
     * known and processed.
     *
     * NOTE: to adhere to our ordering assumption within a decision, we must loop through its subdecisions in reverse.
     */
    private function annulDecision(ReportDecisionModel $target): void
    {
        foreach (array_reverse($target->getSubDecisions()->toArray()) as $targetSubDecision) {
            if ($targetSubDecision instanceof ReportSubDecisionModel\Installation) {
                // installation
                $organMember = $targetSubDecision->getOrganMember();

                // Cannot annul if organ membership changed since installation.
                if (
                    null !== $organMember->getDischargeDate()
                    || !$organMember->getInstallation()->getReappointments()->isEmpty()
                ) {
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                    throw new RuntimeException('Cannot annul installation due to other relevant decisions after installation');
                }

                $targetSubDecision->getFoundation()->getOrgan()->getMembers()->removeElement($organMember);
                $this->emReport->remove($organMember);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Discharge) {
                // discharge
                $organMember = $targetSubDecision->getInstallation()->getOrganMember();
                $organMember->setDischargeDate(null);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Reappointment) {
                // reappointment
                $installation = $targetSubDecision->getInstallation();

                // Cannot annul if the installation has already been discharged.
                if (null !== $installation->getDischarge()) {
                    throw new RuntimeException('Cannot annul reappointment due to discharge after reappointment');
                }

                // Cannot annul if there are later reappointments tied to the same installation.
                foreach ($installation->getReappointments() as $otherReappointment) {
                    if ($otherReappointment === $targetSubDecision) {
                        continue;
                    }

                    // Compare ordering: if another reappointment comes after this one, annulment is invalid.
                    if ($this->isAfter($otherReappointment, $targetSubDecision)) {
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                        throw new RuntimeException('Cannot annul reappointment due to other relevant decisions after reappointment');
                    }
                }

                $installation->removeReappointment($targetSubDecision);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Foundation) {
                // foundation
                $organ = $targetSubDecision->getOrgan();

                // Cannot annul if the organ has installations.
                if (!$organ->getMembers()->isEmpty()) {
                    throw new RuntimeException('Cannot annul foundation due to existing installations in the organ');
                }

                $this->emReport->remove($organ);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Abrogation) {
                // abrogation
                $organ = $targetSubDecision->getFoundation()->getOrgan();
                $organ->setAbrogationDate(null);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Board\Installation) {
                // board installation
                $boardMember = $targetSubDecision->getBoardMember();

                // Cannot annul if the board member has already been released or discharged.
                if (
                    null !== $boardMember->getReleaseDate()
                    || null !== $boardMember->getDischargeDate()
                ) {
                    throw new RuntimeException('Cannot annul board installation due to later release or discharge');
                }

                $this->emReport->remove($boardMember);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Board\Release) {
                // board release
                $installation = $targetSubDecision->getInstallation();
                $boardMember = $installation->getBoardMember();

                // Cannot annul release if the board member was also discharged afterwards.
                if (null !== $boardMember->getDischargeDate()) {
                    throw new RuntimeException('Cannot annul board release due to later discharge');
                }

                $boardMember->setReleaseDate(null);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Board\Discharge) {
                // board discharge
                $installation = $targetSubDecision->getInstallation();
                $boardMember  = $installation->getBoardMember();

                $boardMember->setDischargeDate(null);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Key\Granting) {
                // key code granting
                $keyholder = $targetSubDecision->getKeyholder();

                // Cannot annul granting if it has already been withdrawn.
                if (null !== $keyholder->getWithdrawnDate()) {
                    throw new RuntimeException('Cannot annul key granting due to later withdrawal');
                }

                $this->emReport->remove($keyholder);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Key\Withdrawal) {
                // key code withdrawal
                $keyholder = $targetSubDecision->getGranting()->getKeyholder();
                $keyholder->setWithdrawnDate(null);
            } elseif ($targetSubDecision instanceof ReportSubDecisionModel\Annulment) {
                // This is undefined behaviour.
                throw new LogicException('Annulment of a previous annulment is undefined');
            }

            $this->emReport->persist($targetSubDecision);
        }
    }

    /**
     * Determine if $a occurs after $b in the ledger ordering.
     */
    private function isAfter(ReportSubDecisionModel $a, ReportSubDecisionModel $b): bool
    {
        if ($a->getMeetingType() !== $b->getMeetingType()) {
            throw new LogicException('Cannot compare decisions across different meeting types');
        }

        if ($a->getMeetingNumber() !== $b->getMeetingNumber()) {
            return $a->getMeetingNumber() > $b->getMeetingNumber();
        }

        if ($a->getDecisionPoint() !== $b->getDecisionPoint()) {
            return $a->getDecisionPoint() > $b->getDecisionPoint();
        }

        if ($a->getDecisionNumber() !== $b->getDecisionNumber()) {
            return $a->getDecisionNumber() > $b->getDecisionNumber();
        }

        return $a->getSequence() > $b->getSequence();
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
            case $subDecision instanceof ReportSubDecisionModel\Annulment:
                throw new Exception('Deletion of annulling decisions not implemented');

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
