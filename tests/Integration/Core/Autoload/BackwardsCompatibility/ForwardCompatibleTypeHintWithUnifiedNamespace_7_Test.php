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

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Autoload\BackwardsCompatibility;

class ForwardCompatibleTypeHintWithUnifiedNamespace_7_Test extends \PHPUnit\Framework\TestCase
{

    /**
     * Test the backwards compatibility with camel cased type hints
     */
    public function testForwardCompatibleTypeHintWithUnifiedNamespaceNamespace()
    {
        $object = new \OxidEsales\EshopCommunity\Application\Model\Article();
        /**
         * @param \OxidEsales\Eshop\Application\Model\Article $object
         */
        $functionWithTypeHint = function (\OxidEsales\Eshop\Application\Model\Article $object) {
            /** If the function was called successfully, the test would have failed */
            $this->fail(
                'Using instances of concrete classes is not expected to work when functions 
                 use type hints from the unified namespace'
            );
        };

        try {
            set_error_handler(
                function ($errno, $errstr, $errfile, $errline) {
                    if (E_RECOVERABLE_ERROR === $errno) {
                        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
                    }

                    return false;
                }
            );
            /**
             * We expect a catchable fatal error here.
             * PHP 5.6 and PHP 7.0 will treat this error differently
             */
            $functionWithTypeHint($object);
        } catch (\ErrorException $exception) {
            /** For PHP 5.6 a custom error handler has been registered, which is capable to catch this error */
        } catch (\TypeError $exception) {
            /** As of PHP 7 a TypeError is thrown */
        } finally {
            // restore original error handler
            restore_error_handler();
        }
    }
}
