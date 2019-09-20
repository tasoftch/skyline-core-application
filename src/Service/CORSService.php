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

namespace Skyline\Application\Service;


use Symfony\Component\HttpFoundation\Request;

final class CORSService
{
    private static $hosts;
    public static $acceptsFromReferer = true;
    /**
     * Registers a host name for your application.
     * The Skyline CMS will use this host lists to determine if a request is authorized to obtain data.
     * For example the Public directory of all modules are only accessible for requests of registered hosts.
     *
     * @param string $host
     * @param string|NULL $acceptsFrom
     * @param bool $withCredentials
     */
    public static function registerHost(string $host, $acceptsFrom = "", bool $withCredentials = NULL) {
        $add = function($acceptsFrom) use ($host, $withCredentials) {
            if($acceptsFrom != "" && $withCredentials === NULL)
                $withCredentials = true;

            self::$hosts[ $host ] [$acceptsFrom] = $withCredentials;
        };

        if(is_array($acceptsFrom)) {
            foreach($acceptsFrom as $value)
                $add($value);
        } else {
            $add($acceptsFrom);
        }
    }

    /**
     * Decide, if the request's origin is accepted by this application
     *
     * @param Request $request
     * @param $withCredentials
     * @return string|null
     */
    public static function getAllowedOriginOf(Request $request, &$withCredentials) {
        $serverHost = $request->headers->get("HOST");
        $withCredentials = false;

        if(($info = static::$hosts[$serverHost] ?? false) && self::getOriginOfRequest($request, $host, $scheme)) {

            foreach($info as $pattern => $withC) {
                if(fnmatch($pattern, $host)) {
                    $withCredentials = $withC;
                    return "$scheme://$host";
                }
            }
        }

        return NULL;
    }

    /**
     * Fetches the origin (or if enabled the referer) header field of a request and creates a valid origin string
     *
     * @param Request $request
     * @param string $host
     * @param string $scheme
     * @return string|null
     */
    public static function getOriginOfRequest(Request $request, string &$host = NULL, string &$scheme = NULL): ?string {
        // According to CORS protocol, a cross origin request must declare its origin
        $origin = $request->headers->get("ORIGIN");
        if(!$origin && self::$acceptsFromReferer) {
            // Skyline also accepts from referer
            $origin = $request->headers->get("REFERER");
        }

        if(!$origin)
            return NULL;

        $origin = parse_url($origin);

        $host = $origin["host"] ?? NULL;
        $scheme = $origin["scheme"] ?? NULL;
        return ($host && $scheme) ? "$scheme://$host" : NULL;
    }

    /**
     * Checks, if a given host is registered
     *
     * @param $hostName
     * @return bool
     */
    public static function isRegistered($hostName) {
        return isset(self::$hosts[$hostName]);
    }
}