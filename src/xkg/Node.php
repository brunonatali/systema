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

use BrunoNatali\EventLoop\LoopInterface;
use BrunoNatali\Socket\ConnectionInterface as TheConnection;
use BrunoNatali\SysTema\Misc\Queue;

class Node implements XkgDefinesInterface
{
    Private $loop;
    Private $queue;
    Public $commandQueue;
    Private $IPv6;
    Private $IPv6String;
    Private $consolidateTime = 0;   /* 0 mean not consolidate all... arrays */

    Private $cmdConn = null;
    Private $cmdSocket = null;

    Private $KAs = [];
    Private $index = 0;

    Private $current = [
        'parent' => null,
        'rank' => null,
        'sigMin' => null,
        'sigAvg' => null,
        'sigMax' => null,
        'routes' => null,
        'nbr' => null,
        'noack' => null,
        'kaPeriod' => null,
        'fw' => null
    ];

    Private $allParents = [];
    Private $avgRank = null;
    Private $allRank = [];
    Private $avgSigMin = null;
    Private $allSigMin = [];
    Private $avgSigAvg = null;
    Private $allSigAvg = [];
    Private $avgSigMax = null;
    Private $allSigMax = [];
    Private $avgRoute = null;
    Private $allRoutes = [];
    Private $avgNbr = null;
    Private $allNbr = [];
    Private $allNoack = [];
    Private $allKaPeriod = [];
    Private $allFw = [];

    Private $data = [
        'received' => [
            'packages' => 0,
            'bytes' => 0
        ],
        'sent' => [
            'packagesReal' => 0,
            'bytesReal' => 0,
            'packagesSoft' => 0,
            'bytesSoft' => 0
        ],
        'error' => []
    ];


    function __construct($IPv6, LoopInterface &$loop)
    {
        $this->loop = &$loop;
        $this->queue = new Queue($this->loop, self::XKG_COMMAND_TIME_TO_ANSWER);
        $this->commandQueue = new Queue($this->loop, self::XKG_COMMAND_TIME_TO_ANSWER);
        $this->IPv6 = $IPv6;
        $this->IPv6String = bin2hex($IPv6);

        $this->commandQueue->resume();
    }

    Public function processKA(string $parent, string $ka): bool
    {
        $toReturn = false;
        $this->KAs[ $this->index ] = microtime(true);

        $parent = bin2hex($parent);
        if ($this->current[ 'parent' ] != $parent) $toReturn = true;
        $this->current[ 'parent' ] = $parent;
        $this->allParents[ $parent ][] = &$this->KAs[ $this->index ];

        if ($this->current[ 'rank' ] != ($temp = hexdec(bin2hex($ka{1} . $ka{0})))) $toReturn = true;
        $this->current[ 'rank' ] = $temp;
        $this->allRank[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'rank' ];

        if ($this->current[ 'sigMin' ] != ($temp = hexdec(bin2hex($ka{2})))) $toReturn = true;
        $this->current[ 'sigMin' ] = $temp;
        $this->allSigMin[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'sigMin' ];

        if ($this->current[ 'sigAvg' ] != ($temp = hexdec(bin2hex($ka{3})))) $toReturn = true;
        $this->current[ 'sigAvg' ] = $temp;
        $this->allSigAvg[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'sigAvg' ];

        if ($this->current[ 'sigMax' ] != ($temp = hexdec(bin2hex($ka{4})))) $toReturn = true;
        $this->current[ 'sigMax' ] = $temp;
        $this->allSigMax[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'sigMax' ];

        if ($this->current[ 'noack' ] != ($temp = hexdec(bin2hex($ka{16} . $ka{15} . $ka{14} . $ka{13})))) $toReturn = true;
        $this->current[ 'noack' ] = $temp;
        $this->allNoack[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'noack' ];

        $temp = bin2hex($ka{17});
        if ($this->current[ 'routes' ] != ($temp = hexdec($temp{1} . $temp{0}))) $toReturn = true;
        $this->current[ 'routes' ] = $temp;
        $this->allRoutes[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'routes' ];

        $temp = bin2hex($ka{18});
        if ($this->current[ 'nbr' ] != ($temp = hexdec($temp{1} . $temp{0}))) $toReturn = true;
        $this->current[ 'nbr' ] = $temp;
        $this->allNbr[ $parent ][ $this->KAs[ $this->index ] ] = $this->current[ 'nbr' ];

        if ($this->data[ 'sent' ][ 'bytesSoft' ] != ($temp = hexdec(bin2hex($ka{8} . $ka{7} . $ka{6} . $ka{5}))))
            $toReturn = true;
        $this->data[ 'sent' ][ 'bytesSoft' ] = $temp;

        if ($this->data[ 'sent' ][ 'packagesSoft' ] != ($temp = hexdec(bin2hex($ka{12} . $ka{11} . $ka{10} . $ka{9}))))
            $toReturn = true;
        $this->data[ 'sent' ][ 'packagesSoft' ] = $temp;

        $this->index ++;

        //foreach($this->allRank[ $parent ] as $rank) echo "Rank: " . $rank . PHP_EOL;
        return $toReturn;
    }

    Public function sendCommand(string $cmd)
    {
        $me = &$this;
        $this->cmdConn->then(function (TheConnection $connection) use ($me, $cmd) {
            echo "Send to node (".$me->IPv6String."): '$cmd'" . PHP_EOL;
            $connection->write(
                $me->IPv6 . $cmd . "\r",
                $me->cmdSocket
            );
            $me->commandQueue->pause();
            $me->data[ 'sent' ][ 'packagesReal' ] ++;
            $me->data[ 'sent' ][ 'bytesReal' ] += strlen($cmd);
        });
    }

    Public function receiveCommand($id, $data)
    {
        echo "Received command answer: " . $data . PHP_EOL;
        $this->data[ 'received' ][ 'packages' ] ++;
        $this->data[ 'received' ][ 'bytes' ] += strlen($data);
        return $this->commandQueue->listProccess($id, $data);
    }

    Public function setReceivedUselessData($data)
    {
        echo "[USELESS DATA] : " . $data . PHP_EOL;
        $this->data[ 'received' ][ 'packages' ] ++;
        $this->data[ 'received' ][ 'bytes' ] += strlen($data);
    }

    Public function setCommandConnection(&$connection, string $socket)
    {
        $this->cmdConn = &$connection;
        $this->cmdSocket = $socket;
    }

    Public function getCurrentAsJSON(): string
    {
        $toReturn = '{}';

        if (($temp = json_encode(array_merge($this->current, $this->data[ 'sent' ], $this->data[ 'received' ]))) !== false) $toReturn = $temp;

        return $toReturn;
    }

    public function setValByCommand($cmd, $val): bool
    {
        $toreturn = false;
        switch ($cmd) {
            case 'KAPERIOD':
                $val = intval($val);
                if ($this->current[ 'kaPeriod' ] !== $val) {
                    $this->current[ 'kaPeriod' ] = intval($val);
                    $this->allKaPeriod[ microtime(true) ] = $this->current[ 'kaPeriod' ];
                }
                $toreturn = true;
                break;
            case 'FWVER':
                if ($this->current[ 'fw' ] !== $val) {
                    $this->current[ 'fw' ] = $val;
                    $this->allFw[ microtime(true) ] = $this->current[ 'fw' ];
                }
                $toreturn = true;
                break;
        }

        return $toreturn;
    }

}

?>
