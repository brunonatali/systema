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

use BrunoNatali\SysTema\Misc\Formatter;
use BrunoNatali\SysTema\Misc\Queue;
use BrunoNatali\Socket\ConnectionInterface;
use BrunoNatali\EventLoop\LoopInterface;

class Thing
{
    Protected $name;
    Protected $id;

    Protected $address = null;
    Private $connection = null;

    Public $formatter;
    Public $queue;

    Public $authenticated = false;

    function __construct(LoopInterface &$loop, $id = 0, string $name = null)
    {
        $this->id = $id;
        $this->name = $name;

        $this->queue = new Queue($loop);
        $this->formatter = new Formatter($name, $id);
    }

    Public function sendMessage($msg, $to, $onAnswer = null)
    {
        if (is_array($msg)) $msg = json_encode($msg);
echo "Enviando mensagem '$msg' para '$to', ";
        $counterEnc = null;
        if ($this->formatter->encode($msg, $to, $counterEnc) === 0) {
echo $counterEnc;
            $this->queue->listAdd(
                $counterEnc,
                function ($params) use ($msg) {
echo "executado";
                    if ($this->connection !== null) $this->connection->write($msg);
                },
                $onAnswer
            );
echo "agendado";
        }
    }

    Public function enQueue(mixed $value)
    {
        return $this->queue->push($value);
    }

    Public function changeName($name)
    {
        $this->name = $name;
    }

    Public function changeAddress($address)
    {
        $this->address = $address;
    }

    Public function changeId($id)
    {
echo "Mudando ID para $id" . PHP_EOL;
        $this->id = $id;
        $this->formatter->changeId((string) $id);
    }

    Public function addConnection(ConnectionInterface &$conn)
    {
        $this->connection = &$conn;
    }

    Public function getAddress()
    {
        return $this->address;
    }
}

?>
