<?php

/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 8-3-15
 * Time: 14:28
 */

namespace Checker\Mapper;

trait Filter
{
    /**
     * Filters an array of subdecisions to find decisions that are still valid
     *
     * @param array $deleted array to be filtered by reference
     * @return array $deleted array after it was filtered
     */
    public function filterDeleted(array &$subDecisions)
    {
        $deleted = $this->getDeleted();

        foreach ($subDecisions as $key => $dec) {
            if (in_array($dec, $deleted)) {
                unset($subDecisions[$key]);
            }
        }

        return $subDecisions;
    }

    /**
     * Return an array of all subdecisions that are deleted
     *
     * @return Array of Database\Model\SubDecision
     */
    protected function getDeleted()
    {
        // use static to only make sure that the variable has only be set once
        static $deleted;
        if (is_null($deleted)) {
            // First, fetch all destroy decisions
            $qb = $this->em->createQueryBuilder();
            $qb->select('d')
                ->from('Database\Model\SubDecision\Destroy', 'd');
            $deletions = $qb->getQuery()->getResult();

            // check for all decisions if they are valid
            $deleted = array();
            foreach ($deletions as $key => $del) {
                if ($this->isValid($del)) {
                    // if they are valid, add all the affected subdecisions
                    // and add them to the array
                    $deleted += $del->getTarget()->getSubDecisions()->toArray();
                }
            }
        }

        return $deleted;
    }


    /**
     * Checks if a destroy decision is still valid (i,e. is not destroyed
     *
     * @param \Database\Model\SubDecision\Destroy $d Destroy decision
     * @return bool is the destroy decision not destroyed?
     */
    protected function isValid(\Database\Model\SubDecision\Destroy $d)
    {
        // Get the decision
        $decision = $d->getDecision();

        $destroy = $decision->getDestroyedBy();

        // if this decision was not destroyed, it is certainly valid
        if (is_null($destroy)) {
            return true;
        }

        // else it is valid iff the destroyed by is not valid
        return !$this->isValid($destroy);
    }
}
