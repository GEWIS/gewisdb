<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Decision as DatabaseDecision;
use App\Entity\Database\Meeting as DatabaseMeeting;
use App\Entity\Database\Member as DatabaseMember;
use App\Entity\Database\SubDecision as DatabaseSubDecision;
use App\Entity\Report\Decision as ReportDecision;
use App\Entity\Report\Meeting as ReportMeeting;
use App\Entity\Report\Member as ReportMember;
use App\Entity\Report\SubDecision as ReportSubDecision;
use App\Repository\Database\MeetingRepository;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use function array_reverse;
use function assert;
use function count;
use function get_parent_class;
use function implode;
use function is_a;
use function preg_replace;
use function sprintf;

class MeetingService
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MeetingRepository $meetingRepository,
        private readonly SubDecisionService $subDecisionService,
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
        private readonly MailerInterface $mailer,
        private readonly string $mailFromAddress,
        private readonly string $mailFromName,
        private readonly string $mailToReportErrorAddress,
        private readonly string $mailToReportErrorName,
    ) {
    }

    /**
     * Find the ReportDB twin of a meeting, if it has already been projected.
     */
    private function findReportMeeting(DatabaseMeeting $meeting): ?ReportMeeting
    {
        return $this->emReport->getRepository(ReportMeeting::class)->find([
            'type' => $meeting->getType(),
            'number' => $meeting->getNumber(),
        ]);
    }

    /**
     * Find the ReportDB twin of a decision, if it has already been projected.
     */
    private function findReportDecision(DatabaseDecision $decision): ?ReportDecision
    {
        return $this->emReport->getRepository(ReportDecision::class)->find([
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'point' => $decision->getPoint(),
            'number' => $decision->getNumber(),
        ]);
    }

    /**
     * Find the ReportDB twin of the decision a subdecision belongs to.
     *
     * Keyed off the subdecision's own columns rather than off its decision, so that reaching the twin does not first
     * have to load the ledger's decision.
     */
    private function findReportDecisionOf(DatabaseSubDecision $subdecision): ?ReportDecision
    {
        return $this->emReport->getRepository(ReportDecision::class)->find([
            'meeting_type' => $subdecision->getMeetingType(),
            'meeting_number' => $subdecision->getMeetingNumber(),
            'point' => $subdecision->getDecisionPoint(),
            'number' => $subdecision->getDecisionNumber(),
        ]);
    }

    /**
     * Find the ReportDB twin of a subdecision, if it has already been projected.
     */
    private function findReportSubDecision(DatabaseSubDecision $subdecision): ?ReportSubDecision
    {
        return $this->emReport->getRepository(ReportSubDecision::class)->find([
            'meeting_type' => $subdecision->getMeetingType(),
            'meeting_number' => $subdecision->getMeetingNumber(),
            'decision_point' => $subdecision->getDecisionPoint(),
            'decision_number' => $subdecision->getDecisionNumber(),
            'sequence' => $subdecision->getSequence(),
        ]);
    }

    /**
     * Build ReportDB by replaying every meeting in the order it was held.
     *
     * ReportDB is a materialised view of GEWISDB, and GEWISDB is a ledger: the state it describes is whatever you end
     * up with after going through the decisions in order. So that is all this does. Every subdecision is applied the
     * moment it is reached, including the annulments, which revert what their target set in motion at exactly the
     * point in the ledger where that happened. Entities being created and later removed again along the way is
     * expected, and is what makes the result the same whether ReportDB was empty or already up to date.
     *
     * Progress is reported through the callback rather than written to the console here, so that the service stays
     * usable outside of a command.
     *
     * @param (Closure(int $current, int $total): void)|null $onProgress
     */
    public function generate(?Closure $onProgress = null): void
    {
        // every meeting, oldest first
        $meetings = $this->meetingRepository->findAllWithDecisionCount(true);
        $total = count($meetings);

        $num = 0;
        foreach ($meetings as $meeting) {
            $this->generateMeeting($meeting[0]);
            $this->emReport->flush();
            // Nothing generated so far is needed again by name, and holding on to all of it makes every subsequent
            // flush more expensive than the last.
            $this->emReport->clear();
            ++$num;

            if (null === $onProgress) {
                continue;
            }

            $onProgress($num, $total);
        }

        $this->emReport->flush();
    }

    public function generateMeeting(DatabaseMeeting $meeting): void
    {
        $reportMeeting = $this->findReportMeeting($meeting);

        if (null === $reportMeeting) {
            $reportMeeting = new ReportMeeting();
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
                $this->generateDecision(
                    $decision,
                    $reportMeeting,
                );
            } catch (Throwable $e) {
                // send email, something went wrong
                $this->sendDecisionExceptionMail(
                    $e,
                    $decision,
                );
                continue;
            }
        }

        $this->emReport->persist($reportMeeting);
    }

    public function generateDecision(
        DatabaseDecision $decision,
        ?ReportMeeting $reportMeeting = null,
    ): void {
        if (null === $reportMeeting) {
            $reportMeeting = $this->findReportMeeting($decision->getMeeting());

            if (null === $reportMeeting) {
                throw new LogicException('Decision without meeting');
            }
        }

        // see if decision exists
        $reportDecision = $this->findReportDecision($decision);

        if (null === $reportDecision) {
            $reportDecision = new ReportDecision();
            $reportDecision->setMeeting($reportMeeting);
            $reportDecision->setPoint($decision->getPoint());
            $reportDecision->setNumber($decision->getNumber());
        }

        $contentNL = [];
        $contentEN = [];

        foreach ($decision->getSubdecisions() as $subdecision) {
            $reportSubDecision = $this->generateSubDecision(
                $subdecision,
                $reportDecision,
            );
            // Applied right here, so that what a subdecision brings about is in place before the next one is read.
            $this->subDecisionService->generateRelated($reportSubDecision);
            $contentNL[] = $subdecision->getTranslatedContent(
                $this->translator,
                AppLanguages::Dutch,
            );
            $contentEN[] = $subdecision->getTranslatedContent(
                $this->translator,
                AppLanguages::English,
            );
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
     * Project a subdecision onto its ReportDB counterpart.
     *
     * The two class trees mirror each other one-for-one, so which report class belongs to a subdecision follows from
     * that subdecision's own class name.
     */
    public function generateSubDecision(
        DatabaseSubDecision $subdecision,
        ?ReportDecision $reportDecision = null,
    ): ReportSubDecision {
        if (null === $reportDecision) {
            $reportDecision = $this->findReportDecisionOf($subdecision);

            if (null === $reportDecision) {
                throw new LogicException('Decision without meeting');
            }
        }

        // The projection's subdecision is the same class one namespace over, so the class to build is the ledger's
        // with `Database` swapped for `Report`. A subdecision whose name does not rewrite would silently build a
        // ledger entity here, which is a bug rather than a case to handle.
        /** @var class-string<ReportSubDecision> $class */
        $class = preg_replace(
            '/^App\\\\Entity\\\\Database\\\\/',
            'App\\\\Entity\\\\Report\\\\',
            $this->realClass($subdecision),
        );

        if (
            !is_a(
                $class,
                ReportSubDecision::class,
                true,
            )
        ) {
            throw new LogicException(sprintf('No projection exists for %s', $subdecision::class));
        }

        $reportSubDecision = $this->findReportSubDecision($subdecision);

        if (
            null !== $reportSubDecision
            && $this->realClass($reportSubDecision) !== $class
        ) {
            // A subdecision that turned out to be wrong is put right by replacing it with one of another kind, and
            // then this position no longer holds what ReportDB has at it. Nothing about the old one is still true, so
            // it goes, along with everything derived from it, rather than being dressed up as the new one; keeping it
            // would leave the body member it discharged, or the body it founded, standing on a decision that was
            // never taken. Its removal has to reach the database before the replacement takes its place, because they
            // share an identity and a single flush would insert before it deletes.
            $this->deleteSubDecision($reportSubDecision);
            $this->emReport->flush();

            $reportSubDecision = null;
        }

        if (null === $reportSubDecision) {
            $reportSubDecision = new $class();
            $reportSubDecision->setDecision($reportDecision);
            $reportSubDecision->setSequence($subdecision->getSequence());
        }

        if ($subdecision instanceof DatabaseSubDecision\FoundationReference) {
            // The report subdecision is the same class in the report namespace, built by rewriting the
            // namespace above; asserting it here is what lets the branch use that class's setters.
            assert($reportSubDecision instanceof ReportSubDecision\FoundationReference);

            $ref = $subdecision->getFoundation();
            $foundation = $this->findReportSubDecision($ref);
            assert($foundation instanceof ReportSubDecision\Foundation);

            $reportSubDecision->setFoundation($foundation);
        }

        // transfer specific data
        if ($subdecision instanceof DatabaseSubDecision\Installation) {
            assert($reportSubDecision instanceof ReportSubDecision\Installation);

            $reportSubDecision->setFunction($subdecision->getFunction());
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
        } elseif (
            $subdecision instanceof DatabaseSubDecision\Reappointment
            || $subdecision instanceof DatabaseSubDecision\Discharge
        ) {
            assert(
                $reportSubDecision instanceof ReportSubDecision\Reappointment
                || $reportSubDecision instanceof ReportSubDecision\Discharge,
            );

            $ref = $subdecision->getInstallation();
            $installation = $this->findReportSubDecision($ref);
            assert($installation instanceof ReportSubDecision\Installation);

            $reportSubDecision->setInstallation($installation);
        } elseif ($subdecision instanceof DatabaseSubDecision\Foundation) {
            assert($reportSubDecision instanceof ReportSubDecision\Foundation);

            $reportSubDecision->setName($subdecision->getName());
            $reportSubDecision->setAbbr($subdecision->getAbbr());
            $reportSubDecision->setPurpose($subdecision->getPurpose());
            $reportSubDecision->setOrganType($subdecision->getOrganType());
        } elseif (
            $subdecision instanceof DatabaseSubDecision\Financial\Statement
            || $subdecision instanceof DatabaseSubDecision\Financial\Budget
            || $subdecision instanceof DatabaseSubDecision\OrganRegulation
        ) {
            // There are 147 Board Meetings before BV 1209 that have an "unknown" author for a budget and/or financial
            // statement. As such, we need to allow for the member to be null here. In that case, we simply will not set
            // a member for the report subdecision, and it will be shown as "unknown" in the (sub)decision content.
            assert(
                $reportSubDecision instanceof ReportSubDecision\Financial\Budget
                || $reportSubDecision instanceof ReportSubDecision\OrganRegulation,
            );

            if (null !== $subdecision->getMember()) {
                $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            }

            // Specific to the `OrganRegulation`s, set the abbr and type of organ
            if ($subdecision instanceof DatabaseSubDecision\OrganRegulation) {
                assert($reportSubDecision instanceof ReportSubDecision\OrganRegulation);

                $reportSubDecision->setAbbr($subdecision->getAbbr());
                $reportSubDecision->setOrganType($subdecision->getOrganType());
            } else {
                assert($reportSubDecision instanceof ReportSubDecision\Financial\Budget);

                $reportSubDecision->setName($subdecision->getName());
            }

            $reportSubDecision->setVersion($subdecision->getVersion());
            $reportSubDecision->setDate($subdecision->getDate());
            $reportSubDecision->setApproval($subdecision->getApproval());
            $reportSubDecision->setChanges($subdecision->getChanges());
        } elseif ($subdecision instanceof DatabaseSubDecision\Minutes) {
            assert($reportSubDecision instanceof ReportSubDecision\Minutes);

            $meeting = $this->findReportMeeting($subdecision->getTarget());
            assert($meeting instanceof ReportMeeting);

            $reportSubDecision->setMeeting($meeting);
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            $reportSubDecision->setApproval($subdecision->getApproval());
            $reportSubDecision->setChanges($subdecision->getChanges());
        } elseif ($subdecision instanceof DatabaseSubDecision\Board\Installation) {
            assert($reportSubDecision instanceof ReportSubDecision\Board\Installation);

            $reportSubDecision->setFunction($subdecision->getFunction());
            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            $reportSubDecision->setDate($subdecision->getDate());
        } elseif ($subdecision instanceof DatabaseSubDecision\Board\Release) {
            assert($reportSubDecision instanceof ReportSubDecision\Board\Release);

            $ref = $subdecision->getInstallation();
            $installation = $this->findReportSubDecision($ref);
            assert($installation instanceof ReportSubDecision\Board\Installation);

            $reportSubDecision->setInstallation($installation);
            $reportSubDecision->setDate($subdecision->getDate());
        } elseif ($subdecision instanceof DatabaseSubDecision\Board\Discharge) {
            assert($reportSubDecision instanceof ReportSubDecision\Board\Discharge);

            $ref = $subdecision->getInstallation();
            $installation = $this->findReportSubDecision($ref);
            assert($installation instanceof ReportSubDecision\Board\Installation);

            $reportSubDecision->setInstallation($installation);
        } elseif ($subdecision instanceof DatabaseSubDecision\Key\Granting) {
            assert($reportSubDecision instanceof ReportSubDecision\Key\Granting);

            $reportSubDecision->setMember($this->findMember($subdecision->getMember()));
            $reportSubDecision->setUntil($subdecision->getUntil());
        } elseif ($subdecision instanceof DatabaseSubDecision\Key\Withdrawal) {
            assert($reportSubDecision instanceof ReportSubDecision\Key\Withdrawal);

            $ref = $subdecision->getGranting();
            $granting = $this->findReportSubDecision($ref);
            assert($granting instanceof ReportSubDecision\Key\Granting);

            $reportSubDecision->setGranting($granting);
            $reportSubDecision->setWithdrawnOn($subdecision->getWithdrawnOn());
        } elseif ($subdecision instanceof DatabaseSubDecision\Annulment) {
            assert($reportSubDecision instanceof ReportSubDecision\Annulment);

            $target = $this->findReportDecision($subdecision->getTarget());
            assert($target instanceof ReportDecision);

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
     * in the `Decision` domain, which owns the ledger and turns down an annulment that would break it; by the time an
     * annulment reaches ReportDB it merely has to be applied.
     *
     * NOTE: to adhere to our ordering assumption within a decision, we must loop through its subdecisions in reverse.
     */
    private function annulDecision(ReportDecision $target): void
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
    private function unannulDecision(ReportDecision $target): void
    {
        foreach ($target->getSubdecisions() as $targetSubDecision) {
            $this->subDecisionService->generateRelated($targetSubDecision);
        }
    }

    public function deleteDecision(DatabaseDecision $decision): void
    {
        $reportDecision = $this->findReportDecision($decision);

        foreach (array_reverse($reportDecision->getSubdecisions()->toArray()) as $subDecision) {
            $this->deleteSubDecision($subDecision);
        }

        $this->emReport->remove($reportDecision);
    }

    public function deleteSubDecision(ReportSubDecision $subDecision): void
    {
        if ($subDecision instanceof ReportSubDecision\Annulment) {
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
                    case $subDecision instanceof ReportSubDecision\Discharge:
                        $subDecision->getInstallation()->clearDischarge();
                        break;

                    case $subDecision instanceof ReportSubDecision\Board\Release:
                        $subDecision->getInstallation()->clearRelease();
                        break;

                    case $subDecision instanceof ReportSubDecision\Board\Discharge:
                        $subDecision->getInstallation()->clearDischarge();
                        break;

                    case $subDecision instanceof ReportSubDecision\Key\Withdrawal:
                        $subDecision->getGranting()->clearWithdrawal();
                        break;
                }
            }

            // A body keeps a list of the decisions it was shaped by, and that list stands in the way of the row going
            // anywhere until the body lets go of it.
            $this->subDecisionService->detachFromOrgans($subDecision);
        }

        $this->emReport->remove($subDecision);
    }

    /**
     * The class an entity actually is, rather than the one it presents itself as.
     *
     * Doctrine hands out proxies for entities it has not loaded yet, and those are subclasses in a namespace of their
     * own. Anything reasoning about which kind of subdecision it is holding has to look past that.
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
     * the `Member` domain, this cannot return `null`.
     */
    public function findMember(DatabaseMember $member): ReportMember
    {
        $reportMember = $this->emReport->getRepository(ReportMember::class)
            ->find($member->getLidnr());

        if (null === $reportMember) {
            throw new LogicException(
                sprintf(
                    'Member %d does not exist in ReportDB',
                    $member->getLidnr(),
                ),
            );
        }

        return $reportMember;
    }

    /**
     * Send an email about that something went wrong.
     */
    public function sendDecisionExceptionMail(
        Throwable $e,
        DatabaseDecision $decision,
    ): void {
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

        $email = new Email()
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to(new Address($this->mailToReportErrorAddress, $this->mailToReportErrorName))
            ->subject('Database fout')
            ->text($body);

        $this->mailer->send($email);
    }
}
