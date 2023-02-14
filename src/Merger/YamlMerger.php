<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

use Symfony\Component\Yaml\Yaml;

class YamlMerger extends AbstractMerger
{
    protected ?array $validSections = null;
    protected ?array $blankLineAfter = null;

    public static function getName(): string
    {
        return 'yaml';
    }

    public function merge(array $file): void
    {
        if (($input = $this->getSourceContent($file)) === null) {
            return;
        }

        if (\array_key_exists('valid_sections', $file)) {
            $this->validSections = $file['valid_sections'];
        }

        if (\array_key_exists('blank_line_after', $file)) {
            $this->blankLineAfter = $file['blank_line_after'];
        }

        $inputParsed = Yaml::parse($input);
        $destinationPathname = $this->getDestinationRealPathname($file);
        $output = $this->filesystem->exists($destinationPathname) ? file_get_contents($destinationPathname) : '';
        $updated = false;

        foreach ($this->validSections ?? array_keys($inputParsed) as $section) {
            if (
                !\array_key_exists($section, $inputParsed)
                || !preg_match('/(?:^|\n)' . $section . ':\s+?(\s{4}.*)(?:\n\w|$)/sU', $input, $inputMatch)
            ) {
                continue;
            }

            $recipeSection = $this->wrapRecipeId(trim($inputMatch[1], "\n"), false);

            if (
                preg_match(sprintf(
                    '/(?:(?:^|\n)' . $section . ':\n).*(%s.*%s)/smU',
                    preg_quote($this->getRecipeIdOpeningComment(), '/'),
                    preg_quote($this->getRecipeIdClosingComment(), '/'),
                ), $output, $recipeMatch)
            ) {
                if ($recipeMatch[1] !== trim($recipeSection) && $this->state->getOverwrite()) {
                    $output = str_replace($recipeMatch[1], trim($recipeSection), $output);
                    $updated = true;
                }
            } else {
                if (preg_match('/((?:^|\n)' . $section . ':\n)/', $output, $outputMatch)) {
                    $output = str_replace(
                        $outputMatch[1],
                        $outputMatch[1] . $recipeSection . $this->appendBlankLine($section),
                        $output,
                    );
                } else {
                    $output .= sprintf("\n%s:\n%s\n", $section, $recipeSection . $this->appendBlankLine($section));
                }

                $updated = true;
            }
        }

        if (!$updated) {
            return;
        }

        $output = preg_replace(
            '/(' . preg_quote($this->getRecipeIdClosingComment(), '/') . ')\n{3,}/',
            "$1\n\n",
            $output,
        );

        $fileExists = $this->filesystem->exists($destinationPathname);
        $this->filesystem->mkdir(\dirname($destinationPathname), 0755);
        $this->filesystem->dumpFile($destinationPathname, trim($output) . "\n");

        $this->io->write(sprintf('%s file: %s', $fileExists ? 'Updated' : 'Created', $destinationPathname));
    }

    public function uninstall(array $file): void
    {
        $destinationPathname = $this->getDestinationRealPathname($file);

        if (!$this->filesystem->exists($destinationPathname)) {
            return;
        }

        $content = file_get_contents($destinationPathname);
        $output = preg_replace(
            sprintf(
                '/^\s*%s.*%s\n+?/simU',
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

    private function appendBlankLine(string $section): string
    {
        return \in_array($section, $this->blankLineAfter ?? [], true) ? "\n" : '';
    }
}
