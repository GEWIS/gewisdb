<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\AddressTypes;
use Application\Model\Enums\PostalRegions;
use Database\Model\Enums\CheckoutSessionStates;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

use function in_array;

/**
 * ProspectiveMember model.
 */
#[Entity]
class ProspectiveMember
{
    /**
     * The user
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $lidnr = null;

    /**
     * Member's email address.
     */
    #[Column(type: 'string')]
    protected string $email;

    /**
     * Member's last name.
     */
    #[Column(type: 'string')]
    protected string $lastName;

    /**
     * Middle name.
     */
    #[Column(type: 'string')]
    protected string $middleName;

    /**
     * Initials.
     */
    #[Column(type: 'string')]
    protected string $initials;

    /**
     * First name.
     */
    #[Column(type: 'string')]
    protected string $firstName;

    /**
     * TU/e username.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $tueUsername = null;

    /**
     * Study of the member.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $study = null;

    /**
     * Last changed date of membership.
     */
    #[Column(type: 'date')]
    protected DateTime $changedOn;

    /**
     * Member birthdate.
     */
    #[Column(type: 'date')]
    protected DateTime $birth;

    /**
     * How much the member has paid for membership. 0 by default.
     */
    #[Column(type: 'integer')]
    protected int $paid = 0;

    /**
     * Country.
     */
    #[Column(
        type: 'string',
        enumType: PostalRegions::class,
    )]
    protected PostalRegions $country;

    /**
     * Street.
     */
    #[Column(type: 'string')]
    protected string $street;

    /**
     * House number (+ suffix)
     */
    #[Column(type: 'string')]
    protected string $number;

    /**
     * Postal code.
     */
    #[Column(type: 'string')]
    protected string $postalCode;

    /**
     * City.
     */
    #[Column(type: 'string')]
    protected string $city;

    /**
     * Phone number.
     */
    #[Column(type: 'string')]
    protected string $phone;

    /**
     * Memberships of mailing lists.
     *
     * @var string[] $lists
     */
    #[Column(type: 'simple_array')]
    protected array $lists = [];

    /**
     * The Checkout Sessions for this prospective member.
     *
     * @var Collection<array-key, CheckoutSession>
     */
    #[OneToMany(
        targetEntity: CheckoutSession::class,
        mappedBy: 'prospectiveMember',
        orphanRemoval: true,
        cascade: ['remove'],
    )]
    #[OrderBy(['created' => 'ASC'])]
    protected Collection $checkoutSessions;

    /**
     * Payment link that can be used by the prospective member to restart a Checkout Session.
     */
    #[OneToOne(
        targetEntity: PaymentLink::class,
        mappedBy: 'prospectiveMember',
    )]
    protected ?PaymentLink $paymentLink = null;

    public function __construct()
    {
        $this->checkoutSessions = new ArrayCollection();
    }

    /**
     * Get the membership number.
     *
     * @psalm-ignore-nullable-return
     */
    public function getLidnr(): ?int
    {
        return $this->lidnr;
    }

    /**
     * Get the member's email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the member's last name.
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Get the member's middle name.
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * Get the member's initials.
     */
    public function getInitials(): string
    {
        return $this->initials;
    }

    /**
     * Get the member's first name.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set the lidnr.
     */
    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the member's email address.
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Set the member's last name.
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * Set the member's middle name.
     */
    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * Set the member's initials.
     */
    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    /**
     * Set the member's first name.
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * Assemble the member's full name.
     */
    public function getFullName(): string
    {
        $name = $this->getFirstName() . ' ';

        $middle = $this->getMiddleName();
        if (!empty($middle)) {
            $name .= $middle . ' ';
        }

        return $name . $this->getLastName();
    }

    /**
     * Get the TU/e username.
     */
    public function getTueUsername(): ?string
    {
        return $this->tueUsername;
    }

    /**
     * Set the TU/e username.
     */
    public function setTueUsername(?string $tueUsername): void
    {
        $this->tueUsername = $tueUsername;
    }

    /**
     * Get the study.
     */
    public function getStudy(): ?string
    {
        return $this->study;
    }

    /**
     * Set the study.
     */
    public function setStudy(string $study): void
    {
        $this->study = $study;
    }

    /**
     * Get the birth date.
     */
    public function getBirth(): DateTime
    {
        return $this->birth;
    }

    /**
     * Set the birthdate.
     */
    public function setBirth(DateTime $birth): void
    {
        $this->birth = $birth;
    }

    public function getCountry(): PostalRegions
    {
        return $this->country;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Get the date of the last membership change.
     */
    public function getChangedOn(): DateTime
    {
        return $this->changedOn;
    }

    /**
     * Set the date of the last membership change.
     */
    public function setChangedOn(DateTime $changedOn): void
    {
        $this->changedOn = $changedOn;
    }

    /**
     * Get how much has been paid.
     */
    public function getPaid(): int
    {
        return $this->paid;
    }

    /**
     * Set how much has been paid.
     */
    public function setPaid(int $paid): void
    {
        $this->paid = $paid;
    }

    /**
     * Convert to array.
     *
     * @return array{
     *     lidnr: int,
     *     email: string,
     *     fullName: string,
     *     lastName: string,
     *     middleName: string,
     *     initials: string,
     *     firstName: string,
     *     tueUsername: ?string,
     *     study: ?string,
     *     birth: string,
     *     address: array{
     *         type: AddressTypes,
     *         country: PostalRegions,
     *         street: string,
     *         number: string,
     *         city: string,
     *         postalCode: string,
     *         phone: string,
     *     },
     *     agreed: string,
     *     agreedStripe: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'fullName' => $this->getFullName(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
            'tueUsername' => $this->getTueUsername(),
            'study' => $this->getStudy(),
            'birth' => $this->getBirth()->format('Y-m-d'),
            'address' => $this->getAddresses()['studentAddress']->toArray(),
            'agreed' => '1',
            'agreedStripe' => '1',
        ];
    }

    /**
     * Get all addresses.
     *
     * @return array{studentAddress: Address}
     */
    public function getAddresses(): array
    {
        $address = new Address();
        $address->setType(AddressTypes::Student);
        $address->setCountry($this->country);
        $address->setStreet($this->street);
        $address->setNumber($this->number);
        $address->setPostalCode($this->postalCode);
        $address->setCity($this->city);
        $address->setPhone($this->phone);

        return ['studentAddress' => $address];
    }

    /**
     * Add an address.
     */
    public function setAddress(Address $address): void
    {
        $this->country = $address->getCountry();
        $this->street = $address->getStreet();
        $this->number = $address->getNumber();
        $this->postalCode = $address->getPostalCode();
        $this->city = $address->getCity();
        $this->phone = $address->getPhone();
    }

    /**
     * Get mailing list subscriptions.
     *
     * @return string[]
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * Add a mailing list subscription.
     *
     * Note that this is the owning side.
     */
    public function addList(string $list): void
    {
        if (in_array($list, $this->lists)) {
            return;
        }

        $this->lists[] = $list;
    }

    /**
     * Add multiple mailing lists.
     *
     * @param string[] $lists
     */
    public function addLists(array $lists): void
    {
        foreach ($lists as $list) {
            $this->addList($list);
        }
    }

    /**
     * @return Collection<array-key, CheckoutSession>
     */
    public function getCheckoutSessions(): Collection
    {
        return $this->checkoutSessions;
    }

    /**
     * Determine whether the prospective member can be approved (and thus become a member). This should only be possible
     * if the Checkout Session's state is 'PAID' or 'FAILED' or 'EXPIRED'. The latter two indicate that this is a
     * manual approval, for example, when the prospective member paid with cash.
     */
    public function canBeApproved(): bool
    {
        $lastState = $this->getLastCheckoutSessionState();

        if (null === $lastState) {
            return false;
        }

        return CheckoutSessionStates::Paid === $lastState
            || CheckoutSessionStates::Failed === $lastState
            || CheckoutSessionStates::Expired === $lastState;
    }

    /**
     * Determine whether the prospective member can be deleted. This should only be possible if the last Checkout
     * Session's state is 'PAID' or fully 'EXPIRED'.
     */
    public function canBeDeleted(): bool
    {
        /** @var CheckoutSession|false $lastCheckoutSession */
        $lastCheckoutSession = $this->checkoutSessions->last();

        if (false === $lastCheckoutSession) {
            // No Checkout Session can be found, we are in a state of many unknowns, do not allow removal.
            return false;
        }

        $lastState = $lastCheckoutSession->getState();

        if (CheckoutSessionStates::Expired === $lastState) {
            // Checkout Session is fully expired, it cannot be recovered and is scheduled for automatic removal.
            return (new DateTime()) >= $lastCheckoutSession->getExpiration();
        }

        return CheckoutSessionStates::Paid === $lastState;
    }

    /**
     * Determine whether the prospective member has paid. This should only be possible if the Checkout Session's state
     * is 'PAID'.
     */
    public function hasPaid(): bool
    {
        $lastState = $this->getLastCheckoutSessionState();

        if (null === $lastState) {
            return false;
        }

        return CheckoutSessionStates::Paid === $lastState;
    }

    private function getLastCheckoutSessionState(): ?CheckoutSessionStates
    {
        /** @var CheckoutSession|false $lastCheckoutSession */
        $lastCheckoutSession = $this->checkoutSessions->last();

        if (false === $lastCheckoutSession) {
            // No Checkout Session can be found, we are in a state of many unknowns, return `null` to signal error.
            return null;
        }

        return $lastCheckoutSession->getState();
    }

    /**
     * @psalm-ignore-nullable-return
     */
    public function getPaymentLink(): ?PaymentLink
    {
        return $this->paymentLink;
    }

    public function setPaymentLink(PaymentLink $paymentLink): void
    {
        $this->paymentLink = $paymentLink;
    }
}
