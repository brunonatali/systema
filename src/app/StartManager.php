<?php declare(strict_types=1);

use React\EventLoop\Factory;
use BrunoNatali\SysTema\Managers\Manager;

require __DIR__ . '/../../../../autoload.php';

$loop = Factory::create();

$theManager = new Manager($loop);

$loop->run();
?>
