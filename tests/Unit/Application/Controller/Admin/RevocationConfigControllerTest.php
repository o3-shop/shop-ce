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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Application\Controller\Admin\RevocationConfigController;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\MissingAsset;
use OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator\RevocationTemplateValidator;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for the cross-field validation rule on
 * {@see RevocationConfigController}.
 *
 * Asserts the all-or-nothing save behaviour from spec D11 / spec
 * "Three admin configuration switches plus operator email":
 *   - notify=1 + email empty/invalid → reject the entire save
 *   - notify=0 + any email value → save succeeds
 *   - notify=1 + valid email → save succeeds
 *
 * Side-effect: tracks calls to `Config::saveShopConfVar()` to assert
 * "no row touched on rejection" without poking the actual DB.
 */
class RevocationConfigControllerTest extends UnitTestCase
{
    /** @var array<string,mixed> */
    private array $requestParams = [];

    /** @var array<int,array{string,string,mixed}> tally of saveShopConfVar(type, name, value) calls */
    private array $savedConfVars = [];

    /** @var int how many times UtilsView::addErrorToDisplay was invoked */
    private int $errorsDisplayed = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestParams = [];
        $this->savedConfVars = [];
        $this->errorsDisplayed = 0;
        $this->mockRegistry();
    }

    public function testSaveAcceptedWhenNotifyOffAndEmailEmpty(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '0',
            'sRevocationOperatorEmail'   => '',
        ];

        $controller = $this->makeController();
        $controller->save();

        $this->assertSame([], $controller->getValidationErrors());
        $this->assertCount(4, $this->savedConfVars, 'All four config rows must be persisted on success.');
        $this->assertSame(0, $this->errorsDisplayed);
    }

    public function testSaveAcceptedWhenNotifyOffAndEmailFilled(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '0',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '0',
            'sRevocationOperatorEmail'   => 'whatever-value', // not validated when notify=0
        ];

        $controller = $this->makeController();
        $controller->save();

        $this->assertSame([], $controller->getValidationErrors());
        $this->assertCount(4, $this->savedConfVars);
    }

    public function testSaveAcceptedWhenNotifyOnAndEmailValid(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '1',
            'sRevocationOperatorEmail'   => 'ops@example.com',
        ];

        $controller = $this->makeController();
        $controller->save();

        $this->assertSame([], $controller->getValidationErrors());
        $this->assertCount(4, $this->savedConfVars);
        // Verify each persisted value with type matches.
        $byName = $this->indexBy($this->savedConfVars, 1);
        $this->assertSame('1', $byName['blRevocationNotifyOperator'][2]);
        $this->assertSame('ops@example.com', $byName['sRevocationOperatorEmail'][2]);
    }

    public function testSaveRejectedAllOrNothingWhenNotifyOnAndEmailEmpty(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '1',
            'blRevocationNotifyOperator' => '1',
            'sRevocationOperatorEmail'   => '',
        ];

        $controller = $this->makeController();
        $controller->save();

        $this->assertSame(
            'O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED',
            $controller->getValidationErrors()['sRevocationOperatorEmail'] ?? null
        );
        $this->assertSame(
            [],
            $this->savedConfVars,
            'No row may be persisted on rejection — all-or-nothing per D11.'
        );
        $this->assertSame(1, $this->errorsDisplayed);
    }

    public function testSaveRejectedAllOrNothingWhenNotifyOnAndEmailIsSyntacticallyInvalid(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '1',
            'sRevocationOperatorEmail'   => 'not-an-email',
        ];

        $controller = $this->makeController();
        $controller->save();

        $this->assertSame(
            'O3_REVOCATION_VALIDATION_EMAIL_FORMAT',
            $controller->getValidationErrors()['sRevocationOperatorEmail'] ?? null
        );
        $this->assertSame([], $this->savedConfVars);
        $this->assertSame(1, $this->errorsDisplayed);
    }

    public function testSaveTreatsWhitespaceOnlyEmailAsEmpty(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '1',
            'sRevocationOperatorEmail'   => "   \t  ",
        ];

        $controller = $this->makeController();
        $controller->save();

        $this->assertSame(
            'O3_REVOCATION_VALIDATION_OPERATOR_EMAIL_REQUIRED',
            $controller->getValidationErrors()['sRevocationOperatorEmail'] ?? null
        );
    }

    public function testTemplateGateRejectsActivationWhenAssetsAreMissing(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '1',
            'sRevocationOperatorEmail'   => 'ops@example.com',
        ];
        $missing = [
            new MissingAsset(
                MissingAsset::TYPE_PAGE_TEMPLATE,
                '/path/to/revocation.tpl',
                null,
                'Install the missing page template under the active theme.'
            ),
        ];

        $controller = $this->makeController($missing);
        $controller->save();

        $this->assertSame(
            [],
            $this->savedConfVars,
            'No row may be persisted when the template-presence gate rejects (all-or-nothing per D11).'
        );
        $this->assertSame(
            $missing,
            $controller->getMissingAssets(),
            'The missing-asset list must surface for the template re-render.'
        );
        $this->assertGreaterThanOrEqual(1, $this->errorsDisplayed);
    }

    public function testTemplateGateNotInvokedWhenFeatureRemainsOff(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '0',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '0',
            'sRevocationOperatorEmail'   => '',
        ];
        // Even though our injected validator would report missing assets,
        // it must NOT be consulted — the feature is staying off.
        $missing = [
            new MissingAsset(MissingAsset::TYPE_PAGE_TEMPLATE, '/whatever.tpl', null, 'unused'),
        ];
        $controller = $this->makeController($missing);
        $controller->save();

        $this->assertCount(4, $this->savedConfVars, 'Save must succeed regardless of validator state when feature is off.');
        $this->assertSame([], $controller->getMissingAssets());
    }

    public function testSubmittedValuesAreRetainedForReRender(): void
    {
        $this->requestParams = [
            'blShowRevocationForm'       => '1',
            'blRevocationRequireLogin'   => '0',
            'blRevocationNotifyOperator' => '1',
            'sRevocationOperatorEmail'   => 'foo@', // invalid → triggers rejection
        ];

        $controller = $this->makeController();
        $controller->save();

        // The form-input-preservation rule (shared memory) requires that
        // the rejected save expose the submitted values to the template
        // re-render so the user does not have to re-type.
        $submitted = $controller->getSubmittedValues();
        $this->assertTrue($submitted['blShowRevocationForm']);
        $this->assertFalse($submitted['blRevocationRequireLogin']);
        $this->assertTrue($submitted['blRevocationNotifyOperator']);
        $this->assertSame('foo@', $submitted['sRevocationOperatorEmail']);
    }

    /**
     * The base AdminController constructor loads the active shop and reads
     * its name field, which doesn't exist in this unit-test environment.
     * Skip the constructor entirely — `save()` doesn't depend on what the
     * constructor sets up; it only reads from Registry which we mock.
     *
     * @param MissingAsset[] $missingAssets — what the injected validator returns
     */
    private function makeController(array $missingAssets = []): RevocationConfigController
    {
        $controller = (new \ReflectionClass(RevocationConfigController::class))->newInstanceWithoutConstructor();
        $validator = $this->createMock(RevocationTemplateValidator::class);
        $validator->method('validate')->willReturn($missingAssets);
        $controller->setTemplateValidator($validator);
        return $controller;
    }

    private function mockRegistry(): void
    {
        Registry::set('logger', new NullLogger());

        $request = $this->createMock(Request::class);
        $request->method('getRequestParameter')->willReturnCallback(
            fn ($name, $default = null) => $this->requestParams[$name] ?? $default
        );
        $request->method('getRequestEscapedParameter')->willReturnCallback(
            fn ($name, $default = null) => $this->requestParams[$name] ?? $default
        );
        Registry::set(Request::class, $request);

        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn ($name, $default = null) => $this->requestParams[$name] ?? $default
        );
        $config->method('saveShopConfVar')->willReturnCallback(
            function ($type, $name, $value, $shopId = null, $module = '') {
                $this->savedConfVars[] = [(string) $type, (string) $name, $value];
            }
        );
        Registry::set(Config::class, $config);

        $utilsView = $this->createMock(UtilsView::class);
        $utilsView->method('addErrorToDisplay')->willReturnCallback(
            function () {
                $this->errorsDisplayed++;
            }
        );
        Registry::set(UtilsView::class, $utilsView);
    }

    /**
     * @param array<int,array> $rows
     * @return array<string,array>
     */
    private function indexBy(array $rows, int $columnIndex): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row[$columnIndex]] = $row;
        }
        return $indexed;
    }
}
