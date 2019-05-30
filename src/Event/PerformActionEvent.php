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

namespace Skyline\Application\Event;


use Skyline\Application\Controller\ActionControllerInterface;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Router\Description\ActionDescriptionInterface;
use Symfony\Component\HttpFoundation\Request;
use TASoft\EventManager\Event\Event;

class PerformActionEvent extends Event
{
    /** @var Request */
    private $request;

    /** @var ActionControllerInterface */
    private $actionController;

    /** @var ActionDescriptionInterface */
    private $actionDescription;

    /** @var RenderInfoInterface */
    private $renderInformation;

    /**
     * PerformActionEvent constructor.
     * @param Request $request
     * @param ActionControllerInterface $actionController
     * @param ActionDescriptionInterface $actionDescription
     * @param RenderInfoInterface $renderInformation
     */
    public function __construct(Request $request, ActionControllerInterface $actionController, ActionDescriptionInterface $actionDescription, RenderInfoInterface $renderInformation = NULL)
    {
        $this->request = $request;
        $this->actionController = $actionController;
        $this->actionDescription = $actionDescription;
        $this->renderInformation = $renderInformation;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return ActionControllerInterface
     */
    public function getActionController(): ActionControllerInterface
    {
        return $this->actionController;
    }

    /**
     * @return ActionDescriptionInterface
     */
    public function getActionDescription(): ActionDescriptionInterface
    {
        return $this->actionDescription;
    }

    /**
     * @return RenderInfoInterface
     */
    public function getRenderInformation(): RenderInfoInterface
    {
        return $this->renderInformation;
    }

    /**
     * @param RenderInfoInterface $renderInformation
     */
    public function setRenderInformation(RenderInfoInterface $renderInformation): void
    {
        $this->renderInformation = $renderInformation;
    }
}