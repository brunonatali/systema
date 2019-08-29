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

class Format
{
    Private $name;
    Private $id;

    Private $header = 'AA55';
    private $headerLenght = strlen($this->header);

    Private $timeStampAdd = false;
    Private $timeStampMicroTime = false;
    Private $timeStampLen = 0;

    Private $buffer = null;

    Private $idLenght = 4; // 65535
    Private $dataLenght = 4; // In chars
    Private $payload = 0;

    Private $easyStore;

    Private $lastErrorString = "";

    function __construct(string $name, $id, array $config = [])
    {
        $this->name = $name;
        $this->id = $this->getId((string)$id);

        $this->easyStore = new Store();

        $this->setArgs($config);
        if ($this->timeStampAdd) $this->timeStampLen = (int)strlen(($this->timeStampMicroTime ? (string)microtime(true) : (string)time()));

        $this->getPayload();
    }

    Public function encode(string &$data, $destination): int
    {
        try {
            // {header}{len}{source}{destination}{if time stamp}{data}
            $data = $this->header . $this->getDataLenght($data) . $this->id . $this->getId((string)$destination) . $this->getTimeStamp() . $data;
            return 0;
        } catch (InvalidArgumentException $e) {
            $this->lastErrorString = $e;
            return 1;
        }
    }

    Public function decode(string &$data)
    {
        //Check header
        if($this->header !== substr($data, 0, $this->headerLenght) && $this->buffer === null) {
            return false; // Data error
        } else {
            // Try to recover data
            $data = $this->buffer . $data;
            $this->buffer = null;
            return $this->decode($data);
        }

        // Check data len
        if (strlen($data) == ($thisDataLen = hexdec(substr($data, $this->headerLenght, $this->dataLenght)))) {
            // Check if package destination is this module
            if ($this->id != ($thisDataDestination = substr($data, $this->easyStore->getValByName('decodeDestinationStartLen'), $this->idLenght)))
                return hexdec($thisDataDestination); // Not for him return destination decimal integer

            $data = substr($data, $this->payload);
            return 0;
        } else {
            $this->buffer = substr($data, $thisDataLen * -1);
            $data = substr($data, 0, $thisDataLen);
            return $this->decode($data);
        }
    }

    Private function getTimeStamp(): string
    {
        if (!$this->timeStampAdd) return "";
        return ($this->timeStampMicroTime ? (string)microtime(true) : (string)time());
    }

    Private function getId(string &$ID)
    {
        if(strlen($ID) > $this->idLenght) throw new InvalidArgumentException("getId() maximum ID lenght is " . $this->idLenght);
        return str_pad($ID, $this->idLenght, "0", STR_PAD_LEFT);
    }

    Private function getDataLenght(string &$data): string
    {
        // {data len}{header len + source id len + destination id len}{data len len}{if time stamp len}
        return str_pad(dechex((int)strlen($data) + $this->payload), $this->dataLenght, "0", STR_PAD_LEFT);
    }

    Private function setArgs(array $config)
    {
        if (!count($config)) return;

        foreach ($config as $varName => $varValue) {
            if (isset($this->$varName)) {
                if (gettype($this->$varName) === gettype($varValue)) $this->$varName = $varValue;
                else throw new InvalidArgumentException("Variable '" . $varName . "' value must to be " . gettype($this->$varName) . ", " . gettype($varValue) . " given.");
            }
        }
    }

    Private function getPayload()
    {
        // {header len}{data len}{source id len}{destination id len}{if time stamp len}
        $this->payload = $this->headerLenght + $this->dataLenght + ($this->idLenght << 2) + ($this->timeStampAdd ? $this->timeStampLen : 0);

        $this->easyStore->newVar('decodeDestinationStartLen');
        $this->easyStore->setValByName('decodeDestinationStartLen', $this->payload - $this->idLenght - ($this->timeStampAdd ? $this->timeStampLen : 0));
    }
}
?>
