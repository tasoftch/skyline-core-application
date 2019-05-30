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

namespace Skyline\Application;


use Skyline\Application\Event\ActionControllerEvent;
use Skyline\Application\Event\LaunchEvent;
use Skyline\Application\Event\PerformActionEvent;
use Skyline\Application\Event\RenderEvent;
use Skyline\Application\Exception\ApplicationException;
use Skyline\Application\Exception\RenderResponseException;
use Skyline\Application\Exception\UnresolvedActionDescriptionException;
use Skyline\Application\Exception\UnresolvedActionException;
use Skyline\Application\Exception\UnresolvedRouteException;
use Skyline\Kernel\Config\MainKernelConfig;
use Skyline\Kernel\Config\PluginConfig;
use Skyline\Render\Router\Description\MutableRegexRenderActionDescription;
use Skyline\Router\Description\ActionDescriptionInterface;
use Skyline\Router\Event\HTTPRequestRouteEvent;
use Skyline\Router\Event\RouteEventInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TASoft\DI\DependencyManager;
use TASoft\DI\Injector\CallbackInjector;
use TASoft\EventManager\SectionEventManager;
use TASoft\Service\ServiceManager;
use Throwable;

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

    public function getRouteEvent(): RouteEventInterface
    {
        $request = Request::createFromGlobals();
        $event = new HTTPRequestRouteEvent($request);
        $event->setActionDescription(new MutableRegexRenderActionDescription());
        return $event;
    }


    /**
     * @inheritDoc
     */
    public function run()
    {
        /** @var ServiceManager $SERVICES */
        global $SERVICES;

        try {
            if($SERVICES instanceof ServiceManager) {
                /** @var SectionEventManager $eventManager */
                $eventManager = $SERVICES->get( MainKernelConfig::SERVICE_EVENT_MANAGER );

                $event = new LaunchEvent();
                $event->setApplication($this);
                $eventManager->triggerSection(PluginConfig::EVENT_SECTION_CONTROL,  SKY_EVENT_LAUNCH_APPLICATION, $event );

                if($event->isPropagationStopped())
                    goto finalize;

                $SERVICES->set("application", self::$runningApplication = $this);

                $routeEvent = $event->getApplication()->getRouteEvent();

                $SERVICES->set("request", $request = method_exists($routeEvent, 'getRequest') && ($r = $routeEvent->getRequest()) ? $r : Request::createFromGlobals());

                if(!$eventManager->triggerSection( PluginConfig::EVENT_SECTION_ROUTING, SKY_EVENT_ROUTE, $routeEvent )->isPropagationStopped() && NULL == ($actionDescription = $this->getRouteFailureActionDescription($routeEvent))) {
                    $e = new UnresolvedRouteException("Could not resolve route", 404);
                    $e->setRouteEvent($routeEvent);
                    throw $e;
                }

                $SERVICES->set("actionDescription", $actionDescription = $actionDescription ?? $routeEvent->getActionDescription());

                $actionEvent = new ActionControllerEvent($actionDescription);
                $eventManager->triggerSection(PluginConfig::EVENT_SECTION_CONTROL, SKY_EVENT_ACTION_CONTROLLER, $actionEvent);

                if(!$actionEvent->getActionController()) {
                    $e = new UnresolvedActionDescriptionException("Could not resolve an action controller", 404);
                    $e->setActionDescription($actionDescription);
                    $e->setRouteEvent($routeEvent);
                    throw $e;
                }

                $performActionEvent = new PerformActionEvent($request, $actionEvent->getActionController(), $actionDescription);
                $eventManager->triggerSection(PluginConfig::EVENT_SECTION_CONTROL, SKY_EVENT_PERFORM_ACTION, $performActionEvent);

                if(!$performActionEvent->getRenderInformation()) {
                    $e = new UnresolvedActionException("Performing action did not specify any render information", 404);
                    $e->setActionDescription($actionDescription);
                    $e->setRouteEvent($routeEvent);
                    $e->setActionController($actionEvent->getActionController());
                    throw $e;
                }

                $renderEvent = new RenderEvent($performActionEvent->getRenderInformation());
                $eventManager->triggerSection(PluginConfig::EVENT_SECTION_RENDER, SKY_EVENT_RENDER_RESPONSE, $renderEvent);

                if(!$renderEvent->getResponse()) {
                    $e = new RenderResponseException("Response Render Error", 500);
                    $e->setDetails("Application can not render response.");
                    throw $e;
                }

                $renderEvent->getResponse()->send();
            } else {
                $e = new ApplicationException("Application Launch Error", 500);
                $e->setDetails("Application can not lauch because no service manager is available. Probably Skyline CMS did not bootstrap");
                throw $e;
            }
        } catch (Throwable $exception) {
        }

        finalize:
        // Always trigger the tear down event.
        if(isset($eventManager))
            $eventManager->trigger( SKY_EVENT_TEAR_DOWN );
        if(isset($exception))
            throw $exception;
    }

    protected function getRouteFailureActionDescription(RouteEventInterface $event): ?ActionDescriptionInterface {
        return NULL;
    }
}