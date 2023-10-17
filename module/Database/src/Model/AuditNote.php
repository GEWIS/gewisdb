<?php

declare(strict_types=1);

namespace Database\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class for registering manual notes
 */
#[Entity]
class AuditNote extends AuditEntry
{
    /**
     * When this entry was created
     */
    #[Column(type: 'string')]
    protected string $note;
}
