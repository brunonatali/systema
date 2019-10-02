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

use BrunoNatali\SysTema\Things\Thing;
use BrunoNatali\SysTema\Managers\ManagerDefinesInterface;
use BrunoNatali\Socket\ConnectionInterface;
use BrunoNatali\EventLoop\LoopInterface;
use BrunoNatali\Socket\UnixConnector as TheClient;
use BrunoNatali\SysTema\Misc\Formatter;

class HandleManagerForClients implements ManagerDefinesInterface
{
    Private $me;

    Private $mainSocket;
    Private $resourceType = "stream";
    Private $connForceSocket = false; // Force system use socket instead stream lib

    function __construct(LoopInterface &$loop, $name, $id = null, $connForceSocket = false)
    {
        $this->id = ($id === null ? self::MANAGER_ID : $id);
echo "Criando interface principal para $name - $this->id" . PHP_EOL;
        $this->me = new Thing($loop, $this->id, $name);

        $this->connForceSocket = $connForceSocket;
        $this->mainSocket = new TheClient($loop);

echo "Conectando ao manager : ";
        $this->mainSocket
            ->connect('unix://' . self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS, $this->resourceType, $this->connForceSocket)
            ->then(function (ConnectionInterface $connection) {
                $this->me->addConnection($connection);
echo "Conectado" . PHP_EOL;
                $that = &$this;
                $this->me->sendMessage(['request' => 'ID'], self::MANAGER_ID,
                    function ($data, $params) use ($that) {
                        $data = json_decode($data, true);
                        if (isset($data['response']) && $data['response'])
                            $that->me->changeId($data['value']);
                        return true;
                    }
                );

                $connection->on('data', function ($data) use ($counterEnc){
                    $this->me->formatter->decode($data, $counterDec);
                    if (!$this->me->queue->listProccess($counterDec, $data)) {
                        // If this data is not in the queue list
                    }
                    var_dump($counterEnc, $counterDec, $data);
                });
        });
    }
}
?>
