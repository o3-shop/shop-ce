<?php

/**
 * Compatibility shim for OXID 7's ModuleSettingServiceInterface.
 * OXID 7 introduced typed getters (getString, getBoolean, etc.) in the Facade namespace.
 * o3-shop only has ModuleSettingBridgeInterface with a generic get().
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Facade;

interface ModuleSettingServiceInterface
{
    public function getString(string $name, string $moduleId): \Stringable|string;

    public function getBoolean(string $name, string $moduleId): bool;

    public function getInteger(string $name, string $moduleId): int;

    public function getFloat(string $name, string $moduleId): float;

    public function getCollection(string $name, string $moduleId): array;

    public function saveString(string $name, string $value, string $moduleId): void;

    public function saveBoolean(string $name, bool $value, string $moduleId): void;

    public function saveInteger(string $name, int $value, string $moduleId): void;

    public function saveFloat(string $name, float $value, string $moduleId): void;

    public function saveCollection(string $name, array $value, string $moduleId): void;
}