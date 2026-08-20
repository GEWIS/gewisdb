<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Repository\Database\MeetingRepository;
use DateTimeInterface;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_array;
use function sprintf;

/**
 * Resolves the meeting a decision belongs to from the type and number carried in the form.
 *
 * The date is only carried along for the templates and for the key code date checks; the meeting is identified by its
 * type and number alone, so whatever date was submitted is never written back onto it.
 *
 * @implements DataTransformerInterface<Meeting, array{
 *     type: MeetingTypes|null,
 *     number: string|null,
 *     date: string|null,
 * }>
 */
final readonly class MeetingLookupTransformer implements DataTransformerInterface
{
    public function __construct(private MeetingRepository $meetingRepository)
    {
    }

    /** @return array{type: MeetingTypes|null, number: string|null, date: string|null} */
    #[Override]
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return [
                'type' => null,
                'number' => null,
                'date' => null,
            ];
        }

        return [
            'type' => $value->getType(),
            'number' => (string) $value->getNumber(),
            'date' => $value->getDate()->format(DateTimeInterface::ATOM),
        ];
    }

    #[Override]
    public function reverseTransform(mixed $value): Meeting
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('No meeting was given.');
        }

        $type = $value['type'] ?? null;
        $number = $value['number'] ?? null;

        if (
            !($type instanceof MeetingTypes)
            || null === $number
            || '' === $number
        ) {
            throw new TransformationFailedException('No meeting was given.');
        }

        $meeting = $this->meetingRepository->findMeeting(
            $type,
            (int) $number,
        );

        if (null === $meeting) {
            throw new TransformationFailedException(sprintf(
                'There is no %s %s.',
                $type->value,
                $number,
            ));
        }

        return $meeting;
    }
}
