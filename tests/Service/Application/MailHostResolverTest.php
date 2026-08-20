<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Service\Application\MailHostResolver;
use Egulias\EmailValidator\Validation\DNSGetRecordWrapper;
use Egulias\EmailValidator\Validation\DNSRecords;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailHostResolver::class)]
class MailHostResolverTest extends TestCase
{
    public function testAcceptsAHostWithAnMxRecord(): void
    {
        self::assertTrue(
            $this->resolver(
                new DNSRecords([
                    [
                        'type' => 'MX',
                        'target' => 'mx.gewis.nl',
                    ],
                ]),
            )->canReceiveMail('gewis.nl'),
        );
    }

    /**
     * A host without MX records still accepts mail on its address records (RFC 5321 section 5.1).
     */
    public function testAcceptsAHostThatOnlyHasAddressRecords(): void
    {
        self::assertTrue(
            $this->resolver(
                new DNSRecords([
                    [
                        'type' => 'A',
                        'ip' => '203.0.113.10',
                    ],
                ]),
            )->canReceiveMail('mailonly.gewis.nl'),
        );
    }

    public function testRefusesAHostWithNoRecordsAtAll(): void
    {
        self::assertFalse(
            $this->resolver(new DNSRecords([]))->canReceiveMail('nowhere.gewis.nl'),
        );
    }

    /**
     * A domain publishing a null MX says outright that it accepts no mail (RFC 7505), which the pair of
     * `getmxrr()`/`gethostbynamel()` calls this replaced read as "has an MX record, so it is fine".
     */
    public function testRefusesAHostThatSaysItTakesNoMail(): void
    {
        self::assertFalse(
            $this->resolver(
                new DNSRecords([
                    [
                        'type' => 'MX',
                        'target' => '.',
                    ],
                ]),
            )->canReceiveMail('nomail.gewis.nl'),
        );
    }

    /**
     * A check that cannot be performed refuses rather than waves the address through, or it would stop being a check
     * exactly when something is wrong. The outage is logged, because otherwise it reads as a run of visitors who all
     * typed their address wrong.
     */
    public function testRefusesAHostTheResolverCouldNotAnswerForAndSaysSo(): void
    {
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                self::anything(),
                ['hostname' => 'gewis.nl'],
            );

        self::assertFalse(
            $this->resolver(new DNSRecords([], true), $logger)->canReceiveMail('gewis.nl'),
        );
    }

    /**
     * A domain that simply is not there is not an outage, and saying so on every typo would drown the log.
     */
    public function testDoesNotReportADomainThatDoesNotExistAsAFailure(): void
    {
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        self::assertFalse(
            $this->resolver(new DNSRecords([]), $logger)->canReceiveMail('nowhere.gewis.nl'),
        );
    }

    private function resolver(
        DNSRecords $records,
        ?LoggerInterface $logger = null,
    ): MailHostResolver {
        $wrapper = self::createMock(DNSGetRecordWrapper::class);
        $wrapper->expects(self::atLeastOnce())
            ->method('getRecords')
            ->willReturn($records);

        return new MailHostResolver(
            $logger ?? self::createStub(LoggerInterface::class),
            $wrapper,
        );
    }
}
