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

namespace OxidEsales\EshopCommunity\Tests\Acceptance\Frontend;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\ConfigFile;
use OxidEsales\Facts\Facts;

class ContactFormFrontendTest extends \OxidEsales\EshopCommunity\Tests\Acceptance\FlowThemeTestCase
{
    private $requiredClass = 'req';

    private $contactUrl = 'index.php?cl=contact';

    private $emailInputFieldXpathLocator = '//*[@id="contactEmail"]';

    private $emailLabelXpathLocator = '//label[@for="contactEmail"]';

    private $configuredRequiredInputFieldXpathLocator = '//*[@id="editval[oxuser__oxfname]"]';

    private $configuredRequiredFieldLabelXpathLocator = '//label[@for="editval[oxuser__oxfname]"]';

    /**
     * @group flow-theme
     */
    public function testContactFormRequiresEmailFieldToBeFilledWithoutConfiguration()
    {
        $this->openContactForm();

        $this->assertFieldIsRequired(
            $this->emailInputFieldXpathLocator,
            $this->emailLabelXpathLocator
        );

        $this->assertFieldIsNotRequired(
            $this->configuredRequiredInputFieldXpathLocator,
            $this->configuredRequiredFieldLabelXpathLocator
        );
    }

    /**
     * @group flow-theme
     */
    public function testContactFormRequiresConfiguredFieldToBeFilled()
    {
        $this->insertRequiredFields(['firstName']);
        $this->openContactForm();

        $this->assertFieldIsRequired(
            $this->configuredRequiredInputFieldXpathLocator,
            $this->configuredRequiredFieldLabelXpathLocator
        );

        $this->assertFieldIsNotRequired(
            $this->emailInputFieldXpathLocator,
            $this->emailLabelXpathLocator
        );
    }

    private function openContactForm()
    {
        $this->openNewWindow($this->contactUrl);
    }

    private function insertRequiredFields(array $requiredFields)
    {
        $facts = new Facts();
        $configFile = new ConfigFile($facts->getSourcePath() . '/config.inc.php');
        $configKey = is_null($configFile->getVar('sConfigKey')) ? Config::DEFAULT_CONFIG_KEY : $configFile->getVar('sConfigKey');
        $rawValue = serialize($requiredFields);

        $query = "
        UPDATE `oxconfig`
        SET
          `OXVARVALUE` = ENCODE(?,?)
        WHERE `OXSHOPID`= 1
        AND   `OXVARNAME` = 'contactFormRequiredFields'
        ";

        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $database->execute($query, [$rawValue, $configKey]);
    }

    private function assertFieldIsNotRequired(string $notRequiredInputFieldLocator, string $notRequiredFieldLabelLocator)
    {
        $configuredInputField = $this->getElement($notRequiredInputFieldLocator);
        $this->assertNull(
            $configuredInputField->getAttribute('required'),
            'The input field ' . $notRequiredInputFieldLocator . ' does not have the attribute "required"'
        );

        $configuredLabel = $this->getElement($notRequiredFieldLabelLocator);
        $this->assertFalse(
            in_array($this->requiredClass, explode(' ', $configuredLabel->getAttribute('class'))),
            'The field label ' . $notRequiredFieldLabelLocator . ' is not marked as "required"'
        );
    }

    private function assertFieldIsRequired(string $requiredInputFieldLocator, string $requiredFieldLabelLocator)
    {
        $requiredInputField = $this->getElement($requiredInputFieldLocator);
        $this->assertTrue(
            $requiredInputField->hasAttribute('required'),
            'The input field ' . $requiredInputFieldLocator . ' has the attribute "required"'
        );

        $requiredLabel = $this->getElement($requiredFieldLabelLocator);
        $this->assertTrue(
            $requiredLabel->hasClass($this->requiredClass),
            'The field label ' . $requiredFieldLabelLocator . ' is marked as "required"'
        );
    }
}
