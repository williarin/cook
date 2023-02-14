<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Williarin\Cook\Filter\ClassConstantFilter;
use Williarin\Cook\Filter\SingleLineArrayFilter;
use Williarin\Cook\Merger\PhpArrayMerger;

class PhpArrayMergerTest extends MergerTestCase
{
    private PhpArrayMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merger = new PhpArrayMerger($this->io, $this->state, $this->filesystem, $this->filters);
    }

    public function testGetName(): void
    {
        $this->assertSame('php_array', $this->merger::getName());
    }

    public function testMergeWithoutEntries(): void
    {
        $this->io->shouldReceive('write')
            ->once()
            ->with(
                '<error>Error found in williarin/cook-example recipe: file of type "php_array" requires "entries" field.</>'
            )
        ;

        $this->merger->merge([]);
    }

    public function testMergeNewFileWithoutFilters(): void
    {
        $file = [
            'destination' => 'var/cache/tests/bundles.php',
            'entries' => [
                'Williarin\CookExampleBundle' => [
                    'dev' => true,
                    'test' => true,
                ],
            ],
            'filters' => [],
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./var/cache/tests', 0755);

        $this->filesystem->shouldReceive('exists')
            ->atLeast()
            ->once()
            ->andReturn(false);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './var/cache/tests/bundles.php',
                <<<CODE_SAMPLE
                <?php

                return [
                    'Williarin\CookExampleBundle' => [
                        'dev' => true,
                        'test' => true,
                    ],
                ];

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Created file: ./var/cache/tests/bundles.php');

        $this->merger->merge($file);
    }

    public function testMergeNewFileWithFilters(): void
    {
        $file = [
            'destination' => 'var/cache/tests/bundles.php',
            'entries' => [
                'Williarin\CookExampleBundle' => [
                    'dev' => true,
                    'test' => true,
                ],
            ],
            'filters' => [
                'keys' => ['class_constant'],
                'values' => ['single_line_array'],
            ],
        ];

        $this->addFilter('class_constant', ClassConstantFilter::class);
        $this->addFilter('single_line_array', SingleLineArrayFilter::class);

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./var/cache/tests', 0755);

        $this->filesystem->shouldReceive('exists')
            ->atLeast()
            ->once()
            ->andReturn(false);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './var/cache/tests/bundles.php',
                <<<CODE_SAMPLE
                <?php

                return [
                    Williarin\CookExampleBundle::class => ['dev' => true, 'test' => true],
                ];

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Created file: ./var/cache/tests/bundles.php');

        $this->merger->merge($file);
    }

    public function testMergeExistingFileWithFilters(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/bundles.php',
            'entries' => [
                'Williarin\CookExampleBundle' => [
                    'dev' => true,
                    'test' => true,
                ],
            ],
            'filters' => [
                'keys' => ['class_constant'],
                'values' => ['single_line_array'],
            ],
        ];

        $this->addFilter('class_constant', ClassConstantFilter::class);
        $this->addFilter('single_line_array', SingleLineArrayFilter::class);

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/before/bundles.php',
                <<<CODE_SAMPLE
                <?php

                return [
                    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
                    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
                    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
                    Williarin\CookExampleBundle::class => ['dev' => true, 'test' => true],
                ];

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/before/bundles.php');

        $this->merger->merge($file);
    }

    public function testUninstallWithoutEntries(): void
    {
        $this->io->shouldReceive('write')
            ->once()
            ->with(
                '<error>Error found in williarin/cook-example recipe: file of type "php_array" requires "entries" field.</>'
            )
        ;

        $this->merger->uninstall([]);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/bundles.php',
            'entries' => [
                'Williarin\CookExampleBundle' => [
                    'dev' => true,
                    'test' => true,
                ],
            ],
            'filters' => [
                'keys' => ['class_constant'],
                'values' => ['single_line_array'],
            ],
        ];

        $this->addFilter('class_constant', ClassConstantFilter::class);
        $this->addFilter('single_line_array', SingleLineArrayFilter::class);

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/after/bundles.php',
                <<<CODE_SAMPLE
                <?php

                return [
                    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
                    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
                    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
                ];

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/after/bundles.php');

        $this->merger->uninstall($file);
    }
}
