<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Composer\IO\IOInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Williarin\Cook\StateInterface;

abstract class MergerTestCase extends MockeryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->io = Mockery::mock(IOInterface::class);
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->filters = Mockery::mock(ServiceLocator::class);

        $this->state = Mockery::mock(StateInterface::class);
        $this->state->shouldReceive('getCurrentPackage')
            ->andReturn('williarin/cook-example');
        $this->state->shouldReceive('getCurrentPackageDirectory')
            ->andReturn('tests/Dummy/recipe');
        $this->state->shouldReceive('getProjectDirectory')
            ->andReturn('.');
        $this->state->shouldReceive('replacePathPlaceholders')
            ->andReturnArg(0);
    }

    protected function addFilter(string $name, string $className): void
    {
        $this->filters->shouldReceive('has')
            ->atLeast()
            ->once()
            ->with($name)
            ->andReturn(true);

        $this->filters->shouldReceive('get')
            ->atLeast()
            ->once()
            ->with($name)
            ->andReturn(new $className());
    }
}
