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


use Skyline\API\Render\OutputRenderInterface;
use Skyline\Application\Event\RenderEvent;
use Skyline\Application\Exception\_InternalStopRenderProcessException;
use Skyline\Application\Exception\ActionCancelledException;
use Skyline\Application\Exception\ActionCancelledImmediatelyException;
use Skyline\Application\Exception\RenderResponseException;
use Skyline\Kernel\Config\PluginConfig;
use Skyline\Kernel\ExposeClassInterface;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Render\Context\DefaultRenderContext;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Model\ExtractableArrayModel;
use Skyline\Render\Model\ModelInterface;
use Skyline\Render\Service\RenderControllerInterface;
use Skyline\Router\Description\ActionDescriptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TASoft\Service\ServiceForwarderTrait;
use TASoft\Service\ServiceManager;

abstract class AbstractActionController implements ActionControllerInterface, ExposeClassInterface
{
    use ServiceForwarderTrait;
    /** @var RenderInfoInterface */
    protected $renderInfo;

    protected $modelClassName = ExtractableArrayModel::class;

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

	/**
	 * If an action controller must be used outside of a perform action method, you can use this method to simulate on a given render info.
	 *
	 * @param RenderInfoInterface $renderInfo
	 * @param callable $setupFunction
	 */
    public function setupRenderInfo(RenderInfoInterface $renderInfo, callable $setupFunction) {
    	$ri = $this->renderInfo;
    	$this->renderInfo = $renderInfo;
    	call_user_func($setupFunction);
    	$this->renderInfo = $ri;
	}

    // Methods to call inside performing actions


    /**
     * Use this method in templates to refer to action controllers.
     * Normally an action controller holds a class constant for each reachable action.
     *
     * @param string $host                 The host, may be directly a host or a labelled registered host from compilation
     * @param string $URI                  The URI to append
     * @param mixed ...$arguments          Arguments to apply to the URL. List strings to apply into $0-9 markers, and an array to build query from
     * @return string
     * @see DefaultRenderContext::buildURL()
     */
    public function buildURL($host, $URI = '/', ...$arguments) {
        $ctx = $this->get("renderContext");
        if(method_exists($ctx, 'buildURL'))
            return $ctx->buildURL($host, $URI, ...$arguments);
        return NULL;
    }


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
     * @param int $contentLength
     */
    protected function renderStream(callable $streamHandler, int $contentLength = 0) {
        $resp = new StreamedResponse(function() use ($streamHandler) {
            $this->renderInfo->set( RenderInfoInterface::INFO_TEMPLATE, NULL );
            $this->renderInfo->set( RenderInfoInterface::INFO_SUB_TEMPLATES, NULL);
            $this->renderInfo->set( RenderInfoInterface::INFO_RESPONSE, NULL);

            call_user_func($streamHandler);

            if(NULL === $resp = $this->renderInfo->get( RenderInfoInterface::INFO_RESPONSE )) {
                ServiceManager::generalServiceManager()->set("response", $response = new Response());

                $renderEvent = new RenderEvent($this->renderInfo, $response);
                SkylineServiceManager::getEventManager()->triggerSection(PluginConfig::EVENT_SECTION_RENDER, SKY_EVENT_RENDER_RESPONSE, $renderEvent);

                if(!$renderEvent->getResponse()) {
                    $e = new RenderResponseException("Response Render Error", 500);
                    $e->setDetails("Application can not render response.");
                    throw $e;
                }

                $resp = $renderEvent->getResponse();
            }

            $resp->sendContent();
        });
        if($contentLength)
            $resp->headers->set("Content-Length", $contentLength);
        $this->renderResponse($resp);
    }

    /**
     * Cancel an action. You can explain, why the action was cancelled.
     * Cancelling actions will immediately stop further action code and skips the render phase.
     * But the response headers and contents until now are sent to client.
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
     * Cancel an action immediately, without render or sending any headers or contents.
     *
     * @param string $reason
     * @param string $message
     * @param int $code
     * @param mixed ...$arguments
     */
    protected function cancelActionImmediately(string $reason, string $message = "", int $code = 500, ...$arguments) {
        $e = new ActionCancelledImmediatelyException($reason, $code);
        $e->setActionController($this);
        $e->setDetails($message, ...$arguments);
        throw $e;
    }

	/**
	 * Calling the stop acton method immediately stops Skyline CMS.
	 * So it will stop any output buffering and any further events and exit the program right now.
	 * If you want to do anything before exit, you may pass a callback. Its called right before exit.
	 *
	 * @param callable|null $callback
	 */
    protected function stopAction(callable $callback = NULL) {
    	// Maybe any active listeners
    	ob_end_clean();
    	if(is_callable($callback))
    		call_user_func($callback);
    	exit();
	}

	/**
	 * Stops and redirect immediately to the passed URL
	 *
	 * @param string $URL
	 * @param callable|null $callback
	 */
	protected function stopAndRedirectAction(string $URL, callable $callback = NULL) {
    	$this->stopAction(function() use ($URL, $callback) {
    		if(is_callable($callback))
    			call_user_func($callback);

    		header("Location: $URL");
		});
	}

	/**
	 * Stops and reload the same URI again.
	 *
	 * @param callable|null $callback
	 */
	protected function stopAndReloadAction(callable $callback = NULL) {
		/** @var Request $request */
		$request = $this->request;
		$this->stopAndRedirectAction($request->getUri(), $callback);
	}

    /**
     * Tells the render process to choose a preferred render if possible.
     *
     * @param string $preferredRenderName
     */
    protected function preferRender(string $preferredRenderName) {
        $response = $this->response;
        if($response instanceof Response) {
            $rc = $this->renderController;
            if($rc instanceof RenderControllerInterface) {
                $render = $rc->getRender( $preferredRenderName );
                if($render instanceof OutputRenderInterface)
                    $response->headers->set('Content-Type', $render->getContentType());
            }
        }
        $this->renderInfo->set( RenderInfoInterface::INFO_PREFERRED_RENDER, $preferredRenderName);
    }

    /**
     * Marks a template and children to render
     *
     * @param $template
     * @param array $children
     */
    protected function renderTemplate($template, array $children = []) {
        $this->renderInfo->set( RenderInfoInterface::INFO_TEMPLATE, $template );
        $this->renderInfo->set( RenderInfoInterface::INFO_SUB_TEMPLATES, $children );
    }

    /**
     * Declare a data model to use while rendering a template
     *
     * @param ModelInterface|array $dataModel
     * @param bool $expandModelInScope
     */
    protected function renderModel($dataModel, bool $expandModelInScope = false) {
        if(is_array($dataModel))
            $dataModel = new ExtractableArrayModel($dataModel);

        $this->renderInfo->set(RenderInfoInterface::INFO_MODEL, $dataModel);
        if($expandModelInScope)
            $this->renderInfo->set(RenderInfoInterface::INFO_MODEL . "-expand", true);
    }

    /**
     * Render dynamic title for this page
     *
     * @param $title
     */
    protected function renderTitle($title) {
        $this->renderInfo->set( RenderInfoInterface::INFO_TITLE, $title );
    }

    /**
     * Render dynamic description for this page
     *
     * @param $description
     */
    protected function renderDescription($description) {
        $this->renderInfo->set( RenderInfoInterface::INFO_DESCRIPTION, $description );
    }

    /**
     * Render dynamic metadata in HTML
     *
     * @param $name
     * @param $content
     */
    protected function renderMeta($name, $content) {
        $meta = $this->renderInfo->get( RenderInfoInterface::INFO_DYNAMIC_META );
        if(!$meta)
            $meta = [];
        $meta[$name] = $content;
        $this->renderInfo->set( RenderInfoInterface::INFO_DYNAMIC_META, $meta );
    }

    /**
     * Renders a logical link to the current page
     *
     * @param $relation
     * @param $reference
     * @param null $contentType
     */
    protected function renderLogicalLink($relation, $reference, $contentType = NULL) {
        $link = [
            "rel" => $relation,
            "href" => $reference
        ];
        if($contentType)
            $link["type"] = $contentType;

        $links = $this->renderInfo->get( RenderInfoInterface::INFO_DYNAMIC_LINKS );
        if(!$links)
            $links = [];
        $links[] = $link;
        $this->renderInfo->set( RenderInfoInterface::INFO_DYNAMIC_LINKS, $links );
    }

    /**
     * Marks this page as a member of a collection and let the browser know where to get the next member in the collection
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderNextPage($reference, $contentType = NULL) {
        $this->renderLogicalLink('next', $reference, $contentType);
    }

    /**
     * Marks this page as a member of a collection and let the browser know where to get the previous member in the collection
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderPreviousPage($reference, $contentType = NULL) {
        $this->renderLogicalLink('prev', $reference, $contentType);
    }

    /**
     * Marks this page as a member of a collection and let the browser know where to get the first member in the collection
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderFirstPage($reference, $contentType = NULL) {
        $this->renderLogicalLink('first', $reference, $contentType);
    }

    /**
     * Marks this page as a member of a collection and let the browser know where to get the last member in the collection
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderLastPage($reference, $contentType = NULL) {
        $this->renderLogicalLink('last', $reference, $contentType);
    }

    /**
     * Marks this page as a member of a collection and let the browser know where to get the parent member of the collection
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderParentPage($reference, $contentType = NULL) {
        $this->renderLogicalLink('up', $reference, $contentType);
    }

    /**
     * Tells a browser or search engine where to get a search page to look at for the collection
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderSearchPage($reference, $contentType = NULL) {
        $this->renderLogicalLink('search', $reference, $contentType);
    }

    /**
     * If your application knows several URL to get the same page, you should render a primary URL for the page.
     * This might increase seo ranking, but ignoring this, your page might fall in ranking (double content punishment)
     *
     * @param $reference
     * @param null $contentType
     */
    protected function renderCanonical($reference, $contentType = NULL) {
        $this->renderLogicalLink('canonical', $reference, $contentType);
    }
}