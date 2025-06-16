<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

final class TextMerger extends AbstractMerger
{
    use TextMergerUninstallTrait;

    public static function getName(): string
    {
        return 'text';
    }

    public function merge(array $file): void
    {
        if (($input = $this->getSourceContent($file)) === null) {
            return;
        }

        $destinationPathname = $this->getDestinationRealPathname($file);
        $fileExists = $this->filesystem->exists($destinationPathname);
        $ifExistsPolicy = $file['if_exists'] ?? 'append';

        // 1. Handle ignore policy
        if ($fileExists && $ifExistsPolicy === 'ignore') {
            $this->io->write(sprintf('<info>File "%s" exists, ignoring.</info>', $destinationPathname));
            return;
        }

        $recipeBlock = $this->wrapRecipeId(rtrim($input, "\n"));

        // 2. Handle overwrite policy
        if ($ifExistsPolicy === 'overwrite') {
            $output = $recipeBlock;
            if ($fileExists && file_get_contents($destinationPathname) === $output) {
                return;
            }
        } else { // 3. Handle append policy (default)
            $output = $fileExists ? file_get_contents($destinationPathname) : '';
            $originalOutput = $output;

            if (preg_match(sprintf('/%s/s', preg_quote($this->getRecipeIdOpeningComment(), '/')), $output)) {
                if ($this->state->getOverwrite()) {
                    $output = preg_replace(
                        sprintf(
                            '/%s.*%s\n?/s',
                            preg_quote($this->getRecipeIdOpeningComment(), '/'),
                            preg_quote($this->getRecipeIdClosingComment(), '/')
                        ),
                        $recipeBlock,
                        $output
                    );
                }
            } else {
                $output = rtrim($output) . ($output ? "\n\n" : '') . $recipeBlock;
            }

            if ($originalOutput === $output) {
                return;
            }
        }

        $this->filesystem->mkdir(\dirname($destinationPathname), 0755);
        $this->filesystem->dumpFile($destinationPathname, $output);

        $this->io->write(
            sprintf(
                '%s file: %s',
                $fileExists && $ifExistsPolicy !== 'overwrite' ? 'Updated' : 'Created',
                $destinationPathname
            )
        );
    }
}
