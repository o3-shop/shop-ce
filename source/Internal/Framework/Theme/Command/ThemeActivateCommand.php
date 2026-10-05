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

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\ThemeBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Theme\Exception\ThemeNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command activates a theme by theme id.
 */
class ThemeActivateCommand extends Command
{
    public const MESSAGE_THEME_ACTIVATED = 'Theme - "%s" was activated.';

    public const MESSAGE_THEME_NOT_FOUND = 'Theme - "%s" not found.';

    public const MESSAGE_NO_THEME_CONFIGURED =
        'No theme id was given and no active theme is configured.';

    public const ARGUMENT_THEME_ID = 'theme-id';

    private ThemeBridgeInterface $themeBridge;

    public function __construct(ThemeBridgeInterface $themeBridge)
    {
        parent::__construct(null);
        $this->themeBridge = $themeBridge;
    }

    protected function configure()
    {
        $this->setDescription('Activates a theme.')
            ->addArgument(
                static::ARGUMENT_THEME_ID,
                InputArgument::OPTIONAL,
                'Theme ID (defaults to the configured active theme)'
            )
            ->setHelp(
                'Activates a theme by ID, writing its theme.php defaults to the shop configuration. '
                . 'If no ID is given, the currently configured theme (sCustomTheme or sTheme) is re-activated.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $themeId = (string) $input->getArgument(static::ARGUMENT_THEME_ID);

        if ($themeId === '') {
            $themeId = $this->themeBridge->getActiveThemeId();
            if ($themeId === '') {
                $output->writeln('<error>' . static::MESSAGE_NO_THEME_CONFIGURED . '</error>');
                return 1;
            }
        }

        try {
            $this->themeBridge->activate($themeId);
            $output->writeln('<info>' . sprintf(static::MESSAGE_THEME_ACTIVATED, $themeId) . '</info>');
            return 0;
        } catch (ThemeNotFoundException $exception) {
            $output->writeln('<error>' . sprintf(static::MESSAGE_THEME_NOT_FOUND, $themeId) . '</error>');
            return 1;
        } catch (StandardException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return 1;
        }
    }
}
