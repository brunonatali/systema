<?php declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");

use BrunoNatali\SysTema\Managers\Manager;

require __DIR__ . '/../../../../autoload.php';

$theManager = new Manager();

if ($theManager->isInstantiated()) {
    echo "System started" . PHP_EOL;

    $theManager->run();
} else {
    // Do something to fix
}
?>
