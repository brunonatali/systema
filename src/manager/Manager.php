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

namespace BrunoNatali\SysTema\Managers;

use BrunoNatali\SysTema\Defines\GeneralDefines;
use BrunoNatali\SysTema\Misc\Formatter;
use BrunoNatali\SysTema\Misc\SystemInteraction;
use BrunoNatali\SysTema\Group\Collector;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\UnixServer as UnixReactor;

class Manager extends Collector implements ManagerDefinesInterface
{
    Private $formatter;
    Private $loop;
    Private $server;
    Private $system;

    function __construct(LoopInterface $loop = null)
    {
        $this->formatter = new Formatter(self::MANAGER_NAME, self::MANAGER_ID);
        $this->system = new SystemInteraction();

        if (null === $loop) {
            $this->loop = LoopFactory::create();
        } else {
            $this->loop = &$loop;
        }

        if(!file_exists(self::SYSTEM_RUN_FOLDER[0])) {
            mkdir(self::SYSTEM_RUN_FOLDER[0]);
        } else if(file_exists(self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS)) {
            unlink(self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS);
        }

        try {
            $server = new UnixReactor('unix://' . self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS, $this->loop);

            $that = &$this;
            $server->on('connection', function (ConnectionInterface $client) use ($that) {
                $remoteAddress = $client->getRemoteAddress();

                if (($thingId = $that->searchByRemoteAddress($remoteAddress)) !== 0) {
                    $that->disconnectModule($client, $thingId);
                } else {
                    $thingId = addThing();
                    $that->things[ $thingId ]['thing']->changeAddress( $remoteAddress );
                    $that->things[ $thingId ]['thing']->addConnection( $client );

                    $client->on('data', function ($data) use ($client, $thingId) {
                        $that->processData($client, $thingId, $data);
                    });
                }
            });

            $server->on('error', function (ConnectionInterface $client) use ($that) {
                if (($thingId = $that->searchByRemoteAddress($client->getRemoteAddress())) !== 0) {
                    $that->disconnectModule($client, $thingId);
                }
            });

            $this->system->setAppInitiated(self::MANAGER_NAME);
            $this->instantiated = true;
        } catch (Exception $e) {
            //echo "$e"; Add some exceptions handling
        }
    }

    Private function searchByRemoteAddress($remoteAddress)
    {
        foreach ($this->things as $the) {
            if ($remoteAddress == $the['thing']->address) return $key;
        }
        return 0;
    }

    Private function disconnectModule(ConnectionInterface &$connection, $id, $data = null)
    {
        if ($id === 0) return;
        if ($data !== null) $connection->write($data);
        $connection->close();
        removeThing($id);
    }

    Private function processData(ConnectionInterface &$connection, $id, $data)
    {
        $tempData = $data;
        $msgResultId = $this->formatter->decode($tempData);
        if ($msgResultId === 0) {   // Data for manager
            $dataProcess = $this->processRequest($connection, $tempData, $id);

            if ($dataProcess === false) {
                $data = json_encode([
                    'response' => false,
                    'error' => WRONG_REQUEST
                ]);
                if ($this->formatter->encode($data, $id) === 0) $connection->write($data);
            }
        } else if ($msgResultId) { // Data for others
            if ($this->things[ $thingId ]['handled']) { // Check if is "authenticated", if is redy to communicate with others
                if (isset($this->things[ $msgResultId ])) {
                    $this->things[ $msgResultId ]['thing']->connection->write($data);
                } else {
                    $data = json_encode([
                        'response' => false,
                        'error' => DESTINATION_MISMATCH
                    ]);
                    if ($this->formatter->encode($data, $id) === 0) $connection->write($data);
                }
            } else {
                $data = json_encode([
                    'response' => false,
                    'error' => CONNECTION_NOT_AUTHENTICATED
                ]);
                if ($this->formatter->encode($data, $id) === 0) $this->disconnectModule($connection, $id, $data);
                else $this->disconnectModule($connection, $id);
            }
        } else {
            $data = json_encode([
                'response' => false,
                'error' => ENCODED_DATA_ERROR
            ]);
            if ($this->formatter->encode($data, $id) === 0) $connection->write($data);
        }
    }

    Private function processRequest(ConnectionInterface &$connection, $data, $id = 0)
    {
        if (($data = json_decode($data, true)) !== false) {
            if (!isset($data['request'])) return false;

            switch ($data['request']) {
                case 'ID':
                    if (($thingId = $this->searchByRemoteAddress($connection->getRemoteAddress())) !== 0) {
                        $data = json_encode([
                            'response' => true,
                            'value' => $thingId
                        ]);
                        if (($encodeResult = $this->formatter->encode($data, $id)) === 0) {
                            $connection->write($data);
                            return true;
                        } else {
                            return $encodeResult;
                        }
                    }
                    break;
            }
        }

        return false;
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

?>
