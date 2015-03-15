<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 26-1-15
 * Time: 15:59
 */

namespace Checker\Service;

use Application\Service\AbstractService;

class Budget extends AbstractService
{

    /**
     * Returns all budgets thar are created at $meeting
     *
     * @param \Database\Model\Meeting $meeting
     * @return array \Database\Model\Subdecision\Budget Array of all budgets created at $meeting
     */
    public function getAllBudgets(\Database\Model\Meeting $meeting)
    {
        $mapper = $this->getServiceManager()->get('checker_mapper_budget');
        return $mapper->getAllBudgets($meeting);
    }
}
