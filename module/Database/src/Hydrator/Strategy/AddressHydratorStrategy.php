<?php

declare(strict_types=1);

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\AddressTypes;
use Laminas\Hydrator\Strategy\StrategyInterface;

class AddressHydratorStrategy implements StrategyInterface
{
    public function extract(
        $value,
        ?object $object = null,
    ): string {
        if ($value instanceof AddressTypes) {
            return $value->value;
        }

        return AddressTypes::from($value)->value;
    }

    public function hydrate(
        $value,
        ?array $data,
    ): AddressTypes {
        if ($value instanceof AddressTypes) {
            return $value;
        }

        return AddressTypes::from($value);
    }
}
