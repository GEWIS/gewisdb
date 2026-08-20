<?php

declare(strict_types=1);

namespace App\Entity\Database;

use App\Entity\Database\Enums\MailingListMemberAction;
use App\Entity\Database\Enums\MailingListMemberOrigin;
use App\Entity\User\User;
use App\Repository\Database\AuditMailingListMembershipRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;

#[Entity(repositoryClass: AuditMailingListMembershipRepository::class)]
class AuditMailingListMembership extends AuditEntry
{
    private const string BODY_FORMAT = '<strong>%s mailinglist subscription</strong> for '
        . '<emph>%s</emph> on <emph>%s</emph> (%s)';

    #[Column(
        type: 'string',
        enumType: MailingListMemberAction::class,
    )]
    private MailingListMemberAction $action;

    #[ManyToOne(
        targetEntity: MailingList::class,
        inversedBy: 'auditEntries',
    )]
    #[JoinColumn(
        name: 'mailing_list',
        referencedColumnName: 'name',
        onDelete: 'cascade',
        nullable: true,
    )]
    private MailingList $mailingList;

    #[Column(type: 'string')]
    private string $email;

    #[Column(
        type: 'string',
        enumType: MailingListMemberOrigin::class,
    )]
    private MailingListMemberOrigin $origin;

    public static function create(
        MailingListMemberAction $action,
        MailingListMemberOrigin $origin,
        Member $member,
        MailingList $mailingList,
        string $email,
        ?User $user = null,
    ): self {
        $audit = new self();
        $audit->setAction($action);
        $audit->setOrigin($origin);
        $audit->setMember($member);
        $audit->setMailingList($mailingList);
        $audit->setEmail($email);
        $audit->setUser($user);

        return $audit;
    }

    public function getAction(): MailingListMemberAction
    {
        return $this->action;
    }

    public function setAction(MailingListMemberAction $action): void
    {
        $this->action = $action;
    }

    public function getMailingList(): MailingList
    {
        return $this->mailingList;
    }

    public function setMailingList(MailingList $mailingList): void
    {
        $this->mailingList = $mailingList;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getOrigin(): MailingListMemberOrigin
    {
        return $this->origin;
    }

    public function setOrigin(MailingListMemberOrigin $origin): void
    {
        $this->origin = $origin;
    }

    #[Override]
    protected function getStringBodyFormatted(): string
    {
        return self::BODY_FORMAT;
    }

    /**
     * @return array<string>
     */
    #[Override]
    protected function getStringArguments(): array
    {
        // The body is translated and these are substituted into it afterwards, so they go in as their source string.
        return [
            $this->action->getName()->getMessage(),
            $this->email,
            $this->mailingList->getName(),
            $this->origin->getName()->getMessage(),
        ];
    }
}
