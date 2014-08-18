<?php

namespace DrupalCtl\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Status extends DrupalCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('status');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $input->getOption('root');
        $root = strpos($root, '/') === 0 ? $root : getcwd() . '/' . $root;
        $this->bootstrap($root);
        global $user;
        var_export($user);
    }
}
