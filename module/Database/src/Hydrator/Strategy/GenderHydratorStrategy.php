<?php

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\GenderTypes;
use Laminas\Hydrator\Strategy\StrategyInterface;

class GenderHydratorStrategy implements StrategyInterface
{
    public function extract(
        $value,
        ?object $object = null,
    ): string {
        if ($value instanceof GenderTypes) {
            return $value->value;
        }

        return GenderTypes::from($value)->value;
    }

    public function hydrate(
        $value,
        ?array $data,
    ): GenderTypes {
        if ($value instanceof GenderTypes) {
            return $value;
        }

        return GenderTypes::from($value);
    }
}
