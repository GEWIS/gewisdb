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
use Twig\Environment;

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
        private readonly Environment $twig,
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
        $graduates = $this->memberRepository->getExpiringGraduates($expiresWithin, $limit);

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

        $body = $this->render(
            'email/graduate-renewal.html.twig',
            [
                'firstName' => $link->getMember()->getFirstName(),
                'isInstalled' => $isInstalled,
                'currentExpiration' => $link->getCurrentExpiration(),
                'newExpiration' => $link->getNewExpiration(),
                'url' => $this->publicUrl . '/renew/' . $link->getToken(),
                //TODO: If global config exists, we should make the secretary a global config option
            ],
        );

        $this->emailService->sendEmailTemplate(
            $link->getMember()->getEmailRecipient(),
            'Membership notification',
            'Expiring graduate status',
            $body,
            'GEWIS Graduate Renewal',
            'More information',
            '<p>You are currently registered as a graduate of GEWIS. This is a status
                assigned to members who are no longer active within GEWIS and also are no longer studying.
                <br><br>
                Graduates do not pay contribution and as a graduate,
                you can still join GEWIS activities or visit the social drink like you used to.
                However, sometimes you have to pay an extra fee to join an (expensive) activity.
                You can also no longer serve on the board of GEWIS or vote during the GMM.
                <br><br>
                Article 3.1 of the Internal Regulations allows you to request renewal of your status as graduate.
                Therefore, you are receiving this email.</p>',
            'You receive this message because your registration as a graduate of GEWIS is almost ending.
                You can not opt-out of these emails.',
            'Graduate Renewal (' . $link->getMember()->getLidnr() . ')',
        );
    }

    public function sendRenewalSuccessEmail(RenewalLinkModel $link): void
    {
        $body = $this->render(
            'email/graduate-renewal-success.html.twig',
            [
                'firstName' => $link->getMember()->getFirstName(),
                'oldExpiration' => $link->getCurrentExpiration(),
                'newExpiration' => $link->getNewExpiration(),
                //TODO: If global config exists, we should make the secretary a global config option
            ],
        );

        $this->emailService->sendEmailTemplate(
            $link->getMember()->getEmailRecipient(),
            'Membership notification',
            'Renewed graduate status',
            $body,
            'GEWIS Graduate Renewal',
            null,
            null,
            'You receive this message because you have requested renewal of your registration as a graduate of GEWIS.
                You can not opt-out of these emails.',
            'Graduate Renewal (' . $link->getMember()->getLidnr() . ')',
        );
    }

    /**
     * Render a template with given variables.
     *
     * @param array<array-key, mixed> $vars
     */
    private function render(
        string $template,
        array $vars,
    ): string {
        return $this->twig->render($template, $vars);
    }
}
