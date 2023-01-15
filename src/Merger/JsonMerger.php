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

        if (file_exists($destinationPathname)) {
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

        $fileExists = file_exists($destinationPathname);
        $this->filesystem->mkdir(\dirname($destinationPathname), 0755);
        file_put_contents($destinationPathname, json_encode($output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $this->io->write(sprintf('%s file: %s', $fileExists ? 'Updated' : 'Created', $destinationPathname));
    }
}
