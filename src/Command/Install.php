<?php

namespace DrupalCtl\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Install extends DrupalCommand
{
    const UNINSTALL = <<<SQL
SET FOREIGN_KEY_CHECKS = 0;
SET GROUP_CONCAT_MAX_LEN=32768;
SET @tables = NULL;
SELECT GROUP_CONCAT(table_name) INTO @tables
FROM information_schema.tables
WHERE table_schema = (SELECT DATABASE());
SELECT IFNULL(@tables,'%s') INTO @tables;
SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
PREPARE stmt FROM @tables;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET FOREIGN_KEY_CHECKS = 1;
SQL;

    public function getBootstrap()
    {
        return DRUPAL_BOOTSTRAP_CONFIGURATION;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('install');
        $this->addOption(
            'profile',
            null,
            InputOption::VALUE_OPTIONAL,
            'the install profile',
            'minimal'
        );
        $this->addOption(
            'account',
            null,
            InputOption::VALUE_OPTIONAL,
            'the name of the admin account',
            'admin'
        );
        $this->addOption(
            'password',
            null,
            InputOption::VALUE_OPTIONAL,
            'the password for the admin account'
        );
        $this->addOption(
            'locale',
            null,
            InputOption::VALUE_OPTIONAL,
            'the two-character locale for the site',
            'en'
        );
        $this->addOption(
            'site_mail',
            null,
            InputOption::VALUE_OPTIONAL,
            'email address for the site',
            'admin@example.com'
        );
        $this->addOption(
            'account_mail',
            null,
            InputOption::VALUE_OPTIONAL,
            'email address for the admin account',
            'admin@example.com'
        );
        $this->addOption(
            'site_name',
            null,
            InputOption::VALUE_OPTIONAL,
            'the name for the site',
            'Site Install'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbSpec = $this->getDB();
        $pass = $input->getOption('password')
            ?: substr(str_shuffle(MD5(microtime())), 0, 10);
        $account = $input->getOption('account');
        $settings = array(
            'parameters' => array(
                'profile' => $input->getOption('profile'),
                'locale' => $input->getOption('locale'),
            ),
            'forms' => array(
                'install_settings_form' => array(
                    'driver' => $dbSpec['driver'],
                    $dbSpec['driver'] => $dbSpec,
                    'op' => 'Save and continue',
                ),
                'install_configure_form' => array(
                    'site_name' => $input->getOption('site_name'),
                    'site_mail' => $input->getOption('site_mail'),
                    'account' => array(
                        'name' => $account,
                        'mail' => $input->getOption('account_mail'),
                        'pass' => array(
                            'pass1' => $pass,
                            'pass2' => $pass,
                        ),
                    ),
                    'update_status_module' => array(
                        1 => TRUE,
                        2 => TRUE,
                    ),
                    'clean_url' => TRUE,
                    'op' => 'Save and continue',
                ),
            ),
        );
        $this->log(
            'info',
            'Starting Drupal installation. This takes a few seconds ...'
        );
        $this->installDrupal($settings);
        $this->log(
            'info',
            'Installation complete.  User name: @name  User password: @pass',
            array('@name' => $account, '@pass' => $pass)
        );
    }

    private function installDrupal($settings)
    {
        require_once $this->getRoot() . '/includes/install.core.inc';
        require_once $this->getRoot() . '/includes/menu.inc';
        require_once $this->getRoot() . '/includes/lock.inc';
        $this->uninstallDrupal();
        install_drupal($settings);
    }

    private function uninstallDrupal()
    {
      $query = sprintf(static::UNINSTALL, $this->getDB()['database']);
      require_once $this->getRoot() . '/includes/database/database.inc';
      db_query($query);
    }

    protected function log($severity, $message, $context = array())
    {
        echo $this->t($message, $context);
    }

    protected function t($message, $context = array())
    {
        return empty($args) ? strtr($message, $args) : $message;
    }

    private function getDB()
    {
        $this->bootstrap();
        global $databases;
        return $databases['default']['default'];
    }
}
