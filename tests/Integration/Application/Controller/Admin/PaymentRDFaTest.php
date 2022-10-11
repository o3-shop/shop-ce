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

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Application\Controller\Admin;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Controller\Admin\PaymentRdfa;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use OxidEsales\TestingLibrary\UnitTestCase;

final class PaymentRDFaTest extends UnitTestCase
{
    use ContainerTrait;

    /** @var string */
    private $paymentId;
    /** @var string */
    private $descriptionInDefaultLanguage = 'description-in-default-language';
    /** @var string */
    private $descriptionInLanguage1 = 'description-in-lang-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPayment();
    }

    public function testRenderWithDefaultLanguage(): void
    {
        $this->setRequestParameter('oxid', $this->paymentId);
        /** @var PaymentRdfa $paymentRdfa */
        $paymentRdfa = oxNew(PaymentRdfa::class);

        $paymentRdfa->render();
        $paymentDescription = $paymentRdfa->getViewData()['edit']->getFieldData('OXDESC');

        $this->assertSame($this->descriptionInDefaultLanguage, $paymentDescription);
    }

    public function testRenderWithAvailableLanguage(): void
    {
        $availableLanguageId = 1;
        $this->setRequestParameter('oxid', $this->paymentId);
        /** @var PaymentRdfa $paymentRdfa */
        $paymentRdfa = oxNew(PaymentRdfa::class);
        $this->setProtectedClassProperty($paymentRdfa, '_iEditLang', $availableLanguageId);

        $paymentRdfa->render();
        $paymentDescription = $paymentRdfa->getViewData()['edit']->getFieldData('OXDESC');

        $this->assertSame($this->descriptionInLanguage1, $paymentDescription);
    }

    private function createPayment(): void
    {
        $this->paymentId = \OxidEsales\Eshop\Core\Registry::getUtilsObject()->generateUId();
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->insert('oxpayments')
        ->values([
            'OXID' => "\"$this->paymentId\"",
            'OXACTIVE' => true,
            'OXDESC' => "\"$this->descriptionInDefaultLanguage\"",
            'OXDESC_1' => "\"$this->descriptionInLanguage1\"",
        ]);
        $queryBuilder->execute();
    }
}
