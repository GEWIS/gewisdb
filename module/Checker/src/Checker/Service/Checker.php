<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 22-12-14
 * Time: 18:07
 */
namespace Checker\Service;

use Application\Service\AbstractService;
use \Database\Model\SubDecision\Foundation;
use \Database\Model\Meeting;
use \Checker\Model\Error;
use Zend\Mail\Message;

class Checker extends AbstractService {

    /**
     * Does a full check on each meeting, checking that after each meeting no database violation occur
     */
    public function check() {

        $meetingService = $this->getServiceManager()->get('checker_service_meeting');
        $meetings = $meetingService->getAllMeetings();

        $message = '';
        foreach ($meetings as $meeting) {
            $errors = array_merge(
                $this->checkBudgetOrganExists($meeting),
                $this->checkMembersHaveRoleButNotInOrgan($meeting),
                $this->checkMembersInNotExistingOrgans($meeting),
                $this->checkMembersExpiredButStillInOrgan($meeting),
                $this->checkOrganMeetingType($meeting)
            );

            $message .= $this->handleErrors($meeting, $errors);
        }

        $this->sendMail($message);
    }

    /**
     * Makes sure that the errors are handled correctly
     *
     * @param \Database\Model\Meeting $meeting Meeting for which this errors hold
     * @param array $errors
     */
    private function handleErrors(\Database\Model\Meeting $meeting, array $errors)
    {
        // At this moment only write to output.
        $body =  'Errors after meeting ' . $meeting->getNumber() . ' hold at '
            . $meeting->getDate()->format('Y-m-d') . "\n";

        foreach ($errors as $error) {
            $body.= $error->asText() . "\n";
        }

        $body .= "\n";
        return $body;

    }

    /**
     * Send a mail with the detected errors to the secretary
     *
     * @param $body
     */
    private function sendMail($body)
    {
        $transport = $this->getServiceManager()->get('checker_mail_transport');

        $message = new Message();
        $message->addTo('secr@gewis.nl')
            ->setSubject('Database Checker Report')
            ->setBody($body);

        echo $body;

        $transport->send($message);
    }

    /**
     * Checks if there are members in non existing organs.
     * This can happen if there is still a member in the organ after it gets disbanded
     * Or if there is a member in the organ if the decision to create an organ
     * is nulled
     *
     * @param \Database\Model\Meeting $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkMembersInNotExistingOrgans(\Database\Model\Meeting $meeting)
    {
        $errors = [];
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $installationService = $this->getServiceManager()->get('checker_service_installation');
        $organs = $organService->getAllOrgans($meeting);
        $installations = $installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            $organName = $installation->getFoundation()->toArray()['name'];
            if (!in_array($organName, $organs,true)) {
                $errors[] = new Error\MembersInNonExistingOrgan($installation);
            }
        }
        return $errors;
    }

    /**
     * Checks if there are members that have expired, but are still in an oran
     *
     * @param \Database\Model\Meeting $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkMembersExpiredButStillInOrgan(\Database\Model\Meeting $meeting)
    {
        $errors = [];
        $installationService = $this->getServiceManager()->get('checker_service_installation');
        $installations = $installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            // Check if the members are still member of GEWIS
            $member = $installation->getMember();

            if ($member->getExpiration() < $meeting->getDate()) {
                $errors[] = new Error\MemberExpiredButStillInOrgan($meeting, $installation);
            }
        }
        return $errors;
    }

    /**
     * Checks if members still have a role in an organ (e.g. they are treasurer)
     * but they are not a member of the organ anymore
     *
     * @param \Database\Model\Meeting $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkMembersHaveRoleButNotInOrgan(\Database\Model\Meeting $meeting)
    {
        $errors = [];
        $installationService = $this->getServiceManager()->get('checker_service_installation');
        $membersArray = $installationService->getCurrentRolesPerOrgan($meeting);

        foreach ($membersArray as $organsMembers) {
            foreach ($organsMembers as $memberRoles) {
                if (!isset($memberRoles['Lid'])) {
                    foreach ($memberRoles as $role => $installation) {
                        $errors[] = new Error\MemberHasRoleButNotInOrgan($meeting, $installation, $role);
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Checks all budgets are for a valid organ (an organ that still exists)
     *
     * @param \Database\Model\Meeting $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkBudgetOrganExists(\Database\Model\Meeting $meeting) {
        $errors = [];
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $budgetService = $this->getServiceManager()->get('checker_service_budget');

        $organs = $organService->getAllOrgans($meeting);
        $budgets = $budgetService->getAllBudgets($meeting);

        foreach ($budgets as $budget) {
            $foundation = $budget->getFoundation();

            if (!is_null($foundation) && !in_array($foundation->getName(), $organs)) {
                $errors[] = new Error\BudgetOrganDoesNotExist($budget);
            }
        }

        return $errors;
    }

    /**
     * Checks all Organ creation, and check if they are created at the the correct Meeting
     * e.g. AVCommissies are only created at an AV
     *
     * @param \Database\Model\Meeting $meeting After which meeting do we do the validation
     * @return array Array of errors that may have occured.
     */
    public function checkOrganMeetingType(\Database\Model\Meeting $meeting) {
        $errors = [];
        $organService = $this->getServiceManager()->get('checker_service_organ');
        $organs = $organService->getOrgansCreatedAtMeeting($meeting);

        foreach ($organs as $organ) {
            $organType = $organ->getOrganType();
            $meetingType = $organ->getDecision()->getMeeting()->getType();

            // The meeting type and organ type match iff: The meeting type is not VV, or
            // if either both organtype and meetingtype is AV, or they are both not. So
            // it is wrong if only one of them has a meetingtype of AV
            if (
                $meetingType === Meeting::TYPE_VV ||
                ($organType ===  Foundation::ORGAN_TYPE_AV_COMMITTEE ^ $meetingType === Meeting::TYPE_AV)
            ) {
                $errors[] = new Error\OrganMeetingType($organ);
            }
        }
        return $errors;
    }

} 