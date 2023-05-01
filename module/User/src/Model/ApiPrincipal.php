<?php

declare(strict_types=1);

namespace User\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Laminas\Form\Annotation\Validator;
use Laminas\Validator\StringLength;
use User\Model\Enums\ApiPermissions;

use function array_map;
use function base64_encode;
use function in_array;
use function random_bytes;
use function str_repeat;
use function strlen;
use function substr;

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
    #[Validator(StringLength::class, options: ['min' => 8, 'max' => 255])]
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

    /**
     * Get the token (hidden and used in the form hydrator)
     */
    public function getToken(): string
    {
        return str_repeat('*', strlen($this->token) - 5) . substr($this->token, -5);
    }

    /**
     * Get the full token
     */
    public function getFullToken(): string
    {
        return $this->token;
    }

    /**
     * Generate a (new) token
     * We do not provide a way of specifying a token
     */
    public function generateToken(): void
    {
        $this->token = base64_encode(random_bytes(96));
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
     * To allow for hydrator, we convert possible strings
     *
     * @param ApiPermissions[]|string[] $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = array_map(
            static function ($p): ApiPermissions {
                return $p instanceof ApiPermissions ? $p : ApiPermissions::from($p);
            },
            $permissions,
        );
    }

    public function can(ApiPermissions $permission): bool
    {
        if (in_array(ApiPermissions::All, $this->permissions, true)) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }
}
