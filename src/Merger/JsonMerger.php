<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

use JsonException;

class JsonMerger extends AbstractMerger
{
    protected ?array $validSections = null;

    public static function getName(): string
    {
        return 'json';
    }

    public function merge(array $file): void
    {
        if (!\array_key_exists('entries', $file)) {
            $this->io->write(sprintf(
                '<error>Error found in %s recipe: file of type "json" requires "entries" field.</>',
                $this->state->getCurrentPackage(),
            ));

            return;
        }

        if (\array_key_exists('valid_sections', $file)) {
            $this->validSections = $file['valid_sections'];
        }

        $file['entries'] = array_intersect_key($file['entries'], $this->validSections ?? $file['entries']);
        $destinationPathname = $this->getDestinationRealPathname($file);
        $output = [];

        if ($this->filesystem->exists($destinationPathname)) {
            try {
                $output = json_decode(trim(file_get_contents($destinationPathname)), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->io->write(sprintf(
                    '<error>Error found in %s recipe: invalid JSON in file "%s". Unable to merge.</>',
                    $this->state->getCurrentPackage(),
                    $file['destination'],
                ));

                return;
            }
        }

        $changedCount = 0;

        foreach ($file['entries'] as $section => $value) {
            if (\array_key_exists($section, $output)) {
                if ($output[$section] !== $value) {
                    if (\is_array($value)) {
                        if (array_intersect_key($output[$section], $value) === [] || $this->state->getOverwrite()) {
                            $output[$section] = array_merge($output[$section], $value);
                            $changedCount++;
                        }
                    } else {
                        $output[$section] = $value;
                        $changedCount++;
                    }
                }
            } else {
                $output[$section] = $value;
                $changedCount++;
            }
        }

        if ($changedCount === 0) {
            return;
        }

        $fileExists = $this->filesystem->exists($destinationPathname);
        $this->filesystem->mkdir(\dirname($destinationPathname), 0755);
        $this->filesystem->dumpFile(
            $destinationPathname,
            json_encode($output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        );

        $this->io->write(sprintf('%s file: %s', $fileExists ? 'Updated' : 'Created', $destinationPathname));
    }

    public function uninstall(array $file): void
    {
        if (!\array_key_exists('entries', $file)) {
            $this->io->write(sprintf(
                '<error>Error found in %s recipe: file of type "json" requires "entries" field.</>',
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

        try {
            $output = json_decode(trim(file_get_contents($destinationPathname)), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->io->write(sprintf(
                '<error>Error found in %s recipe: invalid JSON in file "%s". Unable to uninstall.</>',
                $this->state->getCurrentPackage(),
                $file['destination'],
            ));

            return;
        }

        foreach ($file['entries'] as $section => $value) {
            if (\array_key_exists($section, $output)) {
                if (\is_array($value)) {
                    if (!empty($keysToRemove = array_intersect_key($output[$section], $value))) {
                        foreach ($keysToRemove as $k => $v) {
                            unset($output[$section][$k]);
                        }

                        if (empty($output[$section])) {
                            unset($output[$section]);
                        }
                    }
                } else {
                    unset($output[$section]);
                }
            } else {
                unset($output[$section]);
            }
        }

        $this->filesystem->dumpFile(
            $destinationPathname,
            json_encode($output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        );

        $this->io->write(sprintf('Updated file: %s', $destinationPathname));
    }
}
