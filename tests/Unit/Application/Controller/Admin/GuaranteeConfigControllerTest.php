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
use OxidEsales\EshopCommunity\Application\Controller\Admin\GuaranteeConfigController;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for {@see GuaranteeConfigController} (EmpCo guarantee labels,
 * issue #219).
 *
 * Asserts the all-or-nothing save behaviour: an invalid placement value
 * rejects the entire save (no row persisted) and preserves submitted values
 * for the form re-render (`feedback_form-input-preservation.md`).
 */
class GuaranteeConfigControllerTest extends UnitTestCase
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

    public function testSaveAcceptedWithFooterPlacement(): void
    {
        $this->requestParams = [
            'blShowLegalGuaranteeNotice'     => '1',
            'blShowDurabilityGuaranteeLabel' => '0',
            'sLegalGuaranteeNoticePlacement' => 'footer',
        ];

        $controller = $this->makeController(true);
        $controller->save();

        $this->assertSame([], $controller->getValidationErrors());
        $this->assertCount(3, $this->savedConfVars, 'All three config rows must be persisted on success.');
        $this->assertSame(0, $this->errorsDisplayed);
        $this->assertFalse($controller->getNoQualifyingProductsHint());
    }

    public function testSaveAcceptedWithPagePlacement(): void
    {
        $this->requestParams = [
            'blShowLegalGuaranteeNotice'     => '0',
            'blShowDurabilityGuaranteeLabel' => '1',
            'sLegalGuaranteeNoticePlacement' => 'page',
        ];

        $controller = $this->makeController(true);
        $controller->save();

        $this->assertSame([], $controller->getValidationErrors());
        $byName = $this->indexBy($this->savedConfVars, 1);
        $this->assertSame('page', $byName['sLegalGuaranteeNoticePlacement'][2]);
    }

    public function testSaveRejectedWithInvalidPlacement(): void
    {
        $this->requestParams = [
            'blShowLegalGuaranteeNotice'     => '1',
            'blShowDurabilityGuaranteeLabel' => '1',
            'sLegalGuaranteeNoticePlacement' => 'sidebar',
        ];

        $controller = $this->makeController(true);
        $controller->save();

        $this->assertSame(
            'O3_GUARANTEE_ADMIN_VALIDATION_PLACEMENT_INVALID',
            $controller->getValidationErrors()['sLegalGuaranteeNoticePlacement'] ?? null
        );
        $this->assertSame([], $this->savedConfVars, 'No row may be persisted on rejection — all-or-nothing.');
        $this->assertSame(1, $this->errorsDisplayed);
    }

    public function testSubmittedValuesAreRetainedForReRenderOnRejection(): void
    {
        $this->requestParams = [
            'blShowLegalGuaranteeNotice'     => '1',
            'blShowDurabilityGuaranteeLabel' => '0',
            'sLegalGuaranteeNoticePlacement' => 'not-a-placement',
        ];

        $controller = $this->makeController(true);
        $controller->save();

        $submitted = $controller->getSubmittedValues();
        $this->assertTrue($submitted['blShowLegalGuaranteeNotice']);
        $this->assertFalse($submitted['blShowDurabilityGuaranteeLabel']);
        $this->assertSame('not-a-placement', $submitted['sLegalGuaranteeNoticePlacement']);
    }

    public function testNoQualifyingProductsHintSetWhenLabelOnButNoneQualify(): void
    {
        $this->requestParams = [
            'blShowLegalGuaranteeNotice'     => '1',
            'blShowDurabilityGuaranteeLabel' => '1',
            'sLegalGuaranteeNoticePlacement' => 'footer',
        ];

        $controller = $this->makeController(false);
        $controller->save();

        $this->assertSame([], $controller->getValidationErrors());
        $this->assertCount(3, $this->savedConfVars, 'Hint is informational only — save must still succeed.');
        $this->assertTrue($controller->getNoQualifyingProductsHint());
    }

    public function testNoQualifyingProductsHintNotSetWhenLabelSwitchIsOff(): void
    {
        $this->requestParams = [
            'blShowLegalGuaranteeNotice'     => '1',
            'blShowDurabilityGuaranteeLabel' => '0',
            'sLegalGuaranteeNoticePlacement' => 'footer',
        ];

        $controller = $this->makeController(false);
        $controller->save();

        $this->assertFalse($controller->getNoQualifyingProductsHint());
    }

    /**
     * The base AdminController constructor loads the active shop and reads
     * its name field, which doesn't exist in this unit-test environment.
     * Skip the constructor entirely — `save()` doesn't depend on what the
     * constructor sets up; it only reads from Registry which we mock.
     */
    private function makeController(bool $hasQualifyingProduct): GuaranteeConfigController
    {
        $controller = $this->getMockBuilder(GuaranteeConfigController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasQualifyingProduct'])
            ->getMock();
        $controller->method('hasQualifyingProduct')->willReturn($hasQualifyingProduct);

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
