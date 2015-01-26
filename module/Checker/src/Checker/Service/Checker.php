<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 22-12-14
 * Time: 18:07
 */
namespace Checker\Service;

use Application\Service\AbstractService;

class Checker extends AbstractService {

    public function check() {
        $messages = $this->checkBudgetOrganExists(3);
        \Zend\Debug\Debug::dump($messages);
    }

    /**
     * Checks if there are members in non existing organs.
     * This can happen if there is still a member in the organ after it gets disbanded
     * Or if there is a member in the organ if the decision to create an organ
     * is nulled
     * @param int $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkMembersInNotExistingOrgans($meeting)
    {
        $errors = [];
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $installationService = $this->getServiceManager()->get('checker_service_installation');
        $organs = $organService->getAllOrgans($meeting);
        $installations = $installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            $organName = $installation->getFoundation()->toArray()['name'];
            if (!in_array($organName, $organs,true)) {
                $errors[] = 'Member ' . $installation->getMember()->toArray()['fullName'] .
                    ' ('. $installation->getMember()->toArray()['lidnr'] . ')'
                    . ' is still installed in ' . $organName . ' which does not exist anymore';
            }
        }
        return $errors;
    }

    /**
     * Checks if members still have a role in an organ (e.g. they are treasurer)
     * but they are not a member of the organ anymore
     * @param int $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkMembersHaveRoleButNotInOrgan($meeting)
    {
        $errors = [];
        $installationService = $this->getServiceManager()->get('checker_service_installation');
        $membersArray = $installationService->getCurrentRolesPerOrgan($meeting);

        foreach ($membersArray as $organsMembers) {
            foreach ($organsMembers as $memberRoles) {
                if (!isset($memberRoles['Lid'])) {
                    foreach ($memberRoles as $role => $installation) {
                        $errors[] = 'Member ' . $installation->getMember()->toArray()['fullName'] .
                            ' ('. $installation->getMember()->toArray()['lidnr'] . ')'
                            . ' has a special role as ' . $role . ' in  '
                            . $installation->getFoundation()->toArray()['name'] . '  but is not a member anymore';
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Checks all budgets are for a valid organ (an organ that still exists)
     *
     * @param int $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkBudgetOrganExists($meeting) {
        $errors = [];
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $budgetService = $this->getServiceManager()->get('checker_service_budget');

        $organs = $organService->getAllOrgans($meeting);
        $budgets = $budgetService->getAllBudgets($meeting);

        foreach ($budgets as $budget) {
            $foundation = $budget->getFoundation();
            \Zend\Debug\Debug::dump($foundation);
        }

        return $errors;
    }

} 