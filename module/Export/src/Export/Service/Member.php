<?php

namespace Export\Service;

use Application\Service\AbstractService;

class Member extends AbstractService
{

    /**
     * Export members.
     */
    public function export()
    {
        $mapper = $this->getMemberMapper();

        foreach ($mapper->findAll() as $member) {
            echo "Exporting " . $member->getFullName() . "\n";
            // TODO: export member
        }
    }

    /**
     * Get the member mapper.
     *
     * @return \Database\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('database_mapper_member');
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('export_query_member');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
