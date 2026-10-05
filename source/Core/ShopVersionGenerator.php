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
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

namespace OxidEsales\EshopCommunity\Core;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * Composer post-install/post-update hook target.
 *
 * Writes source/Core/version.generated.php with the installed
 * shop-ce version so ShopVersion::tryGeneratedFile() can return it
 * without consulting composer at every request.
 */
class ShopVersionGenerator
{
    /**
     * Wired into composer scripts.post-install-cmd /
     * scripts.post-update-cmd. Silently does nothing when composer's
     * runtime API or the shop-ce package itself is unavailable —
     * ShopVersion's runtime chain still has Step 2 / Step 3.
     */
    public static function generate()
    {
        if (!class_exists(InstalledVersions::class)) {
            return;
        }
        try {
            $version = InstalledVersions::getPrettyVersion(ShopVersion::PACKAGE_NAME);
        } catch (OutOfBoundsException $e) {
            return;
        }
        if (!is_string($version) || $version === '') {
            return;
        }
        $contents = "<?php\n\nreturn " . var_export($version, true) . ";\n";
        @file_put_contents(ShopVersion::GENERATED_VERSION_FILE, $contents);
    }
}
