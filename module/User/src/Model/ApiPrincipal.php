<?php

declare(strict_types=1);

namespace User\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    Table,
};
use User\Model\Enums\ApiPermissions;

/**
 * Member model.
 */
#[Entity]
class ApiPrincipal
{
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Token.
     */
    #[Column(type: "string")]
    protected string $token;

    /**
     * Permission groups.
     */
    #[Column(
        type: "simple_array",
        nullable: true,
        enumType: ApiPermissions::class,
    )]
    protected array $permissions;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @param ApiPermissions $permission
     */
    public function can(ApiPermissions $permission): bool
    {
        if (in_array(ApiPermissions::All, $this->permissions, true)) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }
}
