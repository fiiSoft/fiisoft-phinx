<?php

namespace FiiSoft\Phinx\Console\Command;

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
        
        $missing = array_keys(array_diff_key($env->getVersionLog(), $this->manager->getMigrations()));
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
}