<?php

namespace FiiSoft\Phinx\Console;

use FiiSoft\Phinx\PhinxConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenericPhinxCmd extends Command
{
    const CMDS_WITH_ARGS = ['create', 'seed:create', 'mark', 'unmark', 'remove'];
    
    /** @var PhinxConfig */
    private $phinxConfig;
    
    /**
     * @param PhinxConfig $config
     * @param string|null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(PhinxConfig $config, $name = null)
    {
        $this->phinxConfig = $config;
        
        parent::__construct($name ?: 'phinx');
    }
    
    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setDescription('Run particular Phinx command');
        
        $this->addArgument(
            'config',
            InputArgument::REQUIRED,
            'Name of configuration, one of: '.implode(', ', $this->phinxConfig->configs)
        );
        
        $this->addArgument('environment', InputArgument::OPTIONAL, 'Environment (if not default)');
        $this->addArgument('action', InputArgument::OPTIONAL, 'Action: status, create, migrate, rollback, etc.');
        $this->addArgument('param', InputArgument::OPTIONAL, 'Main param for Phinx command: name of migration or seed to create, target version etc.');
        
        $this->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Environment (if not default)');
        $this->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Target (if command needs it)');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Exception
     * @return int status of operation
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('config');
        if (!$this->phinxConfig->hasConfig($configName)) {
            $output->writeln('<error>configuration '.$configName.' not found</error>');
            return 1;
        }
        
        $action = $input->getArgument('action');
        
        $env = $input->getArgument('environment');
        if ($env) {
            if (!$action) {
                if ($input->getOption('environment')) {
                    $action = $env;
                    $env = $input->getOption('environment');
                } else {
                    $action = $env;
                    $env = null;
                }
            } elseif (in_array($env, self::CMDS_WITH_ARGS, true)) {
                if ($input->getOption('environment')) {
                    $input->setArgument('param', $action);
                    $action = $env;
                    $env = $input->getOption('environment');
                } elseif (!$input->getArgument('param')) {
                    $input->setArgument('param', $action);
                    $action = $env;
                    $env = null;
                }
            }
        } else {
            $action = 'status';
        }
        
        $output->writeln('use configuration <info>'.$configName.'</info>');
        
        if ($env) {
            $output->writeln('use environment <info>'.$env.'</info>');
        }
        
        $output->writeln('call action <info>'.$action.'</info>');
        
        switch ($action) {
            case 'breakpoint':
                $cmd = new PhinxBreakpointCmd($this->phinxConfig);
            break;
            case 'create':
                $cmd = new PhinxCreateCmd($this->phinxConfig);
            break;
            case 'migrate':
                $cmd = new PhinxMigrateCmd($this->phinxConfig);
            break;
            case 'rollback':
                $cmd = new PhinxRollbackCmd($this->phinxConfig);
            break;
            case 'seed:create':
                $cmd = new PhinxSeedCreateCmd($this->phinxConfig);
            break;
            case 'seed:run':
                $cmd = new PhinxSeedRunCmd($this->phinxConfig);
            break;
            case 'status':
                $cmd = new PhinxStatusCmd($this->phinxConfig);
            break;
            case 'mark':
                $cmd = new PhinxMarkCmd($this->phinxConfig);
            break;
            case 'unmark':
                $cmd = new PhinxUnmarkCmd($this->phinxConfig);
            break;
            case 'remove':
                $cmd = new PhinxRemoveCmd($this->phinxConfig);
            break;
            case 'cleanup':
                $cmd = new PhinxCleanupCmd($this->phinxConfig);
            break;
            case 'test':
                $cmd = new PhinxTestCmd($this->phinxConfig);
            break;
            default:
                $output->writeln('<error>invalid action: '.$action.'</error>');
                return 1;
        }
    
        $args = [
            'config' => $configName,
        ];
    
        foreach (['name', 'target'] as $arg) {
            if ($cmd->getDefinition()->hasArgument($arg)) {
                $args[$arg] = $input->getArgument('param');
            }
        }
    
        if ($cmd->getDefinition()->hasOption('environment')) {
            $args['environment'] = $env;
        }
    
        return $cmd->run(new ArrayInput($args), $output);
    }
}