<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Model that represents/caches mailman mailing lists and stores some additional information
 */
#[Entity]
class MailmanMailingList
{
    /**
     * Mailman-identifier.
     */
    #[Id]
    #[Column(
        name: 'id',
        type: 'string',
    )]
    private string $mailmanId;

    /**
     * Name of this list in the mailman side
     */
    #[Column(type: 'string')]
    private string $name;

    /**
     * When this list was last observed in mailman
     */
    #[Column(type: 'datetime')]
    private DateTime $lastSeen;

    /**
     * When the last full check of this mailing list took place
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    private ?DateTime $lastCheck = null;

    /**
     * The corresponding gewisdb mailing list
     * If null, this list is not managed by GEWISDB
     */
    #[OneToOne(
        targetEntity: MailingList::class,
        mappedBy: 'mailmanList',
    )]
    private ?MailingList $mailingList;

    /**
     * Get the mailman ID
     */
    public function getMailmanId(): string
    {
        return $this->mailmanId;
    }

    /**
     * Set the mailman ID
     * It is only sensible if this happens during a sync
     */
    public function setMailmanId(string $mailmanId): void
    {
        $this->mailmanId = $mailmanId;
    }

    /**
     * Get the name of the list in mailman
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the list in mailman
     * It is only sensible if this happens during a sync
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the date the list was last seen
     */
    public function getLastSeen(): DateTime
    {
        return $this->lastSeen;
    }

    /**
     * Set the date the list was last seen
     * It is only sensible if this happens during a sync
     */
    public function setLastSeen(DateTime $lastSeen = new DateTime()): void
    {
        $this->lastSeen = $lastSeen;
    }

    /**
     * Get the date the list was last fully checked
     */
    public function getLastCheck(): ?DateTime
    {
        return $this->lastCheck;
    }

    /**
     * Set the date the list was last fully checked
     */
    public function setLastCheck(DateTime $lastCheck = new DateTime()): void
    {
        $this->lastCheck = $lastCheck;
    }

    /**
     * Get the mailing list corresponding to this mailman list
     */
    public function getMailingList(): ?MailingList
    {
        return $this->mailingList;
    }

    /**
     * Whether this mailman list is managed by GEWISDB
     */
    public function isManaged(): bool
    {
        return null !== $this->mailingList;
    }
}
