<?php declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");

use BrunoNatali\SysTema\Xkg\DeshXkg;

require __DIR__ . '/../../../../autoload.php';

$theXkg = new DeshXkg();

$theXkg->run();
?>
