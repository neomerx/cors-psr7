<?php namespace Neomerx\Cors\Strategies;

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

use \Psr\Http\Message\RequestInterface;
use \Neomerx\Cors\Contracts\Http\ParsedUrlInterface;
use \Neomerx\Cors\Contracts\AnalysisStrategyInterface;

/**
 * Implements strategy as a simple set of setting identical for all resources and requests.
 *
 * @package Neomerx\Cors
 */
class Settings implements AnalysisStrategyInterface
{
    /**
     * @var string|array If specified as array (recommended for better performance) it should
     * be in parse_url() result format.
     *
     * @see http://php.net/manual/function.parse-url.php
     */
    public static $serverOrigin;

    /**
     * A list of allowed request origins (lower-cased, no trail slashes).
     * Value `true` enables and value `null` disables origin.
     *
     * For example,
     *
     * public static $allowedOrigins = [
     *     'http://example.com:123' => true,
     *     'http://evil.com'        => null,
     * ];
     *
     * @var array
     */
    public static $allowedOrigins;

    /**
     * A list of allowed request methods (case sensitive). Value `true` enables and value `null` disables method.
     *
     * For example,
     *
     * public static $allowedMethods = [
     *     'GET'    => true,
     *     'PATCH'  => true,
     *     'POST'   => true,
     *     'PUT'    => null,
     *     'DELETE' => true,
     * ];
     *
     * @var array
     */
    public static $allowedMethods = [
        'GET'    => true,
        'PATCH'  => true,
        'POST'   => true,
        'PUT'    => true,
        'DELETE' => true,
    ];

    /**
     * A list of allowed request headers (lower-cased). BValue `true` enables and value `null` disables header.
     *
     * For example,
     *
     * public static $allowedHeaders = [
     *     'content-type'            => true,
     *     'x-custom-request-header' => null,
     * ];
     *
     * @var array
     */
    public static $allowedHeaders = [];

    /**
     * A list of headers (case insensitive) which will be made accessible to user agent (browser) in response.
     *
     * For example,
     *
     * public static $exposedHeaders = [
     *     'Content-Type',
     *     'X-Custom-Response-Header',
     * ];
     *
     * @var string[]
     */
    public static $exposedHeaders = [];

    /**
     * If access with credentials is supported by the resource.
     *
     * @var bool
     */
    public static $isUsingCredentials = true;

    /**
     * Pre-flight response cache max period in seconds.
     *
     * @var int
     */
    public static $preFlightCacheMaxAge = 0;

    /**
     * If allowed methods should be added to pre-flight response when 'simple' method is requested (see #6.2.9 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @var bool
     */
    public static $isForceAddMethods = false;

    /**
     * If allowed headers should be added when request headers are 'simple' and
     * non of them is 'Content-Type' (see #6.2.10 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @var bool
     */
    public static $isForceAddHeaders = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        assert('static::$serverOrigin !== null', 'Server origin URL must be specified');
        assert('static::$allowedOrigins !== null', 'Allowed request origins must be specified');
    }

    /**
     * @inheritdoc
     */
    public function getServerOrigin()
    {
        return static::$serverOrigin;
    }

    /**
     * @inheritdoc
     */
    public function isPreFlightCanBeCached(RequestInterface $request)
    {
        return $this->getPreFlightCacheMaxAge($request) > 0;
    }

    /**
     * @inheritdoc
     */
    public function getPreFlightCacheMaxAge(RequestInterface $request)
    {
        return static::$preFlightCacheMaxAge;
    }

    /**
     * @inheritdoc
     */
    public function isForceAddAllowedMethodsToPreFlightResponse()
    {
        return static::$isForceAddMethods;
    }

    /**
     * @inheritdoc
     */
    public function isForceAddAllowedHeadersToPreFlightResponse()
    {
        return static::$isForceAddHeaders;
    }

    /**
     * @inheritdoc
     */
    public function isRequestCredentialsSupported(RequestInterface $request)
    {
        return static::$isUsingCredentials;
    }

    /**
     * @inheritdoc
     */
    public function isRequestOriginAllowed(ParsedUrlInterface $requestOrigin)
    {
        $requestOriginStr = strtolower($requestOrigin->getOrigin());
        $isAllowed = isset(static::$allowedOrigins[$requestOriginStr]);

        return $isAllowed;
    }

    /**
     * @inheritdoc
     */
    public function isRequestMethodSupported($method)
    {
        $isAllowed = isset(static::$allowedMethods[$method]);

        return $isAllowed;
    }

    /**
     * @inheritdoc
     */
    public function isRequestAllHeadersSupported($headers)
    {
        $allSupported = true;

        foreach ($headers as $header) {
            $header = strtolower($header);
            if (isset(static::$allowedHeaders[$header]) === false) {
                $allSupported = false;
                break;
            }
        }

        return $allSupported;
    }

    /**
     * @inheritdoc
     */
    public function getRequestAllowedMethods(RequestInterface $request, $requestMethod)
    {
        return implode(', ', $this->getEnabledItems(static::$allowedMethods));
    }

    /**
     * @inheritdoc
     */
    public function getRequestAllowedHeaders(RequestInterface $request, array $requestHeaders)
    {
        return implode(', ', $this->getEnabledItems(static::$allowedHeaders));
    }

    /**
     * @inheritdoc
     */
    public function getResponseExposedHeaders(RequestInterface $request)
    {
        return static::$exposedHeaders;
    }

    /**
     * Select only enabled items from $list.
     *
     * @param array $list
     *
     * @return array
     */
    protected function getEnabledItems(array $list)
    {
        $items = [];

        foreach ($list as $item => $enabled) {
            if ($enabled === true) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
