<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\MailingList as MailingListModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mailing list mapper.
 */
class MailingList
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Persist a list.
     */
    public function persist(MailingListModel $list): void
    {
        $this->em->persist($list);
        $this->em->flush();
    }

    /**
     * Remove a list.
     */
    public function remove(MailingListModel $list): void
    {
        $this->em->remove($list);
        $this->em->flush();
    }

    /**
     * Find all.
     *
     * @return array<array-key, MailingListModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findBy([], ['name' => 'ASC']);
    }

    /**
     * Find all mailing lists that are on the subscription form.
     *
     * @return array<array-key, MailingListModel>
     */
    public function findAllOnForm(): array
    {
        return $this->getRepository()->findBy(['onForm' => true], ['name' => 'ASC']);
    }

    /**
     * Find all default
     *
     * @return array<array-key, MailingListModel>
     */
    public function findDefault(): array
    {
        return $this->getRepository()->findBy([
            'defaultSub' => true,
            'onForm' => false,
        ], [
            'name' => 'ASC',
        ]);
    }

    /**
     * Find a list.
     */
    public function find(string $name): ?MailingListModel
    {
        return $this->getRepository()->find($name);
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository('Database\Model\MailingList');
    }
}
