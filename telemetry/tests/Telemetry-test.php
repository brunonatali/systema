<?php declare(strict_types=1);

include_once("autoload/AutoLoad.php");

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory;
use BrunoNatali\SysTema\TelemetryClient;

require __DIR__ . '../../../../../vendor/autoload.php';

$loop = Factory::create();

final class telemetryTest extends TelemetryClient
{
    Private $telemetryClass;

    function __construct(LoopInterface $loop)
    {
        $this->telemetryClass = new TelemetryClient($loop);
    }

    public function basicTest(int $interations = 1000)
    {
        $this->telemetryClass->functionTelemetryStart('basicTest');

        $this->functionTestTiny($interations);

        $this->telemetryClass->functionTelemetryStop('basicTest');
    }

    private function functionTestTiny(int $interations): boolean
    {
        $temp = null;
        for($i = 0 ; $i <= $interations ; $i++){
            if($temp) $temp .= 'BN';
            else $temp = 'BN';
        }
    }
}

$telemetry = new telemetryTest($loop);

$loop->run();
?>
