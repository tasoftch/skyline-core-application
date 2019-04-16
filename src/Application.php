<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Skyline\Kernel;


use Skyline\Kernel\Config\MainKernelConfig;
use Skyline\Kernel\Event\LaunchEvent;
use Skyline\Kernel\Exception\SkylineKernelDetailedException;
use TASoft\EventManager\EventManager;
use TASoft\Service\ServiceManager;

class Application implements ApplicationInterface
{
    /** @var Application */
    private static $runningApplication;

    /**
     * @return null|Application
     */
    public static function getRunningApplication(): ?ApplicationInterface
    {
        return self::$runningApplication;
    }


    /**
     * @inheritDoc
     */
    public function run()
    {
        /** @var ServiceManager $SERVICES */
        global $SERVICES;
        if($SERVICES instanceof ServiceManager) {
            /** @var EventManager $eventManager */
            $eventManager = $SERVICES->get( MainKernelConfig::SERVICE_EVENT_MANAGER );

            $event = new LaunchEvent();
            $event->setApplication($this);

            if($eventManager->trigger( SKY_EVENT_LAUNCH_APPLICATION, $event )->isPropagationStopped())
                goto finalize;

            // Store the events app as running application
            self::$runningApplication = $event->getApplication();


        } else {
            $e = new SkylineKernelDetailedException("Application Launch Error", 500);
            $e->setDetails("Application can not lauch because no service manager is available. Probably Skyline CMS did not bootstrap");
            throw $e;
        }

        finalize:
        // Always trigger the tear down event.
        $eventManager->trigger( SKY_EVENT_TEAR_DOWN );
    }
}