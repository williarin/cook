<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Williarin\Cook\Merger\DockerComposeMerger;

class DockerComposeMergerTest extends MergerTestCase
{
    private DockerComposeMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merger = new DockerComposeMerger($this->io, $this->state, $this->filesystem, $this->filters);
    }

    public function testGetName(): void
    {
        $this->assertSame('docker_compose', $this->merger::getName());
    }

    public function testMergeWithoutContent(): void
    {
        $this->io->expects($this->once())
            ->method('write')
            ->with(
                '<error>Error found in williarin/cook-example recipe: "source" or "content" field is required for "docker_compose" file type.</>'
            );

        $this->merger->merge([]);
    }
}
