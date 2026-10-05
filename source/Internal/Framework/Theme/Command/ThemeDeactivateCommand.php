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

namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Command;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\CannotDeactivateThemeException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command deactivates a theme by theme id.
 */
class ThemeDeactivateCommand extends Command
{
    public const MESSAGE_THEME_DEACTIVATED = 'Theme - "%s" was deactivated.';

    public const MESSAGE_THEME_NOT_ACTIVE = 'Theme - "%s" is not active.';

    public const MESSAGE_THEME_NOT_FOUND = 'Theme - "%s" not found.';

    public const ARGUMENT_THEME_ID = 'theme-id';

    private ThemeBridgeInterface $themeBridge;

    public function __construct(ThemeBridgeInterface $themeBridge)
    {
        parent::__construct(null);
        $this->themeBridge = $themeBridge;
    }

    protected function configure()
    {
        $this->setDescription('Deactivates a theme.')
            ->addArgument(static::ARGUMENT_THEME_ID, InputArgument::REQUIRED, 'Theme ID')
            ->setHelp(
                'Deactivates a custom (child) theme, reverting the storefront to its parent base theme. '
                . 'Base themes cannot be deactivated; activate another theme instead.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $themeId = (string) $input->getArgument(static::ARGUMENT_THEME_ID);

        try {
            $deactivated = $this->themeBridge->deactivate($themeId);
            if ($deactivated) {
                $output->writeln('<info>' . sprintf(static::MESSAGE_THEME_DEACTIVATED, $themeId) . '</info>');
            } else {
                $output->writeln('<info>' . sprintf(static::MESSAGE_THEME_NOT_ACTIVE, $themeId) . '</info>');
            }
            return 0;
        } catch (ThemeNotFoundException $exception) {
            $output->writeln('<error>' . sprintf(static::MESSAGE_THEME_NOT_FOUND, $themeId) . '</error>');
            return 1;
        } catch (CannotDeactivateThemeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return 1;
        }
    }
}
