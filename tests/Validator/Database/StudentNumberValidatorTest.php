<?php

declare(strict_types=1);

namespace App\Tests\Validator\Database;

use App\Validator\Database\StudentNumber;
use App\Validator\Database\StudentNumberValidator;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<StudentNumberValidator>
 */
#[CoversClass(StudentNumberValidator::class)]
class StudentNumberValidatorTest extends ConstraintValidatorTestCase
{
    #[Override]
    protected function createValidator(): StudentNumberValidator
    {
        return new StudentNumberValidator();
    }

    public function testAcceptsANumberThatSatisfiesTheElfproef(): void
    {
        $this->validate('1234560', new StudentNumber());

        $this->assertNoViolation();
    }

    /**
     * A student number is optional in the forms that ask for it; only what is filled in has to be right.
     */
    #[DataProvider('emptyValues')]
    public function testLeavesAnEmptyValueToTheOtherConstraints(?string $value): void
    {
        $this->validate($value, new StudentNumber());

        $this->assertNoViolation();
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function emptyValues(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    #[DataProvider('malformedNumbers')]
    public function testRejectsWhatIsNotSevenDigits(string $value): void
    {
        $constraint = new StudentNumber();

        $this->validate($value, $constraint);

        $this->buildViolation($constraint->invalidFormatMessage)
            ->setCode(StudentNumber::INVALID_FORMAT_ERROR)
            ->assertRaised();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedNumbers(): array
    {
        return [
            'too short' => ['123456'],
            'too long' => ['12345600'],
            'not a number' => ['abcdefg'],
            'a digit short of it' => ['123456a'],
        ];
    }

    #[DataProvider('numbersFailingTheElfproef')]
    public function testRejectsWhatDoesNotSatisfyTheElfproef(string $value): void
    {
        $constraint = new StudentNumber();

        $this->validate($value, $constraint);

        $this->buildViolation($constraint->failsElfproefMessage)
            ->setCode(StudentNumber::FAILS_ELFPROEF_ERROR)
            ->assertRaised();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function numbersFailingTheElfproef(): array
    {
        return [
            'a typo in the last digit' => ['1234561'],
            // Zeroes weigh nothing, so the sum is trivially divisible by eleven. It is still not a student number.
            'all zeroes' => ['0000000'],
        ];
    }

    #[DataProvider('elfproefOutcomes')]
    public function testWeighsEachDigitByItsPosition(
        string $studentNumber,
        bool $passes,
    ): void {
        self::assertSame(
            $passes,
            StudentNumberValidator::passesElfproef($studentNumber),
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function elfproefOutcomes(): array
    {
        return [
            // 7·1 + 6·2 + 5·3 + 4·4 + 3·5 + 2·6 + 1·0 = 77, which is 7 × 11.
            'weighted sum divisible by eleven' => ['1234560', true],
            'the same digits in another order' => ['1234506', false],
            'sum of zero' => ['0000000', false],
        ];
    }
}
