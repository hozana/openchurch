<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';
new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

$dbExists = false;

// Check if the database exists
if ('0' !== getenv('KEEP_DB')) {
    echo 'Check if database exists: ';
    $databaseUrl = getenv('DATABASE_URL');
    $dbParts = parse_url($databaseUrl);
    $testDBname = substr($dbParts['path'], 1 + strpos($dbParts['path'], '/')).'_test';

    $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$testDBname."'";
    $cmd = 'bin/console doctrine:query:sql "'.$sql.'"';
    try {
        $dbExists = str_contains(
            Process::fromShellCommandline($cmd)->setTimeout(1000)->mustRun()->getOutput(),
            $testDBname
        );
        echo "\033[01;32m \xE2\x9C\x94";
    } catch (Exception) {
        echo "\033[01;31m \xE2\x9D\x8C";
    }
    echo "\033[0m".PHP_EOL;
}

$commands = [];
if (!$dbExists) {
    $commands[] = 'bin/console doctrine:database:drop --force --if-exists --env=test';
    $commands[] = 'bin/console doctrine:database:create --env=test';
    $commands[] = 'bin/console doctrine:schema:drop --force --env=test';
    $commands[] = 'bin/console doctrine:query:sql --env=test "DROP TABLE IF EXISTS doctrine_migration_versions"';
    $commands[] = 'bin/console doctrine:migrations:sync-metadata-storage --env=test';
    $commands[] = 'bin/console doctrine:migrations:migrate --no-interaction --env=test';
}

foreach ($commands as $command) {
    echo "Test bootstrap: running $command\n";
    Process::fromShellCommandline($command)->setTimeout(3600)->mustRun();
}
