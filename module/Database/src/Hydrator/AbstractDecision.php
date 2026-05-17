<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use InvalidArgumentException;
use Laminas\Hydrator\HydratorInterface;
use Override;

use function intval;

abstract class AbstractDecision implements HydratorInterface
{
    /**
     * Decision hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $object is not a Decision.
     */
    #[Override]
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        if (!$object instanceof DecisionModel) {
            throw new InvalidArgumentException('Object is not an instance of Database\Model\Decision.');
        }

        $object->setMeeting($data['meeting']);
        $object->setPoint(intval($data['point']));
        $object->setNumber(intval($data['decision']));

        return $object;
    }

    /**
     * Extraction.
     *
     * Not implemented.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    #[Override]
    public function extract(object $object): array
    {
        if (!$object instanceof DecisionModel) {
            throw new InvalidArgumentException('Object is not an instance of Database\Model\Decision.');
        }

        return [];
    }
}
