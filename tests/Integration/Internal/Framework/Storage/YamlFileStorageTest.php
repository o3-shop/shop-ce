<?php
declare(strict_types=1);

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

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Storage;

use OxidEsales\EshopCommunity\Internal\Framework\Storage\YamlFileStorage;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;

/**
 * @internal
 */
class YamlFileStorageTest extends TestCase
{
    use ContainerTrait;

    /**
     * @var resource
     */
    private $tempFileHandle;

    public function testSaving()
    {
        $testData = [
            'one' => [
                'two',
            ],
            'uno' => [
                'due',
            ],
        ];

        $yamlFileStorage = new YamlFileStorage(
            new FileLocator(),
            $this->getFilePath(),
            $this->getLockFactoryFromContainer(),
            $this->getFileSystemServiceFromContainer()
        );

        $yamlFileStorage->save($testData);

        $this->assertSame(
            $testData,
            $yamlFileStorage->get()
        );
    }

    public function testCreatesNewFileIfDoesNotExist()
    {
        $filePath = $this->getFilePath();
        unlink($filePath);

        $yamlFileStorage = new YamlFileStorage(
            new FileLocator(),
            $filePath,
            $this->getLockFactoryFromContainer(),
            $this->getFileSystemServiceFromContainer()
        );

        $yamlFileStorage->save(['testData']);

        $this->assertSame(
            ['testData'],
            $yamlFileStorage->get()
        );
    }

    public function testCreatesNewDirectoryAndFileIfDoNotExist()
    {
        $filePath = $this->getFilePath();
        unlink($filePath);

        $filePath = $this->getFilePath() . '/fileInNonExistentDirectory.yml';

        $yamlFileStorage = new YamlFileStorage(
            new FileLocator(),
            $filePath,
            $this->getLockFactoryFromContainer(),
            $this->getFileSystemServiceFromContainer()
        );

        $yamlFileStorage->save(['testData']);

        $this->assertSame(
            ['testData'],
            $yamlFileStorage->get()
        );
    }

    public function testStorageWithCorruptedFile()
    {
        $this->expectException(\Symfony\Component\Yaml\Exception\ParseException::class);
        $filePath = $this->getFilePath();
        $yamlContent = "\t";

        file_put_contents($filePath, $yamlContent);

        $yamlFileStorage = new YamlFileStorage(
            new FileLocator(),
            $filePath,
            $this->getLockFactoryFromContainer(),
            $this->getFileSystemServiceFromContainer()
        );

        $yamlFileStorage->get();
    }

    public function testStorageWithEmptyFile()
    {
        $filePath = $this->getFilePath();

        file_put_contents($filePath, '');

        $yamlFileStorage = new YamlFileStorage(
            new FileLocator(),
            $filePath,
            $this->getLockFactoryFromContainer(),
            $this->getFileSystemServiceFromContainer()
        );

        $this->assertSame(
            [],
            $yamlFileStorage->get()
        );
    }

    public function testEmptyYamlArrayThrowsNoError()
    {
        $yaml = '[]';

        file_put_contents($this->getFilePath(), $yaml);

        $yamlFileStorage = new YamlFileStorage(
            new FileLocator(),
            $this->getFilePath(),
            $this->getLockFactoryFromContainer(),
            $this->getFileSystemServiceFromContainer()
        );
        $parsedYaml = $yamlFileStorage->get();

        $this->assertEquals([], $parsedYaml);
    }

    /**
     * @return string
     */
    private function getFilePath(): string
    {
        if ($this->tempFileHandle === null) {
            $this->tempFileHandle = tmpfile();
        }

        return stream_get_meta_data($this->tempFileHandle)['uri'];
    }

    /**
     * @return Factory
     */
    private function getLockFactoryFromContainer(): Factory
    {
        /** @var Factory $lockFactory */
        $lockFactory = $this->get('oxid_esales.common.storage.flock_store_lock_factory');

        return $lockFactory;
    }

    private function getFileSystemServiceFromContainer(): Filesystem
    {
        return $this->get('oxid_esales.symfony.file_system');
    }
}
