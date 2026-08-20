<?php

declare(strict_types=1);

namespace App\Service\Application;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\UnableToGetDNSRecord;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\DNSGetRecordWrapper;
use Psr\Log\LoggerInterface;

/**
 * Whether a hostname can be delivered mail, as far as DNS can say.
 *
 * The lookup itself is `egulias/email-validator`'s, which is already here for `symfony/mime` and knows three things
 * a pair of `getmxrr()`/`gethostbynamel()` calls does not: a null MX (RFC 7505) is a domain saying it accepts no
 * mail rather than one with an MX record, a host may be reachable over AAAA alone, and a resolver that could not
 * answer is a different outcome from a domain that does not exist.
 *
 * That last distinction is what this class is here to act on, and it acts on it by refusing. A check that lets
 * addresses through whenever it cannot perform the check has stopped being a check, and what it would let through is
 * expensive: the registration e-mail carries the payment link, so someone whose address does not work can pay and
 * then have no way back into the flow. An outage is logged instead, because a resolver that cannot answer is the
 * deployment's problem to notice rather than something to read off a wave of rejected registrations.
 *
 * Note that the library walks up the domain labels, so `someone@typo.gewis.nl` is accepted on `gewis.nl`'s records.
 * A subdomain typo therefore gets through where the hand-rolled check caught it; being right about null MX and about
 * resolver failures is worth more than being right about that.
 *
 * Deliberately not final: it is the seam a test replaces to keep the network out of the suite.
 */
readonly class MailHostResolver
{
    public function __construct(
        private LoggerInterface $logger,
        private DNSGetRecordWrapper $dnsGetRecordWrapper = new DNSGetRecordWrapper(),
    ) {
    }

    public function canReceiveMail(string $hostname): bool
    {
        $validation = new DNSCheckValidation($this->dnsGetRecordWrapper);

        // The library takes an address and looks at what follows the last `@`; the caller already has the host, so
        // it is given a local part to hang it on.
        if (
            $validation->isValid(
                'postmaster@' . $hostname,
                new EmailLexer(),
            )
        ) {
            return true;
        }

        if ($validation->getError()?->reason() instanceof UnableToGetDNSRecord) {
            $this->logger->warning(
                'The mail host of an address could not be looked up; the address was refused.',
                ['hostname' => $hostname],
            );
        }

        return false;
    }
}
