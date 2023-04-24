<?php

declare(strict_types=1);

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\MeetingTypes;
use Laminas\Hydrator\Strategy\StrategyInterface;

class MeetingHydratorStrategy implements StrategyInterface
{
    public function extract(
        $value,
        ?object $object = null,
    ): string {
        if ($value instanceof MeetingTypes) {
            return $value->value;
        }

        return MeetingTypes::from($value)->value;
    }

    public function hydrate(
        $value,
        ?array $data,
    ): MeetingTypes {
        if ($value instanceof MeetingTypes) {
            return $value;
        }

        return MeetingTypes::from($value);
    }
}
