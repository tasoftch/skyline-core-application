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

use Skyline\Application\Plugin\ActionController\PerformActionPlugin;
use Skyline\Application\Plugin\Router\ApplicationRouterPlugin;
use Skyline\Application\Plugin\ActionController\ActionControllerCreationPlugin;
use Skyline\Application\Plugin\Render\RenderResponsePlugin;
use Skyline\Application\Plugin\Template\TemplateResolverPlugin;
use Skyline\Kernel\Config\PluginConfig;

return [
    'routing' => [
        PluginConfig::PLUGIN_EVENT_SECTION => PluginConfig::EVENT_SECTION_ROUTING,

        PluginConfig::PLUGIN_FACTORY => ApplicationRouterPlugin::class,
        PluginConfig::PLUGIN_ARGUMENTS => [
            '$(C)/routing.config.php'
        ]
    ],
    'controller' => [
        PluginConfig::PLUGIN_EVENT_SECTION => PluginConfig::EVENT_SECTION_CONTROL,
        PluginConfig::PLUGIN_EVENT_NAME => SKY_EVENT_ACTION_CONTROLLER,

        PluginConfig::PLUGIN_CLASS => ActionControllerCreationPlugin::class,
        PluginConfig::PLUGIN_METHOD => 'makeActionController',
        PluginConfig::PLUGIN_PRIORITY => 100
    ],
    'action' => [
        PluginConfig::PLUGIN_EVENT_SECTION => PluginConfig::EVENT_SECTION_CONTROL,
        PluginConfig::PLUGIN_EVENT_NAME => SKY_EVENT_PERFORM_ACTION,

        PluginConfig::PLUGIN_CLASS => PerformActionPlugin::class,
        PluginConfig::PLUGIN_METHOD => 'performAction',
        PluginConfig::PLUGIN_PRIORITY => 100
    ],
    "render" => [
        PluginConfig::PLUGIN_EVENT_SECTION => PluginConfig::EVENT_SECTION_RENDER,
        PluginConfig::PLUGIN_EVENT_NAME => SKY_EVENT_RENDER_RESPONSE,

        PluginConfig::PLUGIN_CLASS => RenderResponsePlugin::class,
        PluginConfig::PLUGIN_METHOD => 'renderResponse',
        PluginConfig::PLUGIN_PRIORITY => 100
    ],
    "templates" => [
        PluginConfig::PLUGIN_EVENT_NAME => SKY_EVENT_RENDER_RESPONSE,
        PluginConfig::PLUGIN_EVENT_SECTION => PluginConfig::EVENT_SECTION_RENDER,

        PluginConfig::PLUGIN_CLASS => TemplateResolverPlugin::class,
        PluginConfig::PLUGIN_METHOD => 'resolveTemplates',
        PluginConfig::PLUGIN_PRIORITY => 90
    ]
];
