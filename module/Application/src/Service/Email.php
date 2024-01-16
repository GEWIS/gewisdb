<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Mail\Address as MailAddress;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
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

    public function sendEmailTemplate(
        MailAddress $recipient,
        string $titleHeader,
        string $titleBlock,
        string $bodyMain,
        ?string $titleAccessible = null,
        ?string $titleMoreInformation = null,
        ?string $bodyMoreInformation = null,
        ?string $footerReason = null,
        ?string $emailSubject = null,
        ?bool $noTemplate = false,
    ): void {
        $replyTo = new MailAddress($this->config['from_secretary']['address'], $this->config['from_secretary']['name']);


        if (!$noTemplate) {
            $body = $this->render(
                'email/basic',
                [
                    'title_header' => $titleHeader,
                    'title_block' => $titleBlock,
                    'body_main' => $bodyMain,
                    'title_accessible' => $titleAccessible ?? $titleBlock,
                    'title_moreinformation' => $titleMoreInformation,
                    'body_moreinformation' => $bodyMoreInformation,
                    'footer_reason' => $footerReason,
                    'footer_sender_email' => $replyTo->getEmail(),
                ],
            );
        } else {
            $body = $bodyMain;
        }

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
        MailAddress $recipient,
        ?MailAddress $replyTo = null,
    ): void {
        $html = new MimePart($body);
        $html->type = 'text/html';

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setBody($mimeMessage);
        $message->setFrom($this->config['from']['address'], $this->config['from']['name']);
        $message->setTo($recipient);
        $message->setSubject($subject);

        if (null !== $replyTo) {
            $message->setReplyTo($replyTo);
            $message->setBcc($replyTo);
        }

        $this->getMailTransport()->send($message);
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

    private function getMailTransport(): TransportInterface
    {
        return $this->mailTransport;
    }
}
