<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

use Composer\IO\IOInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Williarin\Cook\Filter\Filter;
use Williarin\Cook\StateInterface;

abstract class AbstractMerger implements Merger
{
    public function __construct(
        protected IOInterface $io,
        protected StateInterface $state,
        protected Filesystem $filesystem,
        #[TaggedLocator(Filter::class, defaultIndexMethod: 'getName')]
        private ServiceLocator $filters,
    ) {
    }

    /**
     * @param string[] $filters
     */
    protected function applyFilters(array $filters, mixed $value, mixed $originalValue = null): mixed
    {
        foreach ($filters as $filter) {
            if (!$this->filters->has($filter)) {
                $this->io->write(sprintf(
                    '<error>Error found in %s recipe: filter "%s" unknown.</>',
                    $this->state->getCurrentPackage(),
                    $filter,
                ));
            }

            $value = $this->filters->get($filter)
                ->process($value, $originalValue);
        }

        return $value;
    }

    protected function getDestinationRealPathname(array $file): string
    {
        return sprintf(
            '%s/%s',
            $this->state->getProjectDirectory(),
            $this->state->replacePathPlaceholders($file['destination']),
        );
    }

    protected function getSourceContent(array $file): ?string
    {
        if (\array_key_exists('source', $file)) {
            $sourcePathname = sprintf('%s/%s', $this->state->getCurrentPackageDirectory(), $file['source']);

            if (!$this->filesystem->exists($sourcePathname)) {
                $this->io->write(sprintf(
                    '<error>Error found in %s recipe: file "%s" not found.</>',
                    $this->state->getCurrentPackage(),
                    $sourcePathname,
                ));

                return null;
            }

            return file_get_contents($sourcePathname);
        }

        if (!\array_key_exists('content', $file)) {
            $this->io->write(sprintf(
                '<error>Error found in %s recipe: "source" or "content" field is required for "%s" file type.</>',
                $this->state->getCurrentPackage(),
                static::getName(),
            ));

            return null;
        }

        return $file['content'];
    }

    protected function getRecipeIdOpeningComment(): string
    {
        return sprintf('###> %s ###', $this->state->getCurrentPackage());
    }

    protected function getRecipeIdClosingComment(): string
    {
        return sprintf('###< %s ###', $this->state->getCurrentPackage());
    }

    protected function wrapRecipeId(string $text, bool $trim = false): string
    {
        return sprintf(
            "%s\n%s\n%s\n",
            $this->getRecipeIdOpeningComment(),
            $trim ? trim($text) : $text,
            $this->getRecipeIdClosingComment(),
        );
    }
}
