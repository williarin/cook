<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Composer\IO\IOInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Williarin\Cook\Merger\EnvMerger;
use Williarin\Cook\StateInterface;

class EnvMergerTest extends TestCase
{
    private EnvMerger $merger;
    private IOInterface $io;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->io = $this->createMock(IOInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $state = $this->createMock(StateInterface::class);
        $filters = $this->createMock(ServiceLocator::class);

        $state
            ->method('getCurrentPackage')
            ->willReturn('williarin/cook-example');
        $state
            ->method('getCurrentPackageDirectory')
            ->willReturn('tests/Dummy/recipe');
        $state
            ->method('getProjectDirectory')
            ->willReturn('.');
        $state
            ->method('replacePathPlaceholders')
            ->willReturnArgument(0);

        $this->merger = new EnvMerger($this->io, $state, $this->filesystem, $filters);
    }

    public function testGetName(): void
    {
        $this->assertSame('env', $this->merger::getName());
    }

    public function testMergeCommentPolicy(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/.env.dist',
            'source' => '.env.dist',
            'if_exists' => 'comment',
        ];

        $existsMatcher = $this->exactly(3);
        $this->filesystem->expects($existsMatcher)
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($existsMatcher): bool {
                match ($existsMatcher->numberOfInvocations()) {
                    1 => $this->assertSame('tests/Dummy/recipe/.env.dist', $path),
                    2, 3 => $this->assertSame('./tests/Dummy/before/.env.dist', $path),
                };
                return true;
            });

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0777);

        $expectedContent = <<<CODE_SAMPLE
            ###> symfony/framework-bundle ###
            APP_ENV=dev
            APP_SECRET=s3cr3t
            ###< symfony/framework-bundle ###

            ###> doctrine/doctrine-bundle ###
            #DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"
            ###< doctrine/doctrine-bundle ###

            ###> williarin/cook-example ###
            DATABASE_URL="sqlite:///%kernel.project_dir%/data/app.db"
            NEW_VAR=some_value
            ###< williarin/cook-example ###

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/before/.env.dist', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/.env.dist');

        $this->merger->merge($file);
    }

    public function testMergeDeletePolicy(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/.env.dist',
            'source' => '.env.dist',
            'if_exists' => 'delete',
        ];

        $existsMatcher = $this->exactly(3);
        $this->filesystem->expects($existsMatcher)
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($existsMatcher): bool {
                match ($existsMatcher->numberOfInvocations()) {
                    1 => $this->assertSame('tests/Dummy/recipe/.env.dist', $path),
                    2, 3 => $this->assertSame('./tests/Dummy/before/.env.dist', $path),
                };
                return true;
            });

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0777);

        $expectedContent = <<<CODE_SAMPLE
            ###> symfony/framework-bundle ###
            APP_ENV=dev
            APP_SECRET=s3cr3t
            ###< symfony/framework-bundle ###

            ###> doctrine/doctrine-bundle ###
            ###< doctrine/doctrine-bundle ###

            ###> williarin/cook-example ###
            DATABASE_URL="sqlite:///%kernel.project_dir%/data/app.db"
            NEW_VAR=some_value
            ###< williarin/cook-example ###

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/before/.env.dist', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/.env.dist');

        $this->merger->merge($file);
    }

    public function testMergeNewFile(): void
    {
        $file = [
            'destination' => 'var/cache/tests/.env.dist',
            'source' => '.env.dist',
        ];

        $existsMatcher = $this->exactly(3);
        $this->filesystem->expects($existsMatcher)
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($existsMatcher): bool {
                return match ($existsMatcher->numberOfInvocations()) {
                    1 => (function () use ($path) {
                        $this->assertSame('tests/Dummy/recipe/.env.dist', $path);
                        return true;
                    })(),
                    2, 3 => (function () use ($path) {
                        $this->assertSame('./var/cache/tests/.env.dist', $path);
                        return false;
                    })(),
                };
            });

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./var/cache/tests', 0777);

        $expectedContent = <<<CODE_SAMPLE
            ###> williarin/cook-example ###
            DATABASE_URL="sqlite:///%kernel.project_dir%/data/app.db"
            NEW_VAR=some_value
            ###< williarin/cook-example ###

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./var/cache/tests/.env.dist', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Created file: ./var/cache/tests/.env.dist');

        $this->merger->merge($file);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/.env.uninstall',
            'source' => '.env',
        ];

        $uninstallTestFile = 'tests/Dummy/after/.env.uninstall';
        $content = <<<CONTENT
            ###> williarin/cook-example ###
            SHOULD_BE_REMOVED=true
            ###< williarin/cook-example ###
            
            REMAINING_CONTENT=true
            CONTENT;
        file_put_contents($uninstallTestFile, $content);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with('./' . $uninstallTestFile)
            ->willReturn(true);

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./' . $uninstallTestFile, $this->equalTo("\nREMAINING_CONTENT=true"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./' . $uninstallTestFile);

        $this->merger->uninstall($file);

        unlink($uninstallTestFile);
    }
}
