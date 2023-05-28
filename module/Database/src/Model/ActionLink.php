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
use InvalidArgumentException;

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
    protected bool $used = false;

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
        inversedBy: 'actionLinks',
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

    /**
     * New expiration
     * This is not neccessarily a year from the previous as in principle this
     * will be until the end of next association year.
     */
    #[Column(type: 'date')]
    protected DateTime $newExpiration;

    public function __construct(
        Member $member,
        DateTime $newExpiration,
    ) {
        $this->member = $member;
        $this->newExpiration = $newExpiration;
        $this->currentExpiration = $member->getExpiration();
        $this->generateToken();

        if ($this->currentExpiration >= $this->newExpiration) {
            throw new InvalidArgumentException('New expiration must be strictly later than current expiration');
        }

        return null;
    }

    /**
     * Generate a new token and return it
     */
    private function generateToken(): string
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

    public function getNewExpiration(): DateTime
    {
        return $this->newExpiration;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function used(): void
    {
        $this->used = true;
    }

    /**
     * We assume a link is valid until 30 days after the original membership expired
     * Then, people can still renew their membership after their account gets locked
     */
    public function linkExpired(): bool
    {
        $diff = (new DateTime())->diff($this->currentExpiration);

        return 1 === $diff->invert && ($diff->days > 30);
    }
}
