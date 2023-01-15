<?php

declare(strict_types=1);

namespace Williarin\Cook\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Williarin\Cook\Oven;
use Williarin\Cook\ServiceContainer;

final class CookCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cook')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing files or values')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overwrite = $input->getOption('overwrite');
        (new ServiceContainer($this->requireComposer(), $this->getIO()))
            ->get(Oven::class)
            ?->cookRecipes(null, $overwrite);

        return Command::SUCCESS;
    }
}
