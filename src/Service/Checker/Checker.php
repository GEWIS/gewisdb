<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Application\AssociationYear;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision as SubDecisionModel;
use App\Service\Checker\Annulment as AnnulmentService;
use App\Service\Checker\Installation as InstallationService;
use App\Service\Checker\Key as KeyService;
use App\Service\Checker\Meeting as MeetingService;
use App\Service\Checker\Member as MemberService;
use App\Service\Checker\Organ as OrganService;
use App\ViewModel\Checker\Error as ErrorModel;
use DateInterval;
use DateTime;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use function array_merge;
use function count;
use function in_array;

class Checker
{
    public function __construct(
        private readonly AnnulmentService $annulmentService,
        private readonly InstallationService $installationService,
        private readonly KeyService $keyService,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly OrganService $organService,
        private readonly MailerInterface $mailer,
        private readonly string $mailFromAddress,
        private readonly string $mailFromName,
        private readonly string $mailToCheckerResultAddress,
        private readonly string $mailToCheckerResultName,
    ) {
    }

    /**
     * Does a full check on each meeting, checking that after each meeting no database violation occurs
     */
    public function check(): void
    {
        $meetings = $this->meetingService->getAllMeetings();

        $reports = [];
        foreach ($meetings as $meeting) {
            $errors = array_merge(
                $this->checkMembersHaveRolesButInactiveOrNotInOrgan($meeting),
                $this->checkMembersInNonExistingOrgans($meeting),
                $this->checkMembersExpiredButStillInOrgan($meeting),
                $this->checkOrganFoundationMeetingType($meeting),
                $this->checkKeyGrantingDuration($meeting),
                $this->checkKeyWithdrawalTime($meeting),
                $this->checkOrganComposition($meeting),
                $this->checkAnnulments($meeting),
            );

            $reports[] = [
                'meeting' => $meeting,
                'errors' => $errors,
            ];
        }

        $this->sendMail($reports);
    }

    /**
     * Does a full check on the last meeting (and all previous meetings) to determine if there are members who are
     * currently installed in an organ that was abrogated (i.e. they were never discharged).
     */
    public function checkDischarges(): void
    {
        $meeting = $this->meetingService->getLastMeeting();

        $this->sendMail([
            [
                'meeting' => $meeting,
                'errors' => $this->checkMembersInNonExistingOrgans($meeting),
            ],
        ]);
    }

    /**
     * Send a mail with the detected errors to the secretary
     *
     * @param list<array{meeting: MeetingModel, errors: ErrorModel<SubDecisionModel>[]}> $reports
     */
    private function sendMail(array $reports): void
    {
        $message = new TemplatedEmail()
            ->to(new Address($this->mailToCheckerResultAddress, $this->mailToCheckerResultName))
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->subject('Database Checker Report')
            ->textTemplate('checker/report.txt.twig')
            ->context(['reports' => $reports]);

        $this->mailer->send($message);
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
            $installationToOrganFoundation = $installation->getFoundation()->getHash();

            if (
                in_array(
                    $installationToOrganFoundation,
                    $organs,
                    true,
                )
            ) {
                continue;
            }

            $errors[] = new ErrorModel\MemberInNonExistingOrgan(
                $meeting,
                $installation,
            );
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

            $errors[] = new ErrorModel\MemberExpiredButStillInOrgan(
                $meeting,
                $installation,
            );
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

        // The roles are keyed by the enum's value, so match on that rather than on the Dutch string it happens to be.
        $active = InstallationFunctions::Member->value;
        $inactive = InstallationFunctions::InactiveMember->value;

        foreach ($organs as $organMembers) {
            foreach ($organMembers as $memberRoles) {
                if (
                    isset($memberRoles[$active])
                    && isset($memberRoles[$inactive])
                ) {
                    // Member is active AND inactive in the same organ.
                    if (count($memberRoles) > 2) {
                        $errors[] = new ErrorModel\MemberActiveWithRoleAndInactiveInOrgan(
                            $meeting,
                            $memberRoles[$inactive],
                        );
                    } else {
                        $errors[] = new ErrorModel\MemberActiveAndInactiveInOrgan(
                            $meeting,
                            $memberRoles[$active],
                        );
                    }
                } elseif (
                    !isset($memberRoles[$active])
                    && isset($memberRoles[$inactive])
                    && count($memberRoles) > 1
                ) {
                    // Member is inactive but still has roles.
                    foreach ($memberRoles as $role => $installation) {
                        if ($inactive === $role) {
                            continue;
                        }

                        $errors[] = new ErrorModel\MemberInactiveInOrganButHasOtherRole(
                            $meeting,
                            $installation,
                            $role,
                        );
                    }
                } elseif (
                    !isset($memberRoles[$active])
                    && !isset($memberRoles[$inactive])
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

        $septemberFirstNextAssociationYear = AssociationYear::fromDate($today)->septemberFirst();

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

    /**
     * Checks that the organs that exist are made up the way the Articles of Association and the Internal Regulations
     * require, i.e. that they have a chair, enough members, and no inactive members where the type does not have those.
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return ErrorModel[]
     */
    public function checkOrganComposition(MeetingModel $meeting): array
    {
        $errors = [];
        $organs = $this->organService->getAllOrganFoundations($meeting);
        $installations = [];

        foreach ($this->installationService->getAllInstallations($meeting) as $installation) {
            $installations[$installation->getFoundation()->getHash()][] = $installation;
        }

        foreach ($organs as $hash => $organ) {
            $type = $organ->getOrganType();
            $members = [];
            $chairs = [];

            foreach ($installations[$hash] ?? [] as $installation) {
                $function = $installation->getFunction();
                $lidnr = $installation->getMember()->getLidnr();

                if (InstallationFunctions::Member === $function) {
                    $members[$lidnr] = $lidnr;
                }

                if (InstallationFunctions::Chair === $function) {
                    $chairs[$lidnr] = $lidnr;
                }

                if (
                    InstallationFunctions::InactiveMember !== $function
                    || $type->allowsInactiveMembers()
                ) {
                    continue;
                }

                $errors[] = new ErrorModel\MemberInactiveInOrganWithoutInactiveMembers(
                    $meeting,
                    $installation,
                );
            }

            if (count($members) < $type->getMinimumMembers()) {
                $errors[] = new ErrorModel\OrganTooSmall(
                    $meeting,
                    $organ,
                    count($members),
                );
            } elseif (
                $type->requiresChair()
                && [] === $chairs
            ) {
                // Only worth saying once an organ actually has people in it; an empty one is already reported as
                // being too small.
                $errors[] = new ErrorModel\OrganWithoutChair(
                    $meeting,
                    $organ,
                );
            }
        }

        return $errors;
    }

    /**
     * Checks that the annulments made during a meeting could have been made at all.
     *
     * These are turned down when a decision is entered, so anything found here predates that or was put in by hand.
     *
     * @param MeetingModel $meeting After which meeting do we do the validation
     *
     * @return ErrorModel[]
     */
    public function checkAnnulments(MeetingModel $meeting): array
    {
        $errors = [];

        foreach ($this->annulmentService->getAnnulmentsAtMeeting($meeting) as $annulment) {
            if ($this->annulmentService->annulsAnAnnulment($annulment)) {
                $errors[] = new ErrorModel\AnnulmentOfAnnulment(
                    $meeting,
                    $annulment,
                );
            }

            if ($this->annulmentService->annulsALaterDecision($annulment)) {
                $errors[] = new ErrorModel\AnnulmentOfLaterDecision(
                    $meeting,
                    $annulment,
                );
            }

            if ([] === $this->annulmentService->getEarlierAnnulments($annulment)) {
                continue;
            }

            $errors[] = new ErrorModel\DecisionAnnulledMoreThanOnce(
                $meeting,
                $annulment,
            );
        }

        return $errors;
    }

    /**
     * Make sure that members who are hidden or whose membership has expired do not have an authentication key.
     *
     * @return int the number of members whose key was revoked
     */
    public function checkAuthenticationKeys(): int
    {
        $members = $this->memberService->getExpiredOrHiddenMembersWithAuthenticationKey();

        foreach ($members as $member) {
            $member->setAuthenticationKey(null);
        }

        $this->memberService->persistAll($members);

        return count($members);
    }
}
