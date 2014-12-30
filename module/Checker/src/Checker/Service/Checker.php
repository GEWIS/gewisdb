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
        $messages = $this->checkMembersInNotExistingOrgans(2);
        \Zend\Debug\Debug::dump($messages);
    }

    /**
     * Checks if there are members in non existing organs.
     * This can happen if there is still a member in the organ after it gets disbanded
     * Or if there is a member in the organ if the decision to create an organ
     * is nulled
     * @param int $meeting After which meeting do we do the validation
     */
    public function checkMembersInNotExistingOrgans($meeting)
    {
        $errors = [];
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $installationService = $this->getServiceManager()->get('checker_service_installation');
        $organs = $organService->getAllOrgans($meeting);
        $installations = $installationService->getAllMembers($meeting);

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
} 