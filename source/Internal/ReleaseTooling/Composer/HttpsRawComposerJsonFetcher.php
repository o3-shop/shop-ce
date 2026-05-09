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
 * @copyright  Copyright (c) 2026 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\ReleaseTooling\Composer;

/**
 * HTTPS implementation: GETs
 * https://raw.githubusercontent.com/<package>/<ref>/composer.json
 * and parses the body as JSON.
 *
 * Trusts GitHub's CDN — no auth required for public repos. The CLI
 * runs locally with the maintainer's network access.
 */
class HttpsRawComposerJsonFetcher implements RawComposerJsonFetcher
{
    public const RAW_GITHUB_BASE = 'https://raw.githubusercontent.com';
    public const TIMEOUT_SECONDS = 30;

    public function fetch(string $packageName, string $ref): array
    {
        $slug = PackageRepoSlug::resolve($packageName);
        $url = sprintf('%s/%s/%s/composer.json', self::RAW_GITHUB_BASE, $slug, $ref);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => self::TIMEOUT_SECONDS,
                'header' => "User-Agent: o3-shop/release-cli\r\nAccept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if ($body === false || strpos($statusLine, ' 200 ') === false) {
            throw new RawRepoFetchException(sprintf(
                'could not fetch %s (%s)',
                $url,
                $statusLine !== '' ? trim($statusLine) : 'no response'
            ));
        }
        $parsed = json_decode($body, true);
        if (!is_array($parsed)) {
            throw new RawRepoFetchException(sprintf(
                'could not parse %s: %s',
                $url,
                json_last_error_msg()
            ));
        }
        return $parsed;
    }
}
