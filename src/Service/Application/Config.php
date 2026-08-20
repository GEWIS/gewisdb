<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\ConfigItem;
use App\Entity\Application\Enums\ConfigNamespaces;
use App\Repository\Application\ConfigItemRepository;
use DateTime;

class Config
{
    public function __construct(private readonly ConfigItemRepository $configItemRepository)
    {
    }

    /**
     * @template T of bool|string|DateTime|null
     *
     * @psalm-param T $default
     *
     * @psalm-return (T is null ? bool|string|DateTime|null : T)
     */
    public function getConfig(
        ConfigNamespaces $namespace,
        string $key,
        bool|string|DateTime|null $default = null,
    ): bool|string|DateTime|null {
        $configItem = $this->configItemRepository->findByKey(
            $namespace,
            $key,
        );

        if (
            null === $configItem
            || null === $configItem->getValue()
        ) {
            return $default;
        }

        return $configItem->getValue();
    }

    public function setConfig(
        ConfigNamespaces $namespace,
        string $key,
        bool|string|DateTime $value,
    ): void {
        $configItem = $this->configItemRepository->findByKey(
            $namespace,
            $key,
        );

        if (null === $configItem) {
            $configItem = new ConfigItem();
            $configItem->setKey(
                $namespace,
                $key,
            );
        }

        $configItem->setValue($value);
        $this->configItemRepository->persist($configItem);
    }

    public function unsetConfig(
        ConfigNamespaces $namespace,
        string $key,
    ): void {
        $configItem = $this->configItemRepository->findByKey(
            $namespace,
            $key,
        );

        if (null === $configItem) {
            return;
        }

        $this->configItemRepository->remove($configItem);
    }
}
