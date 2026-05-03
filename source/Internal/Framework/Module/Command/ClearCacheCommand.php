<?php

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheCommand extends Command
{
    protected static $defaultName = 'oe:cache:clear';

    protected function configure()
    {
        $this->setDescription('Clears the application cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clearing cache...');

        $compileDir = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class)->getVar('sCompileDir');
        $this->deleteContents($compileDir);

        $output->writeln('Cache cleared successfully.');

        return 0;
    }

    /**
     * Empty cache contents but keep the directory structure intact. Removing
     * subdirectories caused subtle breakage: code paths that assume `tmp/smarty/`
     * (or per-module `tmp/<module>/`) exists would not recreate it before
     * writing — only `UtilsView::getSmartyDir()` does the lazy mkdir, and only
     * for that single path. After `oe:cache:clear`, the immediate next `phpunit`
     * run would race with whichever code path tried to write into a vanished
     * directory.
     *
     * Trade-off: empty subdirectories survive a clear. That is fine — the
     * filesystem cost is negligible and the structure is what code expects.
     */
    protected function deleteContents($path)
    {
        if (!is_dir($path)) {
            return;
        }
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }
            $fullPath = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($fullPath)) {
                $this->deleteContents($fullPath);
            } else {
                unlink($fullPath);
            }
        }
    }
}
