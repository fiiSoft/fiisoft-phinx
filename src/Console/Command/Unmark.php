<?php

namespace FiiSoft\Phinx\Console\Command;

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
        
        $environment = $this->getOptionEnvironment($input, $output);
    
        $version = $this->getArgumentVersion($input, $output);
        if (is_int($version)) {
            return $version;
        }
    
        $env = $this->manager->getEnvironment($environment);
        $versions = $env->getVersionLog();
        
        if (!isset($versions[$version])) {
            $output->writeln('migration <comment>'.$version.'</comment> is not migrated yet');
            return 0;
        }
        
        $output->writeln('mark migration <info>'.$version.'</info> as not-migrated');
    
        $migrations = $this->manager->getMigrations();
        if (isset($migrations[$version])) {
            $this->markMigrationAsUnmigrated($env, $migrations[$version]);
        } else {
            $this->removeMigrationFromPhinxlog($env, $version);
        }

        return 0;
    }
}