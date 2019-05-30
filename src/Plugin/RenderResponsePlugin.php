<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\Application\Plugin;


use Skyline\Application\Event\RenderResponseEvent;
use Skyline\Render\Service\CompiledRenderController;
use TASoft\EventManager\EventManagerInterface;
use TASoft\Service\ServiceManager;

class RenderResponsePlugin
{
    const EVENT_PRE_ACTION = "skyline.action.pre";
    const EVENT_MAIN_ACTION = "skyline.action.main";
    const EVENT_POST_ACTION = "skyline.action.post";


    public function renderResponse(string $eventName, RenderResponseEvent $event, EventManagerInterface $eventManager, ...$arguments)
    {
        /** @var ServiceManager $SERVICES */
        global $SERVICES;
        if($SERVICES instanceof ServiceManager) {
            if($eventName == SKY_EVENT_RENDER_RESPONSE) {
                $actionDescription = $SERVICES->get("actionDescription");
                if(method_exists($actionDescription, 'getRenderName')) {
                    $renderName = $actionDescription->getRenderName();
                    $renderController = $SERVICES->get("renderController");
                    if($renderController instanceof CompiledRenderController) {
                        $render = $renderController->getRender($renderName);

                        $renderInfo = NULL;

                        $eventManager->trigger(static::EVENT_PRE_ACTION, $event, [&$renderInfo]);
                        $eventManager->trigger(static::EVENT_MAIN_ACTION, $event, [&$renderInfo]);
                        $eventManager->trigger(static::EVENT_POST_ACTION, $event, [&$renderInfo]);

                        $render->render($renderInfo);
                        $event->setResponse( $render->getResponse() );
                    }
                }
            }
        }
    }
}