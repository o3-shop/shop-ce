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

/*
 * Symfony backward-compatibility aliases.
 *
 * The Symfony 3.4 -> 5.4 upgrade removed/renamed a handful of classes. To keep
 * OXID 6.4-era modules (and legacy first-party code) written against the old
 * names working without touching every call site, we alias the old names to
 * their 5.4 replacements. Loaded via composer `autoload.files`, so it runs once
 * right after the Composer autoloader is registered and before any application
 * or module code executes.
 */

// symfony/event-dispatcher: the generic Event base class was removed in 5.0
// (replaced by the API-identical Symfony\Contracts\EventDispatcher\Event).
if (
    !class_exists(\Symfony\Component\EventDispatcher\Event::class, false)
    && class_exists(\Symfony\Contracts\EventDispatcher\Event::class)
) {
    class_alias(
        \Symfony\Contracts\EventDispatcher\Event::class,
        \Symfony\Component\EventDispatcher\Event::class
    );
}

// symfony/lock: the Factory class was removed in 5.0, renamed to LockFactory.
// The public API (createLock/acquire/release) is unchanged.
if (
    !class_exists(\Symfony\Component\Lock\Factory::class, false)
    && class_exists(\Symfony\Component\Lock\LockFactory::class)
) {
    class_alias(
        \Symfony\Component\Lock\LockFactory::class,
        \Symfony\Component\Lock\Factory::class
    );
}
