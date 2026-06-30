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

namespace OxidEsales\EshopCommunity\Tests\Unit\Application\Component\Captcha;

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Domain\Captcha\CaptchaServiceInterface;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Container\ContainerInterface;

class UserComponentCaptchaTest extends UnitTestCase
{
    /** @var string[] error idents passed to UtilsView::addErrorToDisplay */
    private array $shownErrors = [];

    /**
     * Build a UserComponent whose CAPTCHA service is forced to a given verify
     * result, with the CSRF challenge mocked to pass and displayed errors
     * captured into $this->shownErrors.
     */
    private function makeComponentWithCaptcha(bool $captchaResult): UserComponent
    {
        // CSRF check must pass so we actually reach the CAPTCHA gate.
        $session = $this->getMock(Session::class, ['checkSessionChallenge']);
        $session->method('checkSessionChallenge')->willReturn(true);
        \OxidEsales\Eshop\Core\Registry::set(Session::class, $session);

        // Capture the error idents the component tries to display.
        $this->shownErrors = [];
        $utilsView = $this->getMock(UtilsView::class, ['addErrorToDisplay']);
        $utilsView->method('addErrorToDisplay')->willReturnCallback(
            function ($error) {
                $this->shownErrors[] = $error;
                return null;
            }
        );
        \OxidEsales\Eshop\Core\Registry::set(UtilsView::class, $utilsView);

        $service = $this->createMock(CaptchaServiceInterface::class);
        $service->method('verifyForForm')->willReturn($captchaResult);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn ($id) => $id === CaptchaServiceInterface::class ? $service : null);

        // logout() is stubbed: registerUser()'s failure path calls it, and the real
        // logout() needs a parent controller the unit test doesn't set up. It is
        // irrelevant to the CAPTCHA gate under test.
        $component = $this->getMock(UserComponent::class, ['getContainer', 'logout']);
        $component->method('getContainer')->willReturn($container);

        return $component;
    }

    /**
     * Security regression: createUser() is the user-creation chokepoint reached
     * directly by the checkout forms via fnc=createuser. It MUST enforce the
     * CAPTCHA, otherwise the registration captcha is trivially bypassable by
     * posting fnc=createuser instead of fnc=registeruser.
     */
    public function testCreateUserBlockedWhenCaptchaFails(): void
    {
        $component = $this->makeComponentWithCaptcha(false);

        $this->assertFalse($component->createUser(), 'createUser() must abort when the CAPTCHA fails.');
        $this->assertContains(
            'O3_CAPTCHA_FAILED',
            $this->shownErrors,
            'createUser() must show the CAPTCHA failure error (fnc=createuser bypass guard).'
        );
    }

    /**
     * The dedicated registration page (fnc=registeruser) must stay blocked too.
     * It delegates to createUser(), so the same gate protects it.
     */
    public function testRegisterUserBlockedWhenCaptchaFails(): void
    {
        $component = $this->makeComponentWithCaptcha(false);

        $this->assertNotSame('register?success=1', $component->registerUser());
        $this->assertContains('O3_CAPTCHA_FAILED', $this->shownErrors);
    }
}
