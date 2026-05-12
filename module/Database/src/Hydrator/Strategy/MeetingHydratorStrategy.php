<?php

declare(strict_types=1);

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\MeetingTypes;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Override;

class MeetingHydratorStrategy implements StrategyInterface
{
    #[Override]
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof MeetingTypes) {
            return $value->value;
        }

        return MeetingTypes::from($value)->value;
    }

    #[Override]
    public function hydrate(
        mixed $value,
        ?array $data,
    ): MeetingTypes {
        if ($value instanceof MeetingTypes) {
            return $value;
        }

        return MeetingTypes::from($value);
    }
}
