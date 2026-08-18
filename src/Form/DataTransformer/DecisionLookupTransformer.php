<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Repository\Database\MeetingRepository;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_array;
use function sprintf;

/**
 * Resolves the decision referenced by a form from the four parts of its identity.
 *
 * @implements DataTransformerInterface<Decision, array{
 *     meeting_type: MeetingTypes|null,
 *     meeting_number: string|null,
 *     point: string|null,
 *     number: string|null,
 * }>
 */
final readonly class DecisionLookupTransformer implements DataTransformerInterface
{
    public function __construct(private MeetingRepository $meetingRepository)
    {
    }

    /**
     * @return array{
     *     meeting_type: MeetingTypes|null,
     *     meeting_number: string|null,
     *     point: string|null,
     *     number: string|null,
     * }
     */
    #[Override]
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return [
                'meeting_type' => null,
                'meeting_number' => null,
                'point' => null,
                'number' => null,
            ];
        }

        return [
            'meeting_type' => $value->getMeetingType(),
            'meeting_number' => (string) $value->getMeetingNumber(),
            'point' => (string) $value->getPoint(),
            'number' => (string) $value->getNumber(),
        ];
    }

    #[Override]
    public function reverseTransform(mixed $value): Decision
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('No decision was given.');
        }

        $meetingType = $value['meeting_type'] ?? null;
        $meetingNumber = $value['meeting_number'] ?? null;
        $point = $value['point'] ?? null;
        $number = $value['number'] ?? null;

        if (
            !($meetingType instanceof MeetingTypes)
            || null === $meetingNumber
            || '' === $meetingNumber
            || null === $point
            || '' === $point
            || null === $number
            || '' === $number
        ) {
            throw new TransformationFailedException('No decision was given.');
        }

        $decision = $this->meetingRepository->findDecision(
            $meetingType,
            (int) $meetingNumber,
            (int) $point,
            (int) $number,
        );

        if (null === $decision) {
            throw new TransformationFailedException(sprintf(
                'There is no decision %s %s.%s.%s.',
                $meetingType->value,
                $meetingNumber,
                $point,
                $number,
            ));
        }

        return $decision;
    }
}
