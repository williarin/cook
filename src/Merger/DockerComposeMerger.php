<?php

declare(strict_types=1);

namespace Williarin\Cook\Merger;

final class DockerComposeMerger extends YamlMerger
{
    protected ?array $validSections = ['services', 'volumes', 'configs', 'secrets', 'networks'];
    protected ?array $blankLineAfter = ['services'];

    public static function getName(): string
    {
        return 'docker_compose';
    }
}
