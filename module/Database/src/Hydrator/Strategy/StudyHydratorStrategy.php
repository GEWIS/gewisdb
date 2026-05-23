<?php

declare(strict_types=1);

namespace Database\Hydrator\Strategy;

use Database\Model\Enums\Studies;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Override;

class StudyHydratorStrategy implements StrategyInterface
{
    #[Override]
    public function extract(
        mixed $value,
        ?object $object = null,
    ): ?string {
        if ($value instanceof Studies) {
            return $value->value;
        }

        if (null === $value || '' === $value) {
            return null;
        }

        return Studies::from($value)->value;
    }

    #[Override]
    public function hydrate(
        mixed $value,
        ?array $data,
    ): ?Studies {
        if ($value instanceof Studies) {
            return $value;
        }

        if (null === $value || '' === $value) {
            return null;
        }

        return Studies::from($value);
    }
}
