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
use BrunoNatali\Socket\Server as UnixReactor;

class Sockets extends Collector implements SocketsDefinesInterface
{
    Private $loop;
    Private $system;

    Private $genericRun;

    function __construct(LoopInterface $loop = null)
    {
        $this->genericRun = new GenericRun();
        $this->system = new SystemInteraction();

        if (null === $loop) {
            $this->loop = LoopFactory::create();
        } else {
            $this->loop = &$loop;
        }

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
        $this->manager = new HandleManager($this->loop, self::SOCKETS_NAME, null, true);
        $this->manager->functionOnData(function ($data) {
            $this->proccessData($data);
        });

        $this->system->setAppInitiated(self::SOCKETS_NAME);
        $this->instantiated = true;
        echo "iniciado" . PHP_EOL;
    }

    Private function proccessData($data)
    {

    }
}
?>
