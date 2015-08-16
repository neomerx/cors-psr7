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
use \Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;
use \Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface;

/**
 * Implements strategy as a simple set of setting identical for all resources and requests.
 *
 * @package Neomerx\Cors
 */
class Settings implements SettingsStrategyInterface
{
    /**
     * @var string|array If specified as array (recommended for better performance) it should
     * be in parse_url() result format.
     *
     * @see http://php.net/manual/function.parse-url.php
     */
    private $serverOrigin = [
        'scheme' => '',
        'host'   => ParsedUrlInterface::DEFAULT_PORT,
        'port'   => '',
    ];

    /**
     * A list of allowed request origins (lower-cased, no trail slashes).
     * Value `true` enables and value `null` disables origin.
     * If all origins '*' are enabled all settings for other origins are ignored.
     *
     * For example,
     *
     * $allowedOrigins = [
     *     'http://example.com:123' => true,
     *     'http://evil.com'        => null,
     *     '*'                      => null,
     * ];
     *
     * @var array
     */
    private $allowedOrigins = [];

    /**
     * A list of allowed request methods (case sensitive). Value `true` enables and value `null` disables method.
     *
     * For example,
     *
     * $allowedMethods = [
     *     'GET'    => true,
     *     'PATCH'  => true,
     *     'POST'   => true,
     *     'PUT'    => null,
     *     'DELETE' => true,
     * ];
     *
     * Security Note: you have to remember CORS is not access control system and you should not expect all cross-origin
     * requests will have pre-flights. For so-called 'simple' methods with so-called 'simple' headers request
     * will be made without pre-flight. Thus you can not restrict such requests with CORS and should use other means.
     * For example method 'GET' without any headers or with only 'simple' headers will not have pre-flight request so
     * disabling it will not restrict access to resource(s).
     *
     * You can read more on 'simple' methods at http://www.w3.org/TR/cors/#simple-method
     *
     * @var array
     */
    private $allowedMethods = [];

    /**
     * A list of allowed request headers (lower-cased). Value `true` enables and value `null` disables header.
     *
     * For example,
     *
     * $allowedHeaders = [
     *     'content-type'            => true,
     *     'x-custom-request-header' => null,
     * ];
     *
     * Security Note: you have to remember CORS is not access control system and you should not expect all cross-origin
     * requests will have pre-flights. For so-called 'simple' methods with so-called 'simple' headers request
     * will be made without pre-flight. Thus you can not restrict such requests with CORS and should use other means.
     * For example method 'GET' without any headers or with only 'simple' headers will not have pre-flight request so
     * disabling it will not restrict access to resource(s).
     *
     * You can read more on 'simple' headers at http://www.w3.org/TR/cors/#simple-header
     *
     * @var array
     */
    private $allowedHeaders = [];

    /**
     * A list of headers (case insensitive) which will be made accessible to user agent (browser) in response.
     * Value `true` enables and value `null` disables header.
     *
     * For example,
     *
     * $exposedHeaders = [
     *     'Content-Type'             => true,
     *     'X-Custom-Response-Header' => true,
     *     'X-Disabled-Header'        => null,
     * ];
     *
     * @var string[]
     */
    private $exposedHeaders = [];

    /**
     * If access with credentials is supported by the resource.
     *
     * @var bool
     */
    private $isUsingCredentials = false;

    /**
     * Pre-flight response cache max period in seconds.
     *
     * @var int
     */
    private $preFlightCacheMaxAge = 0;

    /**
     * If allowed methods should be added to pre-flight response when 'simple' method is requested (see #6.2.9 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @var bool
     */
    private $isForceAddMethods = false;

    /**
     * If allowed headers should be added when request headers are 'simple' and
     * non of them is 'Content-Type' (see #6.2.10 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @var bool
     */
    private $isForceAddHeaders = false;

    /**
     * If request 'Host' header should be checked against server's origin.
     *
     * @var bool
     */
    private $isCheckHost = false;

    /**
     * @inheritdoc
     */
    public function getServerOrigin()
    {
        return $this->serverOrigin;
    }

    /**
     * @inheritdoc
     */
    public function setServerOrigin($origin)
    {
        $this->serverOrigin = $origin;

        return $this;
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
        return $this->preFlightCacheMaxAge;
    }

    /**
     * @inheritdoc
     */
    public function setPreFlightCacheMaxAge($cacheMaxAge)
    {
        $this->preFlightCacheMaxAge = $cacheMaxAge;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isForceAddAllowedMethodsToPreFlightResponse()
    {
        return $this->isForceAddMethods;
    }

    /**
     * @inheritdoc
     */
    public function setForceAddAllowedMethodsToPreFlightResponse($forceFlag)
    {
        $this->isForceAddMethods = $forceFlag;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isForceAddAllowedHeadersToPreFlightResponse()
    {
        return $this->isForceAddHeaders;
    }

    /**
     * @inheritdoc
     */
    public function setForceAddAllowedHeadersToPreFlightResponse($forceFlag)
    {
        $this->isForceAddHeaders = $forceFlag;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRequestCredentialsSupported(RequestInterface $request)
    {
        return $this->isUsingCredentials;
    }

    /**
     * @inheritdoc
     */
    public function setRequestCredentialsSupported($isSupported)
    {
        $this->isUsingCredentials = $isSupported;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRequestOriginAllowed(ParsedUrlInterface $requestOrigin)
    {
        // check if all origins are allowed with '*'
        $isAllowed = isset($this->allowedOrigins[CorsResponseHeaders::VALUE_ALLOW_ORIGIN_ALL]);

        if ($isAllowed === false) {
            $requestOriginStr = strtolower($requestOrigin->getOrigin());
            $isAllowed        = isset($this->allowedOrigins[$requestOriginStr]);
        }

        return $isAllowed;
    }

    /**
     * @inheritdoc
     */
    public function setRequestAllowedOrigins(array $origins)
    {
        $this->allowedOrigins = $origins;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRequestMethodSupported($method)
    {
        $isAllowed = isset($this->allowedMethods[$method]);

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
            if (isset($this->allowedHeaders[$header]) === false) {
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
        return implode(', ', $this->getEnabledItems($this->allowedMethods));
    }

    /**
     * @inheritdoc
     */
    public function setRequestAllowedMethods(array $methods)
    {
        $this->allowedMethods = $methods;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRequestAllowedHeaders(RequestInterface $request, array $requestHeaders)
    {
        return implode(', ', $this->getEnabledItems($this->allowedHeaders));
    }

    /**
     * @inheritdoc
     */
    public function setRequestAllowedHeaders(array $headers)
    {
        $this->allowedHeaders = $headers;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponseExposedHeaders(RequestInterface $request)
    {
        return $this->getEnabledItems($this->exposedHeaders);
    }

    /**
     * @inheritdoc
     */
    public function setResponseExposedHeaders(array $headers)
    {
        $this->exposedHeaders = $headers;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isCheckHost()
    {
        return $this->isCheckHost;
    }

    /**
     * @inheritdoc
     */
    public function setCheckHost($checkFlag)
    {
        $this->isCheckHost = $checkFlag;

        return $this;
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
