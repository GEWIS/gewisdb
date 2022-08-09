<?php

namespace Checker\Service;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\MembershipTypes;
use Application\Model\Enums\OrganTypes;
use Application\Service\EventAwareService;
use Checker\Model\Error;
use Checker\Service\Installation as InstallationService;
use Checker\Service\Meeting as MeetingService;
use Checker\Service\Member as MemberService;
use Checker\Service\Organ as OrganService;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\Meeting as MeetingModel;
use DateTime;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use Laminas\Json\Json;
use Laminas\Mail\Message;

class Checker
{
    /** @var InstallationService $installationService */
    private $installationService;

    /** @var MeetingService $meetingService */
    private $meetingService;

    /** @var MemberService $memberService */
    private $memberService;

    /** @var OrganService $organService */
    private $organService;

    private $mailTransport;

    /** @var array $config */
    private $config;

    /**
     * @param Installation $installationService
     * @param Meeting $meetingService
     * @param Member $memberService
     * @param Organ $organService
     * @param $mailTransport
     * @param array $config
     */
    public function __construct(
        InstallationService $installationService,
        MeetingService $meetingService,
        MemberService $memberService,
        OrganService $organService,
        $mailTransport,
        array $config,
    ) {
        $this->installationService = $installationService;
        $this->meetingService = $meetingService;
        $this->memberService = $memberService;
        $this->organService = $organService;
        $this->mailTransport = $mailTransport;
        $this->config = $config;
    }

    /**
     * Does a full check on each meeting, checking that after each meeting no database violation occur
     */
    public function check()
    {
        $meetings = $this->meetingService->getAllMeetings();

        $message = '';
        foreach ($meetings as $meeting) {
            $errors = array_merge(
                $this->checkMembersHaveRolesButInactiveOrNotInOrgan($meeting),
                $this->checkMembersInNonExistingOrgans($meeting),
                $this->checkMembersExpiredButStillInOrgan($meeting),
                $this->checkOrganMeetingType($meeting),
            );

            $message .= $this->handleMeetingErrors($meeting, $errors);
        }

        $this->sendMail($message);
    }

    /**
     * Does a full check on the last meeting (and all previous meetings) to determine if there are members who are
     * currently installed in an organ that was abrogated (i.e. they were never discharged).
     */
    public function checkDischarges()
    {
        $meeting = $this->meetingService->getLastMeeting();

        $message = $this->handleMeetingErrors($meeting, $this->checkMembersInNonExistingOrgans($meeting));

        $this->sendMail($message);
    }

    /**
     * Makes sure that the errors are handled correctly
     *
     * @param MeetingModel $meeting Meeting for which this errors hold
     * @param array $errors
     *
     * @return string
     */
    private function handleMeetingErrors(
        MeetingModel $meeting,
        array $errors,
    ): string {
        // At this moment only write to output.
        $body =  'Errors after meeting ' . $meeting->getNumber() . ' hold at '
            . $meeting->getDate()->format('Y-m-d') . "\n";

        foreach ($errors as $error) {
            $body .= $error->asText() . "\n";
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
        $message = new Message();
        $message->addTo($this->config['checker']['report_mail'])
            ->setSubject('Database Checker Report')
            ->setBody($body);

        $this->mailTransport->send($message);
    }

    /**
     * Checks if there are members in non-existing organs.
     * This can happen if there is still a member in the organ after it gets disbanded
     * Or if there is a member in the organ if the decision to create an organ
     * is nulled
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return array Array of errors that may have occurred.
     */
    public function checkMembersInNonExistingOrgans(MeetingModel $meeting): array
    {
        $errors = [];
        $organs = $this->organService->getAllOrgans($meeting);
        $installations = $this->installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            $installationToOrganFoundation = $this->organService->getHash($installation->getFoundation());

            if (!in_array($installationToOrganFoundation, $organs, true)) {
                $errors[] = new Error\MemberInNonExistingOrgan($meeting, $installation);
            }
        }

        return $errors;
    }

    /**
     * Checks if there are members that have expired, but are still in an oran
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return array Array of errors that may have occured.
     */
    public function checkMembersExpiredButStillInOrgan(MeetingModel $meeting): array
    {
        $errors = [];
        $installations = $this->installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            // Check if the members are still member of GEWIS
            $member = $installation->getMember();

            if (null !== ($membershipEndsOn = $member->getMembershipEndsOn())) {
                if ($membershipEndsOn < $meeting->getDate()) {
                    $errors[] = new Error\MemberExpiredButStillInOrgan($meeting, $installation);
                }
            }
        }
        return $errors;
    }

    /**
     * Checks if members still have a role in an organ (e.g. they are treasurer) but they are not a member of the organ
     * anymore, or they are an inactive member.
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return array Array of errors that may have occurred.
     */
    public function checkMembersHaveRolesButInactiveOrNotInOrgan(MeetingModel $meeting): array
    {
        $errors = [];
        $organs = $this->installationService->getCurrentRolesPerOrgan($meeting);

        foreach ($organs as $organMembers) {
            foreach ($organMembers as $memberRoles) {
                if (
                    isset($memberRoles['Lid'])
                    && isset($memberRoles['Inactief Lid'])
                ) {
                    // Member is active AND inactive in the same organ.
                    if (count($memberRoles) > 2) {
                        $errors[] = new Error\MemberActiveWithRoleAndInactiveInOrgan(
                            $meeting,
                            $memberRoles['Inactief Lid'],
                        );
                    } else {
                        $errors[] = new Error\MemberActiveAndInactiveInOrgan(
                            $meeting,
                            $memberRoles['Lid'],
                        );
                    }
                } elseif (
                    !isset($memberRoles['Lid'])
                    && isset($memberRoles['Inactief Lid'])
                    && count($memberRoles) > 1
                ) {
                    // Member is inactive but still has roles.
                    foreach ($memberRoles as $role => $installation) {
                        if ('Inactief Lid' === $role) {
                            continue;
                        }

                        $errors[] = new Error\MemberInactiveInOrganButHasOtherRole(
                            $meeting,
                            $installation,
                            $role,
                        );
                    }
                } elseif (
                    !isset($memberRoles['Lid'])
                    && !isset($memberRoles['Inactief Lid'])
                ) {
                    // Member is not active (nor inactive) but still has roles.
                    foreach ($memberRoles as $role => $installation) {
                        $errors[] = new Error\MemberHasRoleButNotInOrgan(
                            $meeting,
                            $installation,
                            $role,
                        );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Checks all Organ creation, and check if they are created at the the correct Meeting
     * e.g. AVCommissies are only created at an AV
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return array Array of errors that may have occurred.
     */
    public function checkOrganMeetingType(MeetingModel $meeting): array
    {
        $errors = [];
        $organs = $this->organService->getOrgansCreatedAtMeeting($meeting);

        foreach ($organs as $organ) {
            $organType = $organ->getOrganType();
            $meetingType = $organ->getDecision()->getMeeting()->getType();

            // The meeting type and organ type match iff: The meeting type is not VV, or
            // if either both organtype and meetingtype is AV, or they are both not. So
            // it is wrong if only one of them has a meetingtype of AV
            if (
                $meetingType === MeetingTypes::VV ||
                ($organType === OrganTypes::AVC ^ $meetingType === MeetingTypes::AV)
            ) {
                $errors[] = new Error\OrganMeetingType($organ);
            }
        }
        return $errors;
    }

    /**
     * Checks that "ordinary" members are still enrolled at the TU/e. If not, their membership should expire at the end
     * of the current association year. This does not actually update their membership type, as that is still valid for
     * the remainder of the current association year.
     */
    public function checkAtTUe(): void
    {
        $members = $this->memberService->getMembersToCheck();
        $config = $this->config['checker']['membership_api'];

        $client = new Client();
        $client->setAdapter(Curl::class)
            ->setEncType('application/json');

        $request = new Request();
        $request->setMethod(Request::METHOD_GET)
            ->getHeaders()->addHeaders([
                'Authorization' => 'Bearer ' . $config['key'],
            ]);

        // Determine the date of (potential) expiration of a member's membership outside the foreach to make sure we
        // only do it once.
        $exp = $this->getExpiration(new DateTime());

        // Check each member that needs to be checked.
        /** @var MemberModel $member */
        foreach ($members as $member) {
            echo "Performing request for member " . $member->getLidnr() . PHP_EOL;

            $request->setUri($config['endpoint'] . $member->getTueUsername());
            $response = $client->send($request);

            // Check if the request was successful. If something else happens than 200 or 404 that may have broken the
            // request, assume that the member is still in the TU/e student administration database but do not update
            // membership status.
            if (200 === $response->getStatusCode()) {
                echo "Received good response" . PHP_EOL;
                try {
                    $responseContent = Json::decode($response->getBody(), Json::TYPE_ARRAY);

                    // Check that we have a proper response.
                    if (array_key_exists('registrations', $responseContent)) {
                        echo "Response is valid" . PHP_EOL;
                        if (empty($responseContent['registrations'])) {
                            echo "Member is no longer studying at the TU/e" . PHP_EOL;
                            // The member is no longer studying at the TU/e.
                            $member->setChangedOn(new DateTime());
                            $member->setIsStudying(false);
                            $member->setMembershipEndsOn($exp);
                        } else {
                            echo "Member is still studying at the TU/e" . PHP_EOL;
                            // The member is still studying at the TU/e. Determine whether the member is a student at
                            // the Department of Mathematics and Computer Science or another department. If the member
                            // is still a student at the M&CS department don't change anything, otherwise, set date of
                            // expiration.
                            if (!in_array('WIN', array_column($responseContent['registrations'], 'dept'))) {
                                echo "Member is still studying but not at the department of MCS" . PHP_EOL;
                                $member->setChangedOn(new DateTime());
                                $member->setMembershipEndsOn($exp);
                            }
                        }

                        $member->setLastCheckedOn(new DateTime());
                    }
                } catch (\RuntimeException $e) {
                    echo "JSON is malformed or something else went wrong" . PHP_EOL;
                    // The request could not be decoded :/
                }
            } elseif (404 === $response->getStatusCode()) {
                echo "Member is no longer known at the TU/e" . PHP_EOL;
                // The member cannot be found in the TU/e student administration database.
                $member->setChangedOn(new DateTime());
                $member->setIsStudying(false);
                $member->setMembershipEndsOn($exp);
                $member->setLastCheckedOn(new DateTime());
            } else {
                echo "Request failed with status code " . $response->getStatusCode() . PHP_EOL;
            }

            echo "Request handled" . PHP_EOL;

            $this->memberService->getMemberMapper()->persist($member);
        }
    }

    /**
     * Makes sure that members whose membership has end date are actually converted to "graduate" when their membership
     * has ended.
     */
    public function checkProperMembershipType(): void
    {
        $members = $this->memberService->getEndingMembershipsWithNormalTypes();
        $lastMeeting = $this->meetingService->getLastMeeting();
        $activeMembers = $this->installationService->getActiveMembers($lastMeeting);

        echo "" . count($members) . " members have an upcoming ending and expiring membership" . PHP_EOL;
        $now = (new DateTime())->setTime(0, 0);
        $exp = $this->getExpiration($now);

        /** @var MemberModel $member */
        foreach ($members as $member) {
            echo "Determining new membership type for " . $member->getLidnr() . " (ends on " . $member->getMembershipEndsOn()->format('Y-m-d') . " and expiring on " . $member->getExpiration()->format('Y-m-d') . ")" . PHP_EOL;

            if ($member->getMembershipEndsOn() <= $now) {
                echo "Membership has ended and expired" . PHP_EOL;

                if (array_key_exists($member->getLidnr(), $activeMembers)) {
                    echo "Currently an active member, so becoming EXTERNAL. Extending membership to " . $exp->format('Y-m-d') . PHP_EOL;
                    $member->setType(MembershipTypes::External);

                    // External memberships should run till the end of the next association year (which is actually the
                    // same date as the expiration).
                    $member->setMembershipEndsOn($exp);
                } else {
                    echo "Not an active member" . PHP_EOL;
                    // We only have to change the membership type for external or graduates depending on whether the
                    // member is still studying.
                    if ($member->getIsStudying()) {
                        echo "But is studying, so becoming EXTERNAL. Extending membership to " . $exp->format('Y-m-d') . PHP_EOL;
                        $member->setType(MembershipTypes::External);

                        // External memberships should run till the end of the next association year (which is actually
                        // the same date as the expiration).
                        $member->setMembershipEndsOn($exp);
                    } else {
                        echo "Nor studying, so becoming GRADUATE. Not extending membership" . PHP_EOL;
                        $member->setType(MembershipTypes::Graduate);
                    }
                }

                echo "Extending expiration to " . $exp->format('Y-m-d') . PHP_EOL;

                $member->setChangedOn(new DateTime());
                $member->setExpiration($exp);

                $this->memberService->getMemberMapper()->persist($member);
            } else {
                echo "Membership has not yet ended and expired, so changing nothing" . PHP_EOL;
            }
        }
    }

    /**
     * Make sure that ordinary members have their membership expiration automatically extended if they are eligible.
     */
    public function checkNormalExpiration(): void
    {
        $members = $this->memberService->getExpiringMembershipsWithNormalTypes();

        echo "" . count($members) . " members have an upcoming expiring membership" . PHP_EOL;

        // Determine the next expiration date (always the end of the next association year).
        $now = (new DateTime())->setTime(0, 0);
        $exp = $this->getExpiration($now);

        /** @var MemberModel $member */
        foreach ($members as $member) {
            echo "Determining new expiration for " . $member->getLidnr() . " (expiring on " . $member->getExpiration()->format('Y-m-d') . ")" . PHP_EOL;

            if ($member->getExpiration() <= $now) {
                echo "Expired, thus extending to " . $exp->format('Y-m-d') . PHP_EOL;

                $member->setChangedOn(new DateTime());
                $member->setExpiration($exp);

                $this->memberService->getMemberMapper()->persist($member);
            } else {
                echo "Not yet expired, so not extending" . PHP_EOL;
            }
        }
    }

    /**
     * Determine the next expiration date (always the end of the next association year).
     *
     * @param DateTime $now
     *
     * @return DateTime
     */
    private function getExpiration(DateTime $now): DateTime
    {
        $exp = clone $now;
        $exp->setTime(0, 0);

        if ($exp->format('m') >= 7) {
            $year = (int) $exp->format('Y') + 1;
        } else {
            $year = (int) $exp->format('Y');
        }

        $exp->setDate($year, 7, 1);

        return $exp;
    }
}
