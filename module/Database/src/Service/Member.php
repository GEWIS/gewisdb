<?php

declare(strict_types=1);

namespace Database\Service;

use Application\Model\Enums\AddressTypes;
use Application\Model\Enums\MembershipTypes;
use Application\Service\FileStorage as FileStorageService;
use Checker\Service\Checker as CheckerService;
use Checker\Service\Renewal as RenewalService;
use Database\Form\Address as AddressForm;
use Database\Form\AuditEntry\AuditNote as AuditNoteForm;
use Database\Form\BulkMemberRenewal as BulkMemberRenewalForm;
use Database\Form\DeleteAddress as DeleteAddressForm;
use Database\Form\Member as MemberForm;
use Database\Form\MemberApprove as MemberApproveForm;
use Database\Form\MemberEdit as MemberEditForm;
use Database\Form\MemberExpiration as MemberExpirationForm;
use Database\Form\MemberLists as MemberListsForm;
use Database\Form\MemberRenewal as MemberRenewalForm;
use Database\Form\MemberType as MemberTypeForm;
use Database\Mapper\ActionLink as ActionLinkMapper;
use Database\Mapper\Audit as AuditMapper;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\MemberUpdate as MemberUpdateMapper;
use Database\Mapper\ProspectiveMember as ProspectiveMemberMapper;
use Database\Model\Address as AddressModel;
use Database\Model\AuditEntry as AuditEntryModel;
use Database\Model\AuditMailingListMembership;
use Database\Model\AuditNote as AuditNoteModel;
use Database\Model\AuditRenewal as AuditRenewalModel;
use Database\Model\Enums\AttentionReasons;
use Database\Model\Enums\MailingListMemberAction;
use Database\Model\Enums\MailingListMemberOrigin;
use Database\Model\Enums\Studies;
use Database\Model\MailingList as MailingListModel;
use Database\Model\MailingListMember as MailingListMemberModel;
use Database\Model\Member as MemberModel;
use Database\Model\Membership as MembershipModel;
use Database\Model\MemberUpdate as MemberUpdateModel;
use Database\Model\PaymentLink;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Database\Model\RenewalLink as RenewalLinkModel;
use Database\Service\MailingList as MailingListService;
use DateTime;
use InvalidArgumentException;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use ReflectionClass;
use RuntimeException;
use User\Service\UserService;

use function array_diff;
use function array_intersect;
use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
use function assert;
use function bin2hex;
use function count;
use function date;
use function in_array;
use function mb_encode_mimeheader;
use function random_bytes;
use function usort;

class Member
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly Translator $translator,
        private readonly AddressForm $addressForm,
        private readonly AuditNoteForm $auditNoteForm,
        private readonly BulkMemberRenewalForm $bulkMemberRenewalForm,
        private readonly DeleteAddressForm $deleteAddressForm,
        private readonly MemberApproveForm $memberApproveForm,
        private readonly MemberForm $memberForm,
        private readonly MemberEditForm $memberEditForm,
        private readonly MemberExpirationForm $memberExpirationForm,
        private readonly MemberRenewalForm $memberRenewalForm,
        private readonly MemberTypeForm $memberTypeForm,
        private readonly MailingListMapper $mailingListMapper,
        private readonly MailingListMemberMapper $mailingListMemberMapper,
        private readonly ActionLinkMapper $actionLinkMapper,
        private readonly AuditMapper $auditMapper,
        private readonly MemberMapper $memberMapper,
        private readonly MemberUpdateMapper $memberUpdateMapper,
        private readonly ProspectiveMemberMapper $prospectiveMemberMapper,
        private readonly CheckerService $checkerService,
        private readonly FileStorageService $fileStorageService,
        private readonly MailingListService $mailingListService,
        private readonly RenewalService $renewalService,
        private readonly UserService $userService,
        private readonly PhpRenderer $viewRenderer,
        private readonly TransportInterface $mailTransport,
        private readonly Audit $auditService,
        private readonly array $config,
    ) {
    }

    /**
     * Subscribe a member.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function subscribe(array $data): ?ProspectiveMemberModel
    {
        $form = $this->getMemberForm();
        $form->bind(new ProspectiveMemberModel());
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // set some extra data
        /** @var ProspectiveMemberModel $prospectiveMember */
        $prospectiveMember = $form->getData();

        // find if there is an earlier member with the same email or name
        if (
            $this->getMemberMapper()->hasMemberWith($prospectiveMember->getEmail())
            || $this->getProspectiveMemberMapper()->hasMemberWith($prospectiveMember->getEmail())
        ) {
            $form->get('email')->setMessages(['There already is a member with this email address.']);

            return null;
        }

        // changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $prospectiveMember->setChangedOn($date);

        // store the address
        $address = $form->get('address')->getObject();
        $prospectiveMember->setAddress($address);

        // check mailing lists
        foreach ($form->getLists() as $list) {
            if (!$form->get('list-' . $list->getName())->isChecked()) {
                continue;
            }

            $prospectiveMember->addList($list->getName());
        }

        // subscribe to default mailing lists not on the form
        $mailingMapper = $this->mailingListMapper;
        foreach ($mailingMapper->findDefault() as $list) {
            $prospectiveMember->addList($list->getName());
        }

        $this->getProspectiveMemberMapper()->persist($prospectiveMember);

        // Create a payment link for the prospective member in the event that the checkout did not succeed.
        $paymentLink = new PaymentLink();
        $paymentLink->setProspectiveMember($prospectiveMember);
        $prospectiveMember->setPaymentLink($paymentLink);
        $this->getActionLinkMapper()->persist($paymentLink);

        return $prospectiveMember;
    }

    /**
     * Send an e-mail to the (prospective) member and the secretary with an update on the (prospective) member's
     * registration.
     *
     * @psalm-param "registration"|"welcome"|"checkout-expired"|"checkout-failed"|"refund-created" $type
     */
    public function sendRegistrationUpdateEmail(
        MemberModel|ProspectiveMemberModel $member,
        string $type,
    ): void {
        if (!in_array($type, ['registration', 'welcome', 'checkout-expired', 'checkout-failed', 'refund-created'])) {
            throw new InvalidArgumentException('Unknown email type for prospective member.');
        }

        switch ($type) {
            case 'registration':
                $template = 'database/email/member-registration';
                $subjectProspectiveMember = 'GEWIS registration';
                $subjectSecretary = 'New member registration: ' . $member->getFullName();

                break;
            case 'welcome':
                $template = 'database/email/member-welcome';
                $subjectProspectiveMember = 'Your GEWIS membership has been confirmed';
                $subjectSecretary = 'Membership confirmed: ' . $member->getFullName();

                break;
            case 'checkout-expired':
                $template = 'database/email/checkout-expired';
                $subjectProspectiveMember = 'Complete your GEWIS registration';
                $subjectSecretary = 'Membership payment expired: ' . $member->getFullName();

                break;
            case 'checkout-failed':
                $template = 'database/email/checkout-failed';
                $subjectProspectiveMember = 'Your GEWIS membership fee payment has failed';
                $subjectSecretary = 'Membership payment failed: ' . $member->getFullName();

                break;
            case 'refund-created':
                $template = 'database/email/refund-created';
                $subjectProspectiveMember = 'Your GEWIS membership fee is being refunded';
                $subjectSecretary = 'Membership payment refund started: ' . $member->getFullName();

                break;
        }

        $config = $this->config;
        $config = $config['email'];

        $renderer = $this->getRenderer();
        $model = new ViewModel(['member' => $member]);
        $model->setTemplate($template);
        $body = $renderer->render($model);

        $html = new MimePart($body);
        $html->type = 'text/html';

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        // Always try to send the e-mail to the prospective member before sending to the secretary. The secretary can
        // look in the database, the prospective member cannot.
        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setBody($mimeMessage);
        $message->setFrom($config['from']['address'], $config['from']['name']);
        $message->setTo(
            $member->getEmail(),
            mb_encode_mimeheader(
                $member->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setReplyTo($config['to']['subscription']['address'], $config['to']['subscription']['name']);
        $message->setSubject($subjectProspectiveMember);
        $this->getMailTransport()->send($message);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setBody($mimeMessage);
        $message->setFrom($config['from']['address'], $config['from']['name']);
        $message->setTo($config['to']['subscription']['address'], $config['to']['subscription']['name']);
        $message->setSubject($subjectSecretary);
        $this->getMailTransport()->send($message);
    }

    public function sendRefundProblemEmail(
        string $refundId,
        string $refundStatus,
    ): void {
        $config = $this->config;
        $config = $config['email'];

        $renderer = $this->getRenderer();
        $model = new ViewModel([
            'refundId' => $refundId,
            'refundStatus' => $refundStatus,
        ]);
        $model->setTemplate('database/email/refund-problem');
        $body = $renderer->render($model);

        $html = new MimePart($body);
        $html->type = 'text/html';

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setBody($mimeMessage);
        $message->setFrom($config['from']['address'], $config['from']['name']);
        $message->setTo($config['to']['subscription']['address'], $config['to']['subscription']['name']);
        $message->setSubject('Problem while processing membership refund');
        $this->getMailTransport()->send($message);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function finalizeSubscription(
        array $membershipData,
        ProspectiveMemberModel $prospectiveMember,
    ): ?MemberModel {
        // If no membership type has been submitted it does not make sense to do anything else.
        if (!isset($membershipData['type'])) {
            return null;
        }

        $form = $this->getMemberForm();
        $form->bind(new MemberModel());

        // Fill in the address in the form again
        $data = $prospectiveMember->toArray();

        if ($this->getMemberMapper()->hasMemberWith($prospectiveMember->getEmail())) {
            // phpcs:ignore -- user-visible strings should not be split
            throw new RuntimeException('You cannot approve this member. A member with this email address already exists. Make sure this is not an error in the database. Disapproving will refund the member, so make sure they paid twice before refunding.');
        }

        // add list data to the form
        foreach ($form->getLists() as $list) {
            $result = '0';
            foreach ($prospectiveMember->getLists() as $l) {
                if ($list->getName() !== $l) {
                    continue;
                }

                $result = '1';
            }

            $data['list-' . $list->getName()] = $result;
        }

        unset($data['lidnr']);

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var MemberModel $member */
        $member = $form->getData();

        // Copy all remaining information
        $member->setTueUsername($prospectiveMember->getTueUsername());

        // changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        // creating the first membership for the member
        // sensible defaults are set in the creation
        $membershipType = MembershipTypes::from($membershipData['type']);
        $membership = new MembershipModel(
            member: $member,
            type: $membershipType,
            startDate: null,
            endDate: null,
        );
        $member->addMembership($membership);

        // add address
        $member->addAddresses($prospectiveMember->getAddresses());

        foreach ($form->getLists() as $list) {
            if (!$form->get('list-' . $list->getName())->isChecked()) {
                continue;
            }

            // Ignore Mailman/listmonk sync lock here as we _always_ need to persist this information.
            // Will be cascade persisted through `$member`.
            $mailingListMember = new MailingListMemberModel();
            $mailingListMember->setMailingList($list);
            $mailingListMember->setMember($member);
            // Force cascade by adding to member.
            $member->addList($mailingListMember);
        }

        // subscribe to default mailing lists not on the form
        foreach ($this->mailingListMapper->findDefault() as $list) {
            // Ignore Mailman/listmonk sync lock here as we _always_ need to persist this information.
            // Will be cascade persisted through `$member`.
            $mailingListMember = new MailingListMemberModel();
            $mailingListMember->setMailingList($list);
            $mailingListMember->setMember($member);
            // Force cascade by adding to member.
            $member->addList($mailingListMember);
        }

        // Add authentication key to allow external updates.
        $member->setAuthenticationKey($this->generateAuthenticationKey());

        // Set paid automatically.
        $membership->setPaid(20);

        // Remove prospectiveMember model
        $this->getMemberMapper()->persist($member);

        $this->removeProspective($prospectiveMember);

        return $member;
    }

    /**
     * Get member info.
     */
    public function getMember(int $id): ?MemberModel
    {
        return $this->getMemberMapper()->findSimple($id);
    }

    /**
     * Get a member including decision information if that exists. This can therefor return `null` even though the
     * member exists.
     */
    public function getMemberWithDecisions(int $id): ?MemberModel
    {
        return $this->getMemberMapper()->find($id);
    }

    /**
     * Get prospective member info
     *
     * @return array{
     *     member: ?ProspectiveMemberModel,
     *     form: ?MemberApproveForm,
     *     canDelete: ?bool,
     *     approveMessages: ?array<array-key, string[]>,
     * }
     */
    public function getProspectiveMember(int $id): array
    {
        $member = $this->getProspectiveMemberMapper()->find($id);

        if (null === $member) {
            return [
                'member' => null,
                'form' => null,
                'canDelete' => null,
                'approveMessages' => null,
            ];
        }

        $approveMessages = [];

        // During the remainder of 2026, show a warning.
        if (2026 >= date('Y')) {
            $approveMessages[] = [
                'info',
                // phpcs:ignore -- user-visible strings should not be split
                '<b>Warning:</b> TU/e data is no longer automatically being checked as of 2026. Suggestions are based on member-inputted information.',
            ];
        }

        if ($member->getStudy()->isMcsStudy()) {
            $approveMessages[] = [
                'success',
                // phpcs:ignore -- user-visible strings should not be split
                '<b>Info:</b> Member studies at department. Recommended membership type: <strong>Gewoon lid</strong>.',
            ];
        } elseif ($member->getStudy()->isEngDPhD()) {
            $approveMessages[] = [
                'warning',
                // phpcs:ignore -- user-visible strings should not be split
                '<b>Warning:</b> Member is EngD/PhD candidate. Recommended membership type: <strong>Extern lid</strong>.',
            ];
        } else {
            $approveMessages[] = [
                'danger',
                // phpcs:ignore -- user-visible strings should not be split
                '<b>Warning:</b> Member does not study at department, manual check needed.',
            ];
        }

        return [
            'member' => $member,
            'form' => $member->canBeApproved() ? $this->memberApproveForm : null,
            'canDelete' => $member->canBeDeleted(),
            'approveMessages' => $approveMessages,
        ];
    }

    /**
     * Toggle if a member receives the supremum.
     */
    public function setSupremum(
        MemberModel $member,
        string $value,
    ): void {
        $member->setSupremum($value);

        $this->getMemberMapper()->persist($member);
    }

    /**
     * Search for a member.
     *
     * @return MemberModel[]
     */
    public function search(string $query): array
    {
        return $this->getMemberMapper()->search($query);
    }

    /**
     * Search for a member that is not deleted, expired, and hidden.
     *
     * @return MemberModel[]
     */
    public function searchFiltered(string $query): array
    {
        return $this->getMemberMapper()->search($query, true);
    }

    /**
     * Search for a prospective member.
     *
     * @return ProspectiveMemberModel[]
     */
    public function searchProspective(
        string $query,
        string $type,
    ): array {
        return $this->getProspectiveMemberMapper()->search($query, $type);
    }

    /**
     * Check if we can easily remove a member.
     */
    public function canRemove(MemberModel $member): bool
    {
        return $this->getMemberMapper()->canRemove($member);
    }

    /**
     * Remove a member.
     */
    public function remove(MemberModel $member): void
    {
        foreach ($member->getMailingListMemberships() as $mailingListMembership) {
            $mailingListMembership->setToBeDeleted(true);
            $mailingListMembership->unsetMember();
            $this->mailingListMemberMapper->persist($mailingListMembership);
        }

        if ($this->canRemove($member)) {
            $this->getMemberMapper()->remove($member);
        } else {
            $this->clear($member);
        }
    }

    /**
     * Remove all members that are expired on or before some date.
     */
    public function removeExpiredMembers(DateTime $expiration): void
    {
        $members = $this->getMemberMapper()->findExpired($expiration);

        foreach ($members as $member) {
            $this->remove($member);
        }
    }

    /**
     * Remove a prospective member.
     */
    public function removeProspective(ProspectiveMemberModel $member): void
    {
        $this->getProspectiveMemberMapper()->remove($member);
    }

    /**
     * Remove all prospective members whose last Checkout Session has fully expired (1 + 30 + 1 day ago) or failed 31
     * days ago or who don't have a checkout session.
     */
    public function removeExpiredProspectiveMembers(): void
    {
        $prospectiveMembers = array_merge(
            $this->getProspectiveMemberMapper()->findWithFullyExpiredOrFailedCheckout(),
            $this->getProspectiveMemberMapper()->findWithoutCheckout(),
        );

        foreach ($prospectiveMembers as $prospectiveMember) {
            $this->removeProspective($prospectiveMember);
        }
    }

    /**
     * Clear a member.
     */
    public function clear(MemberModel $member): void
    {
        foreach ($member->getAddresses() as $address) {
            $this->getMemberMapper()->removeAddress($address);
        }

        foreach ($member->getAuditEntries() as $auditEntry) {
            $this->getAuditMapper()->remove($auditEntry);
        }

        $date = new DateTime('0001-01-01 00:00:00');

        $member->setEmail(null);
        $member->setTueUsername(null);
        $member->setStudy(Studies::Unknown);
        $member->setLastCheckedOn(null);
        $member->setChangedOn(new DateTime());
        $member->setBirth($date);
        $member->setSupremum('optout');
        $member->setHidden(true);
        $member->setDeleted(true);
        $member->unsetMemberships();
        $this->unsubscribeLists($member, false);

        $this->getMemberMapper()->persist($member);
    }

    /**
     * Edit a member.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function edit(
        MemberModel $member,
        array $data,
    ): ?MemberModel {
        $form = $this->getMemberEditForm($member)['form'];
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // update changed on date
        $date = new DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Edit membership by secretary.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function membership(
        MemberModel $member,
        array $data,
    ): ?MemberModel {
        $form = $this->getMemberTypeForm($member);

        // It is not possible to have another membership type after being an honorary member and there does not exist a
        // good transition to a different membership type (because of the dates/expiration etc.).
        if (MembershipTypes::Honorary === $member->getCurrentOrLastMembership()->getType()) {
            throw new RuntimeException('Unable to change membership type of honorary member.');
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        return $this->applyMembershipChange(
            $member,
            MembershipTypes::from($data['type']),
            null === $data['changeDate'] ? null : new DateTime($data['changeDate']),
        );
    }

    /**
     * @return array<string, string>
     */
    public function getBulkRenewalTypeOptions(): array
    {
        $options = [];

        foreach (MembershipTypes::cases() as $membershipType) {
            $options[$membershipType->value] = $membershipType->getName($this->translator);
        }

        return $options;
    }

    /**
     * @return array{
     *     memberIds: int[],
     *     membershipType: ?MembershipTypes,
     *     rows: array<int, array{
     *         memberId: int,
     *         member: ?MemberModel,
     *         currentExpiration: ?string,
     *         newExpiration: ?string,
     *         valid: bool,
     *         message: string,
     *         executed: false,
     *     }>,
     *     validCount: int,
     *     invalidCount: int,
     * }
     */
    public function buildBulkRenewalPreview(
        string $memberIds,
        string $membershipType,
    ): array {
        $form = $this->getBulkMemberRenewalForm();
        $form->setData([
            'memberIds' => $memberIds,
            'membershipType' => $membershipType,
        ]);
        $form->isValid();

        $rows = [];
        $validCount = 0;
        $invalidCount = 0;
        $selectedType = MembershipTypes::tryFrom($membershipType);

        $now = new DateTime();

        $memberIds = $form->getParsedMemberIds();

        if (null !== $selectedType) {
            foreach ($memberIds as $memberId) {
                $member = $this->getMember($memberId);

                if (null === $member) {
                    $rows[] = [
                        'memberId' => $memberId,
                        'member' => null,
                        'currentExpiration' => null,
                        'newExpiration' => null,
                        'valid' => false,
                        'message' => $this->translator->translate('Member not found.'),
                        'executed' => false,
                    ];
                    $invalidCount++;

                    continue;
                }

                if ($member->getDeleted()) {
                    $rows[] = [
                        'memberId' => $memberId,
                        'member' => $member,
                        'currentExpiration' => $member->getExpiration()->format('Y-m-d'),
                        'newExpiration' => null,
                        'valid' => false,
                        'message' => $this->translator->translate('Member is deleted.'),
                        'executed' => false,
                    ];
                    $invalidCount++;

                    continue;
                }

                // We allow renewing memberships that have not started yet in resolveMembershipChange
                // but we don't allow renewal in bulk
                if ($member->getLastMembership()->getStartDate() > $now) {
                    $rows[] = [
                        'memberId' => $memberId,
                        'member' => $member,
                        'currentExpiration' => $member->getExpiration()->format('Y-m-d'),
                        'newExpiration' => null,
                        'valid' => false,
                        'message' => $this->translator->translate('Member already has a future membership.'),
                        'executed' => false,
                    ];
                    $invalidCount++;

                    continue;
                }

                try {
                    $lastMembership = $member->getLastMembership();
                    assert($lastMembership instanceof MembershipModel);
                    $resolvedChange = $this->resolveMembershipChange(
                        $member,
                        $selectedType,
                        clone $lastMembership->getEndDate(),
                    );

                    $rows[] = [
                        'memberId' => $memberId,
                        'member' => $member,
                        'currentExpiration' => $resolvedChange['oldExpiration']->format('Y-m-d'),
                        'newExpiration' => $resolvedChange['newExpiration']->format('Y-m-d'),
                        'valid' => true,
                        'message' => $this->translator->translate('Ready to renew.'),
                        'executed' => false,
                    ];
                    $validCount++;
                } catch (RuntimeException $exception) {
                    $rows[] = [
                        'memberId' => $memberId,
                        'member' => $member,
                        'currentExpiration' => $member->getExpiration()->format('Y-m-d'),
                        'newExpiration' => null,
                        'valid' => false,
                        'message' => $exception->getMessage(),
                        'executed' => false,
                    ];
                    $invalidCount++;
                }
            }
        }

        return [
            'memberIds' => $memberIds,
            'membershipType' => $selectedType,
            'rows' => $rows,
            'validCount' => $validCount,
            'invalidCount' => $invalidCount,
        ];
    }

    /**
     * @return array{
     *     preview: array{
     *         memberIds: int[],
     *         membershipType: ?MembershipTypes,
     *         rows: array<int, array{
     *             memberId: int,
     *             member: ?MemberModel,
     *             currentExpiration: ?string,
     *             newExpiration: ?string,
     *             valid: bool,
     *             message: string,
     *             executed: bool,
     *         }>,
     *         validCount: int,
     *         invalidCount: int,
     *     },
     *     executedCount: int,
     * }
     */
    public function executeBulkRenewal(
        string $memberIds,
        string $membershipType,
    ): array {
        $preview = $this->buildBulkRenewalPreview($memberIds, $membershipType);
        $selectedType = $preview['membershipType'];
        $executedCount = 0;

        if (null === $selectedType) {
            return [
                'preview' => $preview,
                'executedCount' => $executedCount,
            ];
        }

        foreach ($preview['rows'] as $index => $row) {
            if (!$row['valid'] || null === $row['member']) {
                continue;
            }

            if (null === $row['member'] || $row['member']->getDeleted()) {
                $preview['rows'][$index]['valid'] = false;
                $preview['rows'][$index]['message'] = $this->translator->translate(
                    'Unable to renew deleted member.',
                );

                continue;
            }

            try {
                $lastMembership = $row['member']->getLastMembership();
                assert($lastMembership instanceof MembershipModel);

                $row['member'] = $this->applyMembershipChange(
                    $row['member'],
                    $selectedType,
                    clone $lastMembership->getEndDate(),
                );

                $preview['rows'][$index]['executed'] = true;
                $preview['rows'][$index]['message'] = $this->translator->translate('Renewed.');
                $preview['rows'][$index]['member'] = $row['member'];
                $executedCount++;
            } catch (RuntimeException $exception) {
                $preview['rows'][$index]['valid'] = false;
                $preview['rows'][$index]['message'] = $exception->getMessage();
            }
        }

        return [
            'preview' => $preview,
            'executedCount' => $executedCount,
        ];
    }

    /**
     * Extend the duration of the membership.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function expiration(
        MemberModel $member,
        array $data,
    ): ?MemberModel {
        $form = $this->getMemberExpirationForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $newMembership = new MembershipModel(
            member: $member,
            type: $member->getCurrentOrLastMembership()->getType(),
            startDate: $member->getCurrentOrLastMembership()->getEndDate(),
            endDate: null,
        );
        $member->addMembership($newMembership);

        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Edit address.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function editAddress(
        MemberModel $member,
        AddressTypes $type,
        array $data,
    ): ?AddressModel {
        $form = $this->getAddressForm($member, $type);
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var AddressModel $address */
        $address = $form->getData();

        $this->getMemberMapper()->persistAddress($address);

        return $address;
    }

    /**
     * Add address.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function addAddress(
        MemberModel $member,
        AddressTypes $type,
        array $data,
    ): ?AddressModel {
        $form = $this->getAddressForm($member, $type, true);
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var AddressModel $address */
        $address = $form->getData();

        $this->getMemberMapper()->persistAddress($address);

        return $address;
    }

    /**
     * Remove address.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function removeAddress(
        MemberModel $member,
        AddressTypes $type,
        array $data,
    ): ?MemberModel {
        $form = $this->getDeleteAddressForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $address = $this->getMemberMapper()->findMemberAddress($member, $type);
        $this->getMemberMapper()->removeAddress($address);

        return $member;
    }

    /**
     * Update mailing list subscriptions of a member
     */
    public function subscribeLists(
        MemberModel $member,
        MemberListsForm $form,
    ): ?MemberModel {
        // Check if we are performing a sync or not.
        if ($this->mailingListService->isSyncLocked()) {
            return null;
        }

        $data = $form->getData();

        /** @var string[] $selectedLists */
        $selectedLists = $data['lists'] ?: [];
        $currentLists = $member->getMailingListMemberships()->map(
            static function (MailingListMemberModel $subscription) {
                return $subscription->getMailingList()->getName();
            },
        )->toArray();

        // Determine which mailing lists the member should be (un)subscribed from/to.
        $intersection = array_intersect($selectedLists, $currentLists);
        $toRemove = array_diff($currentLists, $selectedLists);
        $toAdd = array_diff($selectedLists, $intersection);

        // If a member unsubscribes, we set the to be deleted status of that entry
        // This will later be processed and then this entry will be deleted
        foreach ($toRemove as $list) {
            $list = $this->mailingListMapper->find($list);

            if (null === $list) {
                continue;
            }

            $membership = $this->mailingListMemberMapper->findByListAndMember($list, $member);
            $membership->setToBeDeleted(true);

            $this->auditService->persist(
                AuditMailingListMembership::create(
                    MailingListMemberAction::Remove,
                    MailingListMemberOrigin::Manual,
                    $member,
                    $list,
                    $membership->getEmail(),
                    $this->userService->getIdentity(),
                ),
            );
        }

        // Mailing lists to add
        foreach ($toAdd as $list) {
            $list = $this->mailingListMapper->find($list);

            if (null === $list) {
                continue;
            }

            $mailingListMember = new MailingListMemberModel();
            $mailingListMember->setMailingList($list);
            $mailingListMember->setMember($member);
            // Force cascade by adding to member.
            $member->addList($mailingListMember);

            $this->auditService->persist(
                AuditMailingListMembership::create(
                    MailingListMemberAction::Add,
                    MailingListMemberOrigin::Manual,
                    $member,
                    $list,
                    $mailingListMember->getEmail(),
                    $this->userService->getIdentity(),
                ),
            );
        }

        // Simply cascade persist through member.
        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Unsubscribe a member from all mailing lists. This is used when removing/clearing a member.
     * We never use recordAudit = true yet, but it is implemented to avoid forgetting it.
     */
    public function unsubscribeLists(
        MemberModel $member,
        bool $recordAudit = true,
    ): void {
        foreach ($member->getMailingListMemberships() as $mailingListMembership) {
            $mailingListMembership->setToBeDeleted(true);

            if ($recordAudit) {
                $this->auditService->persist(
                    AuditMailingListMembership::create(
                        MailingListMemberAction::Remove,
                        MailingListMemberOrigin::Manual,
                        $member,
                        $mailingListMembership->getMailingList(),
                        $mailingListMembership->getEmail(),
                        $this->userService->getIdentity(),
                    ),
                );
            }

            $this->mailingListMemberMapper->persist($mailingListMembership);
        }
    }

    /**
     * Add audit note to a member.
     */
    public function addAuditNote(
        MemberModel $member,
        AuditNoteForm $form,
    ): ?AuditNoteModel {
        if (!$form->isValid()) {
            return null;
        }

        /** @var AuditNoteModel $auditNote */
        $auditNote = $form->getData();
        $auditNote->setUser($this->userService->getIdentity());

        $this->addAuditEntry($member, $auditNote);

        return $auditNote;
    }

    private function addAuditEntry(
        MemberModel $member,
        AuditEntryModel $auditEntry,
    ): AuditEntryModel {
        $auditEntry->setMember($member);
        $this->getAuditMapper()->persist($auditEntry);

        return $auditEntry;
    }

    /**
     * @return array{
     *     members: int,
     *     graduates: int,
     *     expired: int,
     *     prospectives: array{
     *       total: int,
     *       paid: int,
     *     },
     *     updates: int,
     * }
     */
    public function getFrontpageData(): array
    {
        $totalInclExpired = $this->getMemberMapper()->countMembers(true, false, true);
        $totalExclExpired = $this->getMemberMapper()->countMembers(true, false, false);
        $nongraduatesExclExpired = $this->getMemberMapper()->countMembers(false, false, false);

        return [
            'members' => $nongraduatesExclExpired,
            'graduates' => $totalExclExpired - $nongraduatesExclExpired,
            'expired' => $totalInclExpired - $totalExclExpired,
            'prospectives' => [
                'total' => $this->getProspectiveMemberMapper()->getRepository()->count([]),
                'paid' => count($this->getProspectiveMemberMapper()->search('', 'paid')),
            ],
            'updates' => $this->getPendingUpdateCount(),
        ];
    }

    /**
     * The number of pending member updates, a separate function to make sure we don't have to do a lot
     * of database queries for each page.
     */
    public function getPendingUpdateCount(): int
    {
        return $this->getMemberUpdateMapper()->getRepository()->count([]);
    }

    /**
     * Paid prospective members (separately from frontpage data to reduce number
     * of database queries)
     */
    public function getPaidProspectivesCount(): int
    {
        return count($this->getProspectiveMemberMapper()->search('', 'paid'));
    }

    /**
     * Get a list of all pending member updates.
     *
     * @return MemberUpdateModel[]
     */
    public function getPendingMemberUpdates(): array
    {
        return $this->getMemberUpdateMapper()->getPendingUpdates();
    }

    /**
     * Get a specific member update.
     */
    public function getPendingMemberUpdate(int $lidnr): ?MemberUpdateModel
    {
        return $this->getMemberUpdateMapper()->find($lidnr);
    }

    public function approveMemberUpdate(
        MemberModel $member,
        MemberUpdateModel $memberUpdate,
    ): ?MemberModel {
        // We use reflection here, because using the hydrator on Member(Edit)Form sucks (requires more info). This does
        // not account for any type changes that may be required (everything is currently a string).
        $reflectionClass = new ReflectionClass($member);
        foreach ($memberUpdate->toArray() as $property => $value) {
            if (!$reflectionClass->hasProperty($property)) {
                continue;
            }

            $reflectionProperty = $reflectionClass->getProperty($property);
            $reflectionProperty->setValue($member, $value);
        }

        $member->setAuthenticationKey($this->generateAuthenticationKey());
        $this->getMemberMapper()->persist($member);
        $this->getMemberUpdateMapper()->remove($memberUpdate);

        return $member;
    }

    public function rejectMemberUpdate(MemberUpdateModel $memberUpdate): ?bool
    {
        $this->getMemberUpdateMapper()->remove($memberUpdate);

        return true;
    }

    /**
     * Generate authentication keys for members whose membership has not expired and who are not hidden.
     */
    public function generateAuthenticationKeys(): void
    {
        $members = $this->getMemberMapper()->getNonExpiredNonHiddenMembers();

        foreach ($members as $member) {
            $member->setAuthenticationKey($this->generateAuthenticationKey());
            $this->getMemberMapper()->persist($member);
        }
    }

    /**
     * Generate a cryptographically secure pseudo-random string of 64 bytes, encoded as hex.
     */
    private function generateAuthenticationKey(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Get the member edit form.
     *
     * @return array{
     *     member: MemberModel,
     *     form: MemberEditForm,
     * }
     */
    public function getMemberEditForm(MemberModel $member): array
    {
        $form = $this->memberEditForm;
        $form->bind($member);

        return [
            'member' => $member,
            'form' => $form,
        ];
    }

    /**
     * Get the member expiration form.
     */
    public function getMemberExpirationForm(): MemberExpirationForm
    {
        return $this->memberExpirationForm;
    }

    /**
     * Get the member type form.
     */
    public function getMemberTypeForm(MemberModel $member): MemberTypeForm
    {
        $form = $this->memberTypeForm;
        $form->setMembership($member->getLastMembership());

        return $form;
    }

    public function getBulkMemberRenewalForm(): BulkMemberRenewalForm
    {
        return $this->bulkMemberRenewalForm;
    }

    /**
     * @return array{
     *     currentType: MembershipTypes,
     *     oldExpiration: DateTime,
     *     newExpiration: DateTime,
     *     changeDate: DateTime,
     *     lastMembership: MembershipModel,
     * }
     */
    private function resolveMembershipChange(
        MemberModel $member,
        MembershipTypes $newType,
        ?DateTime $changeDate = null,
    ): array {
        $currentMembership = $member->getCurrentOrLastMembership();

        if (null === $currentMembership) {
            throw new RuntimeException('Unable to change membership type without a membership.');
        }

        if (MembershipTypes::Honorary === $currentMembership->getType()) {
            throw new RuntimeException('Unable to change membership type of honorary member.');
        }

        $lastMembership = $member->getLastMembership();
        assert($lastMembership instanceof MembershipModel);

        $effectiveChangeDate = null === $changeDate ? new DateTime() : clone $changeDate;
        $effectiveChangeDate->setTime(0, 0);

        if ($effectiveChangeDate < $lastMembership->getStartDate()) {
            $effectiveChangeDate = clone $lastMembership->getStartDate();
        }

        if ($effectiveChangeDate > $lastMembership->getEndDate()) {
            $effectiveChangeDate = clone $lastMembership->getEndDate();
        }

        $newExpiration = $effectiveChangeDate->getTimestamp() === $lastMembership->getStartDate()->getTimestamp()
            ? clone $lastMembership->getEndDate()
            : (new MembershipModel($member, $newType, clone $effectiveChangeDate))->getEndDate();

        return [
            'currentType' => $lastMembership->getType(),
            'oldExpiration' => clone $member->getExpiration(),
            'newExpiration' => $newExpiration,
            'changeDate' => $effectiveChangeDate,
            'lastMembership' => $lastMembership,
        ];
    }

    private function applyMembershipChange(
        MemberModel $member,
        MembershipTypes $newType,
        ?DateTime $changeDate = null,
    ): MemberModel {
        $resolvedChange = $this->resolveMembershipChange($member, $newType, $changeDate);

        $date = new DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        $renewalAudit = new AuditRenewalModel();
        $renewalAudit->setOldExpiration($resolvedChange['oldExpiration']);

        $lastMembership = $resolvedChange['lastMembership'];
        $effectiveChangeDate = $resolvedChange['changeDate'];

        if ($effectiveChangeDate->getTimestamp() === $lastMembership->getStartDate()->getTimestamp()) {
            $lastMembership->setType($newType);
        } else {
            $lastMembership->setEndDate(clone $effectiveChangeDate);
            $member->addMembership(new MembershipModel(
                member: $member,
                type: $newType,
                startDate: clone $effectiveChangeDate,
                endDate: null,
            ));
        }

        $renewalAudit->setNewExpiration($resolvedChange['newExpiration']);
        $renewalAudit->setUser($this->userService->getIdentity());
        $this->addAuditEntry($member, $renewalAudit);
        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Get the list edit form.
     *
     * @return array{
     *     form: MemberListsForm,
     *     member: MemberModel,
     *     lists: MailingListModel[],
     * }
     */
    public function getListForm(MemberModel $member): array
    {
        $lists = $this->mailingListService->getAllLists();

        return [
            'form' => new MemberListsForm($this->translator, $member, $lists),
            'member' => $member,
            'lists' => $lists,
        ];
    }

    /**
     * Get the address form.
     */
    public function getAddressForm(
        MemberModel $member,
        AddressTypes $type,
        bool $create = false,
    ): AddressForm {
        // find the address
        if ($create) {
            $address = new AddressModel();
            $address->setMember($member);
            $address->setType($type);
        } else {
            $address = $this->getMemberMapper()->findMemberAddress($member, $type);
        }

        $form = $this->addressForm;
        $form->bind($address);

        return $form;
    }

    /**
     * Get the members requiring attention
     *
     * @return array{
     *     days: int,
     *     members: MemberModel[],
     *     reasons: array<int, AttentionReasons[]>,
     *     bulkRenewalShortcuts: array{
     *         expiringActive: int[],
     *         expiringNonActive: int[],
     *     },
     * }
     */
    public function getMembersRequiringAttention(int $days = 30): array
    {
        $members = [];
        $reasons = [];
        $bulkRenewalShortcuts = [
            'expiringActive' => [],
            'expiringNonActive' => [],
        ];

        /** @var array<value-of<AttentionReasons>, MemberModel[]> $combined */
        $combined = [];

        $combined[AttentionReasons::MissingEmail->value] = $this->getMemberMapper()->findAttentionWithoutEmail();
        $combined[AttentionReasons::MissingStudentIdOrdinary->value] =
        $this->getMemberMapper()->findAttentionWithoutStudentId();
        $combined[AttentionReasons::ExpiringExternalNonActive->value] =
        $this->getMemberMapper()->findAttentionExpiring(
            includeActive: false,
            includeNonActive: true,
            specificType: MembershipTypes::External,
            expiresWithinDays: $days,
        );
        $combined[AttentionReasons::ExpiringExternalActive->value] = $this->getMemberMapper()->findAttentionExpiring(
            includeActive: true,
            includeNonActive: false,
            specificType: MembershipTypes::External,
            expiresWithinDays: $days,
        );
        $combined[AttentionReasons::ExpiringOrdinaryActive->value] = $this->getMemberMapper()->findAttentionExpiring(
            includeActive: true,
            includeNonActive: false,
            specificType: MembershipTypes::Ordinary,
            expiresWithinDays: $days,
        );
        $combined[AttentionReasons::ExpiringOrdinaryNonActive->value] = $this->getMemberMapper()->findAttentionExpiring(
            includeActive: false,
            includeNonActive: true,
            specificType: MembershipTypes::Ordinary,
            expiresWithinDays: $days,
        );
        $combined[AttentionReasons::ExpiringGraduateActiveInactive->value] =
        $this->getMemberMapper()->findAttentionExpiring(
            includeActive: true,
            includeNonActive: false,
            inActiveIsActive: true,
            specificType: MembershipTypes::Graduate,
            expiresWithinDays: $days,
        );

        foreach (AttentionReasons::cases() as $reason) {
            if ($reason->includeBulkActiveMemberRenewal()) {
                foreach ($combined[$reason->value] ?? [] as $member) {
                    $bulkRenewalShortcuts['expiringActive'][] = $member->getLidnr();
                }
            }

            if ($reason->includeBulkGraduateConversion()) {
                foreach ($combined[$reason->value] ?? [] as $member) {
                    $bulkRenewalShortcuts['expiringNonActive'][] = $member->getLidnr();
                }
            }

            foreach ($combined[$reason->value] ?? [] as $member) {
                if (!array_key_exists($member->getLidnr(), $reasons)) {
                    $members[] = $member;
                }

                $reasons[$member->getLidnr()][] = $reason;
            }
        }

        usort($members, static function (MemberModel $a, MemberModel $b) {
            return ($a->getExpiration() <=> $b->getExpiration()) * 10
                + ($a->getLastName() <=> $b->getLastName()) * 2
                + ($a->getFirstName() <=> $b->getFirstName());
        });

        $bulkRenewalShortcuts['expiringActive'] = array_values(
            array_unique($bulkRenewalShortcuts['expiringActive']),
        );
        $bulkRenewalShortcuts['expiringNonActive'] = array_values(
            array_unique($bulkRenewalShortcuts['expiringNonActive']),
        );

        return [
            'days' => $days,
            'members' => $members,
            'reasons' => $reasons,
            'bulkRenewalShortcuts' => $bulkRenewalShortcuts,
        ];
    }

    /**
     * Get the audit note form.
     */
    public function getAuditNoteForm(MemberModel $member): AuditNoteForm
    {
        return $this->auditNoteForm;
    }

    /**
     * Get the delete address form.
     */
    public function getDeleteAddressForm(): DeleteAddressForm
    {
        return $this->deleteAddressForm;
    }

    /**
     * Get the member form.
     */
    public function getMemberForm(): MemberForm
    {
        $this->memberForm->setLists($this->mailingListMapper->findAllOnForm());

        return $this->memberForm;
    }

    /**
     * Get the audit mapper.
     */
    public function getAuditMapper(): AuditMapper
    {
        return $this->auditMapper;
    }

    /**
     * Get the actionlink mapper.
     */
    public function getActionLinkMapper(): ActionLinkMapper
    {
        return $this->actionLinkMapper;
    }

    /**
     * Get the member mapper.
     */
    public function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }

    /**
     * Get the member update mapper.
     */
    public function getMemberUpdateMapper(): MemberUpdateMapper
    {
        return $this->memberUpdateMapper;
    }

    /**
     * Get the member mapper.
     */
    public function getProspectiveMemberMapper(): ProspectiveMemberMapper
    {
        return $this->prospectiveMemberMapper;
    }

    /**
     * Get the renewal form.
     */
    public function getRenewalForm(string $token): ?MemberRenewalForm
    {
        $renewalLink = $this->actionLinkMapper->findRenewalByToken($token);

        if (
            null === $renewalLink
            || $renewalLink->isUsed()
            || $renewalLink->linkExpired()
        ) {
            return null;
        }

        $form = $this->memberRenewalForm;
        $form->bind($renewalLink->getMember());
        $form->setExpiration($renewalLink->getNewExpiration());
        $form->setRenewalLink($renewalLink);

        return $form;
    }

    /**
     * Renew a member (with existing membership type).
     * Currently only used for renewal links.
     */
    public function renewMember(
        MemberModel $member,
        RenewalLinkModel $renewalLink,
        DateTime $newExpiration,
    ): MemberModel {
        $member->setChangedOn(new DateTime());

        $renewalLink->setUsed(true);
        $this->getActionLinkMapper()->persist($renewalLink);
        $this->renewalService->sendRenewalSuccessEmail($renewalLink);

        // Record a renewal audit entry
        $renewalAudit = AuditRenewalModel::fromRenewalLink($renewalLink);
        $renewalAudit->setNewExpiration($newExpiration);
        $this->addAuditEntry($member, $renewalAudit);

        $newMembership = new MembershipModel(
            member: $member,
            type: $member->getCurrentOrLastMembership()->getType(),
            startDate: $member->getCurrentOrLastMembership()->getEndDate(),
            endDate: $newExpiration,
        );
        $member->addMembership($newMembership);

        $this->getMemberMapper()->persist($member);

        return $member;
    }

    /**
     * Gets the storage service.
     */
    public function getFileStorageService(): FileStorageService
    {
        return $this->fileStorageService;
    }

    /**
     * Get the renderer for the email.
     */
    public function getRenderer(): PhpRenderer
    {
        return $this->viewRenderer;
    }

    /**
     * Get the mail transport.
     */
    public function getMailTransport(): TransportInterface
    {
        return $this->mailTransport;
    }

    /**
     * Get the checker service.
     */
    public function getCheckerService(): CheckerService
    {
        return $this->checkerService;
    }
}
