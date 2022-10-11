<?php declare(strict_types=1);
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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Transition\Smarty\Extension;

use OxidEsales\EshopCommunity\Internal\Framework\Smarty\Extension\SmartyDefaultTemplateHandler;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Loader\TemplateLoaderInterface;

class SmartyDefaultTemplateHandlerTest extends \PHPUnit\Framework\TestCase
{
    private $resourceName = 'smartyTemplate.tpl';
    private $resourceContent = "The new contents of the file";
    private $resourceTimeStamp = 1;

    /**
     * If it is not template file or it is not valid,
     * content and timestamp should not be changed.
     *
     * @dataProvider smartyDefaultTemplateHandlerDataProvider
     *
     * @param string $resourceType  The Type of the given file.
     * @param mixed  $givenResource The template to test.
     */
    public function testSmartyDefaultTemplateHandlerWithoutExistingFile($resourceType, $givenResource)
    {
        $resourceName = $this->resourceName;
        $resourceContent = $this->resourceContent;
        $resourceTimestamp = $this->resourceTimeStamp;

        $smarty = new \Smarty();
        $smarty->left_delimiter = '[{';
        $smarty->right_delimiter = '}]';

        $handler = $this->getSmartyDefaultTemplateHandler($givenResource);
        $return = $handler->handleTemplate(
            $resourceType,
            $resourceName,
            $resourceContent,
            $resourceTimestamp,
            $smarty
        );

        $this->assertFalse($return);
        $this->assertSame($this->resourceContent, $resourceContent);
        $this->assertSame($this->resourceTimeStamp, $resourceTimestamp);
    }

    public function smartyDefaultTemplateHandlerDataProvider()
    {
        return [
            ['content', $this->resourceName],
            ['file', $this->resourceName],
            ['file', $this->getTemplateDirectory()]
        ];
    }

    public function testSmartyDefaultTemplateHandler()
    {
        $resourceName = $this->resourceName;
        $resourceContent = $this->resourceContent;
        $resourceTimestamp = $this->resourceTimeStamp;

        $smarty = new \Smarty();
        $smarty->left_delimiter = '[{';
        $smarty->right_delimiter = '}]';

        $template = $this->getTemplateDirectory() . $resourceName;
        $returnContent = '[{assign var=\'title\' value=$title|default:\'Hello OXID!\'}]'."\n".'[{$title}]';

        $handler = $this->getSmartyDefaultTemplateHandler($template);
        $return = $handler->handleTemplate(
            'file',
            $resourceName,
            $resourceContent,
            $resourceTimestamp,
            $smarty
        );

        $this->assertTrue($return);
        $this->assertSame($returnContent, $resourceContent);
        $this->assertSame(filemtime($template), $resourceTimestamp);
    }

    private function getSmartyDefaultTemplateHandler($template)
    {
        $templateLoaderMock = $this
        ->getMockBuilder(TemplateLoaderInterface::class)
        ->getMock();

        $templateLoaderMock
            ->method('getPath')
            ->willReturn($template);

        return new SmartyDefaultTemplateHandler($templateLoaderMock);
    }

    private function getTemplateDirectory()
    {
        return __DIR__ . '/../Fixtures/';
    }
}
