<?php

declare(strict_types=1);

namespace Williarin\Cook;

use Composer\Composer;

final class Options
{
    /** @var array<string, string> */
    private array $options = [];

    public function __construct(
        private Composer $composer
    ) {
        $this->initOptions();
    }

    public function all(): array
    {
        return $this->options;
    }

    public function get(string $option): ?string
    {
        return $this->options[$option] ?? null;
    }

    private function initOptions(): void
    {
        $extra = $this->composer->getPackage()
            ->getExtra();

        $this->options = array_merge([
            'bin-dir' => 'bin',
            'config-dir' => 'config',
            'src-dir' => 'src',
            'var-dir' => 'var',
            'public-dir' => 'public',
            'root-dir' => $extra['symfony']['root-dir'] ?? '.',
        ], $extra);
    }
}
