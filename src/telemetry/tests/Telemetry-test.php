<?php declare(strict_types=1);

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory;
use BrunoNatali\SysTema\Telemetry\TelemetryClient;

require __DIR__ . '/../../../../../autoload.php';

$loop = Factory::create();

final class TelemetryTest extends TelemetryClient
{
    Private $telemetryClass;
    Private $loop;

    function __construct(LoopInterface $loop)
    {
        $this->telemetryClass = new TelemetryClient($loop);

        $this->loop = $loop;
    }

    public function basicTest(int $interations = 1000)
    {
        $this->telemetryClass->functionTelemetryStart('basicTest');

        $this->functionTestTiny($interations);

        $this->telemetryClass->functionTelemetryStop('basicTest');

        print_r($this->telemetryClass->returnTelemetryBenchVal('basicTest', 'function'));
    }

    public function stopTest()
    {
        $this->telemetryClass->stopTelemetry();
    }

    private function functionTestTiny(int $interations)
    {
        $temp = null;
        for($i = 0 ; $i <= $interations ; $i++){
            if($temp) $temp .= 'BN';
            else $temp = 'BN';
        }
    }
}

$thisTelemetrytest = new TelemetryTest($loop);

$loop->addTimer(1, function () use ($thisTelemetrytest) {
    $thisTelemetrytest->basicTest();
    $thisTelemetrytest->stopTest();
});

$loop->run();
?>
