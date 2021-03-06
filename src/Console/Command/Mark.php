<?php

namespace FiiSoft\Phinx\Console\Command;

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
        
        $environment = $this->getOptionEnvironment($input, $output);
    
        $version = $this->getArgumentVersion($input, $output);
        if (is_int($version)) {
            return $version;
        }
    
        $migration = $this->getMigrationWithVersion($version, $output);
        if (is_int($migration)) {
            return $migration;
        }
    
        $env = $this->manager->getEnvironment($environment);
        
        $versions = $env->getVersionLog();
        if (isset($versions[$version])) {
            $output->writeln('migration <comment>'.$version.'</comment> is already migrated');
        } else {
            $output->writeln('mark migration <info>'.$version.'</info> as migrated');
            $this->markMigrationAsMigrated($env, $migration);
        }
    
        return 0;
    }
}