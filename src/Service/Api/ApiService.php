<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\Application\Enums\ConfigNamespaces;
use App\Exception\Api\VersionExpected as VersionExpectedException;
use App\Exception\Api\VersionFormat as VersionFormatException;
use App\Exception\Api\VersionIncompatible as VersionIncompatibleException;
use App\Repository\Report\MemberRepository as ReportMemberRepository;
use App\Service\Application\Config as ConfigService;
use DateTime;
use PHLAK\SemVer\Enums\Compare as SemanticCompare;
use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version as SemanticVersion;

use function array_reduce;
use function is_bool;
use function is_string;
use function max;
use function preg_replace;

class ApiService
{
    public function __construct(
        private readonly ReportMemberRepository $reportMemberRepository,
        private readonly ConfigService $configService,
    ) {
    }

    /**
     * Get active members and inactive fraternity members.
     *
     * It is good to note here that the includeInactiveFraternity argument
     * only changes who is returned. If someone is active in another organ,
     * their inactive fraternity membership still gets returned as organ membership
     *
     * @param array<array-key,string> $additionalProperties
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getActiveMembers(
        array $additionalProperties,
        bool $includeInactiveFraternity = false,
        bool $allowDeleted = false,
    ): array {
        return array_reduce(
            $this->reportMemberRepository->findActive($includeInactiveFraternity),
            static function ($array, $member) use ($additionalProperties, $allowDeleted) {
                if (!$member->getDeleted() || $allowDeleted) {
                    $array[] = $member->toArrayApi($additionalProperties);
                }

                return $array;
            },
        );
    }

    /**
     * Get normal members.
     *
     * @param array<array-key,string> $additionalProperties
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getMembers(
        array $additionalProperties,
        bool $allowDeleted = false,
    ): array {
        return array_reduce(
            $this->reportMemberRepository->findNormal(),
            static function ($array, $member) use ($additionalProperties, $allowDeleted) {
                if (!$member->getDeleted() || $allowDeleted) {
                    $array[] = $member->toArrayApi($additionalProperties);
                }

                return $array;
            },
        );
    }

    /**
     * Get normal members.
     *
     * @param array<array-key,string> $additionalProperties
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getMember(
        int $id,
        array $additionalProperties,
        bool $allowDeleted = false,
    ): ?array {
        $member = $this->reportMemberRepository->findSimple($id);

        if (null === $member) {
            return null;
        }

        if (!$allowDeleted && $member->getDeleted()) {
            return null;
        }

        return $member->toArrayApi($additionalProperties);
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
            (new DateTime())->modify('+' . $minutes . ' minutes'),
        );

        $this->configService->setConfig(ConfigNamespaces::DatabaseApi, 'sync_paused', $syncPausedUntil);
    }

    public function resumeSyncNow(): void
    {
        $this->configService->unsetConfig(ConfigNamespaces::DatabaseApi, 'sync_paused');
    }

    public function isSyncPaused(): bool
    {
        return $this->getSyncPausedUntil() > new DateTime();
    }

    private function getSyncPausedUntil(): ?DateTime
    {
        $pausedUntil = $this->configService->getConfig(ConfigNamespaces::DatabaseApi, 'sync_paused');

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

        if ($given->lt($lower, SemanticCompare::PATCH)) {
            throw new VersionIncompatibleException($lower, $upper, $given);
        }

        if (
            null !== $upper
            && $given->gt($upper, SemanticCompare::PATCH)
        ) {
            throw new VersionIncompatibleException($lower, $upper, $given);
        }
    }
}
