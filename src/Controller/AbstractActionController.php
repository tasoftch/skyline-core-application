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

namespace Skyline\Application\Controller;


use Skyline\Application\Exception\_InternalStopRenderProcessException;
use Skyline\Application\Exception\ActionCancelledException;
use Skyline\Kernel\ExposeClassInterface;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Router\Description\ActionDescriptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TASoft\Service\ServiceForwarderTrait;

abstract class AbstractActionController implements ActionControllerInterface, ExposeClassInterface
{
    use ServiceForwarderTrait;
    /** @var RenderInfoInterface */
    private $renderInfo;

    /**
     * @inheritDoc
     */
    public static function getPurposes(): array
    {
        return [
            "actionController"
        ];
    }

    /**
     * The action controller asks a final time about the definitive method name to perform the action.
     *
     * @param ActionDescriptionInterface $actionDescription
     * @return string
     */
    protected function getActionMethodName(ActionDescriptionInterface $actionDescription): string {
        return $actionDescription->getMethodName();
    }

    /**
     * Return true to register the action controller as a service
     *
     * @return bool
     */
    protected function shouldRegisterService(): bool {
        return true;
    }

    /**
     * Forwards any action to a specified method name.
     *
     * @param ActionDescriptionInterface $actionDescription
     * @param RenderInfoInterface $renderInfo
     */
    public function performAction(ActionDescriptionInterface $actionDescription, RenderInfoInterface $renderInfo)
    {
        $this->renderInfo = $renderInfo;
        $method = $this->getActionMethodName($actionDescription);

        if($this->shouldRegisterService())
            $this->getServiceManager()->set("actionController", $this);

        SkylineServiceManager::getDependencyManager()->call([$this, $method]);
    }

    /**
     * @return RenderInfoInterface
     */
    public function getRenderInfo(): RenderInfoInterface
    {
        return $this->renderInfo;
    }

    // Methods to call inside performing actions

    /**
     * Calling this method will stop rendering process and send the response to the client.
     *
     * @param Response $response
     */
    protected function renderResponse(Response $response) {
        $this->renderInfo->set( RenderInfoInterface::INFO_RESPONSE, $response );
        throw new _InternalStopRenderProcessException();
    }

    /**
     * Calling this to cancel render and initialize a stream response. The streamHandler is called to send data.
     *
     * @param callable $streamHandler
     */
    protected function renderStream(callable $streamHandler) {
        $resp = new StreamedResponse($streamHandler);
        $this->renderResponse($resp);
    }

    /**
     * Cancel an action. You should explain why the action was cancelled.
     *
     * @param string $reason
     * @param string $message
     * @param int $code
     * @param mixed ...$arguments
     */
    protected function cancelAction(string $reason, string $message = "", int $code = 500, ...$arguments) {
        $e = new ActionCancelledException($reason, $code);
        $e->setActionController($this);
        $e->setDetails($message, ...$arguments);
        throw $e;
    }

    /**
     * Tells the render process to choose a preferred render if possible.
     *
     * @param string $preferredRenderName
     */
    protected function preferRender(string $preferredRenderName) {
        $this->renderInfo->set( RenderInfoInterface::INFO_PREFERRED_RENDER, $preferredRenderName);
    }

    protected function renderTemplate($template, array $children = []) {

    }
}