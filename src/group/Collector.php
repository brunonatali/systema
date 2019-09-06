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

class Collector implements ManagerDefinesInterface
{
    Protected $things = [0];
    Protected $names = [0];

    Protected $instantiated = false;

    Private function addThing(string $name = null, $handled = false): int
    {
        $thingsIndex = count($this->things);

        $this->things[ $thingsIndex ] = [
            'thing' => new Thing($thingsIndex, $name),
            'handled' => $handled
        ];
        if ($name !== null && !is_bool($name)) {
            if (isset($this->names[ $name ])) throw new InvalidArgumentException('Name ' . $name . ' exists.');
            $this->names[ $name ] = $thingsIndex;
        }
        return $thingsIndex;
    }

    Private function removeThing($identifier)
    {
        switch(gettype($identifier)){
            case "integer":
                if (isset($this->things[ $identifier ])) {
                    $thisName = $this->things[ $identifier ]->formatter->getName();
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
        !file_exists(self::SYSTEM_RUN_FOLDER[0] . self::MANAGER_ADDRESS))
            return false;
        return true;
    }
}

?>
