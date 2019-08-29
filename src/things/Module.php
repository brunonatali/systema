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

namespace BrunoNatali\SysTema\Things;

use React\EventLoop\LoopInterface;
use React\Socket\Connector;

class Module extends Thing
{
    Private $loop;

    Private $connector;
    Private $connected = false;

    Private $reconnectTime = 1; // Seconds

    Public function connect(LoopInterface $loop)
    {
        $this->connector = new React\Socket\UnixConnector($loop);

        $this->loop = $loop;

        $this->tryConnect();
    }

    Private function tryConnect()
    {
        $that = &$this;

        $this->connector->connect('/tmp/demo.sock')->then(
            function (React\Socket\ConnectionInterface $connection) use ($that) {



                $that->connected = true;
            },
            function (Exception $error) use ($that) {
                $that->connected = false;
                $this->loop->addTimer($this->reconnectTime, function () use ($that) {
                    $that->tryConnect();
                });
            }
        );



    }
}

?>
