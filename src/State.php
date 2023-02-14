<?php

declare(strict_types=1);

namespace Williarin\Cook;

use Composer\Factory;
use Symfony\Component\Filesystem\Filesystem;

final class State implements StateInterface
{
    private ?string $currentPackage = null;
    private bool $overwrite = false;
    private ?string $composerFile = null;
    private array $recipes = [];

    public function __construct(
        private Filesystem $filesystem,
        private Options $options
    ) {
    }

    public function getCurrentPackage(): ?string
    {
        return $this->currentPackage;
    }

    public function setCurrentPackage(?string $currentPackage): self
    {
        $this->currentPackage = $currentPackage;

        return $this;
    }

    public function getOverwrite(): bool
    {
        return $this->overwrite;
    }

    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    public function getProjectDirectory(): string
    {
        if ($this->composerFile === null) {
            $this->composerFile = \dirname(Factory::getComposerFile());
        }

        return $this->composerFile;
    }

    public function getVendorDirectory(): string
    {
        return $this->getProjectDirectory() . '/vendor';
    }

    public function getCurrentPackageDirectory(): string
    {
        return $this->getVendorDirectory() . '/' . $this->currentPackage;
    }

    public function getCurrentPackageRecipePathname(): ?string
    {
        if (!\array_key_exists($this->currentPackage, $this->recipes)) {
            $jsonRecipe = $this->getCurrentPackageDirectory() . '/cook.json';
            $yamlRecipe = $this->getCurrentPackageDirectory() . '/cook.yaml';

            if ($this->filesystem->exists($yamlRecipe)) {
                $recipePathname = $yamlRecipe;
            } elseif ($this->filesystem->exists($jsonRecipe)) {
                $recipePathname = $jsonRecipe;
            } else {
                $recipePathname = null;
            }

            $this->recipes[$this->currentPackage] = $recipePathname;
        }

        return $this->recipes[$this->currentPackage];
    }

    public function replacePathPlaceholders(string $pathname): string
    {
        return preg_replace_callback('/%(.+?)%/', function (array $matches) {
            $option = str_replace('_', '-', strtolower($matches[1]));

            if (!($opt = $this->options->get($option))) {
                return $matches[0];
            }

            return rtrim($opt, '/');
        }, $pathname);
    }
}
