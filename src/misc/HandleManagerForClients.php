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
    Public $modules = [];
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

        $that = &$this;
        $this->me->queue->push(function () use ($that, $name) {
            $that->me->sendMessage(
                ['request' => 'CHANGE_NAME', 'value' => $name],
                self::MANAGER_ID,
                function ($data, $params) use ($that) {
                    $data = json_decode($data, true);
                    if (isset($data['response']) && $data['response'])
echo "Alterado nome da aplicacao" . PHP_EOL;
                    return true;
                }
            );
        });
        $this->me->queue->push(function () use ($that, $name) {
            $that->me->sendMessage(
                ['request' => 'LIST_CLIENTS'],
                self::MANAGER_ID,
                function ($data, $params) use ($that) {
                    $data = json_decode($data, true);
                    if (isset($data['response']) && $data['response'])
                        $that->modules = $data['value'];
print_r($that->modules);
                    return true;
                }
            );
        });

echo "Conectando ao manager : ";
        $this->mainSocket
            ->connect('unix://' . self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS, $this->resourceType, $this->connForceSocket)
            ->then(function (ConnectionInterface $connection) use ($that) {
                $that->me->addConnection($connection);
echo "Conectado" . PHP_EOL;
                $that->me->sendMessage(
                    ['request' => 'ID'],
                    self::MANAGER_ID,
                    function ($data, $params) use ($that) {
echo "Func proc data - $data" . PHP_EOL;
                        $data = json_decode($data, true);
                        if (isset($data['response']) && $data['response']) {
echo "Change id" . PHP_EOL;
                            $that->me->changeId($data['value']);
echo "QueueResume" . PHP_EOL;
                            $that->me->queue->resume();
                        }
                        return true;
                    }
                );

                $connection->on('data', function ($data) use ($that) {
                    var_dump($that->me->formatter->decode($data, $counterDec));
                    if (!$that->me->queue->listProccess($counterDec, $data)) {
                        // If this data is not in the queue list
echo "Data not in the list".PHP_EOL;
                    }
                    var_dump($counterDec, $data);
                });
        });
    }
}
?>
