<?php

declare(strict_types=1);

namespace Database\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * Saved query model.
 */
#[Entity]
class SavedQuery
{
    /**
     * The query ID.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * Category.
     */
    #[Column(type: 'string')]
    private string $category;

    /**
     * Name.
     */
    #[Column(type: 'string')]
    private string $name;

    /**
     * The Saved Query.
     */
    #[Column(type: 'text')]
    private string $query;

    /**
     * Get the ID.
     *
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID.
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
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
     * Get the category.
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Set the category.
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * Set the query.
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    /**
     * Get the query.
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
