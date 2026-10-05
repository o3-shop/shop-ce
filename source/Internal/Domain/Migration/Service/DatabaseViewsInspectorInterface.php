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

namespace OxidEsales\EshopCommunity\Internal\Domain\Migration\Service;

/**
 * Inspects the generated database views (`oxv_*`). After a migration the
 * views must be regenerated (`oe-eshop-db_views_regenerate`); a database
 * with none, or with views that no longer resolve, is broken for the
 * storefront and admin. Mirrors the health surface of the view regenerator.
 */
interface DatabaseViewsInspectorInterface
{
    /**
     * Number of database views present in the current schema.
     */
    public function getViewCount(): int;

    /**
     * Whether a representative core view can actually be queried. Returns
     * false when the view is missing or its definition no longer resolves
     * (e.g. it references a table that a migration renamed or dropped).
     */
    public function coreViewIsQueryable(): bool;
}
