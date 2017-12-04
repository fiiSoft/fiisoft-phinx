<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Console\Command\AbstractCommand;
use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Mark extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('mark')
            ->setDescription('Mark migration as migrated')
            ->addArgument('target', InputArgument::REQUIRED, 'The version number of migration to mark as migrated')
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.')
            ->setHelp(
                <<<EOT
The <info>mark</info> command marks single particular migration (by its version number) as migrated.

<info>phinx mark {target} -e development</info>
EOT
            );
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        
        $environment = $input->getOption('environment');
        if (null === $environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }
    
        $version = $input->getArgument('target');
        if (!$version) {
            $output->writeln('<comment>target version is required</comment>');
            return 2;
        }
        
        if (!ctype_digit($version) || strlen($version) !== 14) { //YmdHis
            $output->writeln('<error>target '.$version.' is not a valid version number</error>');
            return 3;
        }
    
        $migrations = $this->manager->getMigrations();
        if (empty($migrations)) {
            $output->writeln('<info>there are no migrations found</info>');
            return 1;
        }
        
        if (!isset($migrations[$version])) {
            $output->writeln('<error>migration '.$version.' does not exist</error>');
            return 3;
        }
    
        $env = $this->manager->getEnvironment($environment);
        
        $versions = $env->getVersionLog();
        if (isset($versions[$version])) {
            $output->writeln('migration <comment>'.$version.'</comment> is already migrated');
            return 0;
        }
        
        $output->writeln('mark migration <info>'.$version.'</info> as migrated');
    
        $now = date('Y-m-d H:i:s');
        $env->getAdapter()->migrated($migrations[$version], MigrationInterface::UP, $now, $now);
    
        return 0;
    }
}