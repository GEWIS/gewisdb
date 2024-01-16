<?php
declare(strict_types=1);

namespace Checker\Service;

use Database\Mapper\Member as MemberMapper;
use Database\Model\Member as MemberModel;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Application\Service\Email as EmailService;

/**
 * Birthday class that takes care of sending out birthday mails to members
 */
class Birthday
{
    public function __construct(
        private readonly PhpRenderer $renderer,
        private readonly MemberMapper $memberMapper,
        private readonly EmailService $emailService,
    ){

    }



    public function sendBirthday(): void
    {
        $birthdayPeople = $this->memberMapper->getCurrentBirthdays();

        foreach ($birthdayPeople as $person) {
            $this->sendBirthdayEmail($person);
        }
    }

    private function sendBirthdayEmail(MemberModel $person): void
    {
        $body = $this->render(
            'email/birthday-mail',
            [
                'firstName' => $person->getFirstName(),
                'birthday' => $person->getBirth()

            ]
        );

        $this->emailService->sendEmailTemplate(
            $person->getEmailRecipient(),
            'Membership notification',
            'Member birthday',
            $body,
            null,
            null,
            null,
            null,
            null,
            true,
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
        $model = new ViewModel($vars);
        $model->setTemplate($template);

        return $this->renderer->render($model);
    }

}
