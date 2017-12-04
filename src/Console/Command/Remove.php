<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Console\Command\AbstractCommand;
use Phinx\Migration\MigrationInterface;
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
    
        $migrations = $this->manager->getMigrations();
        $versions = $env->getVersionLog();
        
        if (isset($versions[$version])) {
            $output->writeln('remove migration <info>'.$version.'</info> from database');
            
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