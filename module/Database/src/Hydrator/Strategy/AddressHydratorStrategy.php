<?php

declare(strict_types=1);

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\AddressTypes;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Override;

class AddressHydratorStrategy implements StrategyInterface
{
    #[Override]
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof AddressTypes) {
            return $value->value;
        }

        return AddressTypes::from($value)->value;
    }

    #[Override]
    public function hydrate(
        mixed $value,
        ?array $data,
    ): AddressTypes {
        if ($value instanceof AddressTypes) {
            return $value;
        }

        return AddressTypes::from($value);
    }
}
