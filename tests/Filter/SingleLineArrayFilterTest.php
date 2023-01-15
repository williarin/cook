<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Filter;

use Monolog\Test\TestCase;
use Williarin\Cook\Filter\SingleLineArrayFilter;

class SingleLineArrayFilterTest extends TestCase
{
    private SingleLineArrayFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new SingleLineArrayFilter();
    }

    public function testGetName(): void
    {
        $this->assertSame('single_line_array', $this->filter::getName());
    }

    public function testProcess(): void
    {
        $this->assertSame(
            "['name' => 'John', 'age' => 12]",
            $this->filter->process([
                'name' => 'John',
                'age' => 12,
            ], [
                'name' => 'John',
                'age' => 12,
            ])
        );
    }

    public function testProcessNonArray(): void
    {
        $this->assertSame(
            [
                'name' => 'John',
                'age' => 12,
            ],
            $this->filter->process([
                'name' => 'John',
                'age' => 12,
            ], 'some string')
        );
    }
}
