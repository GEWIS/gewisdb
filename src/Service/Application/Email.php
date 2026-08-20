<?php

declare(strict_types=1);

namespace App\Service\Application;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Sending one of the application's e-mails.
 *
 * Every template extends `email/_base.html.twig` and fills its blocks, the way GEWISWEB's do, so the branding lives
 * in one file and a message cannot go out without it. The template is named here and rendered by the mailer, rather
 * than rendered to a string first and handed to a wrapper — that arrangement let a caller send a body on its own,
 * which is how five of these went out unbranded.
 */
class Email
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailFromAddress,
        private readonly string $mailFromName,
        private readonly string $mailFromSecretaryAddress,
        private readonly string $mailFromSecretaryName,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function send(
        Address $recipient,
        string $subject,
        string $template,
        array $context = [],
        ?Address $replyTo = null,
        bool $bccReplyTo = false,
    ): void {
        $replyTo ??= new Address(
            $this->mailFromSecretaryAddress,
            $this->mailFromSecretaryName,
        );

        $message = new TemplatedEmail()
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to($recipient)
            ->replyTo($replyTo)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context + ['secretary_email' => $replyTo->getAddress()]);

        if ($bccReplyTo) {
            $message->bcc($replyTo);
        }

        $this->mailer->send($message);
    }

    public function secretary(): Address
    {
        return new Address(
            $this->mailFromSecretaryAddress,
            $this->mailFromSecretaryName,
        );
    }
}
