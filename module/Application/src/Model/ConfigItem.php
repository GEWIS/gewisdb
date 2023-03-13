<?php

declare(strict_types=1);

namespace Application\Model;

use Application\Model\Enums\ConfigNamespaces;
use Database\Model\Trait\CreatedTrait;
use Database\Model\Trait\UpdatedTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\UniqueConstraint;
use LogicException;
use TypeError;

use function is_bool;
use function is_string;

/**
 * Runtime configuration items model.
 */
#[Entity]
#[HasLifecycleCallbacks]
#[UniqueConstraint(
    name: 'configitem_unique_idx',
    columns: ['namespace', 'key'],
)]
class ConfigItem
{
    use CreatedTrait;
    use UpdatedTrait;

    /**
     * Primary key item ID (to avoid reference issues).
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Namespace
     */
    #[Column(
        type: 'string',
        enumType: ConfigNamespaces::class,
    )]
    protected ConfigNamespaces $namespace;

    /**
     * Configuration item key.
     * Configuration item keys are in snake_case.
     */
    #[Column(type: 'string')]
    protected string $key;

    /**
     * If the item is a string, its value.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $valueString = null;

    /**
     * If the item is a DateTime, its value.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $valueDate = null;

    /**
     * If the item is a boolean, its value.
     */
    #[Column(
        type: 'boolean',
        nullable: true,
    )]
    protected ?bool $valueBool = null;

    #[PrePersist]
    #[PreUpdate]
    public function assertValid(): void
    {
        if (null !== $this->valueDate xor null !== $this->valueString) {
            return;
        }

        throw new LogicException();
    }

    /**
     * Set the namespace and key of the configuration item.
     */
    public function setKey(
        ConfigNamespaces $namespace,
        string $key,
    ): void {
        $this->namespace = $namespace;
        $this->key = $key;
    }

    /**
     * Set the value of the configuration item.
     */
    public function setValue(bool|string|DateTime $value): void
    {
        if ($value instanceof DateTime) {
            $this->valueString = null;
            $this->valueDate = $value;
            $this->valueBool = null;
        } elseif (is_string($value)) {
            $this->valueString = $value;
            $this->valueDate = null;
            $this->valueBool = null;
        } elseif (is_bool($value)) {
            $this->valueString = null;
            $this->valueDate = null;
            $this->valueBool = $value;
        } else {
            throw new TypeError();
        }
    }

    public function getValue(): bool|string|DateTime|null
    {
        if (null !== $this->valueDate) {
            return $this->valueDate;
        }

        if (null !== $this->valueString) {
            return $this->valueString;
        }

        if (null !== $this->valueBool) {
            return $this->valueBool;
        }

        return null;
    }
}
