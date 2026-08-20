<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Database\Member;
use App\Repository\Database\MemberRepository;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_array;
use function sprintf;

/**
 * Resolves the member picked in a form from the membership number the autocomplete filled in.
 *
 * @implements DataTransformerInterface<Member, array{
 *     name: string|null,
 *     lidnr: string|null,
 * }>
 */
final readonly class MemberLookupTransformer implements DataTransformerInterface
{
    public function __construct(private MemberRepository $memberRepository)
    {
    }

    /** @return array{name: string|null, lidnr: string|null} */
    #[Override]
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return [
                'name' => null,
                'lidnr' => null,
            ];
        }

        return [
            'name' => $value->getFullName(),
            'lidnr' => (string) $value->getLidnr(),
        ];
    }

    #[Override]
    public function reverseTransform(mixed $value): Member
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('No member was given.');
        }

        $lidnr = $value['lidnr'] ?? null;

        if (
            null === $lidnr
            || '' === $lidnr
        ) {
            throw new TransformationFailedException('No member was given.');
        }

        $member = $this->memberRepository->findSimple((int) $lidnr);

        if (null === $member) {
            throw new TransformationFailedException(sprintf('There is no member %s.', $lidnr));
        }

        return $member;
    }
}
