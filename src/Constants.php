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

// Event is triggered right at begin of the application launch.
// An event of type LaunchEvent is triggered. The application set by this event is the running application.
use Skyline\Application\Event\LaunchEvent;

/**
 * Event is triggered right at begin of the application launch.
 * An event of type LaunchEvent is triggered. The application set by this event is the running application.
 * @see LaunchEvent::getApplication()
 * @see LaunchEvent::setApplication()
 */
define("SKY_EVENT_LAUNCH_APPLICATION", "skyline.app.launch");

/**
 * The route event is triggered after launching the application.
 */
define("SKY_EVENT_ROUTE", "skyline.route");

/**
 * If the application  could route to an action description,
 * this event is fired to instantiate an action controller instance.
 */
define("SKY_EVENT_ACTION_CONTROLLER", 'skyline.action.create');

/**
 * This event is fired immediately after the action controller could be loaded.
 * The event should handle everything needed to transform a request into either a response or template(s) and data model(s).
 */
define("SKY_EVENT_PERFORM_ACTION", "skyline.action.perform");

/**
 * So finally the render event is fired to perform the action and render a response.
 */
define("SKY_EVENT_RENDER_RESPONSE", "skyline.render");