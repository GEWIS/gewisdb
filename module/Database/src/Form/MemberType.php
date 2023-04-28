<?php

declare(strict_types=1);

namespace Database\Form;

use Application\Model\Enums\MembershipTypes;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

class MemberType extends Form implements InputFilterProviderInterface
{
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
                    MembershipTypes::External->value => $this->translator->translate('External - Specially admitted by the board'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Graduate->value => $this->translator->translate('Graduate - Old member and specially admitted by the board'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Honorary->value => $this->translator->translate('Honorary - Specially appointed by the GMM'),
                ],
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

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
                'required' => true,
            ],
        ];
    }
}
