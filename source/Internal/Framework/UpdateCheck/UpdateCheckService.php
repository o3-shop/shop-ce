<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\UpdateCheck;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;

class UpdateCheckService implements UpdateCheckServiceInterface
{
    public const ENDPOINT = 'https://updates.o3-shop.com/check/v1';

    public const GITHUB_RELEASES_URL = 'https://api.github.com/repos/o3-shop/o3-shop/releases/latest';

    public const CACHE_SESSION_KEY = 'updateCheckResult';

    public const CACHE_TTL_SECONDS = 86400;

    public const CURL_TIMEOUT = 5;

    /** @var ShopConfigurationDaoBridgeInterface */
    private $shopConfigurationDaoBridge;

    /** @var ModuleActivationBridgeInterface */
    private $moduleActivationBridge;

    public function __construct(
        ShopConfigurationDaoBridgeInterface $shopConfigurationDaoBridge,
        ModuleActivationBridgeInterface $moduleActivationBridge
    ) {
        $this->shopConfigurationDaoBridge = $shopConfigurationDaoBridge;
        $this->moduleActivationBridge = $moduleActivationBridge;
    }

    public function check(bool $forceRefresh = false): UpdateCheckResult
    {
        try {
            if (!$forceRefresh) {
                $cached = $this->getCachedResult();
                if ($cached !== null) {
                    return $cached;
                }
            }

            $payload = $this->buildPayload();
            $result = $this->performUpdateCheck($payload);

            $this->cacheResult($result);

            return $result;
        } catch (\Throwable $e) {
            Registry::getLogger()->error(
                __METHOD__ . " - Update check failed with exception: '" . $e->getMessage() . "'.",
                ['exception' => $e]
            );
            return UpdateCheckResult::empty();
        }
    }

    /**
     * @return array
     */
    public function buildPayload(): array
    {
        $modules = [];
        $shopConfiguration = $this->shopConfigurationDaoBridge->get();

        foreach ($shopConfiguration->getModuleConfigurations() as $moduleConfiguration) {
            $modules[$moduleConfiguration->getId()] = $moduleConfiguration->getVersion();
        }

        return [
            'shop_version' => ShopVersion::getVersion(),
            'domain' => Registry::getConfig()->getShopUrl(),
            'modules' => $modules,
        ];
    }

    /**
     * @param array $payload
     *
     * @return UpdateCheckResult
     */
    private function performUpdateCheck(array $payload): UpdateCheckResult
    {
        $response = $this->postJson(self::ENDPOINT, $payload);

        if ($response !== null) {
            return $this->parseEndpointResponse($response, $payload['modules'] ?? []);
        }

        Registry::getLogger()->warning(
            __METHOD__ . ' - O3-Shop update endpoint unreachable. Falling back to GitHub API.'
        );

        return $this->fallbackGitHubCheck();
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return array|null Decoded JSON response or null on failure
     */
    private function postJson(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            Registry::getLogger()->error(
                __METHOD__ . " - Update endpoint returned HTTP '200' but response is not valid JSON."
            );
            return null;
        }

        return $decoded;
    }

    /**
     * @param array $response
     *
     * @return UpdateCheckResult
     */
    private function parseEndpointResponse(array $response, array $installedModules = []): UpdateCheckResult
    {
        $coreUpdateAvailable = !empty($response['core_not_actual']);
        $latestCoreVersion = $response['actual_version'] ?? '';
        $updateLink = $response['update_link'] ?? '';

        $outdatedModules = [];
        $shopId = Registry::getConfig()->getShopId();

        if (is_array($response['plugins'] ?? null)) {
            foreach ($response['plugins'] as $plugin) {
                $moduleId = $plugin['code'] ?? '';
                if ($moduleId === '') {
                    continue;
                }

                if (!$this->moduleActivationBridge->isActive($moduleId, $shopId)) {
                    continue;
                }

                $outdatedModules[] = [
                    'id' => $moduleId,
                    'installed_version' => $installedModules[$moduleId] ?? '',
                    'latest_version' => $plugin['version'] ?? '',
                    'url' => $plugin['url'] ?? '',
                ];
            }
        }

        return new UpdateCheckResult(
            $coreUpdateAvailable,
            $latestCoreVersion,
            $updateLink,
            $outdatedModules
        );
    }

    /**
     * @return UpdateCheckResult
     */
    private function fallbackGitHubCheck(): UpdateCheckResult
    {
        $ch = curl_init(self::GITHUB_RELEASES_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'User-Agent: PHP',
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            Registry::getLogger()->warning(
                __METHOD__ . " - GitHub API also unreachable. HTTP code: '" . $httpCode . "'."
            );
            return UpdateCheckResult::empty();
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            Registry::getLogger()->error(
                __METHOD__ . " - GitHub API returned HTTP '200' but response is not valid JSON."
            );
            return UpdateCheckResult::empty();
        }

        $latestVersion = $data['name'] ?? '';
        $currentVersion = ShopVersion::getVersion();

        if ($latestVersion !== '' && version_compare($currentVersion, $latestVersion, '<')) {
            return new UpdateCheckResult(
                true,
                $latestVersion,
                $data['html_url'] ?? ''
            );
        }

        return UpdateCheckResult::empty();
    }

    /**
     * @return UpdateCheckResult|null
     */
    private function getCachedResult(): ?UpdateCheckResult
    {
        $session = Registry::getSession();
        $cached = $session->getVariable(self::CACHE_SESSION_KEY);

        if (!is_array($cached)) {
            return null;
        }

        $timestamp = $cached['timestamp'] ?? 0;
        if ((time() - $timestamp) >= self::CACHE_TTL_SECONDS) {
            return null;
        }

        $result = $cached['result'] ?? null;
        if (!$result instanceof UpdateCheckResult) {
            return null;
        }

        return $result;
    }

    /**
     * @param UpdateCheckResult $result
     */
    private function cacheResult(UpdateCheckResult $result): void
    {
        Registry::getSession()->setVariable(self::CACHE_SESSION_KEY, [
            'timestamp' => time(),
            'result' => $result,
        ]);
    }
}
