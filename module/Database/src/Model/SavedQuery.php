<?php

namespace Database\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
};

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
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Name.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * The Saved Query.
     */
    #[Column(type: "text")]
    protected string $query;

    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID.
     *
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the query.
     *
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    /**
     * Get the query.
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
