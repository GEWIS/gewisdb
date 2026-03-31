<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Model that represents/caches listmonk mailing lists and stores some additional information
 */
#[Entity]
class ListmonkMailingList
{
    /**
     * Listmonk-identifier (UUID).
     */
    #[Id]
    #[Column(
        name: 'id',
        type: 'string',
    )]
    private string $listmonkId;

    /**
     * Name of this list in the listmonk side
     */
    #[Column(type: 'string')]
    private string $name;

    /**
     * When this list was last observed in listmonk
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
        mappedBy: 'listmonkList',
    )]
    private ?MailingList $mailingList;

    /**
     * Get the listmonk ID (UUID)
     */
    public function getListmonkId(): string
    {
        return $this->listmonkId;
    }

    /**
     * Set the listmonk ID (UUID)
     * It is only sensible if this happens during a sync
     */
    public function setListmonkId(string $listmonkId): void
    {
        $this->listmonkId = $listmonkId;
    }

    /**
     * Get the name of the list in listmonk
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the list in listmonk
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
     * Get the mailing list corresponding to this listmonk list
     */
    public function getMailingList(): ?MailingList
    {
        return $this->mailingList;
    }

    /**
     * Whether this listmonk list is managed by GEWISDB
     */
    public function isManaged(): bool
    {
        return null !== $this->mailingList;
    }
}
