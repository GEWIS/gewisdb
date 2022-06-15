<?php

namespace Report\Listener;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Zend\ServiceManager\ServiceManager;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseDeletionListener
{
    protected $sm;
    public function __construct(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    public function preRemove($eventArgs)
    {
        $em = $this->sm->get('doctrine.entitymanager.orm_report');
        $entity = $eventArgs->getEntity();
        switch (true) {
            case $entity instanceof \Database\Model\Address:
                $this->getMemberService()->deleteAddress($entity);
                break;
            case $entity instanceof \Database\Model\Member:
                try {
                    $this->getMemberService()->deleteMember($entity);
                } catch (ForeignKeyConstraintViolationException $e) {
                    // Member has relations, so we'll just leave it in reportdb
                }
                $this->getMemberService()->deleteMember($entity);
                break;

            case $entity instanceof \Database\Model\Meeting:
                throw new \Exception('reportdb deletion of meetings not implemented');
                break;

            case $entity instanceof \Database\Model\Decision:
                $this->getMeetingService()->deleteDecision($entity);
                break;
        }
        $em->flush();
    }

    /**
     * Get the member service.
     *
     * @return \Report\Service\Member
     */
    public function getMemberService()
    {
        return $this->sm->get('report_service_member');
    }

    /**
     * Get the meeting service.
     *
     * @return \Report\Service\Meeting
     */
    public function getMeetingService()
    {
        return $this->sm->get('report_service_meeting');
    }

    /**
     * Get the organ service.
     *
     * @return \Report\Service\Organ
     */
    public function getOrganService()
    {
        return $this->sm->get('report_service_organ');
    }

    /**
     * Get the board service.
     *
     * @return \Report\Service\Board
     */
    public function getBoardService()
    {
        return $this->sm->get('report_service_board');
    }
}
