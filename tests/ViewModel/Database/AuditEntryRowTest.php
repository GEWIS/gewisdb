<?php

declare(strict_types=1);

namespace App\Tests\ViewModel\Database;

use App\Entity\Database\AuditEntry;
use App\ViewModel\Database\AuditEntryRow;
use DateTime;
use DateTimeInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(AuditEntryRow::class)]
class AuditEntryRowTest extends TestCase
{
    /**
     * An audit entry's format string is the entity's own and carries markup, so the template prints the composed
     * result unescaped. Its arguments are not: an AuditNote's include the free text a secretary typed.
     */
    public function testEscapesArgumentsButKeepsTheFormatStringsMarkup(): void
    {
        $row = AuditEntryRow::fromEntry(
            $this->entry(
                '<strong>%s</strong> noted: %s',
                ['Jan Jansen', '<script>alert(1)</script>'],
            ),
            $this->passthroughTranslator(),
        );

        self::assertStringContainsString('<strong>Jan Jansen</strong>', $row->body);
        self::assertStringNotContainsString('<script>', $row->body);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $row->body);
    }

    public function testEscapesQuotesSoAnArgumentCannotBreakOutOfAnAttribute(): void
    {
        $row = AuditEntryRow::fromEntry(
            $this->entry('<span title="%s">note</span>', ['" onmouseover="alert(1)']),
            $this->passthroughTranslator(),
        );

        self::assertStringNotContainsString('onmouseover="alert(1)"', $row->body);
        self::assertStringContainsString('&quot;', $row->body);
    }

    public function testTranslatesTheFormatStringBeforeInterpolating(): void
    {
        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id): string => 'note by %s' === $id ? 'notitie van %s' : $id,
        );

        $row = AuditEntryRow::fromEntry($this->entry('note by %s', ['Jan']), $translator);

        self::assertSame('notitie van Jan', $row->body);
    }

    public function testCarriesTheEntrysTimestampAndAuthor(): void
    {
        $updatedAt = new DateTime('2026-02-03 04:05:06');
        $row = AuditEntryRow::fromEntry($this->entry('x', [], $updatedAt, 'secretary'), $this->passthroughTranslator());

        self::assertSame($updatedAt, $row->updatedAt);
        self::assertSame('secretary', $row->user);
    }

    /**
     * A real subclass rather than a mock: getStringPlain() is final, which is the point of the contract.
     *
     * @param string[] $arguments
     */
    private function entry(
        string $bodyFormatted,
        array $arguments,
        ?DateTimeInterface $updatedAt = null,
        string $userName = 'someone',
    ): AuditEntry {
        return new class ($bodyFormatted, $arguments, $updatedAt ?? new DateTime(), $userName) extends AuditEntry {
            /**
             * @param string[] $arguments
             */
            public function __construct(
                private readonly string $bodyFormatted,
                private readonly array $arguments,
                private readonly DateTimeInterface $when,
                private readonly string $who,
            ) {
            }

            #[Override]
            public function getUpdatedAt(): DateTimeInterface
            {
                return $this->when;
            }

            #[Override]
            public function getUserName(): string
            {
                return $this->who;
            }

            #[Override]
            protected function getStringBodyFormatted(): string
            {
                return $this->bodyFormatted;
            }

            /**
             * @return string[]
             */
            #[Override]
            protected function getStringArguments(): array
            {
                return $this->arguments;
            }
        };
    }

    private function passthroughTranslator(): TranslatorInterface
    {
        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return $translator;
    }
}
