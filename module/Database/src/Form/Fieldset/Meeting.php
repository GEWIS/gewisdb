<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Meeting as MeetingModel;
use DateTimeInterface;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Fieldset;

/**
 * @phpstan-type MeetingType = array{
 *  type: value-of<MeetingTypes>,
 *  number: int,
 *  date: string,
 * }
 */
class Meeting extends Fieldset
{
    public function __construct()
    {
        parent::__construct('meeting');

        $this->add([
            'name' => 'type',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'number',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'date',
            'type' => Hidden::class,
        ]);

        // TODO: filters
    }

    /**
     * Set meeting data.
     */
    public function setMeetingData(MeetingModel $meeting): void
    {
        $this->get('type')->setValue($meeting->getType()->value);
        $this->get('number')->setValue($meeting->getNumber());
        $this->get('date')->setValue($meeting->getDate()->format(DateTimeInterface::ATOM));
    }
}
