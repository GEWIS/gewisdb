<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\User\UserRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function preg_match;
use function str_contains;

/**
 * User model.
 */
#[Entity(repositoryClass: UserRepository::class)]
#[HasLifecycleCallbacks]
#[Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Login.
     */
    #[Column(
        type: 'string',
        unique: true,
    )]
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
        return !str_contains(
            $this->login,
            '@',
        );
    }

    /**
     * This is the human-readable name
     * Might be changed to firstname later
     */
    public function getName(): string
    {
        if (
            1 === preg_match(
                '/^((?:a|m)(?:[0-9]{4,5}))@GEWISWG\.GEWIS\.NL$/',
                $this->login,
                $matches,
            )
        ) {
            return $matches[1];
        }

        return $this->getLogin();
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * The login is what identifies a user, both locally and against AD.
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    /**
     * There is no authorisation model on the web side: a user is either logged in or is not.
     *
     * @return string[]
     */
    #[Override]
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
