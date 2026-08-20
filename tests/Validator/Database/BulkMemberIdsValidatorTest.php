<?php

declare(strict_types=1);

namespace App\Tests\Validator\Database;

use App\Validator\Database\BulkMemberIds;
use App\Validator\Database\BulkMemberIdsValidator;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<BulkMemberIdsValidator>
 */
#[CoversClass(BulkMemberIdsValidator::class)]
#[CoversClass(BulkMemberIds::class)]
class BulkMemberIdsValidatorTest extends ConstraintValidatorTestCase
{
    #[Override]
    protected function createValidator(): BulkMemberIdsValidator
    {
        return new BulkMemberIdsValidator();
    }

    /**
     * Membership numbers are pasted in from a spreadsheet or a mail, so anything that separates them has to work.
     */
    #[DataProvider('separators')]
    public function testAcceptsNumbersHoweverTheyAreSeparated(string $input): void
    {
        $this->validate($input, new BulkMemberIds());

        $this->assertNoViolation();
        self::assertSame(
            [
                '1',
                '22',
                '333',
            ],
            BulkMemberIds::tokenize($input),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function separators(): array
    {
        return [
            'spaces' => ['1 22 333'],
            'commas' => ['1,22,333'],
            'semicolons' => ['1;22;333'],
            'newlines' => ["1\n22\n333"],
            'a mix, with slack around it' => ["  1, 22;\n333  "],
        ];
    }

    #[DataProvider('inputWithNothingInIt')]
    public function testRefusesAnEmptySelection(string $input): void
    {
        $constraint = new BulkMemberIds();

        $this->validate($input, $constraint);

        $this->buildViolation($constraint->emptyMessage)
            ->setCode(BulkMemberIds::EMPTY_ERROR)
            ->assertRaised();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function inputWithNothingInIt(): array
    {
        return [
            'empty' => [''],
            'only whitespace' => ["  \n "],
        ];
    }

    /**
     * A field that was never filled in is the required constraint's business, not this one's.
     */
    public function testLeavesAMissingValueAlone(): void
    {
        $this->validate(null, new BulkMemberIds());

        $this->assertNoViolation();
    }

    public function testNamesTheTokenThatIsNotAMembershipNumber(): void
    {
        $constraint = new BulkMemberIds();

        $this->validate('1 abc 333', $constraint);

        $this->buildViolation($constraint->nonNumericMessage)
            ->setParameter('{{ value }}', 'abc')
            ->setCode(BulkMemberIds::NON_NUMERIC_ERROR)
            ->assertRaised();
    }

    public function testNamesTheNumberThatWasGivenTwice(): void
    {
        $constraint = new BulkMemberIds();

        $this->validate('1 22 1', $constraint);

        $this->buildViolation($constraint->duplicateMessage)
            ->setParameter('{{ value }}', '1')
            ->setCode(BulkMemberIds::DUPLICATE_ERROR)
            ->assertRaised();
    }

    /**
     * Every bad token is reported, so a long paste does not have to be fixed one error at a time.
     */
    public function testReportsEveryProblemInOnePass(): void
    {
        $constraint = new BulkMemberIds();

        $this->validate('1 abc 1 def', $constraint);

        $this->buildViolation($constraint->nonNumericMessage)
            ->setParameter('{{ value }}', 'abc')
            ->setCode(BulkMemberIds::NON_NUMERIC_ERROR)
            ->buildNextViolation($constraint->duplicateMessage)
            ->setParameter('{{ value }}', '1')
            ->setCode(BulkMemberIds::DUPLICATE_ERROR)
            ->buildNextViolation($constraint->nonNumericMessage)
            ->setParameter('{{ value }}', 'def')
            ->setCode(BulkMemberIds::NON_NUMERIC_ERROR)
            ->assertRaised();
    }
}
