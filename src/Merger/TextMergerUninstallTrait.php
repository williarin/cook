<?php

namespace Williarin\Cook\Merger;

use Composer\IO\IOInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property Filesystem $filesystem
 * @property IOInterface $io
 * @method getDestinationRealPathname(array $file)
 * @method getRecipeIdOpeningComment()
 * @method getRecipeIdClosingComment()
 */
trait TextMergerUninstallTrait
{
    public function uninstall(array $file): void
    {
        $destinationPathname = $this->getDestinationRealPathname($file);

        if (!$this->filesystem->exists($destinationPathname)) {
            return;
        }

        $content = file_get_contents($destinationPathname);
        $output = preg_replace(
            sprintf(
                '/%s.*%s\n/simU',
                preg_quote($this->getRecipeIdOpeningComment(), '/'),
                preg_quote($this->getRecipeIdClosingComment(), '/'),
            ),
            '',
            $content,
        );

        if ($content === $output) {
            return;
        }

        if (!trim($output)) {
            $this->filesystem->remove($destinationPathname);
            $this->io->write(sprintf('Removed file: %s', $destinationPathname));

            return;
        }

        $this->filesystem->dumpFile($destinationPathname, $output);
        $this->io->write(sprintf('Updated file: %s', $destinationPathname));
    }
}
