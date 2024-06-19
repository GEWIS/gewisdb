<?php

declare(strict_types=1);

namespace Report\Model;

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
     * Get subscribed members.
     *
     * @return Collection<array-key, MailingListMember>
     */
    public function getMailingListMemberships(): Collection
    {
        return $this->mailingListMemberships;
    }
}
