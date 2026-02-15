<?php

declare(strict_types=1);

namespace Database\Model\Trait;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

#[HasLifecycleCallbacks]
trait TimestampableTrait
{
    /**
     * When this entry was created
     */
    #[Column(type: 'datetime')]
    protected DateTimeInterface $createdAt;

    /**
     * When this entry was last updated
     */
    #[Column(type: 'datetime')]
    protected DateTimeInterface $updatedAt;

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    private function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    private function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Automatically fill in the `DateTime`s before the initial call to `persist()`.
     */
    #[PrePersist]
    public function prePersist(): void
    {
        $now = new DateTime();

        $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
    }

    /**
     * Automatically update the `updatedAt` `DateTime` when doing an update to the entity.
     */
    #[PreUpdate]
    public function preUpdate(): void
    {
        $this->setUpdatedAt(new DateTime());
    }
}
