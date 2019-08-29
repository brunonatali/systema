<?php declare(strict_types=1);

use BrunoNatali\SysTema\Misc\Formatter;

require __DIR__ . '/../../../../../autoload.php';

$theFormatterServer = new Formatter('theTestServer', 1);
$theFormatterClient = new Formatter('theTestClient', 99);
$theFormatterSubClient = new Formatter('theTestClient', 100);

$serverString = 'Data to be sent to inner client client, the sub-client';
$toSentToClientString = $serverString; // Send data to server

encode($theFormatterServer, $toSentToClientString, 100); // Prepare data to sub-client
//encode($theFormatterServer, $toSentToClientString, 99); // Encapsulate data to be handled by "master" client

if (($newDestination = decode($theFormatterClient, $toSentToClientString)) !== 0){
    decode($theFormatterSubClient, $toSentToClientString);
}

function encode($theFormatter, &$msg, $id)
{
    if ($theFormatter->encode($msg, $id) !== 0) {
        echo "SIMPLE STRING TEST FAIL (" . $theFormatter->getLastError() . ")" . PHP_EOL;
    }
}

function decode($theFormatter, &$msg)
{
    global $serverString;

    $serverResult = $theFormatter->decode($msg); // Receive data
    if ($serverResult === 0) {
        if ($msg === $serverString)  echo "SIMPLE STRING TEST OK" . PHP_EOL;
        else echo "SIMPLE STRING TEST FAIL (resultant string mismach)" . PHP_EOL;
        var_dump($serverString, $msg);
    } else if ($serverResult) {
        echo "SIMPLE STRING PRE-TEST FAIL (destination error -> " . $serverResult . ")" . PHP_EOL;
        return $serverResult;
    } else {
        echo "SIMPLE STRING TEST FAIL (return false)" . PHP_EOL;
    }
    return 0;
}
?>
