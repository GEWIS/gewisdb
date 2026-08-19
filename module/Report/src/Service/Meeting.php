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
use Doctrine\Persistence\Proxy;
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
use Report\Service\SubDecision as SubDecisionService;
use Throwable;

use function array_reverse;
use function count;
use function get_parent_class;
use function implode;
use function preg_replace;
use function sprintf;

class Meeting
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Translator $translator,
        private readonly MeetingMapper $meetingMapper,
        private readonly SubDecisionService $subDecisionService,
        private readonly EntityManager $emReport,
        private readonly array $config,
        private readonly TransportInterface $mailTransport,
    ) {
    }

    /**
     * Build ReportDB by replaying every meeting in the order it was held.
     *
     * ReportDB is a materialised view of GEWISDB, and GEWISDB is a ledger: the state it describes is whatever you end
     * up with after going through the decisions in order. So that is all this does. Every subdecision is applied the
     * moment it is reached, including the annulments, which revert what their target set in motion at exactly the
     * point in the ledger where that happened. Entities being created and later removed again along the way is
     * expected, and is what makes the result the same whether ReportDB was empty or already up to date.
     */
    public function generate(): void
    {
        // every meeting, oldest first
        $meetings = $this->meetingMapper->findAll(true, true);

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($meetings));

        $num = 0;
        foreach ($meetings as $meeting) {
            $this->generateMeeting($meeting[0]);
            $this->emReport->flush();
            // Nothing generated so far is needed again by name, and holding on to all of it makes every subsequent
            // flush more expensive than the last.
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
            $reportMeeting->setType($meeting->getType());
            $reportMeeting->setNumber($meeting->getNumber());
            $reportMeeting->setDate($meeting->getDate());
        } elseif ($reportMeeting->getDate()->format('Y-m-d') !== $meeting->getDate()->format('Y-m-d')) {
            // The type and number identify the meeting and can therefore never change, but the date can be corrected
            // after the fact, so it must be kept in sync. Only assign it when the stored date actually differs:
            // Doctrine detects changes by identity, so handing it an equal but distinct DateTime would mark the
            // meeting as dirty and rewrite the row on every single projection.
            $reportMeeting->setDate($meeting->getDate());
        }

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
            $reportDecision->setPoint($decision->getPoint());
            $reportDecision->setNumber($decision->getNumber());
        }

        $contentNL = [];
        $contentEN = [];

        foreach ($decision->getSubdecisions() as $subdecision) {
            /** @var ReportSubDecisionModel $reportSubDecision */
            $reportSubDecision = $this->generateSubDecision($subdecision, $reportDecision);
            // Applied right here, so that what a subdecision brings about is in place before the next one is read.
            $this->subDecisionService->generateRelated($reportSubDecision);
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

        /** @var class-string<T> $class */
        $class = preg_replace('/^Database/', 'Report', $this->realClass($subdecision));

        /** @var T|null $reportSubDecision */
        $reportSubDecision = $subdecRepo->find([
            'meeting_type' => $subdecision->getMeetingType(),
            'meeting_number' => $subdecision->getMeetingNumber(),
            'decision_point' => $subdecision->getDecisionPoint(),
            'decision_number' => $subdecision->getDecisionNumber(),
            'sequence' => $subdecision->getSequence(),
        ]);

        if (
            null !== $reportSubDecision
            && $this->realClass($reportSubDecision) !== $class
        ) {
            // A subdecision that turned out to be wrong is put right by replacing it with one of another kind, and
            // then this position no longer holds what ReportDB has at it. Nothing about the old one is still true, so
            // it goes, along with everything derived from it, rather than being dressed up as the new one; keeping it
            // would leave the organ member it discharged, or the organ it founded, standing on a decision that was
            // never taken. Its removal has to reach the database before the replacement takes its place, because they
            // share an identity and a single flush would insert before it deletes.
            $this->deleteSubDecision($reportSubDecision);
            $this->emReport->flush();

            $reportSubDecision = null;
        }

        if (null === $reportSubDecision) {
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
            $reportSubDecision->setName($subdecision->getName());
            $reportSubDecision->setAbbr($subdecision->getAbbr());
            $reportSubDecision->setPurpose($subdecision->getPurpose());
            $reportSubDecision->setOrganType($subdecision->getOrganType());
        } elseif (
            $subdecision instanceof DatabaseSubDecisionModel\Financial\Statement
            || $subdecision instanceof DatabaseSubDecisionModel\Financial\Budget
            || $subdecision instanceof DatabaseSubDecisionModel\OrganRegulation
        ) {
            // There are 147 Board Meetings before BV 1209 that have an "unknown" author for a budget and/or financial
            // statement. As such, we need to allow for the member to be null here. In that case, we simply will not set
            // a member for the report subdecision, and it will be shown as "unknown" in the (sub)decision content.
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
     * This function reverts the effects of a target decision by undoing or removing the entities that were derived
     * from its subdecisions. Each subdecision type is handled explicitly by {@see SubDecisionService::revertRelated()}
     * to ensure that the data remains consistent and auditable.
     *
     * GEWISDB operates as a ledger, meaning the chronological order of decisions must be preserved. A target decision
     * made at point X may be annulled at point Z, but only while no decision in between builds on it. That rule lives
     * in the `Database` module, which owns the ledger and turns down an annulment that would break it; by the time an
     * annulment reaches ReportDB it merely has to be applied.
     *
     * NOTE: to adhere to our ordering assumption within a decision, we must loop through its subdecisions in reverse.
     */
    private function annulDecision(ReportDecisionModel $target): void
    {
        foreach (array_reverse($target->getSubdecisions()->toArray()) as $targetSubDecision) {
            $this->subDecisionService->revertRelated($targetSubDecision);
        }
    }

    /**
     * Undoes an annulment, restoring the entities that were derived from the annulled decision.
     *
     * The same ledger assumption applies as for {@see self::annulDecision()}: the annulment can only be taken back
     * while nothing has been decided about the affected entities since.
     */
    private function unannulDecision(ReportDecisionModel $target): void
    {
        foreach ($target->getSubdecisions() as $targetSubDecision) {
            $this->subDecisionService->generateRelated($targetSubDecision);
        }
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
        if ($subDecision instanceof ReportSubDecisionModel\Annulment) {
            if ($this->subDecisionService->stillReferences($subDecision)) {
                $this->unannulDecision($subDecision->getTarget());
            }
        } else {
            // Deleting a subdecision undoes its effects in exactly the same way that annulling it does.
            $this->subDecisionService->revertRelated($subDecision);

            // On top of that, the subdecision is about to disappear, so the references to it must be dropped as well.
            // One that no longer has the subdecision it points at has nothing to drop; being reverted above is what
            // it came here for.
            if ($this->subDecisionService->stillReferences($subDecision)) {
                switch (true) {
                    case $subDecision instanceof ReportSubDecisionModel\Discharge:
                        $subDecision->getInstallation()->clearDischarge();
                        break;

                    case $subDecision instanceof ReportSubDecisionModel\Board\Release:
                        $subDecision->getInstallation()->clearRelease();
                        break;

                    case $subDecision instanceof ReportSubDecisionModel\Board\Discharge:
                        $subDecision->getInstallation()->clearDischarge();
                        break;

                    case $subDecision instanceof ReportSubDecisionModel\Key\Withdrawal:
                        $subDecision->getGranting()->clearWithdrawal();
                        break;
                }
            }

            // An organ keeps a list of the decisions it was shaped by, and that list stands in the way of the row
            // going anywhere until the organ lets go of it.
            $this->subDecisionService->detachFromOrgans($subDecision);
        }

        $this->emReport->remove($subDecision);
    }

    /**
     * The class an entity actually is, rather than the one it presents itself as.
     *
     * Doctrine hands out proxies for entities it has not loaded yet, and those are subclasses in a namespace of their
     * own. Anything that reasons about which kind of subdecision it is holding has to look past that.
     *
     * @return class-string
     */
    private function realClass(object $entity): string
    {
        if ($entity instanceof Proxy) {
            /** @var class-string $parent */
            $parent = get_parent_class($entity);

            return $parent;
        }

        return $entity::class;
    }

    /**
     * Obtain the correct member, given a database member. Because these members are generated based on what happens in
     * the `Database` module, this cannot return `null`.
     */
    public function findMember(DatabaseMemberModel $member): ReportMemberModel
    {
        $reportMember = $this->emReport->getRepository(ReportMemberModel::class)
            ->find($member->getLidnr());

        if (null === $reportMember) {
            throw new LogicException(
                sprintf('Member %d does not exist in ReportDB', $member->getLidnr()),
            );
        }

        return $reportMember;
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
            {$meeting->getType()->value} {$meeting->getNumber()}.{$decision->getPoint()}.{$decision->getNumber()}.

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
