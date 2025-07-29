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

        $DIR = __DIR__;
        $tmp = $DIR . '/../../../../tmp';
        $smarty = $DIR . '/../../../../tmp/smarty';

        $this->deleteContents($tmp);
        $this->deleteContents($smarty);
        // Your cache-clearing logic here
        $output->writeln('Cache cleared successfully.');
    }

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
                rmdir($fullPath);
            } else {
                unlink($fullPath);
            }
        }
    }
}
