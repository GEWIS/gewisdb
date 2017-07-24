<?php
namespace Report\Listener;

use Zend\ServiceManager\ServiceManager;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseUpdateListener
{
    protected $sm;
    public function __construct(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    public function postPersist($eventArgs)
    {
        $this->postUpdate($eventArgs);
    }

    public function postUpdate($eventArgs)
    {
        echo '<br>' .get_class($eventArgs) .' call '.get_class($eventArgs->getEntity()) . '<br>';
        $entity = $eventArgs->getEntity();
        switch (true) {
            case $entity instanceof \Database\Model\Address:
                $this->getMemberService()->generateAddress($entity);
                break;

            case $entity instanceof \Database\Model\Member:
                $this->getMemberService()->generateMember($entity);
                break;

            case $entity instanceof \Database\Model\Meeting:
                $this->getMeetingService()->generateMeeting($entity);
                break;

            case $entity instanceof \Database\Model\Decision:
                $this->getMeetingService()->generateDecision($entity);
                break;

            case $entity instanceof \Database\Model\SubDecision:
                $this->getMeetingService()->generateSubDecision($entity);
                $this->processOrganUpdates($entity);
                break;

        }
        $em = $this->sm->get('doctrine.entitymanager.orm_report');
        $em->flush();
    }

    public function processOrganUpdates($entity)
    {
        switch (true) {
            case $entity instanceof \Database\Model\SubDecision\Foundation:
                $this->getOrganService()->generateFoundation($entity);
                break;

            case $entity instanceof \Database\Model\SubDecision\Abrogation:
                $this->getOrganService()->generateAbrogation($entity);
                break;

            case $entity instanceof \Database\Model\SubDecision\Installation:
                $this->getOrganService()->generateInstallation($entity);
                break;

            case $entity instanceof \Database\Model\SubDecision\Discharge:
                $this->getOrganService()->generateDischarge($entity);
                break;
        }
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
        return $this->getServiceLocator()->get('report_service_organ');
    }

    /**
     * Get the board service.
     *
     * @return \Report\Service\Board
     */
    public function getBoardService()
    {
        return $this->getServiceLocator()->get('report_service_board');
    }
}