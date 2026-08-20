<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Entity\Database\Decision as DecisionModel;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\SubDecision as SubDecisionModel;
use App\Entity\Database\SubDecision\Abrogation as AbrogationModel;
use App\Entity\Database\SubDecision\Annulment as AnnulmentModel;
use App\Entity\Database\SubDecision\Board\Discharge as BoardDischargeModel;
use App\Entity\Database\SubDecision\Board\Installation as BoardInstallationModel;
use App\Entity\Database\SubDecision\Board\Release as BoardReleaseModel;
use App\Entity\Database\SubDecision\Discharge as DischargeModel;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\Entity\Database\SubDecision\FoundationReference as FoundationReferenceModel;
use App\Entity\Database\SubDecision\Installation as InstallationModel;
use App\Entity\Database\SubDecision\Key\Granting as KeyGrantingModel;
use App\Entity\Database\SubDecision\Key\Withdrawal as KeyWithdrawalModel;
use App\Entity\Database\SubDecision\Reappointment as ReappointmentModel;
use App\Exception\Database\AnnulmentNotPossible;
use App\Repository\Database\MeetingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_filter;
use function array_key_exists;
use function array_unique;
use function array_values;
use function sprintf;

/**
 * Decides whether a decision may be annulled, and whether an annulment may be taken back again.
 *
 * GEWISDB operates as a ledger, meaning the chronological order of decisions must be preserved. A decision made at
 * point X may be annulled at point Z, but any decision that builds on X must lie strictly between X and Z. An
 * annulment cannot be applied retroactively or out of sequence: if something was decided about the same organ, board
 * member, or key code in the meantime, taking X back has no well-defined outcome and would leave the database in an
 * inconsistent and potentially irrecoverable state. The same holds in reverse when an annulment is deleted, because
 * that restores exactly what it took away.
 *
 * Decisions that were themselves annulled do not count: they never happened. Neither do the sibling subdecisions of
 * the decision under consideration, as those are annulled or restored along with it.
 */
class Annulment
{
    public function __construct(
        private readonly MeetingRepository $meetingRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Assert that the given decision can be annulled.
     *
     * @return string[] what is worth pointing out about the annulment without standing in its way.
     *
     * @throws AnnulmentNotPossible when another decision builds on the given decision.
     */
    public function assertDecisionCanBeAnnulled(DecisionModel $decision): array
    {
        $warnings = [];

        foreach ($decision->getSubdecisions() as $subDecision) {
            $warnings = [
                ...$warnings,
                ...$this->assertNoDependents($subDecision),
            ];
        }

        $this->assertOrgansRemainValid(
            $decision,
            true,
        );

        return array_values(array_unique($warnings));
    }

    /**
     * Assert that the given annulment can be deleted, restoring the decision it annulled.
     *
     * On top of the requirements for making the annulment in the first place, the annulment must still be the last
     * word on the entities it took away. Anything decided about those entities afterwards was decided in a world
     * where the annulled decision did not exist; putting that decision back would silently invalidate it. This
     * includes later annulments, which are decisions about those entities just as much as the rest.
     *
     * @throws AnnulmentNotPossible when the annulled decision can no longer be restored.
     */
    public function assertAnnulmentCanBeDeleted(AnnulmentModel $annulment): void
    {
        $target = $annulment->getTarget();

        $this->assertDecisionCanBeAnnulled($target);

        foreach ($target->getSubdecisions() as $subDecision) {
            foreach ($this->findRelated($subDecision) as $related) {
                if (
                    $this->isSameDecision(
                        $related,
                        $subDecision,
                    )
                    || $this->isSameDecision(
                        $related,
                        $annulment,
                    )
                ) {
                    continue;
                }

                if (
                    $this->isAfter(
                        $related,
                        $annulment,
                    )
                ) {
                    throw $this->cannot(
                        $subDecision,
                        $this->translator->trans(
                            'it was decided about again after this annulment was made',
                        ),
                    );
                }

                // Looked up rather than read off the inverse side, so that an annulment made earlier in this same
                // request is seen as well.
                $laterAnnulments = $this->meetingRepository->findReferencingSubDecisions(
                    AnnulmentModel::class,
                    'target',
                    $related->getDecision(),
                );

                foreach ($laterAnnulments as $laterAnnulment) {
                    if (
                        !$this->isAfter(
                            $laterAnnulment,
                            $annulment,
                        )
                    ) {
                        continue;
                    }

                    throw $this->cannot(
                        $subDecision,
                        $this->translator->trans(
                            'a related decision was annulled after this annulment was made',
                        ),
                    );
                }
            }
        }

        $this->assertOrgansRemainValid(
            $target,
            false,
        );
    }

    /**
     * Assert that the organs affected by a decision still hold up once that decision starts or stops counting.
     *
     * The Articles of Association and the Internal Regulations put requirements on how an organ is made up; those
     * live on {@see \App\Entity\Database\Enums\OrganTypes}. Taking a decision back can break them just as easily as
     * making a new one can, which would leave GEWISDB describing an organ that is not allowed to exist in that shape.
     *
     * Only requirements that hold right now and would stop holding are reported. An organ that is already in a shape
     * the regulations do not allow (an organ is founded before anyone is installed in it, for one) is not something
     * this annulment has to answer for, and refusing it would only make that state harder to get out of.
     *
     * @throws AnnulmentNotPossible when the change would break a requirement that currently holds.
     */
    private function assertOrgansRemainValid(
        DecisionModel $decision,
        bool $annulled,
    ): void {
        foreach ($this->findAffectedFoundations($decision) as $foundation) {
            $before = $this->findActiveInstallations(
                $foundation,
                $decision,
                !$annulled,
            );
            $after = $this->findActiveInstallations(
                $foundation,
                $decision,
                $annulled,
            );

            if (
                null === $before
                || null === $after
            ) {
                // The organ either does not exist yet (or any more) after the change, or it comes back exactly as it
                // was before it was abolished. Either way this decision does not shape its composition.
                continue;
            }

            $violations = $this->findOrganViolations(
                $foundation,
                $after,
            );

            if ([] === $violations) {
                continue;
            }

            $existing = $this->findOrganViolations(
                $foundation,
                $before,
            );

            foreach ($violations as $rule => $reason) {
                if (
                    array_key_exists(
                        $rule,
                        $existing,
                    )
                ) {
                    continue;
                }

                throw $this->cannot(
                    $decision,
                    $reason,
                );
            }
        }
    }

    /**
     * Find the organs whose composition is touched by the given decision.
     *
     * @return FoundationModel[]
     */
    private function findAffectedFoundations(DecisionModel $decision): array
    {
        $foundations = [];

        foreach ($decision->getSubdecisions() as $subDecision) {
            $foundation = match (true) {
                $subDecision instanceof FoundationModel => $subDecision,
                $subDecision instanceof FoundationReferenceModel => $subDecision->getFoundation(),
                $subDecision instanceof DischargeModel,
                $subDecision instanceof ReappointmentModel => $subDecision->getInstallation()->getFoundation(),
                default => null,
            };

            if (null === $foundation) {
                continue;
            }

            $foundations[$foundation->getHash()] = $foundation;
        }

        return $foundations;
    }

    /**
     * Determine who is installed in an organ, as if $toggled were annulled ($annulled) or not.
     *
     * Returns `null` when the organ does not exist at that point, either because it was never founded or because it
     * has been abolished.
     *
     * @return InstallationModel[]|null
     */
    private function findActiveInstallations(
        FoundationModel $foundation,
        DecisionModel $toggled,
        bool $annulled,
    ): ?array {
        if (
            $this->countsAsAnnulled(
                $foundation->getDecision(),
                $toggled,
                $annulled,
            )
        ) {
            return null;
        }

        $installations = [];
        $references = $this->meetingRepository->findReferencingSubDecisions(
            FoundationReferenceModel::class,
            'foundation',
            $foundation,
        );

        foreach ($references as $reference) {
            if (
                $this->countsAsAnnulled(
                    $reference->getDecision(),
                    $toggled,
                    $annulled,
                )
            ) {
                continue;
            }

            if ($reference instanceof AbrogationModel) {
                return null;
            }

            if (!($reference instanceof InstallationModel)) {
                continue;
            }

            foreach (
                $this->meetingRepository->findReferencingSubDecisions(
                    DischargeModel::class,
                    'installation',
                    $reference,
                ) as $discharge
            ) {
                if (
                    $this->countsAsAnnulled(
                        $discharge->getDecision(),
                        $toggled,
                        $annulled,
                    )
                ) {
                    continue;
                }

                continue 2;
            }

            $installations[] = $reference;
        }

        return $installations;
    }

    /**
     * Which requirements from the Articles of Association and Internal Regulations the given composition breaks.
     *
     * @param InstallationModel[] $installations
     *
     * @return array<string, string> reason per broken requirement, keyed so that the same one can be recognised
     *                               across two compositions.
     */
    private function findOrganViolations(
        FoundationModel $foundation,
        array $installations,
    ): array {
        $violations = [];
        $abbr = $foundation->getAbbr();
        $type = $foundation->getOrganType();

        /** @var array<int, array<string, InstallationFunctions>> $functionsPerMember */
        $functionsPerMember = [];

        foreach ($installations as $installation) {
            $function = $installation->getFunction();
            $functionsPerMember[$installation->getMember()->getLidnr()][$function->value] = $function;
        }

        $members = 0;
        $chairs = 0;

        foreach ($functionsPerMember as $functions) {
            $isMember = isset($functions[InstallationFunctions::Member->value]);
            $isInactive = isset($functions[InstallationFunctions::InactiveMember->value]);
            $hasFunction = [] !== array_filter(
                $functions,
                static fn (InstallationFunctions $function): bool => !$function->isAdministrative(),
            );

            if ($isMember) {
                $members++;
            }

            if (isset($functions[InstallationFunctions::Chair->value])) {
                $chairs++;
            }

            if (
                $isMember
                && $isInactive
            ) {
                $violations['active-and-inactive'] = sprintf(
                    $this->translator->trans(
                        'it would make someone an active and an inactive member of %s at once',
                    ),
                    $abbr,
                );
            }

            if (
                $isInactive
                && !$type->allowsInactiveMembers()
            ) {
                $violations['inactive-member'] = sprintf(
                    $this->translator->trans(
                        'it would leave someone an inactive member of %s, which does not have those',
                    ),
                    $abbr,
                );
            }

            if (
                !$hasFunction
                || $isMember
            ) {
                continue;
            }

            // Holding a function in an organ means being one of its (active) members; an inactive member holds none.
            $violations['function-without-membership'] = sprintf(
                $this->translator->trans(
                    'it would leave someone with a function in %s without being an active member of it',
                ),
                $abbr,
            );
        }

        if (
            $type->requiresChair()
            && 0 === $chairs
        ) {
            $violations['chair'] = sprintf(
                $this->translator->trans('it would leave %s without a chair'),
                $abbr,
            );
        }

        $minimum = $type->getMinimumMembers();

        if ($members < $minimum) {
            $violations['minimum-members'] = sprintf(
                $this->translator->trans('it would leave %s with fewer than %d active member(s)'),
                $abbr,
                $minimum,
            );
        }

        return $violations;
    }

    /**
     * Whether the given decision counts as annulled, with the annulled state of $toggled forced to $annulled.
     */
    private function countsAsAnnulled(
        DecisionModel $decision,
        DecisionModel $toggled,
        bool $annulled,
    ): bool {
        if ($decision->getHash() === $toggled->getHash()) {
            return $annulled;
        }

        return $this->isAnnulled($decision);
    }

    /**
     * @return string[] what is worth pointing out without standing in the way of the annulment.
     *
     * @throws AnnulmentNotPossible when another decision builds on the given subdecision.
     */
    private function assertNoDependents(SubDecisionModel $subDecision): array
    {
        $warnings = [];

        switch (true) {
            case $subDecision instanceof FoundationModel:
                // Every surviving reference (installation, discharge, abrogation) keeps the organ alive.
                $this->assertNone(
                    $this->findDependents(
                        FoundationReferenceModel::class,
                        'foundation',
                        $subDecision,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the organ is still referenced by other decisions'),
                );
                break;

            case $subDecision instanceof AbrogationModel:
                $foundation = $subDecision->getFoundation();
                $this->assertNone(
                    $this->findDependents(
                        AbrogationModel::class,
                        'foundation',
                        $foundation,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the organ was also abolished by another decision'),
                );
                $this->assertNone(
                    array_filter(
                        $this->findDependents(
                            InstallationModel::class,
                            'foundation',
                            $foundation,
                            $subDecision,
                        ),
                        fn (InstallationModel $installation): bool => $this->isAfter(
                            $installation,
                            $subDecision,
                        ),
                    ),
                    $subDecision,
                    $this->translator->trans('members were installed in the organ after it was abolished'),
                );
                break;

            case $subDecision instanceof InstallationModel:
                $this->assertNone(
                    $this->findDependents(
                        DischargeModel::class,
                        'installation',
                        $subDecision,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the member was discharged after being installed'),
                );
                $this->assertNone(
                    $this->findDependents(
                        ReappointmentModel::class,
                        'installation',
                        $subDecision,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the installation was prolonged after it was made'),
                );
                break;

            case $subDecision instanceof ReappointmentModel:
                // Reappointments run until a meeting decides otherwise, and annulling one shortens a term without
                // saying when it ended. GEWISDB cannot invent the discharge that would say so, so this is pointed out
                // rather than refused; whoever enters the annulment can follow it with a discharge if one is needed.
                $installation = $subDecision->getInstallation();
                $warnings = [
                    ...$warnings,
                    ...$this->warnAbout(
                        $this->findDependents(
                            DischargeModel::class,
                            'installation',
                            $installation,
                            $subDecision,
                        ),
                        $subDecision,
                        $this->translator->trans('the member was discharged after being reappointed'),
                    ),
                    ...$this->warnAbout(
                        array_filter(
                            $this->findDependents(
                                ReappointmentModel::class,
                                'installation',
                                $installation,
                                $subDecision,
                            ),
                            fn (ReappointmentModel $other): bool => $this->isAfter(
                                $other,
                                $subDecision,
                            ),
                        ),
                        $subDecision,
                        $this->translator->trans('the installation was prolonged again afterwards'),
                    ),
                ];
                break;

            case $subDecision instanceof DischargeModel:
                $this->assertNone(
                    $this->findDependents(
                        DischargeModel::class,
                        'installation',
                        $subDecision->getInstallation(),
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the member was also discharged by another decision'),
                );
                break;

            case $subDecision instanceof BoardInstallationModel:
                $this->assertNone(
                    $this->findDependents(
                        BoardReleaseModel::class,
                        'installation',
                        $subDecision,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the board member was released after being installed'),
                );
                $this->assertNone(
                    $this->findDependents(
                        BoardDischargeModel::class,
                        'installation',
                        $subDecision,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the board member was discharged after being installed'),
                );
                break;

            case $subDecision instanceof BoardReleaseModel:
                $installation = $subDecision->getInstallation();
                $this->assertNone(
                    $this->findDependents(
                        BoardReleaseModel::class,
                        'installation',
                        $installation,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the board member was also released by another decision'),
                );
                $this->assertNone(
                    $this->findDependents(
                        BoardDischargeModel::class,
                        'installation',
                        $installation,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the board member was discharged after being released'),
                );
                break;

            case $subDecision instanceof BoardDischargeModel:
                $this->assertNone(
                    $this->findDependents(
                        BoardDischargeModel::class,
                        'installation',
                        $subDecision->getInstallation(),
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the board member was also discharged by another decision'),
                );
                break;

            case $subDecision instanceof KeyGrantingModel:
                $this->assertNone(
                    $this->findDependents(
                        KeyWithdrawalModel::class,
                        'granting',
                        $subDecision,
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the key code was withdrawn after it was granted'),
                );
                break;

            case $subDecision instanceof KeyWithdrawalModel:
                $this->assertNone(
                    $this->findDependents(
                        KeyWithdrawalModel::class,
                        'granting',
                        $subDecision->getGranting(),
                        $subDecision,
                    ),
                    $subDecision,
                    $this->translator->trans('the key code was also withdrawn by another decision'),
                );
                break;
        }

        return $warnings;
    }

    /**
     * @param SubDecisionModel[] $dependents
     *
     * @return string[] one warning when there is at least one dependent, nothing otherwise.
     */
    private function warnAbout(
        array $dependents,
        SubDecisionModel $subDecision,
        string $reason,
    ): array {
        if ([] === $dependents) {
            return [];
        }

        $decision = $subDecision->getDecision();

        return [
            sprintf(
                $this->translator->trans(
                    '%s %d.%d.%d has been annulled, but %s. When the term ended now follows from the Internal'
                    . ' Regulations; record a discharge if one is needed.',
                ),
                $decision->getMeetingType()->value,
                $decision->getMeetingNumber(),
                $decision->getPoint(),
                $decision->getNumber(),
                $reason,
            ),
        ];
    }

    /**
     * @param SubDecisionModel[] $dependents
     *
     * @throws AnnulmentNotPossible when there is at least one dependent.
     */
    private function assertNone(
        array $dependents,
        SubDecisionModel $subDecision,
        string $reason,
    ): void {
        if ([] === $dependents) {
            return;
        }

        throw $this->cannot(
            $subDecision,
            $reason,
        );
    }

    private function cannot(
        DecisionModel|SubDecisionModel $decision,
        string $reason,
    ): AnnulmentNotPossible {
        $decision = $decision instanceof SubDecisionModel
            ? $decision->getDecision()
            : $decision;

        return new AnnulmentNotPossible(sprintf(
            $this->translator->trans('Decision %s %d.%d.%d cannot be annulled, because %s.'),
            $decision->getMeetingType()->value,
            $decision->getMeetingNumber(),
            $decision->getPoint(),
            $decision->getNumber(),
            $reason,
        ));
    }

    /**
     * Find every subdecision that concerns the same entity as the given subdecision.
     *
     * Where {@see self::findDependents()} looks only at what builds directly on a subdecision, this walks the whole
     * entity it belongs to: the organ, the board membership, or the key code. Founding and abolishing an organ affect
     * all of its members at once, so for those the entity is the organ as a whole; an installation only concerns the
     * one membership.
     *
     * @return SubDecisionModel[]
     */
    private function findRelated(SubDecisionModel $subDecision): array
    {
        $anchor = $this->findAnchor($subDecision);
        $related = [$anchor];

        switch (true) {
            case $anchor instanceof FoundationModel:
                foreach (
                    $this->meetingRepository->findReferencingSubDecisions(
                        FoundationReferenceModel::class,
                        'foundation',
                        $anchor,
                    ) as $reference
                ) {
                    $related[] = $reference;

                    if (!($reference instanceof InstallationModel)) {
                        continue;
                    }

                    $related = [
                        ...$related,
                        ...$this->findInstallationReferences($reference),
                    ];
                }

                break;

            case $anchor instanceof InstallationModel:
                $related = [
                    ...$related,
                    ...$this->findInstallationReferences($anchor),
                ];
                break;

            case $anchor instanceof BoardInstallationModel:
                foreach ([BoardReleaseModel::class, BoardDischargeModel::class] as $type) {
                    $related = [
                        ...$related,
                        ...$this->meetingRepository->findReferencingSubDecisions(
                            $type,
                            'installation',
                            $anchor,
                        ),
                    ];
                }

                break;

            case $anchor instanceof KeyGrantingModel:
                $related = [
                    ...$related,
                    ...$this->meetingRepository->findReferencingSubDecisions(
                        KeyWithdrawalModel::class,
                        'granting',
                        $anchor,
                    ),
                ];
                break;
        }

        return $related;
    }

    /**
     * @return SubDecisionModel[]
     */
    private function findInstallationReferences(InstallationModel $installation): array
    {
        $references = [];

        foreach ([DischargeModel::class, ReappointmentModel::class] as $type) {
            $references = [
                ...$references,
                ...$this->meetingRepository->findReferencingSubDecisions(
                    $type,
                    'installation',
                    $installation,
                ),
            ];
        }

        return $references;
    }

    /**
     * Determine which entity a subdecision belongs to.
     */
    private function findAnchor(SubDecisionModel $subDecision): SubDecisionModel
    {
        return match (true) {
            // An organ installation only concerns that one membership, but founding or abolishing an organ concerns
            // every membership in it.
            $subDecision instanceof InstallationModel => $subDecision,
            $subDecision instanceof FoundationReferenceModel => $subDecision->getFoundation(),
            $subDecision instanceof DischargeModel,
            $subDecision instanceof ReappointmentModel => $subDecision->getInstallation(),
            $subDecision instanceof BoardReleaseModel,
            $subDecision instanceof BoardDischargeModel => $subDecision->getInstallation(),
            $subDecision instanceof KeyWithdrawalModel => $subDecision->getGranting(),
            default => $subDecision,
        };
    }

    /**
     * Find the subdecisions of the given type that reference $referenced and that still count.
     *
     * @template T of SubDecisionModel
     *
     * @param class-string<T>  $type
     * @param non-empty-string $property
     *
     * @return T[]
     */
    private function findDependents(
        string $type,
        string $property,
        SubDecisionModel $referenced,
        SubDecisionModel $subDecision,
    ): array {
        return array_values(array_filter(
            $this->meetingRepository->findReferencingSubDecisions(
                $type,
                $property,
                $referenced,
            ),
            function (SubDecisionModel $reference) use ($subDecision): bool {
                if (
                    $this->isSameDecision(
                        $reference,
                        $subDecision,
                    )
                ) {
                    return false;
                }

                return !$this->isAnnulled($reference->getDecision());
            },
        ));
    }

    /**
     * Whether the given decision has been annulled.
     *
     * Looked up rather than read off the inverse side, so that an annulment made earlier in this same request counts
     * as well.
     */
    private function isAnnulled(DecisionModel $decision): bool
    {
        return [] !== $this->meetingRepository->findReferencingSubDecisions(
            AnnulmentModel::class,
            'target',
            $decision,
        );
    }

    private function isSameDecision(
        SubDecisionModel $a,
        SubDecisionModel $b,
    ): bool {
        return $a->getMeetingType() === $b->getMeetingType()
            && $a->getMeetingNumber() === $b->getMeetingNumber()
            && $a->getDecisionPoint() === $b->getDecisionPoint()
            && $a->getDecisionNumber() === $b->getDecisionNumber();
    }

    /**
     * Determine if $a occurs after $b in the ledger.
     *
     * Meetings of different types have independent numbering, so the meeting date is what orders them; within a single
     * meeting the position of the (sub)decision takes over.
     */
    private function isAfter(
        SubDecisionModel $a,
        SubDecisionModel $b,
    ): bool {
        $dateA = $a->getDecision()->getMeeting()->getDate()->getTimestamp();
        $dateB = $b->getDecision()->getMeeting()->getDate()->getTimestamp();

        if ($dateA !== $dateB) {
            return $dateA > $dateB;
        }

        if ($a->getMeetingType() !== $b->getMeetingType()) {
            return $a->getMeetingType()->value > $b->getMeetingType()->value;
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

    /**
     * Whether decision $a was taken before decision $b.
     *
     * Meetings of different types have independent numbering, so the meeting date is what orders them; within a single
     * meeting the position of the decision takes over.
     */
    public function isBefore(
        DecisionModel $a,
        DecisionModel $b,
    ): bool {
        $dateA = $a->getMeeting()->getDate()->getTimestamp();
        $dateB = $b->getMeeting()->getDate()->getTimestamp();

        if ($dateA !== $dateB) {
            return $dateA < $dateB;
        }

        if ($a->getMeetingType() !== $b->getMeetingType()) {
            return $a->getMeetingType()->value < $b->getMeetingType()->value;
        }

        if ($a->getMeetingNumber() !== $b->getMeetingNumber()) {
            return $a->getMeetingNumber() < $b->getMeetingNumber();
        }

        if ($a->getPoint() !== $b->getPoint()) {
            return $a->getPoint() < $b->getPoint();
        }

        return $a->getNumber() < $b->getNumber();
    }

    /**
     * Whether the given decision is an annulment of another decision.
     */
    public function isAnnulling(DecisionModel $decision): bool
    {
        foreach ($decision->getSubdecisions() as $subDecision) {
            if ($subDecision instanceof AnnulmentModel) {
                return true;
            }
        }

        return false;
    }
}
