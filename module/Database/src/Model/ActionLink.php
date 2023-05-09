<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

use function base64_encode;
use function random_bytes;
use function str_replace;

/**
 * Class for links that can be clicked
 */
#[Entity]
class ActionLink
{
    /**
     * Mailman-identifier / name.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * If the URL was clicked
     */
    #[Column(type: 'boolean')]
    protected bool $used;

    /**
     * The token in the URL
     */
    #[Column(type: 'string')]
    protected string $token;

    /**
     * The member
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'actionlinks',
    )]
    #[JoinColumn(
        name: 'member',
        referencedColumnName: 'lidnr',
        onDelete: 'cascade',
    )]
    protected Member $member;

    /**
     * Current expiration
     */
    #[Column(type: 'date')]
    protected DateTime $currentExpiration;

    public function __construct()
    {
        $this->generateToken();
    }

    /**
     * Generate a new token and return it
     */
    public function generateToken(): string
    {
        $this->token = base64_encode(random_bytes(96));
        $this->token = str_replace('/', '-', $this->token);

        return $this->token;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getCurrentExpiration(): DateTime
    {
        return $this->currentExpiration;
    }
}
