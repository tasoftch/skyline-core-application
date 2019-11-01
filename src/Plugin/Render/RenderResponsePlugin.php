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

namespace Skyline\Application\Plugin\Render;


use Skyline\Application\Event\RenderEvent;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Service\CompiledRenderController;
use Skyline\Router\Description\ActionDescriptionInterface;
use TASoft\EventManager\EventManagerInterface;
use TASoft\Service\ServiceManager;

class RenderResponsePlugin
{
    public function renderResponse(string $eventName, RenderEvent $event, EventManagerInterface $eventManager, ...$arguments)
    {
        /** @var ServiceManager $SERVICES */
        global $SERVICES;
        if($SERVICES instanceof ServiceManager) {
            /** @var ActionDescriptionInterface $actionDescription */
            $actionDescription = $SERVICES->get("actionDescription");

            if(method_exists($actionDescription, 'getRenderName')) {
                $renderName = $event->getRenderInformation()->get(RenderInfoInterface::INFO_PREFERRED_RENDER);
                if(!$renderName)
                    $renderName = $actionDescription->getRenderName();

                $renderController = $SERVICES->get("renderController");
                if($renderController instanceof CompiledRenderController) {
                    $render = $renderController->getRender($renderName);
                    $render->setResponse( $event->getResponse() );
                    ServiceManager::generalServiceManager()->set("render", $render);

                    $render->render($event->getRenderInformation());

                    $event->setResponse( $render->getResponse() );
                }
            }
        }
    }
}