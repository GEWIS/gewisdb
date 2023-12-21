<?php

declare(strict_types=1);

namespace User\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

use function preg_match;
use function str_contains;

/**
 * User model.
 */
#[Entity]
#[Table(name: 'users')]
class User
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Login.
     */
    #[Column(type: 'string')]
    protected string $login;

    /**
     * User password.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $password = null;

    /**
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function isLocal(): bool
    {
        return !str_contains($this->login, '@');
    }

    /**
     * This is the human-readable name
     * Might be changed to firstname later
     */
    public function getName(): string
    {
        if (1 === preg_match('/^((?:a|m)(?:[0-9]{4,5}))@GEWISWG\.GEWIS\.NL$/', $this->login, $matches)) {
            return $matches[1];
        }

        return $this->getLogin();
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
