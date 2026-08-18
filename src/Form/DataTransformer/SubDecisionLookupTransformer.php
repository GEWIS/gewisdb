<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\SubDecision;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_array;
use function sprintf;

/**
 * Resolves the subdecision referenced by a form from the five parts of its identity.
 *
 * The subdecision class narrows the lookup: a form that points at the foundation of an organ must not resolve to the
 * key code granting that happens to sit at the same position.
 *
 * @template T of SubDecision
 *
 * @implements DataTransformerInterface<T, array{
 *     meeting_type: MeetingTypes|null,
 *     meeting_number: string|null,
 *     decision_point: string|null,
 *     decision_number: string|null,
 *     sequence: string|null,
 * }>
 */
final readonly class SubDecisionLookupTransformer implements DataTransformerInterface
{
    /** @param class-string<T> $subDecisionClass */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $subDecisionClass,
    ) {
    }

    /**
     * @return array{
     *     meeting_type: MeetingTypes|null,
     *     meeting_number: string|null,
     *     decision_point: string|null,
     *     decision_number: string|null,
     *     sequence: string|null,
     * }
     */
    #[Override]
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return [
                'meeting_type' => null,
                'meeting_number' => null,
                'decision_point' => null,
                'decision_number' => null,
                'sequence' => null,
            ];
        }

        return [
            'meeting_type' => $value->getMeetingType(),
            'meeting_number' => (string) $value->getMeetingNumber(),
            'decision_point' => (string) $value->getDecisionPoint(),
            'decision_number' => (string) $value->getDecisionNumber(),
            'sequence' => (string) $value->getSequence(),
        ];
    }

    /** @return T */
    #[Override]
    public function reverseTransform(mixed $value): SubDecision
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('No subdecision was given.');
        }

        $meetingType = $value['meeting_type'] ?? null;
        $meetingNumber = $value['meeting_number'] ?? null;
        $decisionPoint = $value['decision_point'] ?? null;
        $decisionNumber = $value['decision_number'] ?? null;
        $sequence = $value['sequence'] ?? null;

        if (
            !($meetingType instanceof MeetingTypes)
            || null === $meetingNumber
            || '' === $meetingNumber
            || null === $decisionPoint
            || '' === $decisionPoint
            || null === $decisionNumber
            || '' === $decisionNumber
            || null === $sequence
            || '' === $sequence
        ) {
            throw new TransformationFailedException('No subdecision was given.');
        }

        $subDecision = $this->entityManager->getRepository($this->subDecisionClass)->findOneBy([
            'meeting_type' => $meetingType,
            'meeting_number' => (int) $meetingNumber,
            'decision_point' => (int) $decisionPoint,
            'decision_number' => (int) $decisionNumber,
            'sequence' => (int) $sequence,
        ]);

        if (null === $subDecision) {
            throw new TransformationFailedException(sprintf(
                'There is no %s at %s %s.%s.%s.%s.',
                $this->subDecisionClass,
                $meetingType->value,
                $meetingNumber,
                $decisionPoint,
                $decisionNumber,
                $sequence,
            ));
        }

        return $subDecision;
    }
}
