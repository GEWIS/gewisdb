<?php

declare(strict_types=1);

namespace Checker\Service;

use Application\Service\Email as EmailService;
use Checker\Mapper\Member as MemberMapper;
use Database\Mapper\ActionLink as ActionLinkMapper;
use Database\Model\ActionLink as ActionLinkModel;
use DateInterval;
use DateTime;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Throwable;

/**
 * Renewal class that takes care of renewing graduates
 * and converting memberships to graduates
 */
class Renewal
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly ActionLinkMapper $actionLinkMapper,
        private readonly MemberMapper $memberMapper,
        private readonly EmailService $emailService,
        private readonly PhpRenderer $renderer,
        private readonly array $config,
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
        $graduates = $this->memberMapper->getExpiringGraduates($expiresWithin, $limit);

        foreach ($graduates as $graduate) {
            $actionLink = $this->actionLinkMapper->createByMember($graduate);
            try {
                $this->sendRenewalEmail($actionLink);
            } catch (Throwable $e) {
                $this->actionLinkMapper->remove($actionLink);

                throw $e;
            }
        }
    }

    private function sendRenewalEmail(ActionLinkModel $link): void
    {
        $body = $this->render(
            'email/graduate-renewal',
            [
                'firstName' => $link->getMember()->getFirstName(),
                'currentExpiration' => $link->getCurrentExpiration(),
                'newExpiration' => $link->getNewExpiration(),
                'url' => $this->config['application']['public_url'] . '/member/renew/' . $link->getToken(),
                //TODO: If global config exists, we should make the secretary a global config option
            ],
        );

        $this->emailService->sendEmailTemplate(
            $link->getMember()->getEmailRecipient(),
            'Membership notification',
            'Expiring membership',
            $body,
            'GEWIS Graduate Renewal',
            'More information',
            '<p>On July 1st, 2021 the new Articles of Association came into effect.
                This means you can now also be registered with GEWIS as a graduate.
                All non-studying members who were not in organ were registered as a graduate on aforementioned date.
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

    public function sendRenewalSuccessEmail(ActionLinkModel $link): void
    {
        $body = $this->render(
            'email/graduate-renewal-success',
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
            'Renewed membership',
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
     * @param array<array-key,mixed> $vars
     */
    private function render(
        string $template,
        array $vars,
    ): string {
        $model = new ViewModel($vars);
        $model->setTemplate($template);

        return $this->renderer->render($model);
    }
}
