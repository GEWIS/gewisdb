<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 26-1-15
 * Time: 15:59
 */

namespace Checker\Service;

use Application\Service\AbstractService;

class Budget extends AbstractService {

    /**
     * Returns an array of all budgets that have been approved
     * @param $meeting
     * @return mixed budgets
     */
    public function getAllBudgets($meeting)
    {
        $mapper = $this->getServiceManager()->get('checker_mapper_budget');
        return $mapper->getAllBudgets($meeting);
    }
} 