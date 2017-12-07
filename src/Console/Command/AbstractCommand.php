<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Console\Command\AbstractCommand as PhinxAbstractCommand;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\Manager\Environment;
use Phinx\Migration\MigrationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends PhinxAbstractCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    final protected function getOptionEnvironment(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getOption('environment');
        
        if (null === $environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }
        
        return $environment;
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string|int version number of error status
     */
    final protected function getArgumentVersion(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('target');
        if (!$version) {
            $output->writeln('<comment>target version is required</comment>');
            return 1;
        }
    
        if (!ctype_digit($version) || strlen($version) !== 14) { //YmdHis
            $output->writeln('<error>target '.$version.' is not a valid version number</error>');
            return 2;
        }
        
        return $version;
    }
    
    /**
     * @param string $version
     * @param OutputInterface $output
     * @return AbstractMigration|int migration or error status
     */
    final protected function getMigrationWithVersion($version, OutputInterface $output)
    {
        $migrations = $this->manager->getMigrations();
        if (empty($migrations)) {
            $output->writeln('<info>there are no migrations found</info>');
            return 3;
        }
    
        if (!isset($migrations[$version])) {
            $output->writeln('<error>migration '.$version.' does not exist</error>');
            return 4;
        }
        
        return $migrations[$version];
    }
    
    /**
     * @param Environment $env
     * @param string $version
     * @return void
     */
    final protected function removeMigrationFromPhinxlog(Environment $env, $version)
    {
        $env->getAdapter()->execute(sprintf(
            'DELETE FROM %s WHERE %s = %s',
            $env->getSchemaTableName(),
            $env->getAdapter()->quoteColumnName('version'),
            $version
        ));
    }
    
    /**
     * @param Environment $env
     * @param AbstractMigration $migration
     * @return void
     */
    final protected function markMigrationAsMigrated(Environment $env, AbstractMigration $migration)
    {
        $now = date('Y-m-d H:i:s');
        $env->getAdapter()->migrated($migration, MigrationInterface::UP, $now, $now);
    }
    
    /**
     * @param Environment $env
     * @param AbstractMigration $migration
     * @return void
     */
    final protected function markMigrationAsUnmigrated(Environment $env, AbstractMigration $migration)
    {
        $now = date('Y-m-d H:i:s');
        $env->getAdapter()->migrated($migration, MigrationInterface::DOWN, $now, $now);
    }
}