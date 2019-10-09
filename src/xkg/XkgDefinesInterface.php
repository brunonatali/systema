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

interface XkgDefinesInterface
{
    Const XKG_NAME = 'xkg';

    Const BORDER_ROUTER_PORTS = ['gmas'];
    Const XKG_SERVER_PORTS = ['rawdata','mngt','control'];

    Const XKG_ADDRESS = 'xkg.sock';

    Const XKG_COMMAND_TIME_TO_ANSWER = 3; /*Time in seconds (could be 0.1 - 100 miliseconds)*/

    Const XKG_DEFAULT_DATA = [
        'received' => [
            'packages' => 0,
            'bytes' => 0
        ],
        'sent' => [
            'packages' => 0,
            'bytes' => 0
        ],
        'error' => []
    ];

    Const XKG_STATUS = [
        'stoped' => 0x0,
        'started' => 0x1,
        'waitManager' => 0x2,
        'building' => 0x03,
        'redy' => 0x04
    ];

    Const XKG_ERRORS = [
        'node_not_exist' => 0x01,
        'command_not_understood' => 0x02,
        'wrong_command_source' => 0x03,
        'command_already_queued' => 0x04,
        'command_answer_not_understood' => 0x05
    ];
}
?>
