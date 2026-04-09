<?php

/**
 * Adapter implementing the OXID 7 ModuleSettingServiceInterface on top of
 * o3-shop's ModuleSettingBridgeInterface (OXID 6.x).
 *
 * OXID 7 split the generic get()/save() into typed accessors. This adapter
 * forwards them to the bridge's generic get()/save() with type coercion.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Facade;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;

class ModuleSettingService implements ModuleSettingServiceInterface
{
    public function __construct(private ModuleSettingBridgeInterface $bridge)
    {
    }

    public function getString(string $name, string $moduleId): \Stringable|string
    {
        return (string) $this->bridge->get($name, $moduleId);
    }

    public function getBoolean(string $name, string $moduleId): bool
    {
        return (bool) $this->bridge->get($name, $moduleId);
    }

    public function getInteger(string $name, string $moduleId): int
    {
        return (int) $this->bridge->get($name, $moduleId);
    }

    public function getFloat(string $name, string $moduleId): float
    {
        return (float) $this->bridge->get($name, $moduleId);
    }

    public function getCollection(string $name, string $moduleId): array
    {
        $value = $this->bridge->get($name, $moduleId);
        return is_array($value) ? $value : [];
    }

    public function saveString(string $name, string $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveBoolean(string $name, bool $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveInteger(string $name, int $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveFloat(string $name, float $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveCollection(string $name, array $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }
}