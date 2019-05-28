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


use Skyline\Kernel\Config\PluginFactoryInterface;
use Skyline\Kernel\Exception\SkylineKernelDetailedException;
use TASoft\EventManager\EventManagerInterface;

/**
 * This plugin registers router conforming to compiled routing.config.php file.
 * It only registers the necessary routers to deliver all compiled routings.
 * It also includes routing to renders at once.
 *
 * @package Skyline\Application\Plugin
 */
class ApplicationRouterPlugin implements PluginFactoryInterface
{
    /*
     * Each found routing.cfg.php file in packages and routing.config.php in your custom config directory will be compiled.
     * So each file must return an array containing a key route (*_ROUTE constants) which is another array with string keys as pattern and value as array containing keys ROUTED_* to specify the route.
     */

    /** @var string Keys are literal URIs */
    const URI_ROUTE = "URI";
    const HOST_ROUTE = 'HOST';
    const CONTENT_TYPE_ROUTE = "CTYPE";

    const REGEX_URI_ROUTE = "RURI";
    const REGEX_HOST_ROUTE = 'RHOST';
    const REGEX_CONTENT_TYPE_ROUTE = "RCTYPE";

    const ROUTED_CONTROLLER_KEY = 'controller';
    const ROUTED_METHOD_KEY = 'method';
    const ROUTED_RENDER_KEY = 'render';

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
    }
}