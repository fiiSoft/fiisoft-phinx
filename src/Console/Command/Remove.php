<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Util\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('remove')
            ->setDescription('Remove migration')
            ->addArgument('target', InputArgument::REQUIRED, 'The version number of migration to remove')
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.')
            ->setHelp(
                <<<EOT
The <info>remove</info> command removes single particular migration (by its version number) from disk
and deletes corresponding entry in phinxlog table (in specified environment only).

Be aware that this command does NOT perform rollback and obsolete entries can left in other environments!

<info>phinx remove {target} -e development</info>
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
    
        $migrations = $this->manager->getMigrations();
        $versions = $env->getVersionLog();
        
        if (isset($versions[$version])) {
            $output->writeln('remove migration <info>'.$version.'</info> from database');
            
            if (isset($migrations[$version])) {
                $this->markMigrationAsUnmigrated($env, $migrations[$version]);
            } else {
                $this->removeMigrationFromPhinxlog($env, $version);
            }
        }
        
        if (isset($migrations[$version])) {
            $output->writeln('remove migration <info>'.$version.'</info> from disk');
            
            foreach ($this->config->getMigrationPaths() as $path) {
                foreach (Util::glob($path . DIRECTORY_SEPARATOR . '*.php') as $filePath) {
                    if (Util::isValidMigrationFileName(basename($filePath))) {
                        $fileVersion = Util::getVersionFromFileName(basename($filePath));
                        if ($fileVersion === $version) {
                            $output->writeln('delete file <info>'.$filePath.'</info>');
                            unlink($filePath);
                            break;
                        }
                    }
                }
            }
        }

        return 0;
    }
}