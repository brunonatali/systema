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

namespace BrunoNatali\SysTema\Sockets;

use BrunoNatali\Socket\ConnectionInterface;
use BrunoNatali\Socket\Server as SocketServer;
use BrunoNatali\Socket\LimitingServer as MaxClients;

class Socket implements SocketsDefinesInterface
{
    Private $loop;
    Private $socket = null;
    Private $address = null;
    Private $type = null;
    Private $clientLimit = 0;
    Private $clients = [];
    Private $events = [
        'data' => null,
        'connect' => null,
        'disconnect' => null,
    ];

    function __construct(LoopInterface &$loop, $arrayOfRequest = [])
    {
        $this->loop = &$loop;
    }

    Public function set($arrayOfRequest = [])
    {
        $toReturn = true;
        if (isset($arrayOfRequest[ 'new' ]) && isset($arrayOfRequest[ 'address' ])) {
            switch ($arrayOfRequest[ 'new' ]) {
                case 'tcp':
                    $this->close();
                    $this->type = 'tcp';
                    $this->address = $arrayOfRequest[ 'address' ];
                    $this->socket = new SocketServer('tcp://' . $arrayOfRequest[ 'address' ], $this->loop);
                    break;
                case 'udp':
                    $this->close();
                    $this->type = 'udp';
                    $this->address = $arrayOfRequest[ 'address' ];
                    $this->socket = new SocketServer('udp://' . $arrayOfRequest[ 'address' ], $this->loop);
                    break;
                case 'unix':
                    $this->close();
                    $this->type = 'unix';
                    $this->address = self::SYSTEM_RUN_FOLDER[0] . $arrayOfRequest[ 'address' ];
                    $this->socket = new SocketServer('unix://' . self::SYSTEM_RUN_FOLDER[0] . $arrayOfRequest[ 'address' ], $this->loop);
                    break;
                default:
                    $toReturn = false;
                    break;
            }

            if ($toReturn !== false) {
                $that = &$this;
                $this->limitClients();
                $this->socket->on('connection', function (ConnectionInterface $client) use ($that) {
                    $thisClientId = count($that->clients);
                    $that->clients[ $thisClientId ] = [
                        'remoteAddress' => $client->getRemoteAddress(),
                        'connection' => &$client,
                        'data' => [
                            'packages' => 0,
                            'bytes' => 0
                        ]
                    ];
                    if (is_callable($that->events[ 'connect' ])) $that->events[ 'connect' ]($thisClientId);

                    $client->on('data', function ($data) use ($that) {
                        if (is_callable($that->events[ 'data' ])) $that->events[ 'data' ]($data);
                        $that->clients[ $thisClientId ][ 'data' ][ 'packages' ] ++;
                        $that->clients[ $thisClientId ][ 'data' ][ 'bytes' ] += strlen($data);
                    });

                    $client->on('close', function () use ($that, $thisClientId) {
                        if (is_callable($that->events[ 'disconnect' ])) $that->events[ 'disconnect' ]($thisClientId);
                        unset($that->clients[ $thisClientId ]);
                    });

                    $client->on('error', function () use ($that, $thisClientId) {
                        if (is_callable($that->events[ 'disconnect' ])) $that->events[ 'disconnect' ]($thisClientId);
                        unset($that->clients[ $thisClientId ]);
                    });
                });
            }
        }

        if (isset($arrayOfRequest[ 'on' ])) {
            if ($this->socket !== null)
            foreach ($arrayOfRequest[ 'on' ] as $key => $value) {
                switch ($key) {
                    case 'data':
                        $this->events[ 'data' ] = $value;
                        break;
                    case 'connect':
                        $this->events[ 'connect' ] = $value;
                        break;
                    case 'disconnect':
                        $this->events[ 'disconnect' ] = $value;
                        break;
                    default:
                        $toReturn = false;
                        break;
                }
            }
        }

        if (isset($arrayOfRequest[ 'limitClients' ])) {
            $this->limitClients($arrayOfRequest[ 'limitClients' ]);
        }

        return $toReturn;
    }

    Private function limitClients($max = 0)
    {
        if (is_numeric($max)) $max = intval($max);
        else $max = 0;
        $this->clientLimit = (!$max || !$this->clientLimit ? 1 : $max);
        $this->socket = new MaxClients($this->socket, $this->clientLimit);
    }

    Private function close(ConnectionInterface &$socket = null)
    {
        if ($socket !== null) {
            $socket->close();
            $socket = null;
        } else if ($this->socket !== null) {
            $this->socket->close();
            $this->socket = null;
            if ($this->type == 'unix') unlink($this->address);
        }
    }
}
?>
