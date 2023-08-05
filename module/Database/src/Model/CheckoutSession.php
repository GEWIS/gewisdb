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
use Doctrine\ORM\Mapping\OneToOne;
use InvalidArgumentException;

use function in_array;
use function sprintf;

/**
 * Saved query model.
 */
#[Entity]
class CheckoutSession
{
    public const CREATED = 0;
    public const CANCELLED = 1;
    public const EXPIRED = 2;
    public const PENDING = 3;
    public const FAILED = 4;
    public const PAID = 5;

    /**
     * Payment ID.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * Identifier of the checkout session, Stripe uses case SENSITIVE identifiers, with PostgreSQL this is not a problem
     * but if we ever switch we need to use the `utf8_bin` collation on this column (as recommended).
     *
     * See {@link https://stripe.com/docs/upgrades#what-changes-does-stripe-consider-to-be-backwards-compatible}.
     */
    #[Column(
        type: 'string',
        unique: true,
    )]
    protected string $checkoutId;

    #[ManyToOne(
        targetEntity: ProspectiveMember::class,
        inversedBy: 'checkoutSessions',
    )]
    #[JoinColumn(
        name: 'prospective_member',
        referencedColumnName: 'lidnr',
    )]
    protected ProspectiveMember $prospectiveMember;

    /**
     * Expiration of the checkout session.
     */
    #[Column(type: 'datetime')]
    protected DateTime $expiration;

    /**
     * Recovery URL for the Checkout Session when the state is 'EXPIRED'.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $recoveryUrl = null;

    #[OneToOne(targetEntity: self::class)]
    #[JoinColumn(
        name: 'recovered_from_id',
        referencedColumnName: 'id',
    )]
    protected ?CheckoutSession $recoveredFrom = null;

    /**
     * The state of the payment.
     */
    #[Column(type: 'integer')]
    protected int $state = self::CREATED;

    /**
     * Get the ID.
     *
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCheckoutId(): string
    {
        return $this->checkoutId;
    }

    public function setCheckoutId(string $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
    }

    public function getProspectiveMember(): ProspectiveMember
    {
        return $this->prospectiveMember;
    }

    public function setProspectiveMember(ProspectiveMember $prospectiveMember): void
    {
        $this->prospectiveMember = $prospectiveMember;
    }

    public function getExpiration(): DateTime
    {
        return $this->expiration;
    }

    public function setExpiration(DateTime $expiration): void
    {
        $this->expiration = $expiration;
    }

    public function getRecoveryUrl(): ?string
    {
        return $this->recoveryUrl;
    }

    public function setRecoveryUrl(string $recoveryUrl): void
    {
        $this->recoveryUrl = $recoveryUrl;
    }

    /**
     * @psalm-ignore-nullable-return
     */
    public function getRecoveredFrom(): ?CheckoutSession
    {
        return $this->recoveredFrom;
    }

    public function setRecoveredFrom(CheckoutSession $recoveredFrom): void
    {
        $this->recoveredFrom = $recoveredFrom;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        if (
            !in_array(
                $state,
                [
                    self::CREATED,
                    self::CANCELLED,
                    self::EXPIRED,
                    self::PENDING,
                    self::FAILED,
                    self::PAID,
                ],
            )
        ) {
            throw new InvalidArgumentException(sprintf('Unexpected payment state %d', $state));
        }

        $this->state = $state;
    }
}
