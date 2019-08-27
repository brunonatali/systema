<?php declare(strict_types=1);

/**                                LICENSE
 *     The MIT License (MIT)
 *
 *     Copyright (c) 2019  Bruno Natali
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace BrunoNatali\SysTema\Telemetry;

use React\EventLoop\LoopInterface;

/**
 * Do the system telemetry
 */
class TelemetryClient
{
    Private $loop;
    private $timer;

    Private $registry = [0];            // Used to store all entries (empty first entry to return non zero value)
    Private $allNick = [                // Enable registry call from nickName
        'function' => [0],
        'name' => [0],
        'id' => [0]
    ];
    private $benchmark = [
        'avg' => [0],
        'current' => [0],
        'lastMin' => [0],
        'lastHour' => [0],
        'lastDay' => [0],
        'lastTS' => [0],
        'calendar' => [0]               // Now it`s not implemented, leave just for reference
    ];
    Private $benchItemBase = ['val' => 0, 'list' => ['val' => [], 'ts' => []]];



    function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;

        $this->calcTelemetry();
    }

// Calcular tempo fazendo uma referenca com a quantidade maxima de interaçoes no array  de times e a interaçao do ultimo dando uma nota e ajustando quando ele deve rodar da próxima vez
    public function calcTelemetry(float $time = 0.1)
    {
        $registryLen = count($this->registry);
        for($i = 1 ; $i < $registryLen ; $i++){


            rsort($this->registry[$i]['times']); // Sort descending order (newer first)
            $firstRegAlias = $this->registry[$i]['times'][0];
            $lastMinIndex = count($this->benchmark['lastMin'][$i]['list']['ts']);
            $lastHourIndex = count($this->benchmark['lastHour'][$i]['list']['ts']);
            $lastDayIndex = count($this->benchmark['lastDay'][$i]['list']['ts']);

            /*USED TO DO ARITHMETIC MEAN - Remove after implementer better way*/
            $lastMinVal = 0;
            $lastHourVal = 0;
            $lastDayVal = 0;

            // Arrange new entries
            foreach($this->registry[$i]['times'] as $key => $reg){
                if(($calc = ($firstRegAlias - $reg)) > 60){ // If is more than 1 minute since newer registry
                    if($calc > 3600){ // If is more than 1 hour since newer registry
                        if($calc <= 86400){ // If is less than 24H
                            $this->benchmark['lastDay'][$i]['list']['ts'][] = $reg;
                            $this->benchmark['lastDay'][$i]['list']['val'][] = $this->registry[$i]['procTime'][$key];
                            $lastDayVal += $this->registry[$i]['procTime'][$key];
                        }
                    } else {
                        $this->benchmark['lastHour'][$i]['list']['ts'][] = $reg;
                        $this->benchmark['lastHour'][$i]['list']['val'][] = $this->registry[$i]['procTime'][$key];
                        $lastHourVal += $this->registry[$i]['procTime'][$key];
                    }
                } else {
                    $this->benchmark['lastMin'][$i]['list']['ts'][] = $reg;
                    $this->benchmark['lastMin'][$i]['list']['val'][] = $this->registry[$i]['procTime'][$key];
                    $lastMinVal += $this->registry[$i]['procTime'][$key];
                }
                // unset($this->registry[$i]['times'][$key]); // Use that when enabled stop becharking
            }

            $this->registry[$i]['times'] = []; // Remove that when enabled stop becharking
            $this->registry[$i]['procTime'] = []; // and this

            // Arrange OLD entries
            $firstMinBaseAlias = $this->benchmark['lastMin'][$i]['list']['ts'][$lastMinIndex];
            for($n = 0 ; $n < $lastMinIndex ; $n++){
                if(($calc = ($firstMinBaseAlias - $this->benchmark['lastMin'][$i]['list']['ts'][$n])) > 60){ // If is more than 1 minute since newer registry
                    if($calc > 3600){ // If is more than 1 hour since newer registry
                        if($calc <= 86400){ // If is less than 24H
                            $this->benchmark['lastDay'][$i]['list']['ts'][] = $this->benchmark['lastMin'][$i]['list']['ts'][$n];
                            $this->benchmark['lastDay'][$i]['list']['val'][] = $this->benchmark['lastMin'][$i]['list']['val'][$n];
                            unset($this->benchmark['lastMin'][$i]['list']['ts'][$n], $this->benchmark['lastMin'][$i]['list']['val'][$n]);
                            $lastDayVal += $this->benchmark['lastMin'][$i]['list']['val'][$n];
                        }
                    } else {
                        $this->benchmark['lastHour'][$i]['list']['ts'][] = $this->benchmark['lastMin'][$i]['list']['ts'][$n];
                        $this->benchmark['lastHour'][$i]['list']['val'][] = $this->benchmark['lastMin'][$i]['list']['val'][$n];
                        unset($this->benchmark['lastMin'][$i]['list']['ts'][$n], $this->benchmark['lastMin'][$i]['list']['val'][$n]);
                        $lastHourVal += $this->benchmark['lastMin'][$i]['list']['val'][$n];
                    }
                } else {
                    $lastMinVal += $this->benchmark['lastMin'][$i]['list']['val'][$n];
                }
            }

            $firstHourBaseAlias = $this->benchmark['lastHour'][$i]['list']['ts'][$lastHourIndex];
            for($n = 0 ; $n < $lastHourIndex ; $n++){
                if(($calc = ($firstHourBaseAlias - $this->benchmark['lastHour'][$i]['list']['ts'][$n])) > 3600){ // If is more than 1 Hour since newer registry
                    if($calc <= 86400){ // If is less than 24H
                        $this->benchmark['lastDay'][$i]['list']['ts'][] = $this->benchmark['lastHour'][$i]['list']['ts'][$n];
                        $this->benchmark['lastDay'][$i]['list']['val'][] = $this->benchmark['lastHour'][$i]['list']['val'][$n];
                        unset($this->benchmark['lastHour'][$i]['list']['ts'][$n], $this->benchmark['lastHour'][$i]['list']['val'][$n]);
                        $lastDayVal += $this->benchmark['lastHour'][$i]['list']['val'][$n];
                    }
                } else {
                    $lastHourVal += $this->benchmark['lastHour'][$i]['list']['val'][$n];
                }
            }

            $firstDayBaseAlias = $this->benchmark['lastDay'][$i]['list']['ts'][$lastDayIndex];
            for($n = 0 ; $n < $lastDayIndex ; $n++){
                if(($calc = ($firstDayBaseAlias - $this->benchmark['lastDay'][$i]['list']['ts'][$n])) > 86400){ // If is more than 24H since newer registry
                    unset($this->benchmark['lastDay'][$i]['list']['ts'][$n], $this->benchmark['lastDay'][$i]['list']['val'][$n]);
                } else {
                    $lastDayVal += $this->benchmark['lastDay'][$i]['list']['val'][$n];
                }
            }

            // Eliminate dead keys
            rsort($this->benchmark['lastMin'][$i]['list']['ts']);
            rsort($this->benchmark['lastMin'][$i]['list']['val']);
            rsort($this->benchmark['lastHour'][$i]['list']['ts']);
            rsort($this->benchmark['lastHour'][$i]['list']['val']);
            rsort($this->benchmark['lastDay'][$i]['list']['ts']);
            rsort($this->benchmark['lastDay'][$i]['list']['val']);

            if(    ($lastMinLen = count($this->benchmark['lastMin'][$i]['list']['val'])) != count($this->benchmark['lastMin'][$i]['list']['ts']) &&
                ($lastHourLen = count($this->benchmark['lastHour'][$i]['list']['val'])) != count($this->benchmark['lastHour'][$i]['list']['ts']) &&
                ($lastDayLen = count($this->benchmark['lastDay'][$i]['list']['val'])) != count($this->benchmark['lastDay'][$i]['list']['ts']))
                    throw new \RuntimeException('Something went wrong current val lenghth and ts lenghth are different.');

            $this->benchmark['lastMin'][$i]['val'] = $lastMinVal / $lastMinLen;
            $this->benchmark['lastHour'][$i]['val'] = $lastMinVal / $lastHourLen;
            $this->benchmark['lastDay'][$i]['val'] = $lastMinVal / $lastDayLen;
        }

        $that = $this;
        $this->timer = $this->loop->addTimer($time, function () use ($time, $that) {
            try{
                $that->calcTelemetry($time);
            } catch (RuntimeException $e){
                /*[NEED] workaround*/
            }
        });
    }

    Public function functionTelemetryStart(string $functionName)
    {
        $theIndex = $this->registerTelemetry($functionName, 'function');
        $this->registry[$theIndex]['times'][ (++$this->registry[$theIndex]['timesIndex']) ] = microtime(true);
        $this->registry[$theIndex]['startTime']  = &$this->registry[$theIndex]['times'][ ($this->registry[$theIndex]['timesIndex']) ];
    }

    Public function functionTelemetryStop(string $functionName)
    {
        $theIndex = $this->registerTelemetry($functionName, 'function');

        $now = microtime(true);
        if($this->registry[$theIndex]['startTime'] === null) throw new Exception('STOP Telemetry with null START');
        $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ] = $now - $this->registry[$theIndex]['startTime'];

        if(isset($this->benchmark['avg'][$theIndex])){
            $this->benchmark['avg'][$theIndex] = ($this->benchmark['avg'][$theIndex] + $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ]) > 1;
            $this->benchmark['current'][$theIndex] = $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ];
        } else {
            $this->benchmark['avg'][$theIndex] = $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ];
            $this->benchmark['current'][$theIndex] = $this->benchmark['avg'][$theIndex];
            $this->benchmark['lastMin'][$theIndex] = $this->benchItemBase;
            $this->benchmark['lastHour'][$theIndex] = $this->benchItemBase;
            $this->benchmark['lastDay'][$theIndex] = $this->benchItemBase;
            $this->benchmark['lastTS'][$theIndex] = $now;
        }

        $this->registry[$theIndex]['startTime'] = null;
    }

    Public function returnTelemetryBenchVal(string $name, string $scope = null)
    {
        if($this->isTelemetryRegistered($name, $scope)) return $this->benchmark; //($scope === null ? $this->allNick['name'][$name] : $this->allNick[$scope][$name]);
    }

    Public function stopTelemetry()
    {
        $this->loop->cancelTimer($this->timer);
    }

    private function registerTelemetry(string $name, string $scope = null): int
    {
        if($this->isTelemetryRegistered($name, $scope)) return ($scope === null ? $this->allNick['name'][$name] : $this->allNick[$scope][$name]);

        $index = count($this->registry);
        $this->registry[$index] = array(
            'name' => $name,                     // Provided name
            'id' => md5(uniqid("", true)),   // Unique id
            'scope' => $scope,                   // Desired scope
            'allTime' => 0,                      // The runtime
            'startTime' => 0,                    // Last execution start time
            'times' => array(),                  // Time Stamp call history
            'timesIndex'=> 0,                    // Used to easy access times and to be light
            'procTime' => array()
        );

        if($scope === null){
            $this->allNick['name'][$name] = $index;
        } else {
            $this->allNick[$scope][$name] = $index;
        }
        return $index;
    }

    private function isTelemetryRegistered($registry, string $nick = null): bool
    {
        if($nick !== null){
            if(isset($this->allNick[$nick])) return isset($this->allNick[$nick][$registry]);
            throw new InvalidArgumentException('Nick \'' . $nick . '\' does not exist');
        } else {
            return isset($this->registry[$registry]);
        }
    }
}
?>
