<?php

declare(strict_types=1);

namespace Database\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Mailing List model.
 */
#[Entity]
class MailingList
{
    /**
     * Mailman-identifier / name.
     */
    #[Id]
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Dutch description of the mailing list.
     */
    #[Column(type: 'text')]
    protected string $nl_description;

    /**
     * English description of the mailing list.
     */
    #[Column(type: 'text')]
    protected string $en_description;

    /**
     * If the mailing list should be on the form.
     */
    #[Column(type: 'boolean')]
    protected bool $onForm;

    /**
     * If members should be subscribed by default.
     *
     * (when it is on the form, that means that the checkbox is checked by default)
     */
    #[Column(type: 'boolean')]
    protected bool $defaultSub;

    /**
     * The identifier of the mailing list in Mailman.
     */
    #[Column(
        type: 'string',
        unique: true,
    )]
    protected string $mailmanId;

    /**
     * Mailing list members.
     *
     * @var Collection<array-key, MailingListMember>
     */
    #[OneToMany(
        targetEntity: MailingListMember::class,
        mappedBy: 'mailingList',
    )]
    protected Collection $mailingListMemberships;

    public function __construct()
    {
        $this->mailingListMemberships = new ArrayCollection();
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the english description.
     */
    public function getEnDescription(): string
    {
        return $this->en_description;
    }

    /**
     * Set the english description.
     */
    public function setEnDescription(string $description): void
    {
        $this->en_description = $description;
    }

    /**
     * Get the dutch description.
     */
    public function getNlDescription(): string
    {
        return $this->nl_description;
    }

    /**
     * Set the dutch description.
     */
    public function setNlDescription(string $description): void
    {
        $this->nl_description = $description;
    }

    /**
     * Get if it should be on the form.
     */
    public function getOnForm(): bool
    {
        return $this->onForm;
    }

    /**
     * Set if it should be on the form.
     */
    public function setOnForm(bool $onForm): void
    {
        $this->onForm = $onForm;
    }

    /**
     * Get if it is a default list.
     */
    public function getDefaultSub(): bool
    {
        return $this->defaultSub;
    }

    /**
     * Set if it is a default list.
     */
    public function setDefaultSub(bool $default): void
    {
        $this->defaultSub = $default;
    }

    /**
     * Get the identifier of the mailing list in Mailman.
     */
    public function getMailmanId(): string
    {
        return $this->mailmanId;
    }

    /**
     * Set the identifier of the mailing list in Mailman.
     */
    public function setMailmanId(string $mailmanId): void
    {
        $this->mailmanId = $mailmanId;
    }

    /**
     * Get subscribed members.
     *
     * @return Collection<array-key, MailingListMember>
     */
    public function getMailingListMemberships(): Collection
    {
        return $this->mailingListMemberships;
    }

    /**
     * @return array{
     *     name: string,
     *     nl_description: string,
     *     en_description: string,
     *     defaultSub: bool,
     *     onForm: bool,
     *     mailmanId: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'nl_description' => $this->getNlDescription(),
            'en_description' => $this->getEnDescription(),
            'defaultSub' => $this->getDefaultSub(),
            'onForm' => $this->getOnForm(),
            'mailmanId' => $this->getMailmanId(),
        ];
    }
}
