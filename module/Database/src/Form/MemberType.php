<?php

declare(strict_types=1);

namespace Database\Form;

use Application\Model\Enums\MembershipTypes;
use Database\Model\Membership as MembershipModel;
use DateTime;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Override;

use function assert;
use function min;

class MemberType extends Form implements InputFilterProviderInterface
{
    private MembershipModel $membership;

    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'type',
            'type' => Radio::class,
            'options' => [
                'label' => $this->translator->translate('Membership Type'),
                'value_options' => [
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Ordinary->value => $this->translator->translate('Ordinary - Enrolled at the department of M&CS'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::External->value => $this->translator->translate('External - Admitted by the board'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Graduate->value => $this->translator->translate('Graduate - Former member admitted by the board as graduate'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Honorary->value => $this->translator->translate('Honorary - Appointed by the GMM'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'changeDate',
            'type' => Date::class,
            'options' => [
                'label' => $translator->translate('Change Date'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Change Membership Type'),
            ],
        ]);
    }

    public function getMembership(): MembershipModel
    {
        return $this->membership;
    }

    /**
     * Function to limit the start and end date of the change date.
     */
    public function setMembership(MembershipModel $membership): void
    {
        $this->membership = $membership;

        $element = $this->get('type');
        assert($element instanceof Radio);
        $element->setValue($membership->getType()->value);

        $element = $this->get('changeDate');
        assert($element instanceof Date);

        $value = min($membership->getStartDate(), new DateTime())->format('Y-m-d');

        $element->setAttributes([
            'min' => $membership->getStartDate()->format('Y-m-d'),
            'max' => $membership->getEndDate()->format('Y-m-d'),
            'value' => $value,
        ]);
    }

    /**
     * Specification of input filter.
     */
    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
                'required' => true,
            ],
        ];
    }
}
