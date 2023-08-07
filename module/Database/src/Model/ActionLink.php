<?php

declare(strict_types=1);

namespace Database\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;

use function base64_encode;
use function random_bytes;
use function str_replace;

/**
 * Class for links that can be clicked.
 */
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'payment' => PaymentLink::class,
        'renewal' => RenewalLink::class,
    ],
)]
abstract class ActionLink
{
    /**
     * Identity
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

    public function __construct()
    {
        $this->generateToken();
    }

    /**
     * Generate a new token. Slashes `/` are replaced to make the token URL-friendly.
     */
    private function generateToken(): void
    {
        $token = base64_encode(random_bytes(96));
        $this->token = str_replace('/', '-', $token);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }
}
