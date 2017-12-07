<?php

namespace FiiSoft\Phinx\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Repeat extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('repeat')
            ->setDescription('Repeat particular migration')
            ->addArgument('target', InputArgument::REQUIRED, 'The version number of migration to migrate again')
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.')
            ->setHelp(
                <<<EOT
The <info>repeat</info> command repeates migration of single particular migration (by its version number).

<info>phinx repeat {target} -e development</info>
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
        
        $output->writeln('repeat migration <info>'.$version.'</info>');
    
        $start = microtime(true);
    
        $this->markMigrationAsUnmigrated($this->manager->getEnvironment($environment), $migration);
        $this->manager->executeMigration($environment, $migration);
        
        $end = microtime(true);
    
        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    
        return 0;
    }
}