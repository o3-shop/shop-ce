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

namespace OxidEsales\EshopCommunity\Internal\Domain\Revocation\TemplateValidator;

use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `bin/oe-console o3:check-templates`
 *
 * Reports the per-asset list that the admin save handler / template-presence
 * gate would produce, so an operator can verify their installation without
 * going through the admin form.
 *
 * Command name is **deliberately feature-neutral** — it does not say
 * "revocation". When a future feature gains its own template-presence
 * requirements, this command becomes a registry of validators (one per
 * feature). Today it dispatches to `RevocationTemplateValidator` only;
 * the registry-of-validators refactor is YAGNI for now (per spec D11).
 */
class CheckTemplatesCommand extends Command
{
    protected static $defaultName = 'o3:check-templates';

    private RevocationTemplateValidator $validator;

    public function __construct(RevocationTemplateValidator $validator)
    {
        parent::__construct();
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Verify that every asset (templates + translations) the §356a '
            . 'electronic revocation feature needs is in place for the active '
            . 'shop, theme, and active languages. Future features may register '
            . 'additional checks under the same command name.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Registry::getConfig();
        $shopId = (int) $config->getShopId();
        $themeId = $this->resolveActiveThemeId($config);
        $activeLangIds = $this->resolveActiveLangIds();

        $output->writeln("Checking revocation assets for shop=$shopId theme=$themeId languages="
            . json_encode($activeLangIds) . '...');

        $missing = $this->validator->validate($shopId, $themeId, $activeLangIds);

        if ($missing === []) {
            $output->writeln('<info>OK — all revocation assets present.</info>');
            return 0;
        }

        $output->writeln('<error>Missing assets — ' . count($missing) . ' issue(s):</error>');
        foreach ($missing as $asset) {
            $langTag = $asset->getLangId() === null ? '' : ' [lang ' . $asset->getLangId() . ']';
            $output->writeln(
                '  - [' . $asset->getAssetType() . ']'
                . $langTag
                . ' ' . $asset->getExpectedPath()
            );
            $output->writeln('      ' . $asset->getRemediationHint());
        }
        return 1;
    }

    /**
     * Resolve the currently-active storefront theme. OXID stores it in
     * `oxconfig` as `sTheme`; falls back to "wave" if for some reason
     * unset (the most permissive assumption — the validator will then
     * report "missing in wave" if the assets aren't there either).
     */
    private function resolveActiveThemeId(\OxidEsales\Eshop\Core\Config $config): string
    {
        $custom = (string) $config->getConfigParam('sCustomTheme');
        if ($custom !== '') {
            return $custom;
        }
        $main = (string) $config->getConfigParam('sTheme');
        return $main !== '' ? $main : 'wave';
    }

    /**
     * Active language IDs from the shop config. Falls back to language 0
     * (German) when `aLanguageParams` is unparseable — the validator
     * will then check at least one language and the operator sees
     * coherent output.
     *
     * @return int[]
     */
    private function resolveActiveLangIds(): array
    {
        $params = Registry::getConfig()->getConfigParam('aLanguageParams');
        if (!is_array($params) || $params === []) {
            return [0];
        }
        $ids = [];
        foreach ($params as $entry) {
            if (is_array($entry) && isset($entry['active']) && (int) $entry['active'] === 1) {
                $ids[] = isset($entry['baseId']) ? (int) $entry['baseId'] : 0;
            }
        }
        return $ids === [] ? [0] : $ids;
    }
}
