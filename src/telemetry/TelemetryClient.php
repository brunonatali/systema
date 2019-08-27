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

namespace BrunoNatali\SysTema;

use React\EventLoop\LoopInterface;

echo "------>" . PHP_EOL;

/**
 * Do the system telemetry
 */
class TelemetryClient
{
    Private     $loop;
    private        $timer;

    Private        $registry        = [0];            // Used to store all entries (empty first entry to return non zero value)
    Private        $allNick        = [                // Enable registry call from nickName
        'function'        => [0],
        'name'            => [0],
        'id'            => [0]
    ];
    private        $benchmark        = [
        'avg'            => [0],
        'current'        => [0],
        'lastMin'        => [0],
        'lastHour'        => [0],
        'lastDay'        => [0],
        'lastTS'        => [0],
        'calendar'        => [0]                    // Now it`s not implemented, leave just for reference
    ];
    Private     $benchItemBase    = ['val' => 0, 'list' = ['val' = [], 'ts' = []]];



    function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;


    }

// Calcular tempo fazendo uma referenca com a quantidade maxima de interaçoes no array  de times e a interaçao do ultimo dando uma nota e ajustando quando ele deve rodar da próxima vez
    public function calcTelemetry(float $time = 0.1)
    {
        $registryLen = count($this->registry)
        for($i = 1 ; $i < $registryLen ; $i++){


            rsort($this->registry[$i]['times']); // Sort descending order (newer first)
            $firstRegAlias = $this->registry[$i]['times'][0];
            $lastMinIndex = count($this->benchmark['lastMin'][$index]['list']['ts']);
            $lastHourIndex = count($this->benchmark['lastHour'][$index]['list']['ts']);
            $lastDayIndex = count($this->benchmark['lastDay'][$index]['list']['ts']);

            /*USED TO DO ARITHMETIC MEAN - Remove after implementer better way*/
            $lastMinVal = 0;
            $lastHourVal = 0;
            $lastDayVal = 0;

            // Arrange new entries
            foreach($this->registry[$i]['times'] as $key => $reg){
                if(($calc = ($firstRegAlias - $reg)) > 60){ // If is more than 1 minute since newer registry
                    if($calc > 3600){ // If is more than 1 hour since newer registry
                        if($calc <= 86400){ // If is less than 24H
                            $this->benchmark['lastDay'][$index]['list']['ts'][] = $reg;
                            $this->benchmark['lastDay'][$index]['list']['val'][] = $this->registry[$i]['procTime'][$key];
                            $lastDayVal += $this->registry[$i]['procTime'][$key];
                        }
                    } else {
                        $this->benchmark['lastHour'][$index]['list']['ts'][] = $reg;
                        $this->benchmark['lastHour'][$index]['list']['val'][] = $this->registry[$i]['procTime'][$key];
                        $lastHourVal += $this->registry[$i]['procTime'][$key];
                    }
                } else {
                    $this->benchmark['lastMin'][$index]['list']['ts'][] = $reg;
                    $this->benchmark['lastMin'][$index]['list']['val'][] = $this->registry[$i]['procTime'][$key];
                    $lastMinVal += $this->registry[$i]['procTime'][$key];
                }
                // unset($this->registry[$i]['times'][$key]); // Use that when enabled stop becharking
            }

            $this->registry[$i]['times'] = []; // Remove that when enabled stop becharking
            $this->registry[$i]['procTime'] = []; // and this

            // Arrange OLD entries
            $firstMinBaseAlias = $this->benchmark['lastMin'][$index]['list']['ts'][$lastMinIndex];
            for($n = 0 ; $n < $lastMinIndex ; $n++){
                if(($calc = ($firstMinBaseAlias - $this->benchmark['lastMin'][$index]['list']['ts'][$n])) > 60){ // If is more than 1 minute since newer registry
                    if($calc > 3600){ // If is more than 1 hour since newer registry
                        if($calc <= 86400){ // If is less than 24H
                            $this->benchmark['lastDay'][$index]['list']['ts'][] = $this->benchmark['lastMin'][$index]['list']['ts'][$n];
                            $this->benchmark['lastDay'][$index]['list']['val'][] = $this->benchmark['lastMin'][$index]['list']['val'][$n];
                            unset($this->benchmark['lastMin'][$index]['list']['ts'][$n], $this->benchmark['lastMin'][$index]['list']['val'][$n]);
                            $lastDayVal += $this->benchmark['lastMin'][$index]['list']['val'][$n];
                        }
                    } else {
                        $this->benchmark['lastHour'][$index]['list']['ts'][] = $this->benchmark['lastMin'][$index]['list']['ts'][$n];
                        $this->benchmark['lastHour'][$index]['list']['val'][] = $this->benchmark['lastMin'][$index]['list']['val'][$n];
                        unset($this->benchmark['lastMin'][$index]['list']['ts'][$n], $this->benchmark['lastMin'][$index]['list']['val'][$n]);
                        $lastHourVal += $this->benchmark['lastMin'][$index]['list']['val'][$n];
                    }
                } else {
                    $lastMinVal += $this->benchmark['lastMin'][$index]['list']['val'][$n];
                }
            }

            $firstHourBaseAlias = $this->benchmark['lastHour'][$index]['list']['ts'][$lastHourIndex];
            for($n = 0 ; $n < $lastHourIndex ; $n++){
                if(($calc = ($firstHourBaseAlias - $this->benchmark['lastHour'][$index]['list']['ts'][$n])) > 3600){ // If is more than 1 Hour since newer registry
                    if($calc <= 86400){ // If is less than 24H
                        $this->benchmark['lastDay'][$index]['list']['ts'][] = $this->benchmark['lastHour'][$index]['list']['ts'][$n];
                        $this->benchmark['lastDay'][$index]['list']['val'][] = $this->benchmark['lastHour'][$index]['list']['val'][$n];
                        unset($this->benchmark['lastHour'][$index]['list']['ts'][$n], $this->benchmark['lastHour'][$index]['list']['val'][$n]);
                        $lastDayVal += $this->benchmark['lastHour'][$index]['list']['val'][$n];
                    }
                } else {
                    $lastHourVal += $this->benchmark['lastHour'][$index]['list']['val'][$n];
                }
            }

            $firstDayBaseAlias = $this->benchmark['lastDay'][$index]['list']['ts'][$lastDayIndex];
            for($n = 0 ; $n < $lastDayIndex ; $n++){
                if(($calc = ($firstDayBaseAlias - $this->benchmark['lastDay'][$index]['list']['ts'][$n])) > 86400){ // If is more than 24H since newer registry
                    unset($this->benchmark['lastDay'][$index]['list']['ts'][$n], $this->benchmark['lastDay'][$index]['list']['val'][$n]);
                } else {
                    $lastDayVal += $this->benchmark['lastDay'][$index]['list']['val'][$n];
                }
            }

            // Eliminate dead keys
            rsort($this->benchmark['lastMin'][$index]['list']['ts']);
            rsort($this->benchmark['lastMin'][$index]['list']['val']);
            rsort($this->benchmark['lastHour'][$index]['list']['ts']);
            rsort($this->benchmark['lastHour'][$index]['list']['val']);
            rsort($this->benchmark['lastDay'][$index]['list']['ts']);
            rsort($this->benchmark['lastDay'][$index]['list']['val']);

            if(    ($lastMinLen = count($this->benchmark['lastMin'][$index]['list']['val'])) != count($this->benchmark['lastMin'][$index]['list']['ts']) &&
                ($lastHourLen = count($this->benchmark['lastHour'][$index]['list']['val'])) != count($this->benchmark['lastHour'][$index]['list']['ts']) &&
                ($lastDayLen = count($this->benchmark['lastDay'][$index]['list']['val'])) != count($this->benchmark['lastDay'][$index]['list']['ts']))
                    throw new \RuntimeException('Something went wrong current val lenghth and ts lenghth are different.');

            $this->benchmark['lastMin'][$index]['val'] = $lastMinVal / $lastMinLen;
            $this->benchmark['lastHour'][$index]['val'] = $lastMinVal / $lastHourLen;
            $this->benchmark['lastDay'][$index]['val'] = $lastMinVal / $lastDayLen;
        }

        $that = $this;
        $this->timer = $loop->addTimer($time, function () use ($time, $that) {
            try{
                $that->calcTelemetry($time);
            } catch RuntimeException ($e){

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

        if(isset($this->benchmark['avg'][$index])){
            $this->benchmark['avg'][$index] = ($this->benchmark['avg'][$index] + $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ]) > 1;
            $this->benchmark['current'][$index] = $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ];
        } else {
            $this->benchmark['avg'][$index] = $this->registry[$theIndex]['procTime'][ ($this->registry[$theIndex]['timesIndex']) ];
            $this->benchmark['current'][$index] = $this->benchmark['avg'][$index];
            $this->benchmark['lastMin'][$index] = $this->benchItemBase;
            $this->benchmark['lastHour'][$index] = $this->benchItemBase;
            $this->benchmark['lastDay'][$index] = $this->benchItemBase;
            $this->benchmark['lastTS'][$index] = $now;
        }

        $this->registry[$theIndex]['startTime'] = null;
    }

    private function registerTelemetry(string $name, string $scope = null): int
    {
        if($this->isTelemetryRegistered($name, $scope)) return ($scope === null, $this->allNick['name'][$name], $this->allNick[$scope][$name]);

        $index = count($this->registry);
        $this->registry[$index] = array(
            'name'        => $name,                        // Provided name
            'id'        => md5(uniqid(rand(), true)),    // Unique id
            'scope'     => $scope,                        // Desired scope
            'allTime'     => 0,                            // The runtime
            'startTime' => 0,                            // Last execution start time
            'times'        => array(),                        // Time Stamp call history
            'timesIndex'=> 0,                            // Used to easy access times and to be light
            'procTime'    => array()
        );

        if($scope === null){
            $this->allNick['name'][$name] = $index
        } else {
            $this->allNick[$scope][$name] = $index
        }
        return $index;
    }

    private function isTelemetryRegistered($registry, string $nick = null): boolean
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
