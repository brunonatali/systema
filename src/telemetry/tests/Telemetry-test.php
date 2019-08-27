<?php declare(strict_types=1);

namespace SysTema\Telemetry;

include_once("autoload/AutoLoad.php");

include("SysTema\React\EventLoop\Factory.php");
include("SysTema\React\EventLoop\LoopInterface.php");
//use SysTema\React\EventLoop\LoopInterface;
//use SysTema\React\EventLoop\Factory;
use SysTema\TelemetryClient;

print_r(get_declared_classes());
print_r(get_declared_interfaces());

$eventLoopFactory = new Factory;
$loop = $eventLoopFactory->create();

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
?>
