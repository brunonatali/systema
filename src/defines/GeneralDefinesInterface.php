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

namespace BrunoNatali\SysTema\Defines;


interface GeneralDefinesInterface
{
    // This will only work under unix.
    // Need to be implemented something like: if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    Const SYSTEM_TEMP_FOLDER = '/tmp/Desh/';
    Const SYSTEM_RUN_FOLDER = ['/run/systema/', '/var/run/systema/'];

    /**
    *   HANDLE CONNECTION
    */
    Const CONNECTION_REFUSED = 0xFFFFFFF0;
    Const CONNECTION_NOT_AUTHENTICATED = 0xFFFFFFF1;

    /**
    *   HANDLE COMMUNICATION
    */
    Const DESTINATION_MISMATCH = 0xFFFFFFF2;
    Const WRONG_REQUEST = 0xFFFFFFF3;
    Const ENCODED_DATA_ERROR = 0xFFFFFFF4;
}
?>
