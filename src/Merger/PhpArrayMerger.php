<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

use ColinODell\Indentation\Indentation;
use Symfony\Component\VarExporter\VarExporter;

final class PhpArrayMerger extends AbstractMerger
{
    public static function getName(): string
    {
        return 'php_array';
    }

    public function merge(array $file): void
    {
        if (!\array_key_exists('entries', $file)) {
            $this->io->write(sprintf(
                '<error>Error found in %s recipe: file of type "php_array" requires "entries" field.</>',
                $this->state->getCurrentPackage(),
            ));

            return;
        }

        $destinationPathname = sprintf(
            '%s/%s',
            $this->state->getProjectDirectory(),
            $this->state->replacePathPlaceholders($file['destination']),
        );
        $output = $this->filesystem->exists($destinationPathname) ? require($destinationPathname) : [];
        $changedCount = 0;

        foreach ($file['entries'] as $key => $value) {
            if (\array_key_exists($key, $output)) {
                if ($output[$key] !== $value && $this->state->getOverwrite()) {
                    $output[$key] = $value;
                    $changedCount++;
                }
            } else {
                $output[$key] = $value;
                $changedCount++;
            }
        }

        if ($changedCount === 0) {
            return;
        }

        $fileExists = $this->filesystem->exists($destinationPathname);
        $this->filesystem->mkdir(\dirname($destinationPathname), 0755);
        $this->filesystem->dumpFile($destinationPathname, $this->dump($output, $file['filters'] ?? []));

        $this->io->write(sprintf('%s file: %s', $fileExists ? 'Updated' : 'Created', $destinationPathname));
    }

    public function uninstall(array $file): void
    {
        if (!\array_key_exists('entries', $file)) {
            $this->io->write(sprintf(
                '<error>Error found in %s recipe: file of type "php_array" requires "entries" field.</>',
                $this->state->getCurrentPackage(),
            ));

            return;
        }

        $destinationPathname = sprintf(
            '%s/%s',
            $this->state->getProjectDirectory(),
            $this->state->replacePathPlaceholders($file['destination']),
        );

        if (!$this->filesystem->exists($destinationPathname)) {
            return;
        }

        $output = require($destinationPathname);

        foreach ($file['entries'] as $key => $value) {
            unset($output[$key]);
        }

        $this->filesystem->dumpFile($destinationPathname, $this->dump($output, $file['filters'] ?? []));

        $this->io->write(sprintf('Updated file: %s', $destinationPathname));
    }

    protected function dump(array $array, array $filters = []): string
    {
        $output = "<?php\n\nreturn [\n";

        foreach ($array as $key => $value) {
            $output .= '    ' . $this->applyFilters($filters['keys'] ?? [], "'" . $key . "'", $key) . ' => ';

            if (\is_array($value)) {
                $output .= $this->applyFilters(
                    $filters['values'] ?? [],
                    ltrim(Indentation::indent(
                        VarExporter::export($value),
                        new Indentation(4, Indentation::TYPE_SPACE),
                    )),
                    $value,
                );
            } else {
                $output .= $this->applyFilters($filters['values'] ?? [], VarExporter::export($value), $value);
            }

            $output .= ",\n";
        }

        $output .= "];\n";

        return $output;
    }
}
