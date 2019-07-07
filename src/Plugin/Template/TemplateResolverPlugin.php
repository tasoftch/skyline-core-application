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

namespace Skyline\Application\Plugin\Template;


use Skyline\Application\Event\RenderEvent;
use Skyline\Application\Exception\TemplateResolutionException;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Service\TemplateControllerInterface;

class TemplateResolverPlugin
{
    public function resolveTemplates(string $eventName, RenderEvent $event)
    {
        $renderInfo = $event->getRenderInformation();

        $notResolvedError = function($info) {
            $e = new TemplateResolutionException("Template Resolution Error");
            $e->setDetails("Could not resolve template information");
            $e->setTemplateInfo($info);
            throw $e;
        };

        /** @var TemplateControllerInterface $tc */
        $tc = SkylineServiceManager::getServiceManager()->get("templateController");

        if($template = $renderInfo->get(RenderInfoInterface::INFO_TEMPLATE)) {
            if($tmp = $tc->findTemplate($template)) {
                $renderInfo->set(RenderInfoInterface::INFO_TEMPLATE, $tmp);
            } else {
                $notResolvedError($template);
            }
        }

        if($children = $renderInfo->get(RenderInfoInterface::INFO_SUB_TEMPLATES)) {
            foreach($children as $key => &$child) {
                $ch = $tc->findTemplate($child);
                if(!$ch) {
                    $notResolvedError($child);
                    $child = NULL;
                } else {
                    if(!is_string($key) && is_string($child)) {
                        $children[$key] = $ch;
                        $child = NULL;
                    } else
                        $child = $ch;
                }
            }

            $children = array_filter($children, function($_) { return $_ ? true : false; });
            $renderInfo->set(RenderInfoInterface::INFO_SUB_TEMPLATES, $children);
        }
    }
}