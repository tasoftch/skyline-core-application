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

namespace Skyline\Application\Plugin\Router;


use Skyline\Kernel\Config\PluginFactoryInterface;
use Skyline\Kernel\Exception\SkylineKernelDetailedException;
use Skyline\Render\Router\Assigner\ControllerWithRenderAssigner;
use Skyline\Router\AbstractRouterPlugin;
use Skyline\Router\HTTP\LiteralContentTypeRouter;
use Skyline\Router\HTTP\LiteralHOSTRouter;
use Skyline\Router\HTTP\LiteralURIRouter;
use Skyline\Router\HTTP\RegexContentTypeRouter;
use Skyline\Router\HTTP\RegexHOSTRouter;
use Skyline\Router\HTTP\RegexURIRouter;
use Skyline\Router\PartialAssigner\ControllerAndMethodNameAssigner;
use Skyline\Router\PartialAssigner\ControllerOnlyAssigner;
use Skyline\Router\PartialAssigner\PartialAssignerInterface;
use Skyline\Router\PartialAssigner\PriorityAssigner;
use Skyline\Router\RouterInterface;
use TASoft\EventManager\EventManagerInterface;

/**
 * This plugin registers router conforming to compiled routing.config.php file.
 * It only registers the necessary routers to deliver all compiled routings.
 * It also includes routing to renders at once.
 *
 * @package Skyline\Application\Plugin
 */
class ApplicationRouterPlugin extends AbstractRouterPlugin implements PluginFactoryInterface
{
    /*
     * Each found routing.cfg.php file in packages and routing.config.php in your custom config directory will be compiled.
     * So each file must return an array containing a key route (*_ROUTE constants) which is another array with string keys as pattern and value as array containing keys ROUTED_* to specify the route.
     */



    /** @var string */
    private $routingTableFilename;

    /**
     * CMSRouter constructor.
     * @param string $routingTableFilename
     */
    public function __construct(string $routingTableFilename)
    {
        $this->routingTableFilename = $routingTableFilename;
    }

    /**
     * @return string
     */
    public function getRoutingTableFilename(): string
    {
        return $this->routingTableFilename;
    }

    /**
     * @inheritDoc
     */
    public function initialize(EventManagerInterface $eventManager, bool $once)
    {
        $table = @ include $this->getRoutingTableFilename();
        if(!$table) {
            error_clear_last();
            $e = new SkylineKernelDetailedException("Routing table not found");
            $e->setDetails("Routing table %s not found", basename($this->getRoutingTableFilename()));
            throw $e;
        }

        foreach($table as $key => $contents) {
            $priority = 100;

            $router = $this->getRouterForSection($key, $contents, $priority);
            if($router) {
                $eventManager->addListener(SKY_EVENT_ROUTE, [$router, 'routeEvent'], $priority);
            } else
                trigger_error("Could not instantiate router for $key", E_USER_WARNING);
        }
    }

    /**
     * This method decides which router kind is used for the sections.
     *
     * @param string $section
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     *
     * @see ApplicationRouterPlugin::*_ROUTE constants for sections
     */
    protected function getRouterForSection(string $section, array $contents, int &$priority): ?RouterInterface {
        switch ($section) {
            case static::URI_ROUTE: return $this->getRouterForURI($contents, $priority);
            case static::HOST_ROUTE: return $this->getRouterForHOST($contents, $priority);
            case static::CONTENT_TYPE_ROUTE: return $this->getRouterForContentType($contents, $priority);
            case static::REGEX_URI_ROUTE: return $this->getRouterForRegexURI($contents, $priority);
            case static::REGEX_HOST_ROUTE: return $this->getRouterForRegexHOST($contents, $priority);
            case static::REGEX_CONTENT_TYPE_ROUTE: return $this->getRouterForRegexContentType($contents, $priority);
            default: return $this->getRouterForUnknownSection($section, $contents, $priority);
        }
    }

    protected function getAssignerForSection(string $section): PartialAssignerInterface {
        $assigner = new PriorityAssigner();

        $assigner->addAssigner(new ControllerWithRenderAssigner(), 10);
        $assigner->addAssigner(new ControllerAndMethodNameAssigner(), 100);
        $assigner->addAssigner(new ControllerOnlyAssigner(), 1000);

        return $assigner;
    }

    /**
     * Create router for section URI_ROUTE.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForURI(array $contents, int &$priority): ?RouterInterface {
        $router = new LiteralURIRouter($contents, LiteralURIRouter::OPT_IGNORE_FRAGMENT|LiteralURIRouter::OPT_IGNORE_QUERY);
        $router->setAssigner( $this->getAssignerForSection(static::URI_ROUTE) );
        return $router;
    }

    /**
     * Create router for section HOST_ROUTE.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForHOST(array $contents, int &$priority): ?RouterInterface {
        $router = new LiteralHOSTRouter($contents, LiteralURIRouter::OPT_IGNORE_FRAGMENT|LiteralURIRouter::OPT_IGNORE_QUERY);
        $router->setAssigner( $this->getAssignerForSection(static::HOST_ROUTE) );
        $priority = 90;
        return $router;
    }

    /**
     * Create router for section CONTENT_TYPE_ROUTE.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForContentType(array $contents, int &$priority): ?RouterInterface {
        $router = new LiteralContentTypeRouter($contents);
        $router->setAssigner( $this->getAssignerForSection(static::CONTENT_TYPE_ROUTE) );
        $priority = 80;
        return $router;
    }

    /**
     * Create router for section REGEX_URI_ROUTE.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForRegexURI(array $contents, int &$priority): ?RouterInterface {
        $router = new RegexURIRouter($contents);
        $router->setAssigner( $this->getAssignerForSection(static::REGEX_URI_ROUTE) );
        $priority = 1000;
        return $router;
    }

    /**
     * Create router for section REGEX_HOST_ROUTE.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForRegexHOST(array $contents, int &$priority): ?RouterInterface {
        $router = new RegexHOSTRouter($contents);
        $router->setAssigner( $this->getAssignerForSection(static::REGEX_HOST_ROUTE) );
        $priority = 900;
        return $router;
    }

    /**
     * Create router for section REGEX_CONTENT_TYPE_ROUTE.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForRegexContentType(array $contents, int &$priority): ?RouterInterface {
        $router = new RegexContentTypeRouter($contents);
        $router->setAssigner( $this->getAssignerForSection(static::REGEX_CONTENT_TYPE_ROUTE) );
        $priority = 81;
        return $router;
    }

    /**
     * Create router for any section this factory does not know.
     *
     * @param array $contents
     * @param int $priority
     * @return RouterInterface|null
     */
    protected function getRouterForUnknownSection(string $section, array $contents, int &$priority): ?RouterInterface {
        return NULL;
    }
}