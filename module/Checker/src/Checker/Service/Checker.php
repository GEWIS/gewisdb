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
        $this->checkMembersInNotExistingOrgans(2);
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
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $organs = $organService->getAllOrgans($meeting);
        \Zend\Debug\Debug::dump($organs);
    }
} 