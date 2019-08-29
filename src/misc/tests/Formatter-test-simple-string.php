<?php declare(strict_types=1);

use BrunoNatali\SysTema\Misc\Format;

require __DIR__ . '/../../../../../autoload.php';

$theFormatterServer = new Format('theTestServer', 1);
$theFormatterClient = new Format('theTestClient', 99);

$clientString = 'Data to encode as a simple string';
$toSentToServerString = $theFormatterClient->encode($clientString, 1); // Send data to server

$receivedInServerString = $theFormatterServer->decode($toSentToServerString); // Receive data

if ($toSentToServerString === $receivedInServerString)  echo "SIMPLE STRING TEST OK" . PHP_EOL;
else echo "SIMPLE STRING TEST FAIL" . PHP_EOL;
?>
