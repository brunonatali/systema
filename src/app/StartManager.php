<?php declare(strict_types=1);

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
