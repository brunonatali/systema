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

namespace BrunoNatali\SysTema\Misc;

use BrunoNatali\SysTema\Managers\ManagerDefinesInterface;
use BrunoNatali\Socket\ConnectionInterface;
use BrunoNatali\EventLoop\LoopInterface;
use BrunoNatali\Socket\UnixConnector as TheClient;
use BrunoNatali\SysTema\Misc\Formatter;

class HandleManagerForClients implements ManagerDefinesInterface
{
    Private $id;
    Private $formatter;

    Private $mainSocket;
    Private $resourceType = "stream";
    Private $connForceSocket = false; // Force system use socket instead stream lib

    function __construct(LoopInterface $loop, $name, $id = self::MANAGER_ID, $connForceSocket = false)
    {
        $this->formatter = new Formatter($name, $id);

        $this->connForceSocket = $connForceSocket;
        $this->mainSocket = new TheClient($loop);

        $this->id = $id;

        $this->mainSocket
            ->connect('unix://' . self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS, $this->resourceType, $this->connForceSocket)
            ->then(function (ConnectionInterface $connection) {
                $data = json_encode(['request' => 'ID']);
                $counterEnc = null;
                if ($this->formatter->encode($data, self::MANAGER_ID, $counterEnc) === 0) $connection->write($data);
echo $data;
                $connection->on('data', function ($data) use ($counterEnc){
                    $this->formatter->decode($data, $counterDec);
                    var_dump($counterEnc, $counterDec, $data);
                });
        });
    }
}
?>
