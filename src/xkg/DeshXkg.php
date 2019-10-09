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

namespace BrunoNatali\SysTema\Xkg;

use BrunoNatali\SysTema\Defines\GeneralDefines;
use BrunoNatali\SysTema\Misc\SystemInteraction;
use BrunoNatali\SysTema\Misc\GenericRun;
use BrunoNatali\SysTema\Misc\HandleManagerForClients as HandleManager;
use BrunoNatali\SysTema\Misc\Queue;
use BrunoNatali\SysTema\Group\Collector;
use BrunoNatali\EventLoop\Factory as LoopFactory;
use BrunoNatali\EventLoop\LoopInterface;
use BrunoNatali\Socket\ConnectionInterface as TheConnection;
use BrunoNatali\Socket\UnixConnector as UnixReactor;

class DeshXkg extends Collector implements XkgDefinesInterface
{
    Private $loop;
    Private $system;
    Private $manager;
    Private $genericRun;

    Private $server = [];
    Private $network = [];
    Private $zeroe;
    Private $status = self::XKG_STATUS['stoped'];

    function __construct(LoopInterface $loop = null)
    {
        $this->genericRun = new GenericRun();
        $this->system = new SystemInteraction();

        if (null === $loop) {
            $this->loop = LoopFactory::create();
        } else {
            $this->loop = &$loop;
        }

        $this->zeroe = hex2bin('00');

        $this->status = self::XKG_STATUS['started'];
        $this->instantiate();
    }

    /**
     * Used to call run() from another class
     */
    public function __call($method, $args)
    {
        $this->genericRun->$method($this->loop);
    }

    Private function instantiate()
    {
        $that = &$this;
        if (!$this->isManagerRunning()) {
            $this->loop->addTimer(1, function () use ($that){
                $that->instantiate();
            });
            $this->status = self::XKG_STATUS['waitManager'];
            return;
        }

        $this->status = self::XKG_STATUS['building'];
        $this->manager = new HandleManager($this->loop, self::XKG_NAME, null, true);

        foreach (self::XKG_SERVER_PORTS as $xkgPort) {
            $socketFileAddress = self::SYSTEM_TEMP_FOLDER . $xkgPort;
            if(file_exists($socketFileAddress))  unlink($socketFileAddress);

            switch ($xkgPort) {
                    case 'mngt':
                    $this->server[ 'mngt' ] =  [
                        'connection' => new UnixReactor($this->loop),
                        'data' => self::XKG_DEFAULT_DATA
                    ];
                    $this->server[ 'mngt' ][ 'connection' ]
                        ->connect('unix://' . $socketFileAddress, "dgram")
                        ->then(function (TheConnection $connection) use ($that) {
                    			$connection->on('data', function ($data) use ($that, $connection) {
                                    echo "Data received in (mngt) from (" . $connection->getRemoteAddress(true) . "): ";
                                    $that->dataOnMngt($data);
                    			});
                    		}
                    	);
                    break;
                    case 'control':
                    $that = &$this;
                    $this->server[ 'control' ] =  [
                        'connection' => new UnixReactor($this->loop),
                        'data' => self::XKG_DEFAULT_DATA,
                        'queue' => new Queue($this->loop),
                        'send' => function ($destination, $data, $to = null) use ($that) {
                            if (!isset($that->network[ $destination ])) return;
                            echo "Prepare to send" . PHP_EOL;
                            if ($to === null) $to = function ($resp) use ($that, $data, $destination) {
                                if (strpos($data, $that->network[ $destination ][ 'node' ]->setValByCommand($resp)) !== false) return true;
                                return false;
                            };
                            $that->network[ $destination ][ 'node' ]->sendCommand($data, $to);
                        }
                    ];
                    $this->server[ 'control' ][ 'connection' ] = $this->server[ 'control' ][ 'connection' ]
                        ->connect('unix://' . $socketFileAddress, "dgram");
                    $this->server[ 'control' ][ 'connection' ]->then(function (TheConnection $connection) use ($that) {
                			$connection->on('data', function ($data) use ($that, $connection) {
                                echo "Data received in (control) from (" . $connection->getRemoteAddress(true) . "): " . $data;

                                if (!isset($that->network[ ($destination = substr($data, 0, 16)) ])) return;

                                if (is_array($data = $that->getCommandByAnswer($rawData = substr($data, 16)))) {
                                    if (!$that->network[ $destination ][ 'node' ]->receiveCommand($data[0], $data[1]))
                                        $that->network[ $destination ][ 'node' ]->setReceivedUselessData($rawData);
                                } else {
                                    $that->network[ $destination ][ 'node' ]->setReceivedUselessData($rawData);
                                }
                			});
                		}
                	);
                    break;
            }
            $this->system->setFunctionOnAppAborted(function () use ($socketFileAddress) {
                unlink($socketFileAddress);
            });
        }

        $this->server[ 'control' ][ 'queue' ]->resume();

        $this->system->setAppInitiated(self::XKG_NAME);
        $this->instantiated = true;
        echo "iniciado" . PHP_EOL;
    }

    Private function dataOnMngt($data)
    {
        var_dump(bin2hex($data));
        $thisLenght = strlen($data);
        $this->server[ 'mngt' ][ 'data' ][ 'received' ][ 'bytes' ] += $thisLenght;
        $thisIPv6 = substr($data, 0, 16);

        if ($thisLenght == 66 || $thisLenght ==  76) {
            $thisParentIPv6 = substr($data, 16, 16);

            if ($thisIPv6 == $thisParentIPv6) { /* KA from Manager*/
                /*
                $tmp = array(
        			'bytes_sent'=> hexdec(bin2hex($par[2]{8} . $par[2]{7} . $par[2]{6} . $par[2]{5})),
        			'pkt_sent'	=> hexdec(bin2hex($par[2]{12} . $par[2]{11} . $par[2]{10} . $par[2]{9})),
       			    'noack'		=> hexdec(bin2hex($par[3]{0} . $par[2]{15} . $par[2]{14} . $par[2]{13})),
        			'num_routes'=> hexdec($nr{1} . $nr{0}),
        			'num_nbr'	=> hexdec($nbr{1} . $nbr{0}),
        			'ts'		=> microtime(true)
        		);
                */
            } else { /* KA from Node*/
                $this->createNewNode($thisIPv6);
                if ($this->network[ $thisIPv6 ][ 'node' ]->processKA($thisParentIPv6, substr($data, 32)))
                    echo $this->network[ $thisIPv6 ][ 'node' ]->getCurrentAsJSON();
            }
            var_dump(bin2hex($thisIPv6), bin2hex($thisParentIPv6));
            $this->server[ 'mngt' ][ 'data' ][ 'received' ][ 'packages' ] ++;
        } else {
            if (substr($data, 16, 4) === "nbr:") {
                $thisAllNbr = str_split(substr($data, 20), 16);
                $break = false;
                foreach ($thisAllNbr as $key => $thisNbr) {
                    $thisNbrMac = substr($thisNbr, 0, 8);
                    if ($break || !$this->checkIsValidMac($thisNbrMac)) {
                        unset($thisAllNbr[$key]);
                        $break = true;
                        continue;
                    }
                    $thisAllNbr[$key] = [
                        'mac' => bin2hex($thisNbrMac),
                        'etx' => bin2hex(substr($thisNbr, 8, 2)),
                        'rank' => bin2hex(substr($thisNbr, 10, 2)),
                        'rssi' => bin2hex(substr($thisNbr, 12, 14)),
                        'last-tx' => bin2hex(substr($thisNbr, 14)),
                    ];
                }

                $this->server[ 'mngt' ][ 'data' ][ 'received' ][ 'packages' ] ++;
            } else { /* ERROR */
                $this->server[ 'mngt' ][ 'data' ][ 'error'][] = $data;
            }
        }
    }

    Private function createNewNode(string $thisIPv6)
    {
        if (isset($this->network[ $thisIPv6 ])) return;

        $this->network[ $thisIPv6 ] = [
            'node' => new Node($thisIPv6, $this->loop),
            'id' => count($this->network) + 1  // Never be a 0 id
        ];
        $this->network[ $thisIPv6 ][ 'node' ]->setCommandConnection(
            $this->server[ 'control' ][ 'connection' ],
            self::SYSTEM_TEMP_FOLDER . self::BORDER_ROUTER_PORTS[0]
        );

        $this->updateNodeConfigs($thisIPv6);
    }

    Private function checkIsValidMac(string $mac): bool
    {
        $pontuation = 4;
        for ($i = 0 ; $i < 4 ; $i++) {
            if ($this->zeroe == $mac{$i}) $pontuation --;
        }
        return ($pontuation > 2 ? true : false);
    }

    Private function updateNodeConfigs($ip)
    {
        $this->sendCommand('myself', $ip, 'AT+KAPERIOD?');
        $this->sendCommand('myself', $ip, 'AT+GETRFPARAMS=10'); // Add for test only
        $this->sendCommand('myself', $ip, 'AT+FWVER?');
    }

    Private function sendCommand($from, $destination, $cmd, $tryCount = 5): int
    {
        if (!isset($this->network[ $destination ])) return self::XKG_ERRORS['node_not_exist'];

        $endCmd = strpos($cmd, '=');
        if ($endCmd === false) $endCmd = strpos($cmd, '?');
        if ($endCmd === false) return self::XKG_ERRORS['command_not_understood'];

        $cmdId = substr($cmd, 3, ($endCmd - 3)); // AT+FWVER? -> FWVER
        $that = &$this->network[ $destination ][ 'node' ];
        if ($that->commandQueue->isIdInTheList($cmdId))
            return self::XKG_ERRORS['command_already_queued'];

        $me = &$this;
        $that->commandQueue->push(function () use ($me, $that, $cmd, $cmdId, $from, $tryCount) {
            echo "Add to cmd queue into id($cmdId)" . PHP_EOL;
            $that->commandQueue->listAdd(
                $cmdId,
                function ($params) use ($that, $cmd) {
                    $that->sendCommand($cmd);
                },
                function ($data, $params) use ($me, $that, $cmdId, $from) {
                    if ($that->setValByCommand($params['id'], $data)) {
                        echo "Command answer is correct" . PHP_EOL;

                        if ($me->delivery($from) === 0) {
                            echo "Resuming queue ... " . PHP_EOL;
                            $that->commandQueue->resume();
                            return true;
                        }
                    }
                },
                function ($params) use ($that, $cmdId) {
                    if (!$that->commandQueue->getTryByListId($cmdId)) $that->commandQueue->resume();
                },
                $tryCount
            );
        });

        return 0;
    }

    Private function delivery($from): int
    {
        return 0;
        $parent = null;
        $child = null;
        if (is_string($from)) {
            $parent = $from;
        } else if (is_array($from) && count($from) === 1) {
            foreach ($from as $p => $c) {
                $parent = $p;
                $child = $c;
                $from = $p.$c;
            }
        } else {
            return self::XKG_ERRORS['wrong_command_source'];
        }
    }

    /*
        return array [
         0 - command
         1 - value
        ]
        or
        error code
    */
    Public function getCommandByAnswer($answer)
    {
        $answer = explode(':', trim($answer), 2);
        if (count($answer) !== 2) return self::XKG_ERRORS[ 'command_answer_not_understood' ];
        $answer[0] = substr($answer[0], 1);
        return $answer;
    }
}
