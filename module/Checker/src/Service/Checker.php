<?php

declare(strict_types=1);

namespace Checker\Service;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\MembershipTypes;
use Application\Model\Enums\OrganTypes;
use Checker\Model\Error as ErrorModel;
use Checker\Model\Exception\LookupException;
use Checker\Model\TueData;
use Checker\Service\Installation as InstallationService;
use Checker\Service\Key as KeyService;
use Checker\Service\Meeting as MeetingService;
use Checker\Service\Member as MemberService;
use Checker\Service\Organ as OrganService;
use Database\Model\Meeting as MeetingModel;
use DateInterval;
use DateTime;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;

use function array_key_exists;
use function array_merge;
use function count;
use function in_array;

use const PHP_EOL;

class Checker
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly InstallationService $installationService,
        private readonly KeyService $keyService,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly OrganService $organService,
        private readonly TransportInterface $mailTransport,
        private readonly array $config,
    ) {
    }

    /**
     * Does a full check on each meeting, checking that after each meeting no database violation occurs
     */
    public function check(): void
    {
        $meetings = $this->meetingService->getAllMeetings();

        $message = '';
        foreach ($meetings as $meeting) {
            $errors = array_merge(
                $this->checkMembersHaveRolesButInactiveOrNotInOrgan($meeting),
                $this->checkMembersInNonExistingOrgans($meeting),
                $this->checkMembersExpiredButStillInOrgan($meeting),
                $this->checkOrganFoundationMeetingType($meeting),
                $this->checkKeyGrantingDuration($meeting),
                $this->checkKeyWithdrawalTime($meeting),
            );

            $message .= $this->handleMeetingErrors($meeting, $errors);
        }

        $this->sendMail($message);
    }

    /**
     * Does a full check on the last meeting (and all previous meetings) to determine if there are members who are
     * currently installed in an organ that was abrogated (i.e. they were never discharged).
     */
    public function checkDischarges(): void
    {
        $meeting = $this->meetingService->getLastMeeting();

        $message = $this->handleMeetingErrors($meeting, $this->checkMembersInNonExistingOrgans($meeting));

        $this->sendMail($message);
    }

    /**
     * Makes sure that the errors are handled correctly
     *
     * @param ErrorModel[] $errors
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
     */
    private function sendMail(string $body): void
    {
        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setTo(
            $this->config['email']['to']['checker_result']['address'],
            $this->config['email']['to']['checker_result']['name'],
        )
            ->setFrom($this->config['email']['from']['address'], $this->config['email']['from']['name'])
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
     * @return ErrorModel[]
     */
    public function checkMembersInNonExistingOrgans(MeetingModel $meeting): array
    {
        $errors = [];
        $organs = $this->organService->getAllOrgans($meeting);
        $installations = $this->installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            $installationToOrganFoundation = $this->organService->getHash($installation->getFoundation());

            if (in_array($installationToOrganFoundation, $organs, true)) {
                continue;
            }

            $errors[] = new ErrorModel\MemberInNonExistingOrgan($meeting, $installation);
        }

        return $errors;
    }

    /**
     * Checks if there are members that have expired, but are still in an oran
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return ErrorModel[]
     */
    public function checkMembersExpiredButStillInOrgan(MeetingModel $meeting): array
    {
        $errors = [];
        $installations = $this->installationService->getAllInstallations($meeting);

        foreach ($installations as $installation) {
            // Check if the members are still member of GEWIS
            $member = $installation->getMember();

            if (
                $member->getExpiration() >= $meeting->getDate()
                || $member->getDeleted()
            ) {
                continue;
            }

            $errors[] = new ErrorModel\MemberExpiredButStillInOrgan($meeting, $installation);
        }

        return $errors;
    }

    /**
     * Checks if members still have a role in an organ (e.g. they are treasurer) but they are not a member of the organ
     * anymore, or they are an inactive member.
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return ErrorModel[]
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
                        $errors[] = new ErrorModel\MemberActiveWithRoleAndInactiveInOrgan(
                            $meeting,
                            $memberRoles['Inactief Lid'],
                        );
                    } else {
                        $errors[] = new ErrorModel\MemberActiveAndInactiveInOrgan(
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

                        $errors[] = new ErrorModel\MemberInactiveInOrganButHasOtherRole(
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
                        $errors[] = new ErrorModel\MemberHasRoleButNotInOrgan(
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
     * Checks all Organ creation, and check if they are created at the correct Meeting
     * e.g. ALV-Commissies are only created at an ALV
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return ErrorModel[]
     */
    public function checkOrganFoundationMeetingType(MeetingModel $meeting): array
    {
        $errors = [];
        $organs = $this->organService->getOrgansCreatedAtMeeting($meeting);

        foreach ($organs as $organ) {
            $organType = $organ->getOrganType();
            $meetingType = $organ->getDecision()->getMeeting()->getType();

            // Chair's Meetings (VV) cannot be used to found an(y) organ. During General Members Meetings (ALV) only
            // specific organs can be founded, namely: AVC, AVW, KCC, Fraternity, and RvA. Furthermore, these organ can
            // only be founded in ALVs, not in any other meeting (except virtual meetings).
            //
            // However, this only holds after October 7, 2021, when the Internal Regulations of the association were
            // updated to reflect changes with respect to fraternities (before October 7, 2021, they could be founded
            // during a board meeting [BV]).
            if (
                MeetingTypes::VV === $meetingType
                || (
                    MeetingTypes::ALV === $meetingType
                    && (
                        OrganTypes::AVC !== $organType
                        && OrganTypes::AVW !== $organType
                        && OrganTypes::Fraternity !== $organType
                        && OrganTypes::KCC !== $organType
                        && OrganTypes::RvA !== $organType
                    )
                )
            ) {
                $errors[] = new ErrorModel\OrganMeetingType($organ);
                continue;
            }

            // Special case for the updates to the internal regulations. Skip fraternities when they were founded during
            // a BV before October 7, 2021.
            if (
                MeetingTypes::ALV === $meetingType
                || MeetingTypes::VIRT === $meetingType
                || (
                    OrganTypes::AVC !== $organType
                    && OrganTypes::AVW !== $organType
                    && OrganTypes::Fraternity !== $organType
                    && OrganTypes::KCC !== $organType
                    && OrganTypes::RvA !== $organType
                )
            ) {
                continue;
            }

            if (
                OrganTypes::Fraternity === $organType
                && MeetingTypes::BV === $meetingType
                && $organ->getDecision()->getMeeting()->getDate() <= new DateTime('2021-10-06')
            ) {
                continue;
            }

            $errors[] = new ErrorModel\OrganMeetingType($organ);
        }

        return $errors;
    }

    /**
     * Checks that key codes that have been granted do not expire too late. In accordance with the current Key Policy
     * this means that a key code may not be granted for a period longer than a year nor may it be granted for a period
     * that ends after September 1st of the next association year.
     *
     * @return ErrorModel[]
     */
    public function checkKeyGrantingDuration(MeetingModel $meeting): array
    {
        $errors = [];
        $grantings = $this->keyService->getKeysGrantedDuringMeeting($meeting);
        // With BV 1749.15.1 no more restrictions on max. one year.
        $maxOneYearCutOff = new DateTime('2025-07-01 midnight');

        // `$today` is when the meeting took place
        $today = $meeting->getDate();
        $todayNextYear = (clone $today)->add(new DateInterval('P1Y'));

        if ($today->format('m') >= 7) {
            $year = (int) $today->format('Y') + 1;
        } else {
            $year = (int) $today->format('Y');
        }

        $septemberFirstNextAssociationYear = clone $today;
        $septemberFirstNextAssociationYear->setDate($year, 9, 1);

        foreach ($grantings as $granting) {
            $until = $granting->getUntil();

            if ($until < $today) {
                $errors[] = new ErrorModel\KeyGrantedInThePast($granting);
            } else {
                if (
                    $today < $maxOneYearCutOff
                    && $until > $todayNextYear
                ) {
                    $errors[] = new ErrorModel\KeyGrantedLongerThanOneYear($granting);
                }

                if ($until > $septemberFirstNextAssociationYear) {
                    $errors[] = new ErrorModel\KeyGrantedPastBoundary($granting);
                }
            }
        }

        return $errors;
    }

    /**
     * @return ErrorModel[]
     */
    public function checkKeyWithdrawalTime(MeetingModel $meeting): array
    {
        $errors = [];
        $withdrawals = $this->keyService->getKeysWithdrawnDuringMeeting($meeting);

        foreach ($withdrawals as $withdrawal) {
            if ($withdrawal->getWithdrawnOn() <= $withdrawal->getGranting()->getUntil()) {
                continue;
            }

            $errors[] = new ErrorModel\KeyWithdrawnPastOriginalGranting($withdrawal);
        }

        return $errors;
    }

    public function tueDataObject(): TueData
    {
        return new TueData($this->config['checker']['membership_api']);
    }

    /**
     * Checks that "ordinary" members are still enrolled at the TU/e. If not, their membership should expire at the end
     * of the current association year. This does not actually update their membership type, as that is still valid for
     * the remainder of the current association year.
     */
    public function checkAtTUe(): void
    {
        $members = $this->memberService->getMembersToCheck();
        $user = $this->tueDataObject();

        // Determine the date of (potential) expiration of a member's membership outside the foreach to make sure we
        // only do it once.
        $exp = $this->getExpiration(new DateTime());

        // Check each member that needs to be checked.
        foreach ($members as $member) {
            echo 'Request for member ' . $member->getLidnr() . ':' . PHP_EOL;

            try {
                $user->setUser($member->getTueUsername());

                if (
                    0 !== $user->getStatus()
                    && 404 !== $user->getStatus()
                ) {
                    echo '--> Did not retrieve data, but no exception was thrown' . PHP_EOL;
                    continue;
                }

                echo '--> Successfully retrieved data' . PHP_EOL;

                if (
                    404 === $user->getStatus()
                    || !$user->studiesAtTue()
                ) {
                    echo '--> Member is no longer studying at the TU/e' . PHP_EOL;
                    // The member is no longer studying at the TU/e.
                    $member->setChangedOn(new DateTime());
                    $member->setIsStudying(false);
                    $member->setMembershipEndsOn($exp);
                } elseif (!$user->studiesAtDepartment()) {
                    echo '--> Member is still studying but not at the department of MCS' . PHP_EOL;
                    // The member does not study at WIN anymore, so we set the expiration date for the membership
                    $member->setChangedOn(new DateTime());
                    $member->setIsStudying(true);
                    $member->setMembershipEndsOn($exp);
                } else {
                    echo '--> Member is still studying at the department of MCS' . PHP_EOL;
                    // The member is still studying at MCS, so their membership does not end. If the member is currently
                    // external they will be converted to ordinary on July 1.
                    $member->setChangedOn(new DateTime());
                    $member->setIsStudying(true);
                    $member->setMembershipEndsOn(null);
                }

                // If we made it here, we have successfully checked the member
                $member->setLastCheckedOn(new DateTime());
            } catch (LookupException $e) {
                echo '--> Error occurred during lookup: ' . $e->getMessage() . PHP_EOL;
            }

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

        echo '' . count($members) . ' members have an upcoming ending and expiring membership' . PHP_EOL;
        $now = (new DateTime())->setTime(0, 0);
        $exp = $this->getExpiration($now);

        foreach ($members as $member) {
            echo 'Determining new membership type for ' . $member->getLidnr() . ' (ends on ' .
                $member->getMembershipEndsOn()->format('Y-m-d') . ' and expiring on ' .
                $member->getExpiration()->format('Y-m-d') . ')' . PHP_EOL;

            if ($member->getMembershipEndsOn() <= $now) {
                echo 'Membership has ended and expired' . PHP_EOL;

                if (array_key_exists($member->getLidnr(), $activeMembers)) {
                    echo 'Currently an active member, so becoming EXTERNAL. Extending membership to ' .
                        $exp->format('Y-m-d') . PHP_EOL;
                    $member->setType(MembershipTypes::External);

                    // External memberships should run till the end of the next association year (which is actually the
                    // same date as the expiration).
                    $member->setMembershipEndsOn($exp);
                } else {
                    echo 'Not an active member' . PHP_EOL;
                    // We only have to change the membership type for external or graduates depending on whether the
                    // member is still studying.
                    if (
                        $member->getIsStudying()
                        && $member->getLastCheckedOn() >= (new DateTime())->sub(new DateInterval('P1Y'))
                    ) {
                        echo 'But is studying, so becoming EXTERNAL. Extending membership to ' .
                            $exp->format('Y-m-d') . PHP_EOL;
                        $member->setType(MembershipTypes::External);

                        // External memberships should run till the end of the next association year (which is actually
                        // the same date as the expiration).
                        $member->setMembershipEndsOn($exp);
                    } else {
                        echo 'Nor studying, so becoming GRADUATE. Not extending membership' . PHP_EOL;
                        $member->setType(MembershipTypes::Graduate);
                        $member->setIsStudying(false);
                    }
                }

                echo 'Extending expiration to ' . $exp->format('Y-m-d') . PHP_EOL;

                $member->setChangedOn(new DateTime());
                $member->setExpiration($exp);

                $this->memberService->getMemberMapper()->persist($member);
            } else {
                echo 'Membership has not yet ended and expired, so changing nothing' . PHP_EOL;
            }
        }
    }

    /**
     * Make sure that ordinary members have their membership expiration automatically extended if they are eligible.
     */
    public function checkNormalExpiration(): void
    {
        $members = $this->memberService->getExpiringMembershipsWithNormalTypes();

        echo '' . count($members) . ' members have an upcoming expiring membership' . PHP_EOL;

        // Determine the next expiration date (always the end of the next association year).
        $now = (new DateTime())->setTime(0, 0);
        $exp = $this->getExpiration($now);

        foreach ($members as $member) {
            echo 'Determining new expiration for ' . $member->getLidnr() . ' (expiring on ' .
                $member->getExpiration()->format('Y-m-d') . ')' . PHP_EOL;

            if ($member->getExpiration() <= $now) {
                echo 'Expired, thus extending to ' . $exp->format('Y-m-d') . PHP_EOL;

                $member->setChangedOn(new DateTime());
                $member->setType(MembershipTypes::Ordinary);
                $member->setExpiration($exp);

                $this->memberService->getMemberMapper()->persist($member);
            } else {
                echo 'Not yet expired, so not extending' . PHP_EOL;
            }
        }
    }

    /**
     * Make sure that members who are hidden or whose membership has expired do not have an authentication key.
     */
    public function checkAuthenticationKeys(): void
    {
        $members = $this->memberService->getExpiredOrHiddenMembersWithAuthenticationKey();

        echo '' . count($members) . ' members incorrectly have an authentication key' . PHP_EOL;

        foreach ($members as $member) {
            $member->setAuthenticationKey(null);
            $this->memberService->getMemberMapper()->persist($member);
        }
    }

    /**
     * Determine the next expiration date (always the end of the next association year).
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
