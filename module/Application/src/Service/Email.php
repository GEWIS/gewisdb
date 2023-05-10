<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Mail\Transport\TransportInterface;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

class Email
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly PhpRenderer $renderer,
        private readonly TransportInterface $mailTransport,
        private readonly array $config,
    ) {
    }

    public function getEmailBody(): string
    {
        return $this->render(
            'email/basic',
            [
                'title_accessible' => 'GEWIS Graduate Renewal',
                'title_header' => 'Membership notification',
                'title_block' => 'Expiring membership',
                'body_main' => '<p style="margin: 0; font-size: 18px;">
                    Dear {{firstname}},<br><br>
                    You are currently registered as a Graduate (<i>Afgestudeerde</i>) at GEWIS until <b>July 1st, 2023</b>.  We care about your privacy, so we\'d like to confirm that you want to renew this with another year.
                    <br><br> Please click <a href="https://database.gewis.nl/" style="color: #C40000; text-decoration: none;" target="_blank" rel="noopener nofollow">here</a> to review your personal details and renew  your status as graduate of GEWIS for another year. If you do not click this link, your status will automatically expire and we will delete your data in  the way described in our <a href="https://gewis.nl/association/regulations/privacy-statement" style="color: #C40000; text-decoration: none;" target="_blank" rel="noopener nofollow">privacy policy</a>.
                    <br><br> If you prefer this, you can also renew your membership by replying to this email. Please keep the subject intact so we can process it quicker.
                    <br><br> On behalf of the the board,<br><br> .. ..<br> Secretary of GEWIS ....-....
                </p>',
                'title_moreinformation' => 'More information',
                'body_moreinformation' => '<p>On July 1st, 2021 the new Articles of Association came into effect.         This means you can now also be registered with GEWIS as a graduate. All non-studying members who were not in organ were registered as a graduate on aforementioned date.  <br><br> Graduates do not pay contribution and as a graduate, you can still join  GEWIS activities or visit the social drink like you used to. However, sometimes you have to pay an extra fee to join an (expensive) activity. You can also no longer serve on the board of GEWIS or vote during the GMM. <br><br> Article 3.1 of the Internal Regulations allows you to request renewal  of your status as graduate. Therefore, you are receiving this email.</p>',
                'footer_reason' => 'You receive this message because your registration as a graduate of GEWIS is almost ending. You can not opt-out of these emails.',
            ],
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

    /**
     * Get the storage config, as used by this service.
     *
     * @return array containing the config for the module
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    private function getConfig(): array
    {
        return $this->config['email'];
    }
}
