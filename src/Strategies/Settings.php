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

use \Neomerx\Cors\Log\LoggerAwareTrait;
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
    use LoggerAwareTrait;

    /**
     * 'All' value for allowed origins.
     */
    const VALUE_ALLOW_ORIGIN_ALL = CorsResponseHeaders::VALUE_ALLOW_ORIGIN_ALL;

    /**
     * 'All' values for allowed headers.
     *
     * @deprecated
     * Please list all supported headers instead. 'All headers allowed' is not supported by browsers.
     * @see https://github.com/neomerx/cors-psr7/issues/23
     */
    const VALUE_ALLOW_ALL_HEADERS = '*';

    /** Settings key */
    const KEY_SERVER_ORIGIN = 0;

    /**
     * @deprecated Server Scheme is not used for `Host` header comparison anymore. You should remove it from settings.
     *
     * Settings key
     */
    const KEY_SERVER_ORIGIN_SCHEME = 'scheme';

    /** Settings key */
    const KEY_SERVER_ORIGIN_HOST = 'host';

    /** Settings key */
    const KEY_SERVER_ORIGIN_PORT = 'port';

    /** Settings key */
    const KEY_ALLOWED_ORIGINS = 1;

    /** Settings key */
    const KEY_ALLOWED_METHODS = 2;

    /** Settings key */
    const KEY_ALLOWED_HEADERS = 3;

    /** Settings key */
    const KEY_EXPOSED_HEADERS = 4;

    /** Settings key */
    const KEY_IS_USING_CREDENTIALS = 5;

    /** Settings key */
    const KEY_FLIGHT_CACHE_MAX_AGE = 6;

    /** Settings key */
    const KEY_IS_FORCE_ADD_METHODS = 7;

    /** Settings key */
    const KEY_IS_FORCE_ADD_HEADERS = 8;

    /** Settings key */
    const KEY_IS_CHECK_HOST = 9;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     */
    public function __construct(array $settings = null)
    {
        $this->setSettings($settings !== null ? $settings : $this->getDefaultSettings());
    }

    /**
     * @inheritdoc
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @inheritdoc
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @inheritdoc
     */
    public function getServerOrigin()
    {
        return $this->settings[self::KEY_SERVER_ORIGIN];
    }

    /**
     * @inheritdoc
     */
    public function setServerOrigin($origin)
    {
        $this->settings[self::KEY_SERVER_ORIGIN] = is_string($origin) === true ? parse_url($origin) : $origin;

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
        return $this->getValue(self::KEY_FLIGHT_CACHE_MAX_AGE, 0);
    }

    /**
     * @inheritdoc
     */
    public function setPreFlightCacheMaxAge($cacheMaxAge)
    {
        $this->settings[self::KEY_FLIGHT_CACHE_MAX_AGE] = $cacheMaxAge;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isForceAddAllowedMethodsToPreFlightResponse()
    {
        return $this->getValue(self::KEY_IS_FORCE_ADD_METHODS, false);
    }

    /**
     * @inheritdoc
     */
    public function setForceAddAllowedMethodsToPreFlightResponse($forceFlag)
    {
        $this->settings[self::KEY_IS_FORCE_ADD_METHODS] = $forceFlag;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isForceAddAllowedHeadersToPreFlightResponse()
    {
        return $this->getValue(self::KEY_IS_FORCE_ADD_HEADERS, false);
    }

    /**
     * @inheritdoc
     */
    public function setForceAddAllowedHeadersToPreFlightResponse($forceFlag)
    {
        $this->settings[self::KEY_IS_FORCE_ADD_HEADERS] = $forceFlag;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRequestCredentialsSupported(RequestInterface $request)
    {
        return $this->getValue(self::KEY_IS_USING_CREDENTIALS, false);
    }

    /**
     * @inheritdoc
     */
    public function setRequestCredentialsSupported($isSupported)
    {
        $this->settings[self::KEY_IS_USING_CREDENTIALS] = $isSupported;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRequestOriginAllowed(ParsedUrlInterface $requestOrigin)
    {
        // check if all origins are allowed with '*'
        $isAllowed =
            isset($this->settings[self::KEY_ALLOWED_ORIGINS][CorsResponseHeaders::VALUE_ALLOW_ORIGIN_ALL]);

        if ($isAllowed === false) {
            $requestOriginStr = strtolower($requestOrigin->getOrigin());
            $isAllowed        = isset($this->settings[self::KEY_ALLOWED_ORIGINS][$requestOriginStr]);
        }

        return $isAllowed;
    }

    /**
     * @inheritdoc
     */
    public function setRequestAllowedOrigins(array $origins)
    {
        $this->settings[self::KEY_ALLOWED_ORIGINS] = [];
        foreach ($origins as $origin => $enabled) {
            $lcOrigin                                             = strtolower($origin);
            $this->settings[self::KEY_ALLOWED_ORIGINS][$lcOrigin] = $enabled;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRequestMethodSupported($method)
    {
        $isAllowed = isset($this->settings[self::KEY_ALLOWED_METHODS][$method]);

        return $isAllowed;
    }

    /**
     * @inheritdoc
     */
    public function isRequestAllHeadersSupported($headers)
    {
        $allSupported = true;

        if (isset($this->settings[self::KEY_ALLOWED_HEADERS][self::VALUE_ALLOW_ALL_HEADERS]) === true) {
            return $allSupported;
        }

        foreach ($headers as $header) {
            $lcHeader = strtolower($header);
            if (isset($this->settings[self::KEY_ALLOWED_HEADERS][$lcHeader]) === false) {
                $allSupported = false;
                $this->logInfo(
                    'Request header is not allowed. Check config settings for Allowed Headers.',
                    ['header' => $header]
                );
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
        return implode(', ', $this->getEnabledItems($this->settings[self::KEY_ALLOWED_METHODS]));
    }

    /**
     * @inheritdoc
     */
    public function setRequestAllowedMethods(array $methods)
    {
        $this->settings[self::KEY_ALLOWED_METHODS] = [];
        foreach ($methods as $method => $enabled) {
            $this->settings[self::KEY_ALLOWED_METHODS][$method] = $enabled;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRequestAllowedHeaders(RequestInterface $request, array $requestHeaders)
    {
        $headers = $this->settings[self::KEY_ALLOWED_HEADERS];

        // 'all headers' is not a header actually so we remove it
        unset($headers[self::VALUE_ALLOW_ALL_HEADERS]);

        $enabled = $this->getEnabledItems($headers);

        return implode(', ', $enabled);
    }

    /**
     * @inheritdoc
     */
    public function setRequestAllowedHeaders(array $headers)
    {
        $this->settings[self::KEY_ALLOWED_HEADERS] = [];
        foreach ($headers as $header => $enabled) {
            $lcHeader                                             = strtolower($header);
            $this->settings[self::KEY_ALLOWED_HEADERS][$lcHeader] = $enabled;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponseExposedHeaders(RequestInterface $request)
    {
        return $this->getEnabledItems($this->settings[self::KEY_EXPOSED_HEADERS]);
    }

    /**
     * @inheritdoc
     */
    public function setResponseExposedHeaders(array $headers)
    {
        $this->settings[self::KEY_EXPOSED_HEADERS] = $headers;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isCheckHost()
    {
        return $this->getValue(self::KEY_IS_CHECK_HOST, false);
    }

    /**
     * @inheritdoc
     */
    public function setCheckHost($checkFlag)
    {
        $this->settings[self::KEY_IS_CHECK_HOST] = $checkFlag;

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

    /**
     * @return array
     */
    protected function getDefaultSettings()
    {
        return [
            /**
             * Array should be in parse_url() result format.
             * @see http://php.net/manual/function.parse-url.php
             *
             * Recommendation: specify only Host.
             * Browsers don't send port neither for HTTP(80), nor for HTTPS(443) in Host header which covers 99% cases.
             * You should specify the port only if you want to require non-standard port in `Host` header (e.g. 8080).
             */
            self::KEY_SERVER_ORIGIN        => [
                self::KEY_SERVER_ORIGIN_HOST   => '',
                self::KEY_SERVER_ORIGIN_PORT   => ParsedUrlInterface::DEFAULT_PORT,
            ],
            /**
             * A list of allowed request origins (lower-cased, no trail slashes).
             * Value `true` enables and value `null` disables origin.
             * If all origins '*' are enabled all settings for other origins are ignored.
             * For example, [
             *     'http://example.com:123'     => true,
             *     'http://evil.com'            => null,
             *     self::VALUE_ALLOW_ORIGIN_ALL => null,
             * ];
             */
            self::KEY_ALLOWED_ORIGINS      => [],
            /**
             * A list of allowed request methods (case sensitive).
             * Value `true` enables and value `null` disables method.
             * For example, [
             *     'GET'    => true,
             *     'PATCH'  => true,
             *     'POST'   => true,
             *     'PUT'    => null,
             *     'DELETE' => true,
             * ];
             * Security Note: you have to remember CORS is not access control system and you should not expect all
             * cross-origin requests will have pre-flights. For so-called 'simple' methods with so-called 'simple'
             * headers request will be made without pre-flight. Thus you can not restrict such requests with CORS
             * and should use other means.
             * For example method 'GET' without any headers or with only 'simple' headers will not have pre-flight
             * request so disabling it will not restrict access to resource(s).
             * You can read more on 'simple' methods at http://www.w3.org/TR/cors/#simple-method
             */
            self::KEY_ALLOWED_METHODS      => [],
            /**
             * A list of allowed request headers (lower-cased). Value `true` enables and
             * value `null` disables header.
             * For example, [
             *     'content-type'                => true,
             *     'x-custom-request-header'     => null,
             *     self::VALUE_ALLOW_ALL_HEADERS => null,
             * ];
             * Security Note: you have to remember CORS is not access control system and you should not expect all
             * cross-origin requests will have pre-flights. For so-called 'simple' methods with so-called 'simple'
             * headers request will be made without pre-flight. Thus you can not restrict such requests with CORS
             * and should use other means.
             * For example method 'GET' without any headers or with only 'simple' headers will not have pre-flight
             * request so disabling it will not restrict access to resource(s).
             * You can read more on 'simple' headers at http://www.w3.org/TR/cors/#simple-header
             */
            self::KEY_ALLOWED_HEADERS      => [],
            /**
             * A list of headers (case insensitive) which will be made accessible to
             * user agent (browser) in response.
             * Value `true` enables and value `null` disables header.
             * For example, [
             *     'Content-Type'             => true,
             *     'X-Custom-Response-Header' => true,
             *     'X-Disabled-Header'        => null,
             * ];
             */
            self::KEY_EXPOSED_HEADERS      => [],
            /**
             * If access with credentials is supported by the resource.
             */
            self::KEY_IS_USING_CREDENTIALS => false,
            /**
             * Pre-flight response cache max period in seconds.
             */
            self::KEY_FLIGHT_CACHE_MAX_AGE => 0,
            /**
             * If allowed methods should be added to pre-flight response when
             * 'simple' method is requested (see #6.2.9 CORS).
             * @see http://www.w3.org/TR/cors/#resource-preflight-requests
             */
            self::KEY_IS_FORCE_ADD_METHODS => false,
            /**
             * If allowed headers should be added when request headers are 'simple' and
             * non of them is 'Content-Type' (see #6.2.10 CORS).
             * @see http://www.w3.org/TR/cors/#resource-preflight-requests
             */
            self::KEY_IS_FORCE_ADD_HEADERS => false,
            /**
             * If request 'Host' header should be checked against server's origin.
             */
            self::KEY_IS_CHECK_HOST        => false,
        ];
    }

    /**
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function getValue($key, $default)
    {
        return array_key_exists($key, $this->settings) === true ? $this->settings[$key] : $default;
    }
}
