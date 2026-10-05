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

namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Revocation;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Model\O3Revocation;
use OxidEsales\TestingLibrary\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for the §356a revocation email methods on {@see Email}.
 *
 * Focus: the recipient-resolution branching in `sendRevocationEmailToOperator()`
 * — that is the genuinely-feature-specific logic. The actual mail rendering
 * and SMTP send paths are end-to-end concerns covered by the manual smoke
 * test against Mailpit (task 4.9 / 5.7 in the §356a task list).
 *
 * Strategy: partially-mock {@see Email} by overriding `send()` so the test
 * can assert on the recipient/subject set up to that point without actually
 * calling out to SMTP.
 */
class EmailRevocationTest extends UnitTestCase
{
    /** @var array<string, mixed> */
    private array $configValues = [];

    /** @var string */
    private string $shopOrderEmail = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configValues = [
            'sRevocationOperatorEmail' => '',
            'blRevocationNotifyOperator' => true,
        ];
        $this->shopOrderEmail = '';

        Registry::set('logger', new NullLogger());

        $config = $this->createMock(Config::class);
        $config->method('getConfigParam')->willReturnCallback(
            fn ($name, $default = null) => $this->configValues[$name] ?? $default
        );
        Registry::set(Config::class, $config);
    }

    public function testOperatorEmailSkippedWhenBothAddressesAreEmpty(): void
    {
        $this->configValues['sRevocationOperatorEmail'] = '';
        $this->shopOrderEmail = '';

        $email = $this->makeEmail();
        $result = $email->sendRevocationEmailToOperator($this->makeSubmission());

        $this->assertFalse(
            $result,
            'When both sRevocationOperatorEmail and oxshops.oxorderemail are empty, the operator '
            . 'email must skip cleanly (return false) without attempting to send.'
        );
        $this->assertSame(
            0,
            $email->sendCallCount,
            'send() must not be called when no recipient is resolvable.'
        );
    }

    public function testOperatorEmailUsesConfiguredAddressWhenValid(): void
    {
        $this->configValues['sRevocationOperatorEmail'] = 'ops@example.com';
        $this->shopOrderEmail = 'shop@example.com';

        $email = $this->makeEmail();
        $email->sendRevocationEmailToOperator($this->makeSubmission());

        $this->assertContains(
            'ops@example.com',
            $email->capturedRecipients,
            'When sRevocationOperatorEmail is set and valid, it MUST be the recipient.'
        );
        $this->assertNotContains(
            'shop@example.com',
            $email->capturedRecipients,
            'When sRevocationOperatorEmail is valid, the fallback to oxshops.oxorderemail MUST NOT happen.'
        );
    }

    public function testOperatorEmailFallsBackToOxOrderEmailWhenConfiguredIsEmpty(): void
    {
        $this->configValues['sRevocationOperatorEmail'] = '';
        $this->shopOrderEmail = 'shop@example.com';

        $email = $this->makeEmail();
        $email->sendRevocationEmailToOperator($this->makeSubmission());

        $this->assertContains(
            'shop@example.com',
            $email->capturedRecipients,
            'Empty sRevocationOperatorEmail must fall back to oxshops.oxorderemail.'
        );
    }

    public function testOperatorEmailFallsBackWhenConfiguredIsSyntacticallyInvalid(): void
    {
        $this->configValues['sRevocationOperatorEmail'] = 'not-an-email';
        $this->shopOrderEmail = 'shop@example.com';

        $email = $this->makeEmail();
        $email->sendRevocationEmailToOperator($this->makeSubmission());

        $this->assertContains(
            'shop@example.com',
            $email->capturedRecipients,
            'A non-FILTER_VALIDATE_EMAIL value in sRevocationOperatorEmail must trigger the fallback.'
        );
    }

    public function testCustomerEmailRecipientIsSubmissionEmail(): void
    {
        $email = $this->makeEmail();
        $submission = $this->makeSubmission('maria@example.com', 'Maria Schmidt');

        $email->sendRevocationEmailToCustomer($submission);

        $this->assertContains(
            'maria@example.com',
            $email->capturedRecipients,
            'Customer email recipient must be the submission email — never matched against any other source.'
        );
    }

    public function testCustomerEmailReturnsFalseWhenUnderlyingSendFails(): void
    {
        $email = $this->makeEmail();
        $email->forceSendFailure = true;

        $result = $email->sendRevocationEmailToCustomer($this->makeSubmission());

        $this->assertFalse(
            $result,
            'When the underlying SMTP send fails, sendRevocationEmailToCustomer must propagate '
            . 'false so the controller can flag the row "send failed" and the manual resend path applies.'
        );
    }

    public function testOperatorEmailReturnsFalseWhenUnderlyingSendFails(): void
    {
        $this->configValues['sRevocationOperatorEmail'] = 'ops@example.com';

        $email = $this->makeEmail();
        $email->forceSendFailure = true;

        $result = $email->sendRevocationEmailToOperator($this->makeSubmission());

        $this->assertFalse(
            $result,
            'When the underlying SMTP send fails, sendRevocationEmailToOperator must propagate false.'
        );
    }

    private function makeSubmission(string $email = 'consumer@example.com', string $name = 'Maria'): O3Revocation
    {
        $submission = oxNew(O3Revocation::class);
        $submission->setId('_revtest_email');
        $submission->assign([
            'oxshopid' => 1,
            'oxlang' => 0,
            'oxname' => $name,
            'oxorderident' => 'ORDER-1',
            'oxemail' => $email,
        ]);
        return $submission;
    }

    private function makeEmail(): EmailRevocationTestSpy
    {
        return new EmailRevocationTestSpy($this->shopOrderEmail);
    }
}

/**
 * Test spy: replaces {@see Email::send()} with a no-op recorder, and stubs
 * the shop / renderer / SMTP setup so the recipient-resolution logic in
 * `sendRevocationEmailTo*()` runs to completion without actually contacting
 * SMTP. Lives here (not in its own file) because it's only used by the
 * containing test class.
 */
class EmailRevocationTestSpy extends Email
{
    public int $sendCallCount = 0;
    /** @var array<int, string> recipients passed through setRecipient() */
    public array $capturedRecipients = [];
    public bool $forceSendFailure = false;
    private string $stubbedOrderEmail;

    public function __construct(string $stubbedOrderEmail)
    {
        parent::__construct();
        $this->stubbedOrderEmail = $stubbedOrderEmail;
    }

    public function send()
    {
        $this->sendCallCount++;
        return !$this->forceSendFailure;
    }

    public function setRecipient($address = null, $name = '')
    {
        $this->capturedRecipients[] = (string) $address;
        return parent::setRecipient($address, $name);
    }

    // The shop helper is normally protected and loads from DB. Stub it
    // with an inline shop carrying just the fields the methods read.
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    protected function _getShop($langId = null, $shopId = null)
    {
        $shop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
        $shop->oxshops__oxname = new Field('Test Shop', Field::T_RAW);
        $shop->oxshops__oxorderemail = new Field($this->stubbedOrderEmail, Field::T_RAW);
        $shop->oxshops__oxowneremail = new Field('owner@example.com', Field::T_RAW);
        return $shop;
    }

    // The methods below are no-ops so the test bypasses SMTP/renderer setup
    // — the test is about recipient resolution, not actual mail formatting.
    protected function _setMailParams($shop = null)
    {
    }

    public function setSmtp($shop = null)
    {
    }
    // phpcs:enable PSR2.Methods.MethodDeclaration.Underscore

    public function getRenderer()
    {
        // Minimal stub: exists() returns true, renderTemplate returns a
        // placeholder string. The test doesn't assert on rendered content.
        return new class () {
            public function exists(string $tpl): bool
            {
                return true;
            }

            public function renderTemplate(string $tpl, array $viewData): string
            {
                return '[stubbed: ' . $tpl . ']';
            }
        };
    }
}
