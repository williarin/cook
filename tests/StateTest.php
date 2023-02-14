<?php

declare(strict_types=1);

namespace Williarin\Cook\Test;

use Composer\Composer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Williarin\Cook\Options;
use Williarin\Cook\State;

class StateTest extends MockeryTestCase
{
    private State $state;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('getPackage->getExtra');
        $options = new Options($composer);
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->state = new State($this->filesystem, $options);
    }

    public function testGetCurrentPackage(): void
    {
        $this->assertNull($this->state->getCurrentPackage());
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('williarin/cook-example', $this->state->getCurrentPackage());
    }

    public function testGetOverwrite(): void
    {
        $this->state->setOverwrite(true);
        $this->assertTrue($this->state->getOverwrite());
    }

    public function testGetProjectDirectory(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('.', $this->state->getProjectDirectory());
    }

    public function testGetVendorDirectory(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('./vendor', $this->state->getVendorDirectory());
    }

    public function testGetCurrentPackageDirectory(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('./vendor/williarin/cook-example', $this->state->getCurrentPackageDirectory());
    }

    public function testGetCurrentPackageRecipePathnameYaml(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->assertSame('./vendor/williarin/cook-example/cook.yaml', $this->state->getCurrentPackageRecipePathname());
    }

    public function testGetCurrentPackageRecipePathnameJson(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->andReturn(false, true);

        $this->assertSame('./vendor/williarin/cook-example/cook.json', $this->state->getCurrentPackageRecipePathname());
    }

    public function testGetCurrentPackageRecipePathnameNotFound(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->andReturn(false);

        $this->assertNull($this->state->getCurrentPackageRecipePathname());
    }

    public function testReplacePathPlaceholders(): void
    {
        $this->assertSame('config/bundles.php', $this->state->replacePathPlaceholders('%CONFIG_DIR%/bundles.php'));
    }

    public function testReplacePathPlaceholdersWithNonExistentPlaceholders(): void
    {
        $this->assertSame(
            '%UNDEFINED_PLACEHOLDER%/bundles.php',
            $this->state->replacePathPlaceholders('%UNDEFINED_PLACEHOLDER%/bundles.php')
        );
    }
}
