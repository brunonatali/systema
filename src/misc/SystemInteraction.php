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

use BrunoNatali\SysTema\Defines\GeneralDefinesInterface;

class SystemInteraction implements GeneralDefinesInterface
{
    Protected $systemIsSHDN = false;
    Private $functionsOnAborted = [];

    Public function setAppInitiated($appName)
    {
        $thisAppPid = getmypid();

        foreach (self::SYSTEM_RUN_FOLDER as $sysRunFolder) {
            $pidHandle = fopen($sysRunFolder . $appName . '.pid', 'w+');
            if (fwrite($pidHandle, (string)$thisAppPid) === FALSE){
        		throw new Exception('ERROR creating pid file.');
        	}
            @fclose($pidHandle);
        }

        $this->setFunctionOnAppAborted(function () use ($appName){
            foreach (self::SYSTEM_RUN_FOLDER as $sysRunFolder)
                if(file_exists($sysRunFolder . $appName . '.pid')) @unlink($sysRunFolder . $appName . '.pid');
        });

        register_shutdown_function([$this, 'setAborted']);
    	pcntl_signal(SIGTERM, [$this, 'setAborted']);
    	pcntl_signal(SIGHUP,  [$this, 'setAborted']);
    	pcntl_signal(SIGINT,  [$this, 'setAborted']);
    }

    Public function setAborted()
    {
        if ($this->systemIsSHDN) return;
        $this->systemIsSHDN = true;

        foreach ($this->functionsOnAborted as $func) $func();
        exit;
    }

    Public function getAppInitiated($appName): bool
    {
        foreach (self::SYSTEM_RUN_FOLDER as $sysRunFolder)
            if(file_exists($sysRunFolder . $appName . '.pid')) return true;

        return false;
    }

   Public function setFunctionOnAppAborted($func)
   {
       $this->functionsOnAborted[] = $func;
   }
}
