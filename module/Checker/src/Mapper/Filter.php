<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Database\Model\SubDecision as SubDecisionModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;

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
            // First, fetch all annulment decisions
            $qb = $this->em->createQueryBuilder();
            $qb->select('d')
                ->from(AnnulmentModel::class, 'd');
            /** @var AnnulmentModel[] $deletions */
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
     * Checks if an annulment decision is still valid (i.e. is not annulled).
     *
     * @param AnnulmentModel $d Annulment decision
     *
     * @return bool is the annul decision not annulled?
     */
    protected function isValid(AnnulmentModel $d): bool
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
