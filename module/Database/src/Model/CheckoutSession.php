<?php

declare(strict_types=1);

namespace Database\Model;

use Database\Model\Enums\CheckoutSessionStates;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Saved query model.
 */
#[Entity]
class CheckoutSession
{
    /**
     * Payment ID.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

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
    private string $checkoutId;

    #[ManyToOne(
        targetEntity: ProspectiveMember::class,
        inversedBy: 'checkoutSessions',
    )]
    #[JoinColumn(
        name: 'prospective_member',
        referencedColumnName: 'lidnr',
    )]
    private ProspectiveMember $prospectiveMember;

    /**
     * Creation of the checkout session.
     */
    #[Column(type: 'datetime')]
    private DateTime $created;

    /**
     * Expiration of the checkout session.
     *
     * If $state == CheckoutSessionStates::Expired, then this is the last date this checkout session can be recovered.
     */
    #[Column(type: 'datetime')]
    private DateTime $expiration;

    /**
     * The identifier of the PaymentIntent associated with this Checkout Session when the state is 'PAID'.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $paymentIntentId = null;

    /**
     * Recovery URL for the Checkout Session when the state is 'EXPIRED'.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $recoveryUrl = null;

    /**
     * One-to-many side for self-reference after recovery.
     *
     * @var Collection<array-key, CheckoutSession>
     */
    #[OneToMany(
        targetEntity: self::class,
        mappedBy: 'recoveredFrom',
    )]
    private Collection $recoveredBy;

    #[ManyToOne(
        targetEntity: self::class,
        inversedBy: 'recoveredBy',
        cascade: ['remove'],
    )]
    #[JoinColumn(
        name: 'recovered_from_id',
        referencedColumnName: 'id',
        onDelete: 'SET NULL',
    )]
    private ?CheckoutSession $recoveredFrom = null;

    /**
     * The state of the payment.
     */
    #[Column(
        type: 'integer',
        enumType: CheckoutSessionStates::class,
    )]
    private CheckoutSessionStates $state = CheckoutSessionStates::Created;

    public function __construct()
    {
        $this->recoveredBy = new ArrayCollection();
    }

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

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): void
    {
        $this->created = $created;
    }

    public function getExpiration(): DateTime
    {
        return $this->expiration;
    }

    public function setExpiration(DateTime $expiration): void
    {
        $this->expiration = $expiration;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): void
    {
        $this->paymentIntentId = $paymentIntentId;
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
     * @return Collection<array-key, CheckoutSession>
     */
    public function getRecoveredBy(): Collection
    {
        return $this->recoveredBy;
    }

    public function getRecoveredFrom(): ?CheckoutSession
    {
        return $this->recoveredFrom;
    }

    public function setRecoveredFrom(CheckoutSession $recoveredFrom): void
    {
        $this->recoveredFrom = $recoveredFrom;
    }

    public function getState(): CheckoutSessionStates
    {
        return $this->state;
    }

    public function setState(CheckoutSessionStates $state): void
    {
        $this->state = $state;
    }
}
