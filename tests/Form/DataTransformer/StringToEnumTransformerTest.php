<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Entity\Database\Enums\MembershipTypes;
use App\Form\DataTransformer\StringToEnumTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[CoversClass(StringToEnumTransformer::class)]
class StringToEnumTransformerTest extends TestCase
{
    public function testRendersAnEnumAsItsBackingValue(): void
    {
        self::assertSame(
            'graduate',
            $this->transformer()->transform(MembershipTypes::Graduate),
        );
    }

    public function testRendersNothingWhenTheFieldIsUnset(): void
    {
        self::assertSame(
            '',
            $this->transformer()->transform(null),
        );
    }

    public function testReadsTheSubmittedValueBackAsTheEnum(): void
    {
        self::assertSame(
            MembershipTypes::Ordinary,
            $this->transformer()->reverseTransform('ordinary'),
        );
    }

    /**
     * Mapping runs before validation, so an empty submission has to fail here rather than write null into a
     * non-nullable property.
     */
    public function testRefusesAnEmptySubmission(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer()->reverseTransform('');
    }

    public function testRefusesAMissingSubmission(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer()->reverseTransform(null);
    }

    public function testRefusesAValueTheEnumDoesNotHave(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer()->reverseTransform('life');
    }

    /**
     * @return StringToEnumTransformer<MembershipTypes>
     */
    private function transformer(): StringToEnumTransformer
    {
        return new StringToEnumTransformer(MembershipTypes::class);
    }
}
