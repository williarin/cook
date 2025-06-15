<?php

declare(strict_types=1);

namespace Williarin\Cook;

use Composer\Composer;
use Composer\InstalledVersions;
use Composer\IO\IOInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Williarin\Cook\Merger\Merger;

final class Oven
{
    private ?array $requiredPackages = null;

    public function __construct(
        private Composer $composer,
        private IOInterface $io,
        private Filesystem $filesystem,
        private State $state,
        #[AutowireLocator(Merger::class, defaultIndexMethod: 'getName')]
        private ServiceLocator $mergers,
    ) {
    }

    public function cookRecipes(?array $packages = null, bool $overwrite = false): void
    {
        $this->state->setOverwrite($overwrite);

        foreach ($this->getRequiredPackages($packages) as $package) {
            $this->state->setCurrentPackage($package);

            if ($this->executePackageRecipe() === false) {
                $this->io->write(sprintf('<warning>Aborting %s recipe execution.</>', $package));
            }
        }

        $this->io->write('');
    }

    public function uninstallRecipes(array $packages): void
    {
        $this->state->setOverwrite(false);

        foreach ($packages as $package) {
            $this->state->setCurrentPackage($package);

            if ($this->uninstallPackageRecipe() === false) {
                $this->io->write(sprintf('<warning>Aborting %s recipe uninstallation.</>', $package));
            }
        }

        $this->io->write('');
    }

    public function displayPostInstallOutput(?array $packages = null): void
    {
        $this->state->setOverwrite(false);

        foreach ($this->getRequiredPackages($packages) as $package) {
            $this->state->setCurrentPackage($package);
            $this->displayPackageRecipePostInstallOutput();
        }

        $this->io->write('');
    }

    private function executePackageRecipe(): ?bool
    {
        if (!$this->state->getCurrentPackageRecipePathname()) {
            return null;
        }

        $this->io->write(sprintf("\nFound Cook recipe for <comment>%s</>", $this->state->getCurrentPackage()));

        if (!($recipe = $this->loadAndValidateRecipe())) {
            return false;
        }

        foreach ($recipe['files'] ?? [] as $file) {
            if (!$this->mergers->has($file['type'])) {
                $this->io->write(sprintf(
                    '<error>Error found in %s recipe: type "%s" unknown.</>',
                    $this->state->getCurrentPackage(),
                    $file['type'],
                ));

                continue;
            }

            $this->mergers->get($file['type'])->merge($file);
        }

        foreach ($recipe['directories'] ?? [] as $destination => $source) {
            $this->copyDirectory($source, $destination);
        }

        return true;
    }

    private function uninstallPackageRecipe(): ?bool
    {
        if (!$this->state->getCurrentPackageRecipePathname()) {
            return null;
        }

        $this->io->write(sprintf("\nUninstalling Cook recipe for <comment>%s</>", $this->state->getCurrentPackage()));

        if (!($recipe = $this->loadAndValidateRecipe())) {
            return false;
        }

        foreach ($recipe['files'] ?? [] as $file) {
            if (!$this->mergers->has($file['type'])) {
                $this->io->write(sprintf(
                    '<error>Error found in %s recipe: type "%s" unknown.</>',
                    $this->state->getCurrentPackage(),
                    $file['type'],
                ));

                continue;
            }

            $this->mergers->get($file['type'])->uninstall($file);
        }

        foreach ($recipe['directories'] ?? [] as $destination => $source) {
            $this->removeFilesFromDirectory($source, $destination);
        }

        return true;
    }

    private function loadAndValidateRecipe(): ?array
    {
        if (($recipe = $this->loadRecipe()) === null) {
            return null;
        }

        $recipe = $this->transformRecipe($recipe);

        if (!$this->validateRecipeSchema($recipe)) {
            return null;
        }

        return $this->setRecipeDefaults($recipe);
    }

    private function loadRecipe(): ?array
    {
        $isYamlRecipe = str_ends_with($this->state->getCurrentPackageRecipePathname(), '.yaml');

        if ($isYamlRecipe && !\in_array('symfony/yaml', InstalledVersions::getInstalledPackages())) {
            $this->io->error(sprintf(
                'Recipe for package %s is in YAML format but symfony/yaml is not installed.',
                $this->state->getCurrentPackage(),
            ));

            return null;
        }

        return $isYamlRecipe ? $this->loadYamlRecipe() : $this->loadJsonRecipe();
    }

    private function loadYamlRecipe(): ?array
    {
        try {
            return Yaml::parseFile($this->state->getCurrentPackageRecipePathname());
        } catch (ParseException) {
            $this->io->error(sprintf(
                'Invalid YAML syntax in %s',
                $this->state->getCurrentPackageRecipePathname(),
            ));
        }

        return null;
    }

    private function loadJsonRecipe(): ?array
    {
        try {
            return json_decode(
                trim(file_get_contents($this->state->getCurrentPackageRecipePathname())),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException) {
            $this->io->error(sprintf('Invalid JSON syntax in %s', $this->state->getCurrentPackageRecipePathname()));
        }

        return null;
    }

    private function transformRecipe(array $recipe): array
    {
        foreach ($recipe['files'] ?? [] as $destination => $file) {
            if (\is_string($file)) {
                $recipe['files'][$destination] = [
                    'source' => $file,
                ];
            }

            $recipe['files'][$destination]['destination'] = $destination;
        }

        return $recipe;
    }

    private function validateRecipeSchema(array $recipe): bool
    {
        $constraints = new Assert\Collection([
            'files' => new Assert\Optional(
                new Assert\All(
                    new Assert\Collection([
                        'type' => new Assert\Optional(new Assert\Choice([
                            'text',
                            'php_array',
                            'json',
                            'yaml',
                            'docker_compose',
                        ])),
                        'source' => new Assert\Optional([new Assert\NotBlank(), new Assert\Type('string')]),
                        'destination' => [new Assert\NotBlank(), new Assert\Type('string')],
                        'entries' => new Assert\Optional(new Assert\Type('array')),
                        'content' => new Assert\Optional(new Assert\Type('string')),
                        'filters' => new Assert\Optional([
                            new Assert\Collection([
                                'keys' => new Assert\Optional(new Assert\All(new Assert\Choice(['class_constant']))),
                                'values' => new Assert\Optional(
                                    new Assert\All(new Assert\Choice(['class_constant', 'single_line_array'])),
                                ),
                            ]),
                        ]),
                        'valid_sections' => new Assert\Optional(new Assert\Type('array')),
                        'blank_line_after' => new Assert\Optional(new Assert\Type('array')),
                    ]),
                ),
            ),
            'directories' => new Assert\Optional(new Assert\Type('array')),
            'post_install_output' => new Assert\Optional(new Assert\Type('string')),
        ]);

        $validator = Validation::createValidator();
        $violations = $validator->validate($recipe, $constraints);

        if ($violations->count() > 0) {
            foreach ($violations as $violation) {
                $this->io->write(sprintf(
                    '<error>Error found in %s recipe: %s %s</>',
                    $this->state->getCurrentPackage(),
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                ));
            }
        }

        return $violations->count() === 0;
    }

    private function setRecipeDefaults(?array $recipe): array
    {
        $resolver = (new OptionsResolver())
            ->setDefaults([
                'files' => static function (OptionsResolver $filesResolver) {
                    $filesResolver
                        ->setPrototype(true)
                        ->setDefined([
                            'type',
                            'destination',
                            'entries',
                            'filters',
                            'content',
                            'source',
                            'valid_sections',
                            'blank_line_after',
                        ])
                        ->setDefaults([
                            'type' => 'text',
                        ]);
                },
            ])
            ->setDefined(['files', 'directories', 'post_install_output']);

        return $resolver->resolve($recipe);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        $sourceDir = $this->state->getCurrentPackageDirectory() . '/' . $source;

        if (!$this->filesystem->exists($sourceDir)) {
            $this->io->write(sprintf(
                "<error>Error executing %s recipe: %s directory doesn't exist.</>",
                $this->state->getCurrentPackage(),
                $sourceDir,
            ));
        }

        $finder = new Finder();
        $finder->in($sourceDir)
            ->files();

        foreach ($finder as $file) {
            $destinationPathname = str_replace(
                [$this->state->getCurrentPackageDirectory(), $source],
                [$this->state->getProjectDirectory(), $this->state->replacePathPlaceholders($destination)],
                $file->getPathname(),
            );

            $this->filesystem->mkdir(\dirname($destinationPathname));
            $fileExists = $this->filesystem->exists($destinationPathname);

            if (
                !$fileExists
                || (
                    $this->state->getOverwrite()
                    && $file->getContents() !== file_get_contents($destinationPathname)
                )
            ) {
                $this->filesystem->copy($file->getPathname(), $destinationPathname, true);
                $this->io->write(sprintf('%s file: %s', $fileExists ? 'Updated' : 'Created', $destinationPathname));
            }
        }
    }

    private function removeFilesFromDirectory(string $source, string $destination): void
    {
        $sourceDir = $this->state->getCurrentPackageDirectory() . '/' . $source;

        if (!$this->filesystem->exists($sourceDir)) {
            $this->io->write(sprintf(
                "<error>Error executing %s recipe: %s directory doesn't exist.</>",
                $this->state->getCurrentPackage(),
                $sourceDir,
            ));
        }

        $finder = new Finder();
        $finder->in($sourceDir)
            ->files();

        foreach ($finder as $file) {
            $destinationPathname = str_replace(
                [$this->state->getCurrentPackageDirectory(), $source],
                [$this->state->getProjectDirectory(), $this->state->replacePathPlaceholders($destination)],
                $file->getPathname(),
            );

            $fileExists = $this->filesystem->exists($destinationPathname);

            if ($fileExists && $file->getContents() === file_get_contents($destinationPathname)) {
                $this->filesystem->remove($destinationPathname);
                $this->io->write(sprintf('Removed file: %s', $destinationPathname));
            }
        }
    }

    private function getRequiredPackages(?array $packages = null): array
    {
        if ($this->requiredPackages === null) {
            $rootPackage = $this->composer->getPackage();
            $this->requiredPackages = $packages
                ?? array_keys($rootPackage->getRequires() + $rootPackage->getDevRequires());
        }

        return $this->requiredPackages;
    }

    private function displayPackageRecipePostInstallOutput(): void
    {
        if (!$this->state->getCurrentPackageRecipePathname()) {
            return;
        }

        if (!($recipe = $this->loadAndValidateRecipe())) {
            return;
        }

        if (!empty($recipe['post_install_output'])) {
            $this->io->write(sprintf(
                "\n<comment>%s</> instructions:\n\n%s",
                $this->state->getCurrentPackage(),
                $recipe['post_install_output'],
            ));
        }
    }
}
