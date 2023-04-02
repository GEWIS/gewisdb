<?php

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\PostalRegions;
use Laminas\Hydrator\Strategy\StrategyInterface;

class PostalRegionHydratorStrategy implements StrategyInterface
{
    public function extract(
        $value,
        ?object $object = null,
    ): string {
        if ($value instanceof PostalRegions) {
            return $value->value;
        }

        return PostalRegions::from($value)->value;
    }

    public function hydrate(
        $value,
        ?array $data,
    ): PostalRegions {
        if ($value instanceof PostalRegions) {
            return $value;
        }

        return PostalRegions::from($value);
    }
}
