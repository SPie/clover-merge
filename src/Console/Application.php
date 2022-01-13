<?php


namespace Kavinsky\CloverMerge\Console;

use Kavinsky\CloverMerge\Command\MergeCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('CloverMerge', '2.0.0');
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return \Symfony\Component\Console\Command\Command[] An array of default Command instances
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function getDefaultCommands()
    {
        return [
            ...parent::getDefaultCommands(),
            new MergeCommand(),
        ];
    }
}
