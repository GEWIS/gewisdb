<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Mapper\Meeting as MeetingMapper;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

use function strtoupper;

/**
 * @template TFilteredValues
 *
 * @extends Form<array{
 *     meetings: string[],
 * }>
 */
class Export extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingMapper $meetingMapper,
    ) {
        parent::__construct();

        $this->add([
            'name' => 'meetings',
            'type' => Select::class,
            'attributes' => ['multiple' => 'multiple'],
            'options' => [
                'label' => $this->translator->translate('Meetings'),
                'value_options' => $this->getValueOptions($meetingMapper),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Export Decisions'),
            ],
        ]);
    }

    protected function getValueOptions(MeetingMapper $meetingMapper): array
    {
        $options = [];

        foreach ($meetingMapper->findAll() as $meeting) {
            $meeting = $meeting[0];
            $id = $meeting->getType()->value . '-' . $meeting->getNumber();
            $options[$id] = strtoupper($meeting->getType()->value) . ' ' . $meeting->getNumber()
                          . '   (' . $meeting->getDate()->format('j F Y') . ')';
        }

        return $options;
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
