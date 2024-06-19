<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

class Minutes extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        MemberFieldset $member,
        MeetingFieldset $minutes,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Meeting'),
            ],
        ]);

        $member->setName('author');
        $member->setLabel($this->translator->translate('Author'));
        $this->add($member);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Submit'),
            ],
        ]);

        $this->add([
            'name' => 'approve',
            'type' => Radio::class,
            'options' => [
                'label' => $this->translator->translate('Approval'),
                'value_options' => [
                    '1' => $this->translator->translate('Approve'),
                    '0' => $this->translator->translate('Disapprove'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'changes',
            'type' => Radio::class,
            'options' => [
                'label' => $this->translator->translate('Modifications'),
                'value_options' => [
                    '1' => $this->translator->translate('With Modifications'),
                    '0' => $this->translator->translate('Without Modifications'),
                ],
            ],
        ]);

        $minutes->setName('fmeeting');
        $this->add($minutes);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'approve' => [
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ],
            'changes' => [
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ],
        ];
    }
}
