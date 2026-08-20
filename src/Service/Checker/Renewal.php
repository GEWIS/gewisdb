<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\RenewalLink as RenewalLinkModel;
use App\Entity\Report\OrganMember as OrganMemberModel;
use App\Repository\Checker\MemberRepository;
use App\Repository\Database\ActionLinkRepository;
use App\Repository\Report\MemberRepository as ReportMemberRepository;
use App\Service\Application\Email as EmailService;
use DateInterval;
use DateTime;
use Throwable;

/**
 * Renewal class that takes care of renewing graduates
 * and converting memberships to graduates
 */
class Renewal
{
    public function __construct(
        private readonly ActionLinkRepository $actionLinkRepository,
        private readonly MemberRepository $memberRepository,
        private readonly ReportMemberRepository $reportMemberRepository,
        private readonly EmailService $emailService,
        private readonly string $publicUrl,
    ) {
    }

    /**
     * Create an actionlink and send emails to expiring graduates
     * Emails are sent 45 days before expiry
     * A limit of 10 graduates is used; e.g. on a cronjob each hour this would mean 250 per day
     * Limiting to make sure the secretary does not get overwhelmed with questions regarding renewal.
     */
    public function sendRenewalGraduates(): void
    {
        $expiresWithin = new DateTime();
        $expiresWithin->add(new DateInterval('P45D'));
        $limit = 10;
        $graduates = $this->memberRepository->getExpiringGraduates(
            $expiresWithin,
            $limit,
        );

        foreach ($graduates as $graduate) {
            $renewalLink = $this->actionLinkRepository->createRenewalByMember($graduate);

            try {
                $this->sendRenewalEmail($renewalLink);
            } catch (Throwable $e) {
                $this->actionLinkRepository->remove($renewalLink);

                throw $e;
            }
        }
    }

    private function sendRenewalEmail(RenewalLinkModel $link): void
    {
        $reportMember = $this->reportMemberRepository->findSimple($link->getMember()->getLidnr());
        $isInstalled = !$reportMember->getOrganInstallations()
            ->filter(static fn (OrganMemberModel $member) => $member->isCurrent())
            ->isEmpty();

        $this->emailService->send(
            $link->getMember()->getEmailRecipient(),
            'Graduate Renewal (' . $link->getMember()->getLidnr() . ')',
            'email/graduate-renewal.html.twig',
            [
                'firstName' => $link->getMember()->getFirstName(),
                'isInstalled' => $isInstalled,
                'currentExpiration' => $link->getCurrentExpiration(),
                'newExpiration' => $link->getNewExpiration(),
                'url' => $this->publicUrl . '/renew/' . $link->getToken(),
            ],
            // The secretary keeps a copy of both renewal messages, as they did when every templated e-mail was
            // blind-copied to the reply-to address. Unlike the registration e-mails there is no separate send here.
            bccReplyTo: true,
        );
    }

    public function sendRenewalSuccessEmail(RenewalLinkModel $link): void
    {
        $this->emailService->send(
            $link->getMember()->getEmailRecipient(),
            'Graduate Renewal (' . $link->getMember()->getLidnr() . ')',
            'email/graduate-renewal-success.html.twig',
            [
                'firstName' => $link->getMember()->getFirstName(),
                'oldExpiration' => $link->getCurrentExpiration(),
                'newExpiration' => $link->getNewExpiration(),
            ],
            bccReplyTo: true,
        );
    }
}
