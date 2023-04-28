<?php

declare(strict_types=1);

namespace Database\Hydrator\Strategy;

use Application\Model\Enums\PostalRegions;
use Laminas\Hydrator\Strategy\StrategyInterface;

class PostalRegionHydratorStrategy implements StrategyInterface
{
    public function extract(
        mixed $value,
        ?object $object = null,
    ): string {
        if ($value instanceof PostalRegions) {
            return $value->value;
        }

        return PostalRegions::from($value)->value;
    }

    public function hydrate(
        mixed $value,
        ?array $data,
    ): PostalRegions {
        if ($value instanceof PostalRegions) {
            return $value;
        }

        return PostalRegions::from($value);
    }
}
