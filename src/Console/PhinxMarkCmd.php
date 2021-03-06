<?php

namespace FiiSoft\Phinx\Console;

use FiiSoft\Phinx\Console\Command\Mark;
use FiiSoft\Phinx\PhinxCmdExecuteTrait;
use FiiSoft\Phinx\PhinxConfig;

final class PhinxMarkCmd extends Mark
{
    use PhinxCmdExecuteTrait;
    
    /**
     * @param PhinxConfig $config
     * @param string|null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(PhinxConfig $config, $name = null)
    {
        $this->phinxConfig = $config;
        
        parent::__construct($name);
    }
}