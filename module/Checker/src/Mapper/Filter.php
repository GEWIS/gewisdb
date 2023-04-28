<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Database\Model\SubDecision as SubDecisionModel;
use Database\Model\SubDecision\Destroy as DestroyModel;

use function in_array;

trait Filter
{
    /**
     * Filters an array of subdecisions to find decisions that are still valid
     *
     * @template TSubDecision of SubDecisionModel
     *
     * @param TSubDecision[] $subDecisions array to be filtered by reference
     *
     * @return TSubDecision[] after it was filtered
     */
    public function filterDeleted(array $subDecisions): array
    {
        $deleted = $this->getDeleted();

        foreach ($subDecisions as $key => $dec) {
            if (!in_array($dec, $deleted)) {
                continue;
            }

            unset($subDecisions[$key]);
        }

        return $subDecisions;
    }

    /**
     * Return an array of all subdecisions that are deleted
     *
     * @return SubDecisionModel[]
     */
    protected function getDeleted(): array
    {
        // use static to only make sure that the variable has only be set once
        static $deleted;
        if (null === $deleted) {
            // First, fetch all destroy decisions
            $qb = $this->em->createQueryBuilder();
            $qb->select('d')
                ->from(DestroyModel::class, 'd');
            /** @var DestroyModel[] $deletions */
            $deletions = $qb->getQuery()->getResult();

            // check for all decisions if they are valid
            $deleted = [];
            foreach ($deletions as $key => $del) {
                if (!$this->isValid($del)) {
                    continue;
                }

                // if they are valid, add all the affected subdecisions
                // and add them to the array
                $deleted += $del->getTarget()->getSubDecisions()->toArray();
            }
        }

        return $deleted;
    }

    /**
     * Checks if a destroy decision is still valid (i,e. is not destroyed
     *
     * @param DestroyModel $d Destroy decision
     *
     * @return bool is the destroy decision not destroyed?
     */
    protected function isValid(DestroyModel $d): bool
    {
        // Get the decision
        $decision = $d->getDecision();

        $destroy = $decision->getDestroyedBy();

        // if this decision was not destroyed, it is certainly valid
        if (null === $destroy) {
            return true;
        }

        // else it is valid iff the destroyed by is not valid
        return !$this->isValid($destroy);
    }
}
