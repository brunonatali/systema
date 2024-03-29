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

namespace BrunoNatali\SysTema\Group;

use BrunoNatali\SysTema\Things\Thing;
use BrunoNatali\SysTema\Misc\SystemInteraction;
use BrunoNatali\SysTema\Managers\ManagerDefinesInterface;
use BrunoNatali\EventLoop\LoopInterface;

class Collector implements ManagerDefinesInterface
{
    Protected $things = [0];
    Protected $names = [0];

    Protected $instantiated = false;

    Protected function addThing(LoopInterface &$loop, string $name = null, $handled = false): int
    {
        $thingsIndex = count($this->things);
        $this->things[ $thingsIndex ] = [
            'thing' => new Thing($loop, $thingsIndex, $name),
            'handled' => $handled,
            'password' => null
        ];
        if ($name !== null && !is_bool($name)) {
            if (isset($this->names[ $name ])) throw new InvalidArgumentException('Name ' . $name . ' exists.');
            $this->names[ $name ] = $thingsIndex;
        }
        return $thingsIndex;
    }

    Public function changeThingName($current, $new): bool
    {
        if (!isset($this->names[ $current ]) || isset($this->names[ $new ])) return false;
        $theId = $this->names[ $current ];
        $this->names[ $new ] = $theId;
        unset($this->names[ $current ]);
        return true;
    }

    Public function getThingName($id): string
    {
        $toReturn = null;
        $id = intval($id);
        foreach ($this->names as $name => $val)
            if ($val == $id) return (string) $name;
        return $toReturn;
    }

    Public function setPasswordToThing($index, $pass = null): bool
    {
        if (!isset($this->things[ $index ])) return false;
        if ($pass === null) {
            $this->things[ $index ]['handled'] = true;
        } else {
            $this->things[ $index ]['password'] = $pass;
        }
        return true;
    }

    Public function authThing($index, $pass): bool
    {
        if (!isset($this->things[ $index ])) return false;
        if ($pass !== $this->things[ $index ]['password']) return false;
        return true;
    }

    Protected function removeThing($identifier)
    {
        switch(gettype($identifier)){
            case "integer":
                if (isset($this->things[ $identifier ])) {
                    $thisName = $this->things[ $identifier ]['thing']->formatter->getName();
                    if (isset($this->names[ $thisName ])) unset($this->names[ $thisName ]);
                    unset($this->things[ $identifier ]);
                } else {
                    throw new InvalidArgumentException('Thing identified by ' . $identifier . ' not exist.');
                }
                break;
            case "string":
                if (isset($this->names[ $identifier ]) && isset($this->things[ ($this->names[ $identifier ]) ])) {
                    unset($this->things[ ($this->names[ $identifier ]) ]);
                    unset($this->names[ $identifier ]);
                } else {
                    throw new InvalidArgumentException('Thing identified by ' . $identifier . ' not exist.');
                }
                break;
            default:
                throw new InvalidArgumentException('Thing identified by ' . $identifier . ' not exist.');
                break;
        }
    }

    Public function isInstantiated()
    {
        return $this->instantiated;
    }

    Public function isManagerRunning(): bool
    {
        if(!file_exists(self::SYSTEM_RUN_FOLDER[0]) ||
        !file_exists(self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_NAME . '.pid'))
            return false;
        return true;
    }
}

?>
