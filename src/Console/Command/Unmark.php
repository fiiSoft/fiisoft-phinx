<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Console\Command\AbstractCommand;
use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Unmark extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('unmark')
            ->setDescription('Mark migration as not-migrated yet')
            ->addArgument('target', InputArgument::REQUIRED, 'The version number of migration to mark as not-migrated')
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.')
            ->setHelp(
                <<<EOT
The <info>unmark</info> command marks single particular migration (by its version number) as not-migrated.

<info>phinx unmark {target} -e development</info>
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
    
        $env = $this->manager->getEnvironment($environment);
        
        $versions = $env->getVersionLog();
        if (!isset($versions[$version])) {
            $output->writeln('<comment>migration '.$version.' is not migrated yet</comment>');
            return 0;
        }
        
        $output->writeln('<info>mark migration '.$version.' as not-migrated</info>');
    
        $migrations = $this->manager->getMigrations();
        if (isset($migrations[$version])) {
            $now = date('Y-m-d H:i:s');
            $env->getAdapter()->migrated($migrations[$version], MigrationInterface::DOWN, $now, $now);
        } else {
            $env->getAdapter()->execute(sprintf(
                'DELETE FROM %s WHERE %s = %s',
                $env->getSchemaTableName(),
                $env->getAdapter()->quoteColumnName('version'),
                $version
            ));
        }

        return 0;
    }
}