<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\AuditEntry as AuditEntryModel;
use Database\Model\AuditNote as AuditNoteModel;
use InvalidArgumentException;
use Laminas\Hydrator\HydratorInterface;

use function method_exists;

class AuditEntry implements HydratorInterface
{
    /**
     * Decision hydration
     *
     * @param AuditEntryModel $object
     *
     * @throws InvalidArgumentException when $object is not a Decision.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function hydrate(
        array $data,
        object $object,
    ): AuditEntryModel {
        if (!$object instanceof AuditEntryModel) {
            throw new InvalidArgumentException('Object is not an instance of ' . AuditEntryModel::class);
        }

        if (method_exists($object, 'setNote')) {
            $object->setNote($data['note']);
        }

        return $object;
    }

    /**
     * Extraction.
     *
     * Not implemented.
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function extract(object $object): array
    {
        if (!$object instanceof AuditNoteModel) {
            throw new InvalidArgumentException('Object is not an instance of ' . AuditEntryModel::class);
        }

        return [];
    }
}
