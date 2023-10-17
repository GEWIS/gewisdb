<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use User\Model\User;

/**
 * Abstract audit log entry, can take different types
 */
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'note' => AuditNote::class,
    ],
)]
abstract class AuditEntry
{
    /**
     * Entry ID.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * When this entry was created
     */
    #[Column(type: 'datetime')]
    protected DateTime $created;

    /**
     * The user who created the entry
     */
    #[OneToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'user',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected User $user;
}
