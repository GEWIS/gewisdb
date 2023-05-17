<?php

declare(strict_types=1);

namespace Checker\Service;

use Application\Service\Email as EmailService;
use Checker\Mapper\Member as MemberMapper;
use Database\Mapper\ActionLink as ActionLinkMapper;
use Database\Model\Member as MemberModel;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

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
     * Send emails to expiring graduates
     */
    public function sendRenewalGraduates(): void
    {
        $graduates = $this->memberMapper->getExpiringGraduates();

        // TODO
        foreach ($graduates as $graduate) {
            $this->sendRenewalEmail($graduate);
        }
    }

    private function sendRenewalEmail(MemberModel $graduate): void
    {
        $body = $this->render(
            'email/graduate-renewal',
            [
                'firstName' => $graduate->getFirstName(),
                'expiration' => $graduate->getExpiration(),
                'url' => $this->config['application']['public_url'],
                //TODO: If global config exists, we should make the secretary a global config option
            ],
        );

        $this->emailService->sendEmailTemplate(
            $graduate->getEmailRecipient(),
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
            'Graduate Renewal (' . $graduate->getLidnr() . ')',
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
