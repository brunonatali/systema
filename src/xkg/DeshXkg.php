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
use BrunoNatali\SysTema\Misc\Formatter;
use BrunoNatali\SysTema\Group\Collector;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\UnixConnector as UnixReactor;

class DeshXkg extends Collector implements XkgDefinesInterface
{
    Private $formatter;
    Private $loop;
    Private $system;

    Private $server = [];

    function __construct(LoopInterface $loop = null)
    {
        $this->formatter = new Formatter(self::XKG_NAME, self::MANAGER_ID);
        $this->system = new SystemInteraction();

        if (null === $loop) {
            $this->loop = LoopFactory::create();
        } else {
            $this->loop = &$loop;
        }

        $this->instantiate();
    }

    Private function instantiate()
    {
        if (!$this->isManagerRunning()) {
            $that = &$this;
            $loop->addTimer(1, function () use ($that){
                $that->instantiate();
            });
        }

        foreach (self::XKG_SERVER_PORTS as $xkgPort) {
            if(file_exists(self::SYSTEM_TEMP_FOLDER . $xkgPort))  unlink(self::SYSTEM_TEMP_FOLDER . $xkgPort);

            $this->server[$xkgPort] = new UnixReactor($loop);

            $this->server[$xkgPort]
                ->connect('unix://' . self::SYSTEM_TEMP_FOLDER . $xkgPort, "dgram")
                ->then(function (React\Socket\ConnectionInterface $connection) use ($xkgPort, &$loop) {
        			$connection->on('data', function ($data) use ($xkgPort, &$loop) {
                        echo "Data received in ($xkgPort) ";
                        var_dump($data);
        			});
        		}
        	);
        }

        $this->system->setAppInitiated(self::XKG_NAME);
        $this->instantiated = true;
    }

    /**
     * Run the application by entering the event loop
     * @throws \RuntimeException If a loop was not previously specified
     */
    Public function run()
    {
        if (null === $this->loop) {
            throw new \RuntimeException("A React Loop was not provided during instantiation");
        }

        $this->loop->run();
    }

}
