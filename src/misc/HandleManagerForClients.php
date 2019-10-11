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
    Private $loop;
    Private $onData = [];
    Private $onJson = [];
    Public $modules = [];
    Private $mainSocket;
    Private $resourceType = "stream";
    Private $connForceSocket = false; // Force system use socket instead stream lib

    function __construct(LoopInterface &$loop, $name, $id = null, $connForceSocket = false)
    {
        $this->id = ($id === null ? self::MANAGER_ID : $id);
        $this->loop = &$loop;
        $this->connForceSocket = $connForceSocket;

        $this->me = new Thing($this->loop, $this->id, $name);

        $this->queueStartup();
        $this->connectToManager();

        $this->functionOnData(function ($data) {
            /**
            * Add primary function to proccess received json
            */
            if (($decodedJson = json_decode($data, true)) !== false) {
                return $this->functionWhenJSON($decodedJson);
            }
            return false;
        });
    }

    Private function queueStartup()
    {
        $that = &$this;
        $this->me->queue->push(function () use ($that) {
            $that->me->sendMessage(
                ['request' => 'CHANGE_NAME', 'value' => $that->me->getName()],
                self::MANAGER_ID,
                function ($data, $params) use ($that) {
                    $data = json_decode($data, true);
                    if (isset($data['response']) && $data['response'])
                    return true;
                }
            );
        });
        $this->me->queue->push(function () use ($that) {
            $that->me->sendMessage(
                ['request' => 'LIST_CLIENTS'],
                self::MANAGER_ID,
                function ($data, $params) use ($that) {
                    $data = json_decode($data, true);
                    if (isset($data['response']) && $data['response'])
                        $that->modules = $data['value'];
                    return true;
                }
            );
        });
    }

    Private function connectToManager()
    {
        if (!$this->isManagerRunning()) {
            $this->loop->addTimer(1, function (){
                $this->connectToManager();
            });
            return false;
        }
        $that = &$this;
        $this->mainSocket = new TheClient($this->loop);
        $this->mainSocket
            ->connect('unix://' . self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS, $this->resourceType, $this->connForceSocket)
            ->then(function (ConnectionInterface $connection) use ($that) {

            $connection->on('data', function ($data) use ($that) {
                $originalData = $data;
                if (($destination = $that->me->formatter->decode($data, $counterDec)) !== false) {
                    if ($destination === 0) { // is for me

                        $result = true;
                        if ($that->me->queue->isIdInTheList($counterDec)) {
                            /**
                            * Pre proccess queue and let functions know if this data need to be proccessed by internal env
                            */
                            $result = $that->me->queue->listProccess($counterDec, $data);
                        } else {
                            /**
                            * Proccess internal env functions
                            */
                            echo "Data not in the list" . PHP_EOL;
                            foreach ($this->onData as $key => $onDataFunc) {
                                echo "Proccessing onDataFunc ($key)" . PHP_EOL;
                                $result = $result AND $onDataFunc($data);
                            }
                        }
                        return $result; // Now this return is ommited, but could be used to answer caller
                    }
                } else {
                    /* DATA ERROR - NEED TO BE HANDLED*/
                }
                var_dump($counterDec, $data);
            });

            $connection->on('close', function () use ($that, $connection) {
                $that->closeManagerConnection($connection);
                $that->connectToManager();
            });

            $connection->on('error', function () use ($that, $connection) {
                $that->closeManagerConnection($connection);
                $that->connectToManager();
            });

            $that->me->setConnection($connection);
            $that->me->sendMessage(
                ['request' => 'ID'],
                self::MANAGER_ID,
                function ($data, $params) use ($that) {
                    $data = json_decode($data, true);
                    if (isset($data['response']) && $data['response']) {
                        $that->me->changeId($data['value']);
                        $that->me->queue->resume();
                    }
                    return true;
                }
            );
        });
    }

    Public function functionOnData($func, $index = null): bool
    {
        if (is_callable($func)) {
            if (is_int($index))
                array_splice( $this->onData, $index, 0, $func );
            else
                $this->onData[] = $func;
            return true;
        }
        return false;
    }

    Public function functionWhenJSON($json = null): bool
    {
        if (is_callable($json)) {
            $this->onJson[] = $json;
            return true;
        } else if (!is_array($json)) {
            return false;
        }

        if (!isset($json['request'])) {
            $toReturn = true;
            foreach ($this->onJson as $key => $onJsonFunc) {
                echo "Proccessing onJsonFunc ($key)" . PHP_EOL;
                $toReturn = $toReturn AND $onJsonFunc($json);
            }
            return $toReturn;
        }

        /**
        * Proccess
        */
        switch ($data['request']) {
            case 'teste':
                // Implement module / class change configuration
                break;

            default:
                foreach ($this->onJson as $key => $onJsonFunc) {
                    echo "Proccessing onJsonFunc ($key)" . PHP_EOL;
                    $toReturn = $toReturn AND $onJsonFunc($json);
                }
            break;
        }
    }

    Private function closeManagerConnection(ConnectionInterface $connection)
    {
        echo "manager connection closed".PHP_EOL;
        $connection->close();
        $this->mainSocket = null;
    }

    Private function isManagerRunning(): bool
    {
        if(!file_exists(self::SYSTEM_RUN_FOLDER[0]) ||
        !file_exists(self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_NAME . '.pid'))
            return false;
        return true;
    }
}
?>
