<?php

declare(strict_types=1);

namespace Database\Model\Trait;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;

#[HasLifecycleCallbacks]
trait CreatedTrait
{
    /**
     * When this entry was created
     */
    #[Column(type: 'datetime')]
    protected DateTimeInterface $createdAt;

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    #[PrePersist()]
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new DateTime();

        return $this;
    }
}
