<?php

declare(strict_types=1);

namespace App\Tests\Validator\Database;

use App\Service\Application\MailHostResolver;
use App\Validator\Database\DeliverableEmailAddress;
use App\Validator\Database\DeliverableEmailAddressValidator;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<DeliverableEmailAddressValidator>
 */
#[CoversClass(DeliverableEmailAddressValidator::class)]
class DeliverableEmailAddressValidatorTest extends ConstraintValidatorTestCase
{
    /** @var MailHostResolver&MockObject */
    private MailHostResolver $mailHostResolver;

    #[Override]
    protected function createValidator(): DeliverableEmailAddressValidator
    {
        $this->mailHostResolver = $this->createMock(MailHostResolver::class);

        return new DeliverableEmailAddressValidator($this->mailHostResolver);
    }

    public function testAcceptsAnAddressWhoseHostCanTakeMail(): void
    {
        $this->mailHostResolver->expects(self::once())
            ->method('canReceiveMail')
            ->with('gewis.nl')
            ->willReturn(true);

        $this->validate('secretary@gewis.nl', new DeliverableEmailAddress());

        $this->assertNoViolation();
    }

    /**
     * The host is what follows the *last* `@`. The `html5` mode of the Email constraint next to this one never lets
     * a quoted local part through, so this cannot arrive from the registration form; the parsing does not lean on
     * that.
     */
    #[DataProvider('addressesAndTheirHost')]
    public function testAsksAboutTheHostAfterTheLastAtSign(
        string $address,
        string $hostname,
    ): void {
        $this->mailHostResolver->expects(self::once())
            ->method('canReceiveMail')
            ->with($hostname)
            ->willReturn(false);

        $constraint = new DeliverableEmailAddress();

        $this->validate($address, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ hostname }}', $hostname)
            ->setCode(DeliverableEmailAddress::NO_MX_RECORD_ERROR)
            ->assertRaised();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function addressesAndTheirHost(): array
    {
        return [
            'an ordinary address' => ['someone@nowhere.example', 'nowhere.example'],
            'an at sign in the local part' => ['"we@ird"@nowhere.example', 'nowhere.example'],
        ];
    }

    /**
     * Whether the address is shaped like one is the syntax constraint's business, and reporting it twice would put
     * two errors under one field. Nothing is looked up for input that never gets that far.
     */
    #[DataProvider('inputThisConstraintLeavesAlone')]
    public function testLeavesAnythingThatIsNotAnAddressToTheOtherConstraints(?string $value): void
    {
        $this->mailHostResolver->expects(self::never())->method('canReceiveMail');

        $this->validate($value, new DeliverableEmailAddress());

        $this->assertNoViolation();
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function inputThisConstraintLeavesAlone(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'no at sign' => ['not-an-address'],
            'nothing after the at sign' => ['someone@'],
        ];
    }
}
