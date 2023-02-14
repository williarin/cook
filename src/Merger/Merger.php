<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface Merger
{
    public static function getName(): string;
    public function merge(array $file): void;
    public function uninstall(array $file): void;
}
