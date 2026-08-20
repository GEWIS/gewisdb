<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Application\Enums\ConfigNamespaces;
use App\Entity\Database\Enums\BoardFunctions;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\User\Enums\ApiPermissions;
use App\Exception\Report\VersionExpected as VersionExpectedException;
use App\Exception\Report\VersionFormat as VersionFormatException;
use App\Exception\Report\VersionIncompatible as VersionIncompatibleException;
use App\Repository\Report\MemberRepository as ReportMemberRepository;
use App\Service\Application\Config as ConfigService;
use DateTime;
use PHLAK\SemVer\Enums\Compare as SemanticCompare;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version as SemanticVersion;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_reduce;
use function is_bool;
use function is_string;
use function max;
use function preg_replace;

class ApiService
{
    /**
     * The permission that has to be held for a member property to be part of the response, keyed by the property as
     * {@see \App\Entity\Report\Member::toArrayApi()} names it.
     */
    private const array PROPERTY_PERMISSIONS = [
        'organs' => ApiPermissions::OrgansMembershipR,
        'keyholder' => ApiPermissions::MembersPropertyKeyholder,
        'type' => ApiPermissions::MembersPropertyType,
        'email' => ApiPermissions::MembersPropertyEmail,
        'birthdate' => ApiPermissions::MembersPropertyBirthDate,
        'is_16_plus' => ApiPermissions::MembersPropertyAge16,
        'is_18_plus' => ApiPermissions::MembersPropertyAge18,
        'is_21_plus' => ApiPermissions::MembersPropertyAge21,
    ];

    /**
     * The release that introduced the function lists; consumers on an older contract are turned away.
     */
    private const string FUNCTIONS_MINIMUM_VERSION = 'v4.3.3';

    public function __construct(
        private readonly ReportMemberRepository $reportMemberRepository,
        private readonly ConfigService $configService,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Get active members and inactive fraternity members.
     *
     * It is good to note here that the includeInactiveFraternity argument
     * only changes who is returned. If someone is active in another organ,
     * their inactive fraternity membership still gets returned as organ membership
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getActiveMembers(bool $includeInactiveFraternity = false): array
    {
        $additionalProperties = $this->additionalProperties();
        $allowDeleted = $this->allowsDeletedMembers();

        return array_reduce(
            $this->reportMemberRepository->findActive($includeInactiveFraternity),
            static function ($array, $member) use ($additionalProperties, $allowDeleted) {
                if (
                    !$member->getDeleted()
                    || $allowDeleted
                ) {
                    $array[] = $member->toArrayApi($additionalProperties);
                }

                return $array;
            },
        );
    }

    /**
     * Get normal members.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getMembers(bool $includeOrgans = true): array
    {
        $additionalProperties = $this->additionalProperties($includeOrgans);
        $allowDeleted = $this->allowsDeletedMembers();

        return array_reduce(
            $this->reportMemberRepository->findNormal(),
            static function ($array, $member) use ($additionalProperties, $allowDeleted) {
                if (
                    !$member->getDeleted()
                    || $allowDeleted
                ) {
                    $array[] = $member->toArrayApi($additionalProperties);
                }

                return $array;
            },
        );
    }

    /**
     * Get normal members.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getMember(int $id): ?array
    {
        $member = $this->reportMemberRepository->findSimple($id);

        if (null === $member) {
            return null;
        }

        if (
            !$this->allowsDeletedMembers()
            && $member->getDeleted()
        ) {
            return null;
        }

        return $member->toArrayApi($this->additionalProperties());
    }

    /**
     * Get the organ functions and their translations.
     *
     * @return array<non-empty-string, array{
     *  isAdministrative: bool,
     *  isLegacy: bool,
     *  translations: non-empty-array<array-key, string>
     * }>
     */
    public function getOrganFunctions(?string $acceptHeader): array
    {
        $this->assertVersion(
            new SemanticVersion(self::FUNCTIONS_MINIMUM_VERSION),
            null,
            $acceptHeader,
        );

        return InstallationFunctions::getMultilangArray($this->translator);
    }

    /**
     * Get the board functions and their translations.
     *
     * @return array<non-empty-string, array{
     *  isLegacy: bool,
     *  translations: non-empty-array<array-key, string>
     * }>
     */
    public function getBoardFunctions(?string $acceptHeader): array
    {
        $this->assertVersion(
            new SemanticVersion(self::FUNCTIONS_MINIMUM_VERSION),
            null,
            $acceptHeader,
        );

        return BoardFunctions::getMultilangArray($this->translator);
    }

    /**
     * The properties the authenticated principal may see on top of the ones every principal gets.
     *
     * Organ membership is the one property a request can also opt out of, because it is by far the most expensive to
     * assemble; every other property follows from the permissions alone.
     *
     * @return array<array-key,string>
     */
    private function additionalProperties(bool $includeOrgans = true): array
    {
        $additionalProperties = [];

        foreach (self::PROPERTY_PERMISSIONS as $property => $permission) {
            if (
                'organs' === $property
                && !$includeOrgans
            ) {
                continue;
            }

            if (!$this->authorizationChecker->isGranted($permission->value)) {
                continue;
            }

            $additionalProperties[] = $property;
        }

        return $additionalProperties;
    }

    /**
     * Whether members that have been marked as deleted are part of the response at all.
     */
    private function allowsDeletedMembers(): bool
    {
        return $this->authorizationChecker->isGranted(ApiPermissions::MembersDeleted->value);
    }

    /**
     * @return array{
     *     syncPaused: bool,
     *     syncPausedUntil: ?DateTime,
     * }
     */
    public function getFrontpageData(): array
    {
        return [
            'syncPaused' => $this->isSyncPaused(),
            'syncPausedUntil' => $this->getSyncPausedUntil(),
        ];
    }

    /**
     * Flag to other applications using GEWISDB API that they should wait with syncing
     */
    public function pauseSync(int $minutes): void
    {
        $syncPausedUntil = max(
            $this->getSyncPausedUntil(),
            new DateTime()->modify('+' . $minutes . ' minutes'),
        );

        $this->configService->setConfig(
            ConfigNamespaces::DatabaseApi,
            'sync_paused',
            $syncPausedUntil,
        );
    }

    public function resumeSyncNow(): void
    {
        $this->configService->unsetConfig(
            ConfigNamespaces::DatabaseApi,
            'sync_paused',
        );
    }

    public function isSyncPaused(): bool
    {
        return $this->getSyncPausedUntil() > new DateTime();
    }

    private function getSyncPausedUntil(): ?DateTime
    {
        $pausedUntil = $this->configService->getConfig(
            ConfigNamespaces::DatabaseApi,
            'sync_paused',
        );

        if (is_string($pausedUntil)) {
            return null;
        }

        if (is_bool($pausedUntil)) {
            return null;
        }

        return $pausedUntil;
    }

    /**
     * Function that asserts that the given api version is between two bounds.
     *
     * The version is negotiated through the `Accept` header, which is handed in verbatim.
     *
     * @throws VersionExpectedException if not allowed.
     */
    public function assertVersion(
        SemanticVersion $lower,
        ?SemanticVersion $upper,
        ?string $acceptHeader,
    ): void {
        if (null === $acceptHeader) {
            throw new VersionExpectedException();
        }

        $count = 0;
        $value = preg_replace(
            pattern: '/application\\/vnd\\.gewis\\.gewisdb\\+json;version=(.*)/i',
            replacement: 'v${1}',
            subject: $acceptHeader,
            count: $count,
        );

        try {
            $given = new SemanticVersion($value);
        } catch (InvalidVersionException) {
            throw new VersionFormatException($value);
        }

        if (1 !== $count) {
            throw new VersionExpectedException();
        }

        if (
            $given->lt(
                $lower,
                SemanticCompare::PATCH,
            )
        ) {
            throw new VersionIncompatibleException(
                $lower,
                $upper,
                $given,
            );
        }

        if (
            null !== $upper
            && $given->gt(
                $upper,
                SemanticCompare::PATCH,
            )
        ) {
            throw new VersionIncompatibleException(
                $lower,
                $upper,
                $given,
            );
        }
    }
}
