<?php

namespace DrupalCtl\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DrupalCommand extends Command
{
    public function getBootstrap()
    {
        return DRUPAL_BOOTSTRAP_FULL;
    }

    protected function configure()
    {
        $this->addOption(
            'root',
            'r',
            InputOption::VALUE_OPTIONAL,
            'The drupal root',
            getcwd()
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $pwd = getcwd();
        if (($root = $input->getOption('root')) && strpos($root, $pwd) !== 0) {
            $this->root = "$pwd/$root";
        }
        else {
            $this->root = $pwd;
        }
    }

    protected function getRoot()
    {
        return $this->root;
    }

    protected function bootstrap()
    {
        $root = $this->getRoot();
        chdir($root);
        define('DRUPAL_ROOT', $root);
        require_once "$root/includes/bootstrap.inc";
        drupal_override_server_variables();
        drupal_bootstrap($this->getBootstrap());
    }
}
