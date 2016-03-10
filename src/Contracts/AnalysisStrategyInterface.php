<?php namespace Neomerx\Cors\Contracts;

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

use \Psr\Log\LoggerAwareInterface;
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Cors\Contracts\Http\ParsedUrlInterface;

/**
 * @package Neomerx\Cors
 */
interface AnalysisStrategyInterface extends LoggerAwareInterface
{
    /**
     * Get server Origin URL. If array is returned it should be in parse_url() result format.
     *
     * @see http://php.net/manual/function.parse-url.php
     *
     * @return string|array
     */
    public function getServerOrigin();

    /**
     * If pre-flight request result should be cached by user agent.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function isPreFlightCanBeCached(RequestInterface $request);

    /**
     * Get pre-flight cache max period in seconds.
     *
     * @param RequestInterface $request
     *
     * @return int
     */
    public function getPreFlightCacheMaxAge(RequestInterface $request);

    /**
     * If allowed methods should be added to pre-flight response when 'simple' method is requested (see #6.2.9 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @return bool
     */
    public function isForceAddAllowedMethodsToPreFlightResponse();

    /**
     * If allowed headers should be added when request headers are 'simple' and
     * non of them is 'Content-Type' (see #6.2.10 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     *
     * @return bool
     */
    public function isForceAddAllowedHeadersToPreFlightResponse();

    /**
     * If access with credentials is supported by the resource.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function isRequestCredentialsSupported(RequestInterface $request);

    /**
     * If request origin is allowed.
     *
     * @param ParsedUrlInterface $requestOrigin
     *
     * @return bool
     */
    public function isRequestOriginAllowed(ParsedUrlInterface $requestOrigin);

    /**
     * If method is supported for actual request (case-sensitive compare).
     *
     * @param string $method
     *
     * @return bool
     */
    public function isRequestMethodSupported($method);

    /**
     * If requests headers are allowed (case-insensitive compare).
     *
     * @param string[] $headers
     *
     * @return bool
     */
    public function isRequestAllHeadersSupported($headers);

    /**
     * Get methods allowed for request. May return originally requested method ($requestMethod) or
     * comma separated method list (#6.2.9 CORS).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS#Access-Control-Allow-Methods
     *
     * @param RequestInterface $request
     * @param string           $requestMethod
     *
     * @return string
     */
    public function getRequestAllowedMethods(RequestInterface $request, $requestMethod);

    /**
     * Get headers allowed for request (comma-separated list).
     *
     * @see http://www.w3.org/TR/cors/#resource-preflight-requests
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS#Access-Control-Allow-Headers
     *
     * @param RequestInterface $request
     * @param array            $requestHeaders
     *
     * @return string
     */
    public function getRequestAllowedHeaders(RequestInterface $request, array $requestHeaders);

    /**
     * Get headers other than the simple ones that might be exposed to user agent.
     *
     * @param RequestInterface $request
     *
     * @return string[]
     */
    public function getResponseExposedHeaders(RequestInterface $request);

    /**
     * If request 'Host' header should be checked against server's origin.
     * Check of Host header is strongly encouraged by #6.3 CORS.
     * Header 'Host' must present for all requests rfc2616 14.23
     *
     * @return bool
     */
    public function isCheckHost();
}
