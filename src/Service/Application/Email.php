<?php

declare(strict_types=1);

namespace App\Service\Application;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as MimeEmail;
use Twig\Environment;

class Email
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
        private readonly string $mailFromAddress,
        private readonly string $mailFromName,
        private readonly string $mailFromSecretaryAddress,
        private readonly string $mailFromSecretaryName,
    ) {
    }

    public function sendEmailTemplate(
        Address $recipient,
        string $titleHeader,
        string $titleBlock,
        string $bodyMain,
        ?string $titleAccessible = null,
        ?string $titleMoreInformation = null,
        ?string $bodyMoreInformation = null,
        ?string $footerReason = null,
        ?string $emailSubject = null,
    ): void {
        $replyTo = new Address($this->mailFromSecretaryAddress, $this->mailFromSecretaryName);

        $body = $this->render(
            'email/basic.html.twig',
            [
                'title_header' => $titleHeader,
                'title_block' => $titleBlock,
                'body_main' => $bodyMain,
                'title_accessible' => $titleAccessible ?? $titleBlock,
                'title_moreinformation' => $titleMoreInformation,
                'body_moreinformation' => $bodyMoreInformation,
                'footer_reason' => $footerReason,
                'footer_sender_email' => $replyTo->getAddress(),
            ],
        );

        $this->sendEmail(
            $body,
            $emailSubject ?? $titleHeader,
            $recipient,
            $replyTo,
        );
    }

    private function sendEmail(
        string $body,
        string $subject,
        Address $recipient,
        ?Address $replyTo = null,
    ): void {
        $message = (new MimeEmail())
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to($recipient)
            ->subject($subject)
            ->html($body);

        if (null !== $replyTo) {
            $message->replyTo($replyTo);
            $message->bcc($replyTo);
        }

        $this->mailer->send($message);
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
        return $this->twig->render($template, $vars);
    }
}
