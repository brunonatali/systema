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

use SplQueue;
use BrunoNatali\EventLoop\LoopInterface;

class Queue
{
    Private $main;
    Private $loop;
    Private $timer;
    Private $timeToAnswer;
    Private $listById = [];
    Private $running = false;
    Private $enabled = false;
    Private $waitToRun = false;
    Private $lastErrorString;

    function __construct(LoopInterface &$loop, $timeToAnswer = 0)
    {
        $this->main = new SplQueue();
        $this->loop = &$loop;
        $this->timeToAnswer = $timeToAnswer;
    }

    /*
    * $retryOnError could be integer or bool, if is integer value will be decreased each time function is called
    */
    Public function listAdd(string &$id = null, $onAdd = null, $onData = null, $onError = false, $retryOnError = null)
    {
        if ($id === null ) $id = $this->genId();
        $this->listById[$id] = [
            'id' => $id,
            'onAdd' => $onAdd,
            'onData' => $onData,
            'onError' => $onError,
            'retryOnError' => (is_int($retryOnError) ? $retryOnError - 1 : $retryOnError),
            'timer' => ($this->timeToAnswer === 0 ? null : $this->loop->addTimer($this->timeToAnswer, function () use ($id){
                if (is_callable($this->listById[$id]['onError']))
                    $this->listById[$id]['onError']($params = $this->listById[$id]);
                if ($this->listById[$id]['retryOnError'])
                    $this->listAdd(
                        $this->listById[$id][ 'id' ],
                        $this->listById[$id][ 'onAdd' ],
                        $this->listById[$id][ 'onData' ],
                        $this->listById[$id][ 'onError' ],
                        $this->listById[$id][ 'retryOnError' ]
                    );
            }))
        ];
        if (is_callable($this->listById[$id]['onAdd'])) {
            echo "is callable";
            return $this->listById[$id]['onAdd']($params = $this->listById[$id]);
        }
        return true;
    }

    Public function listProccess(string $id, $data = null)
    {
        $toReturn = true;
        if (!isset($this->listById[$id])) return false;

        if (is_callable($this->listById[$id]['onData']))
            $toReturn = $this->listById[$id]['onData']($data, $params = $this->listById[$id]);

        $this->listRemove($id);

        return $toReturn;
    }

    Public function listRemove(string $id)
    {
        if (isset($this->listById[$id])) unset($this->listById[$id]);
    }

    Public function push($value, number $id = null)
    {
        if ($id !== null) {
            try {
                $this->main->add($id,$value);
            } catch (OutOfRangeException $e) {
                $this->lastErrorString = $e;
                return false;
            }
        } else {
            $this->main->enqueue($value);
        }

        return $this->next();
    }

    Public function next()
    {
        if ($this->main->isEmpty()) return null;
        if (!$this->enabled || $this->running) {
            $this->waitToRun = true;
            return false;
        }

        $queueExecuteNode = $this->main->dequeue();
        if (is_callable($queueExecuteNode)) {
            $this->running = true;
            $this->timer = $this->loop->addTimer(0.1, function () use ($queueExecuteNode){
                $queueExecuteNode();
                $this->running = false;
                $this->timer = null;
                $this->next();
            });
        }
        return true;
    }

    Public function pause()
    {
        $this->enabled = false;
    }

    Public function resume()
    {
        $this->enabled = true;
        if ($this->waitToRun) {
            $this->waitToRun = false;
            $this->next();
        }
    }

    Private function genId(): int
    {
        while(isset($this->listById[$temp = rand()])) {}
        return $temp;
    }
}
