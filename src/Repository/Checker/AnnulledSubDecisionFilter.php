<?php

declare(strict_types=1);

namespace App\Repository\Checker;

use App\Entity\Database\SubDecision;
use App\Entity\Database\SubDecision\Annulment;
use Doctrine\ORM\EntityManagerInterface;

use function in_array;

/**
 * Removes subdecisions that were annulled from the results of the checker queries.
 *
 * Not backed by a single entity: it reads every annulment and works on subdecisions of any type.
 */
class AnnulledSubDecisionFilter
{
    /** @var SubDecision[]|null */
    private ?array $deleted = null;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Filters an array of subdecisions to find decisions that are still valid
     *
     * @template TSubDecision of SubDecision
     *
     * @param TSubDecision[] $subDecisions array to be filtered
     *
     * @return TSubDecision[] after it was filtered
     */
    public function filterDeleted(array $subDecisions): array
    {
        $deleted = $this->getDeleted();

        foreach ($subDecisions as $key => $dec) {
            // Strict, so that two subdecisions that merely look alike are not mistaken for one another.
            if (!in_array($dec, $deleted, true)) {
                continue;
            }

            unset($subDecisions[$key]);
        }

        return $subDecisions;
    }

    /**
     * Return an array of all subdecisions that are deleted
     *
     * @return SubDecision[]
     */
    private function getDeleted(): array
    {
        // Only ever determined once, the set cannot change while the checker runs.
        if (null === $this->deleted) {
            $qb = $this->em->getRepository(Annulment::class)->createQueryBuilder('d');

            /** @var Annulment[] $deletions */
            $deletions = $qb->getQuery()->getResult();

            // check for all decisions if they are valid
            $deleted = [];
            foreach ($deletions as $del) {
                if (!$this->isValid($del)) {
                    continue;
                }

                // if they are valid, add all the affected subdecisions
                // and add them to the array
                $deleted = [...$deleted, ...$del->getTarget()->getSubdecisions()->toArray()];
            }

            $this->deleted = $deleted;
        }

        return $this->deleted;
    }

    /**
     * Checks if an annulment decision is still valid (i.e. is not annulled).
     *
     * @param Annulment $d Annulment decision
     *
     * @return bool is the annul decision not annulled?
     */
    private function isValid(Annulment $d): bool
    {
        // Get the decision
        $decision = $d->getDecision();

        $annulment = $decision->getAnnulledBy();

        // if this decision was not annulled, it is certainly valid
        if (null === $annulment) {
            return true;
        }

        // else it is valid iff the annulled by is not valid
        return !$this->isValid($annulment);
    }
}
