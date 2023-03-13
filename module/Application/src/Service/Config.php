<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Mapper\ConfigItem as ConfigItemMapper;
use Application\Model\ConfigItem as ConfigItemModel;
use Application\Model\Enums\ConfigNamespaces;
use DateTime;

class Config
{
    public function __construct(private readonly ConfigItemMapper $configItemMapper)
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
        $configItem = $this->getConfigItemMapper()->findByKey($namespace, $key);

        if (null === $configItem || null === $configItem->getValue()) {
            return $default;
        }

        return $configItem->getValue();
    }

    public function setConfig(
        ConfigNamespaces $namespace,
        string $key,
        bool|string|DateTime $value,
    ): void {
        $configItem = $this->getConfigItemMapper()->findByKey($namespace, $key);

        if (null === $configItem) {
            $configItem = new ConfigItemModel();
            $configItem->setKey($namespace, $key);
        }

        $configItem->setValue($value);
        $this->getConfigItemMapper()->persist($configItem);
    }

    public function unsetConfig(
        ConfigNamespaces $namespace,
        string $key,
    ): void {
        $configItem = $this->getConfigItemMapper()->findByKey($namespace, $key);

        if (null === $configItem) {
            return;
        }

        $this->getConfigItemMapper()->remove($configItem);
    }

    /**
     * Get the member mapper.
     */
    private function getConfigItemMapper(): ConfigItemMapper
    {
        return $this->configItemMapper;
    }
}
