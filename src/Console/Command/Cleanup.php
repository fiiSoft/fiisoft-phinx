<?php

namespace FiiSoft\Phinx\Console\Command;

use Phinx\Migration\Manager\Environment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->setName('cleanup')
            ->setDescription('Remove missing migrations from phinxlog')
            ->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment.')
            ->setHelp(
                <<<EOT
The <info>cleanup</info> command removes missing migrations from phinxlog.

<info>phinx cleanup -e development</info>
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
        $env = $this->manager->getEnvironment($environment);
        
        $missing = $this->getMissingMigrations($env);
        if (empty($missing)) {
            $output->writeln('no missing migrations found');
            return 0;
        }
        
        foreach ($missing as $version) {
            $output->writeln('mark migration <info>'.$version.'</info> as not-migrated');
            $this->removeMigrationFromPhinxlog($env, $version);
        }
        
        return 0;
    }
    
    /**
     * Code copied from Phinx\Migration\Manager::printStatus().
     * I have no idea how it works, but it works (I hope).
     *
     * @param Environment $env
     * @return array
     */
    public function getMissingMigrations(Environment $env)
    {
        $migrations = $this->manager->getMigrations();
        $versions = $env->getVersionLog();
    
        $missingVersions = array_diff_key($versions, $migrations);
        $sortedMigrations = array();
    
        foreach ($versions as $versionCreationTime => $version) {
            if (isset($migrations[$versionCreationTime])) {
                $sortedMigrations[] = $migrations[$versionCreationTime];
                unset($migrations[$versionCreationTime]);
            }
        }
    
        if (empty($sortedMigrations) && !empty($missingVersions)) {
            foreach ($missingVersions as $missingVersionCreationTime => $missingVersion) {
                unset($missingVersions[$missingVersionCreationTime]);
            }
        }
    
        if (!empty($migrations)) {
            $sortedMigrations = array_merge($sortedMigrations, $migrations);
        }
    
        foreach ($sortedMigrations as $migration) {
            $version = array_key_exists($migration->getVersion(), $versions) ? $versions[$migration->getVersion()] : false;
            if ($version) {
                foreach ($missingVersions as $missingVersionCreationTime => $missingVersion) {
                    if ($this->getConfig()->isVersionOrderCreationTime()) {
                        if ($missingVersion['version'] > $version['version']) {
                            break;
                        }
                    } else {
                        if ($missingVersion['start_time'] > $version['start_time']) {
                            break;
                        }
                        
                        if ($missingVersion['start_time'] == $version['start_time'] &&
                            $missingVersion['version'] > $version['version']) {
                            break;
                        }
                    }
                
                    unset($missingVersions[$missingVersionCreationTime]);
                }
            }
        
            unset($versions[$migration->getVersion()]);
        }
    
        return array_keys($missingVersions);
    }
}