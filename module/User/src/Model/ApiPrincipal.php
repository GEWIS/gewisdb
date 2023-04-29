<?php

declare(strict_types=1);

namespace User\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Laminas\Math\Rand;
use User\Model\Enums\ApiPermissions;

use function in_array;

/**
 * Member model.
 */
#[Entity]
class ApiPrincipal
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Token.
     */
    #[Column(type: 'string')]
    protected string $token;

    /**
     * Description.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $description;

    /**
     * Permission groups.
     *
     * @var ApiPermissions[] $permissions
     */
    #[Column(
        type: 'simple_array',
        nullable: true,
        enumType: ApiPermissions::class,
    )]
    protected array $permissions;

    /**
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Generate a (new) token
     * We do not provide a way of specifying a token
     */
    public function generateToken(): void
    {
        $this->token = Rand::getString(64);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get all ApiPermissions for principal
     *
     * @return ApiPermissions[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param ApiPermissions[] $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function can(ApiPermissions $permission): bool
    {
        if (in_array(ApiPermissions::All, $this->permissions, true)) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }
}
