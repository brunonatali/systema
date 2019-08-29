<?php declare(strict_types=1);

use BrunoNatali\SysTema\Misc\Formatter;

require __DIR__ . '/../../../../../autoload.php';

$theFormatterServer = new Formatter('theTestServer', 1);
$theFormatterClient = new Formatter('theTestClient', 99);

$clientString = 'Data to encode as a simple string';
$toSentToServerString = $clientString; // Send data to server
if ($theFormatterClient->encode($toSentToServerString, 1) === 0) {
    $serverResult = $theFormatterServer->decode($toSentToServerString); // Receive data
    if ($serverResult === 0) {
        if ($toSentToServerString === $clientString)  echo "SIMPLE STRING TEST OK" . PHP_EOL;
        else echo "SIMPLE STRING TEST FAIL (resultant string mismach)" . PHP_EOL;
        var_dump($clientString, $toSentToServerString);
    } else if ($serverResult) {
        echo "SIMPLE STRING TEST FAIL (destination error -> " . $serverResult . ")" . PHP_EOL;
    } else {
        echo "SIMPLE STRING TEST FAIL (return false)" . PHP_EOL;
    }
} else {
    echo "SIMPLE STRING TEST FAIL (" . $theFormatterClient->getLastError() . ")" . PHP_EOL;
}
?>
