<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Revoke extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('revoke')
            ->setDescription('Rollback particular migration')
            ->addArgument('target', InputArgument::REQUIRED, 'The version number of migration to rollback')
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.')
            ->setHelp(
                <<<EOT
The <info>revoke</info> command forces to rollback single particular migration (by its version number).

<info>phinx revoke {target} -e development</info>
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
        
        $output->writeln('rollback migration <info>'.$version.'</info>');
    
        $start = microtime(true);
        $this->manager->executeMigration($environment, $migration, MigrationInterface::DOWN);
        $end = microtime(true);
    
        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
        
        return 0;
    }
}