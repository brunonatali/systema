<?php declare(strict_types=1);

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory;
use BrunoNatali\SysTema\Telemetry\TelemetryClient;

require __DIR__ . '/../../../../../autoload.php';

$loop = Factory::create();

final class TelemetryTest extends TelemetryClient
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

$telemetry = new TelemetryTest($loop);

$loop->run();
?>
