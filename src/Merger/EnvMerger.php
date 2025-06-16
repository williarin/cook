<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

final class EnvMerger extends AbstractMerger
{
    use TextMergerUninstallTrait;

    public static function getName(): string
    {
        return 'env';
    }

    public function merge(array $file): void
    {
        if (($recipeContent = $this->getSourceContent($file)) === null) {
            return;
        }

        $destinationPathname = $this->getDestinationRealPathname($file);
        $destContent = $this->filesystem->exists($destinationPathname) ? file_get_contents($destinationPathname) : '';
        $originalDestContent = $destContent;

        $ownBlockPattern = sprintf(
            "/\n?%s.*%s\n?/simU",
            preg_quote($this->getRecipeIdOpeningComment(), '/'),
            preg_quote($this->getRecipeIdClosingComment(), '/')
        );
        $destContent = preg_replace($ownBlockPattern, '', $destContent);

        $recipeVars = $this->parseEnvVariables($recipeContent);
        $ifExistsPolicy = $file['if_exists'] ?? 'comment';

        if (!empty($recipeVars)) {
            $lines = explode("\n", $destContent);
            $newLines = [];
            $modified = false;

            foreach ($lines as $line) {
                $isDuplicate = false;
                if (trim($line) !== '' && !str_starts_with(trim($line), '#')) {
                    foreach ($recipeVars as $varName) {
                        if (preg_match('/^\s*(?:export\s+)?' . preg_quote($varName) . '\s*=/i', $line)) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                }

                if ($isDuplicate) {
                    $modified = true;
                    if ($ifExistsPolicy === 'comment') {
                        $newLines[] = '#' . $line;
                    }
                } else {
                    $newLines[] = $line;
                }
            }

            if ($modified) {
                $destContent = implode("\n", $newLines);
            }
        }

        $recipeBlock = $this->wrapRecipeId(rtrim($recipeContent, "\n"));
        $destContent = rtrim($destContent);
        if ($destContent !== '') {
            $destContent .= "\n\n";
        }
        $destContent .= $recipeBlock;

        if (trim($originalDestContent) === trim($destContent)) {
            return;
        }

        $fileExists = $this->filesystem->exists($destinationPathname);
        $this->filesystem->mkdir(\dirname($destinationPathname));
        $this->filesystem->dumpFile($destinationPathname, $destContent);

        $this->io->write(sprintf('%s file: %s', $fileExists ? 'Updated' : 'Created', $destinationPathname));
    }

    private function parseEnvVariables(string $content): array
    {
        $vars = [];
        preg_match_all('/^\s*(?:export\s+)?(\w+)\s*=/m', $content, $matches);

        if (!empty($matches[1])) {
            $vars = array_unique($matches[1]);
        }

        return $vars;
    }
}
