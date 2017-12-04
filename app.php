<?php

use FiiSoft\Phinx\Console\GenericPhinxCmd;
use FiiSoft\Phinx\Console\PhinxBreakpointCmd;
use FiiSoft\Phinx\Console\PhinxCleanupCmd;
use FiiSoft\Phinx\Console\PhinxCreateCmd;
use FiiSoft\Phinx\Console\PhinxMarkCmd;
use FiiSoft\Phinx\Console\PhinxMigrateCmd;
use FiiSoft\Phinx\Console\PhinxRemoveCmd;
use FiiSoft\Phinx\Console\PhinxRollbackCmd;
use FiiSoft\Phinx\Console\PhinxSeedCreateCmd;
use FiiSoft\Phinx\Console\PhinxSeedRunCmd;
use FiiSoft\Phinx\Console\PhinxStatusCmd;
use FiiSoft\Phinx\Console\PhinxTestCmd;
use FiiSoft\Phinx\Console\PhinxUnmarkCmd;
use FiiSoft\Phinx\PhinxConfig;
use Symfony\Component\Console\Application;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'vendor', 'autoload.php']);

$configFile = getcwd() . DIRECTORY_SEPARATOR . 'config.php';
if (!is_file($configFile)) {
    echo PHP_EOL, 'configuration file for phinx not available: ', $configFile, PHP_EOL;
    exit(1);
}

$config = new PhinxConfig(require $configFile);
$app = new Application();

$app->addCommands([
    new GenericPhinxCmd($config),
    new PhinxBreakpointCmd($config),
    new PhinxCleanupCmd($config),
    new PhinxCreateCmd($config),
    new PhinxMarkCmd($config),
    new PhinxMigrateCmd($config),
    new PhinxRemoveCmd($config),
    new PhinxRollbackCmd($config),
    new PhinxSeedCreateCmd($config),
    new PhinxSeedRunCmd($config),
    new PhinxStatusCmd($config),
    new PhinxTestCmd($config),
    new PhinxUnmarkCmd($config),
]);

$app->run();