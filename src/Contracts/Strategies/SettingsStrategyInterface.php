<?php namespace Neomerx\Cors\Contracts\Strategies;

/**
 * Copyright 2015 info@neomerx.com (www.neomerx.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use \Neomerx\Cors\Contracts\AnalysisStrategyInterface;

/**
 * @package Neomerx\Cors
 */
interface SettingsStrategyInterface extends AnalysisStrategyInterface
{
    /**
     * Get all settings in internal format (for caching).
     *
     * @return array
     */
    public function getSettings();

    /**
     * Set settings from data in internal format.
     *
     * @param array $settings
     *
     * @return void
     */
    public function setSettings(array $settings);

    /**
     * Set server Origin URL. If array should be in parse_url() result format.
     *
     * @see http://php.net/manual/function.parse-url.php
     *
     * @param array|string $origin
     *
     * @return SettingsStrategyInterface
     */
    public function setServerOrigin($origin);

    /**
     * Set allowed origins. Should be a list of origins (lower-cased, no trail slashes) as keys and null/true as values.
     *
     * @param array $origins
     *
     * @return SettingsStrategyInterface
     */
    public function setRequestAllowedOrigins(array $origins);

    /**
     * Set allowed methods. Should be a list of methods (case sensitive) as keys and null/true as values.
     *
     * @param array $methods
     *
     * @return SettingsStrategyInterface
     */
    public function setRequestAllowedMethods(array $methods);

    /**
     * Set allowed headers. Should be a list of headers (case insensitive) as keys and null/true as values.
     *
     * @param array $headers
     *
     * @return SettingsStrategyInterface
     */
    public function setRequestAllowedHeaders(array $headers);

    /**
     * Set headers other than the simple ones that might be exposed to user agent.
     * Should be a list of headers (case insensitive) as keys and null/true as values.
     *
     * @param array $headers
     *
     * @return SettingsStrategyInterface
     */
    public function setResponseExposedHeaders(array $headers);

    /**
     * If access with credentials is supported by the resource.
     *
     * @param bool $isSupported
     *
     * @return SettingsStrategyInterface
     */
    public function setRequestCredentialsSupported($isSupported);

    /**
     * Set pre-flight cache max period in seconds.
     *
     * @param int $cacheMaxAge
     *
     * @return SettingsStrategyInterface
     */
    public function setPreFlightCacheMaxAge($cacheMaxAge);

    /**
     * If allowed methods should be added to pre-flight response when 'simple' method is requested (see #6.2.9 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @param bool $forceFlag
     *
     * @return SettingsStrategyInterface
     */
    public function setForceAddAllowedMethodsToPreFlightResponse($forceFlag);

    /**
     * If allowed headers should be added when request headers are 'simple' and
     * non of them is 'Content-Type' (see #6.2.10 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @param bool $forceFlag
     *
     * @return SettingsStrategyInterface
     */
    public function setForceAddAllowedHeadersToPreFlightResponse($forceFlag);

    /**
     * If request 'Host' header should be checked against server's origin.
     * Check of Host header is strongly encouraged by #6.3 CORS.
     * Header 'Host' must present for all requests rfc2616 14.23
     *
     * @param bool $checkFlag
     *
     * @return SettingsStrategyInterface
     */
    public function setCheckHost($checkFlag);
}
