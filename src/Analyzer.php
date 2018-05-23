<?php namespace Neomerx\Cors;

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

use \Psr\Log\LoggerInterface;
use \InvalidArgumentException;
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Cors\Log\LoggerAwareTrait;
use \Neomerx\Cors\Contracts\AnalyzerInterface;
use \Neomerx\Cors\Contracts\Http\ParsedUrlInterface;
use \Neomerx\Cors\Contracts\AnalysisResultInterface;
use \Neomerx\Cors\Contracts\Factory\FactoryInterface;
use \Neomerx\Cors\Contracts\AnalysisStrategyInterface;
use \Neomerx\Cors\Contracts\Constants\CorsRequestHeaders;
use \Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;
use \Neomerx\Cors\Contracts\Constants\SimpleRequestHeaders;
use \Neomerx\Cors\Contracts\Constants\SimpleRequestMethods;

/**
 * @package Neomerx\Cors
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Analyzer implements AnalyzerInterface
{
    use LoggerAwareTrait {
        LoggerAwareTrait::setLogger as psrSetLogger;
    }

    /** HTTP method for pre-flight request */
    const PRE_FLIGHT_METHOD = 'OPTIONS';

    /**
     * @var array
     */
    private $simpleMethods = [
        SimpleRequestMethods::GET  => true,
        SimpleRequestMethods::HEAD => true,
        SimpleRequestMethods::POST => true,
    ];

    /**
     * @var string[]
     */
    private $simpleHeadersExclContentType = [
        SimpleRequestHeaders::ACCEPT,
        SimpleRequestHeaders::ACCEPT_LANGUAGE,
        SimpleRequestHeaders::CONTENT_LANGUAGE,
    ];

    /**
     * @var AnalysisStrategyInterface
     */
    private $strategy;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @param AnalysisStrategyInterface $strategy
     * @param FactoryInterface          $factory
     */
    public function __construct(AnalysisStrategyInterface $strategy, FactoryInterface $factory)
    {
        $this->factory  = $factory;
        $this->strategy = $strategy;
    }

    /**
     * Create analyzer instance.
     *
     * @param AnalysisStrategyInterface $strategy
     *
     * @return AnalyzerInterface
     */
    public static function instance(AnalysisStrategyInterface $strategy)
    {
        return static::getFactory()->createAnalyzer($strategy);
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->psrSetLogger($logger);
        $this->strategy->setLogger($logger);
    }

    /**
     * @inheritdoc
     *
     * @see http://www.w3.org/TR/cors/#resource-processing-model
     */
    public function analyze(RequestInterface $request)
    {
        $this->logDebug('CORS analysis for request started.');

        $result = $this->analyzeImplementation($request);

        $this->logDebug('CORS analysis for request completed.');

        return $result;
    }

    /**
     * @param RequestInterface $request
     *
     * @return AnalysisResultInterface
     */
    protected function analyzeImplementation(RequestInterface $request)
    {
        $serverOrigin = $this->factory->createParsedUrl($this->strategy->getServerOrigin());

        // check 'Host' request
        if ($this->strategy->isCheckHost() === true && $this->isSameHost($request, $serverOrigin) === false) {
            $host = $this->getRequestHostHeader($request);
            $this->logInfo(
                'Host header in request either absent or do not match server origin. ' .
                'Check config settings for Server Origin and Host Check.',
                ['host' => $host, 'server' => $serverOrigin]
            );
            return $this->createResult(AnalysisResultInterface::ERR_NO_HOST_HEADER);
        }

        // Request handlers have common part (#6.1.1 - #6.1.2 and #6.2.1 - #6.2.2)

        // #6.1.1 and #6.2.1
        $requestOrigin = $this->getOrigin($request);
        if ($requestOrigin === null) {
            $this->logInfo(
                'Request is not CORS (request origin is empty).',
                ['request' => $requestOrigin, 'server' => $serverOrigin]
            );
            return $this->createResult(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE);
        } elseif ($this->isCrossOrigin($requestOrigin, $serverOrigin) === false) {
            $this->logInfo(
                'Request is not CORS (request origin equals to server one). ' .
                'Check config settings for Server Origin.',
                ['request' => $requestOrigin, 'server' => $serverOrigin]
            );
            return $this->createResult(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE);
        }

        // #6.1.2 and #6.2.2
        if ($this->strategy->isRequestOriginAllowed($requestOrigin) === false) {
            $this->logInfo(
                'Request origin is not allowed. Check config settings for Allowed Origins.',
                ['origin' => $requestOrigin]
            );
            return $this->createResult(AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED);
        }

        // Since this point handlers have their own path for
        // - simple CORS and actual CORS request (#6.1.3 - #6.1.4)
        // - pre-flight request (#6.2.3 - #6.2.10)

        if ($request->getMethod() === self::PRE_FLIGHT_METHOD) {
            $result = $this->analyzeAsPreFlight($request, $requestOrigin);
        } else {
            $result = $this->analyzeAsRequest($request, $requestOrigin);
        }

        return $result;
    }

    /**
     * Analyze request as simple CORS or/and actual CORS request (#6.1.3 - #6.1.4).
     *
     * @param RequestInterface   $request
     * @param ParsedUrlInterface $requestOrigin
     *
     * @return AnalysisResultInterface
     */
    protected function analyzeAsRequest(RequestInterface $request, ParsedUrlInterface $requestOrigin)
    {
        $this->logDebug('Request is identified as an actual CORS request.');

        $headers = [];

        // #6.1.3
        $headers[CorsResponseHeaders::ALLOW_ORIGIN] = $requestOrigin->getOrigin();
        if ($this->strategy->isRequestCredentialsSupported($request) === true) {
            $headers[CorsResponseHeaders::ALLOW_CREDENTIALS] = CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE;
        }
        // #6.4
        $headers[CorsResponseHeaders::VARY] = CorsRequestHeaders::ORIGIN;

        // #6.1.4
        $exposedHeaders = $this->strategy->getResponseExposedHeaders($request);
        if (empty($exposedHeaders) === false) {
            $headers[CorsResponseHeaders::EXPOSE_HEADERS] = $exposedHeaders;
        }

        return $this->createResult(AnalysisResultInterface::TYPE_ACTUAL_REQUEST, $headers);
    }

    /**
     * Analyze request as CORS pre-flight request (#6.2.3 - #6.2.10).
     *
     * @param RequestInterface   $request
     * @param ParsedUrlInterface $requestOrigin
     *
     * @return AnalysisResultInterface
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function analyzeAsPreFlight(RequestInterface $request, ParsedUrlInterface $requestOrigin)
    {
        // #6.2.3
        $requestMethod = $request->getHeader(CorsRequestHeaders::METHOD);
        if (empty($requestMethod) === true) {
            $this->logDebug('Request is not CORS (header ' . CorsRequestHeaders::METHOD . ' is not specified).');
            return $this->createResult(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE);
        } else {
            $requestMethod = $requestMethod[0];
        }

        // OK now we are sure it's a pre-flight request
        $this->logDebug('Request is identified as a pre-flight CORS request.');

        /** @var string $requestMethod */

        // #6.2.4
        $requestHeaders = $this->getRequestedHeadersInLowerCase($request);

        // #6.2.5
        if ($this->strategy->isRequestMethodSupported($requestMethod) === false) {
            $this->logInfo(
                'Request method is not supported. Check config settings for Allowed Methods.',
                ['method' => $requestMethod]
            );
            return $this->createResult(AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED);
        }

        // #6.2.6
        if ($this->strategy->isRequestAllHeadersSupported($requestHeaders) === false) {
            return $this->createResult(AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED);
        }

        // pre-flight response headers
        $headers = [];

        // #6.2.7
        $headers[CorsResponseHeaders::ALLOW_ORIGIN] = $requestOrigin->getOrigin();
        if ($this->strategy->isRequestCredentialsSupported($request) === true) {
            $headers[CorsResponseHeaders::ALLOW_CREDENTIALS] = CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE;
        }
        // #6.4
        $headers[CorsResponseHeaders::VARY] = CorsRequestHeaders::ORIGIN;

        // #6.2.8
        if ($this->strategy->isPreFlightCanBeCached($request) === true) {
            $headers[CorsResponseHeaders::MAX_AGE] = $this->strategy->getPreFlightCacheMaxAge($request);
        }

        // #6.2.9
        $isSimpleMethod = isset($this->simpleMethods[$requestMethod]);
        if ($isSimpleMethod === false || $this->strategy->isForceAddAllowedMethodsToPreFlightResponse() === true) {
            $headers[CorsResponseHeaders::ALLOW_METHODS] =
                $this->strategy->getRequestAllowedMethods($request, $requestMethod);
        }

        // #6.2.10
        // Has only 'simple' headers excluding Content-Type
        $isSimpleExclCT = empty(array_diff($requestHeaders, $this->simpleHeadersExclContentType));
        if ($isSimpleExclCT === false || $this->strategy->isForceAddAllowedHeadersToPreFlightResponse() === true) {
            $headers[CorsResponseHeaders::ALLOW_HEADERS] =
                $this->strategy->getRequestAllowedHeaders($request, $requestHeaders);
        }

        return $this->createResult(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST, $headers);
    }

    /**
     * @param RequestInterface $request
     *
     * @return string[]
     */
    protected function getRequestedHeadersInLowerCase(RequestInterface $request)
    {
        $requestHeaders = $request->getHeader(CorsRequestHeaders::HEADERS);
        if (empty($requestHeaders) === false) {
            // after explode header names might have spaces in the beginnings and ends...
            $requestHeaders = explode(CorsRequestHeaders::HEADERS_SEPARATOR, $requestHeaders[0]);
            // ... so trim the spaces and convert values to lower case
            $requestHeaders = array_map(function ($headerName) {
                return strtolower(trim($headerName));
            }, $requestHeaders);
        }

        return $requestHeaders;
    }

    /**
     * @param RequestInterface   $request
     * @param ParsedUrlInterface $serverOrigin
     *
     * @return bool
     */
    protected function isSameHost(RequestInterface $request, ParsedUrlInterface $serverOrigin)
    {
        $host = $this->getRequestHostHeader($request);

        // parse `Host` header
        //
        // According to https://tools.ietf.org/html/rfc7230#section-5.4 `Host` header could be
        //
        //                     "uri-host" OR "uri-host:port"
        //
        // `parse_url` function thinks the first value is `path` and the second is `host` with `port`
        // which is a bit annoying so...
        $portOrNull = parse_url($host, PHP_URL_PORT);
        $hostUrl    = $portOrNull === null ? $host : parse_url($host, PHP_URL_HOST);

        // Neither MDN, nor RFC tell anything definitive about Host header comparison.
        // Browsers such as Firefox and Chrome do not add the optional port for
        // HTTP (80) and HTTPS (443).
        // So we require port match only if it specified in settings.

        $isHostUrlMatch = strcasecmp($serverOrigin->getHost(), $hostUrl) === 0;
        $isSameHost =
            $isHostUrlMatch === true &&
            ($serverOrigin->getPort() === null || $serverOrigin->getPort() === $portOrNull);

        return $isSameHost;
    }

    /**
     * @param ParsedUrlInterface $requestOrigin
     * @param ParsedUrlInterface $serverOrigin
     *
     * @return bool
     *
     * @see http://tools.ietf.org/html/rfc6454#section-5
     */
    protected function isSameOrigin(ParsedUrlInterface $requestOrigin, ParsedUrlInterface $serverOrigin)
    {
        $isSameOrigin =
            $requestOrigin->isHostEqual($serverOrigin) === true &&
            $requestOrigin->isPortEqual($serverOrigin) === true &&
            $requestOrigin->isSchemeEqual($serverOrigin) === true;

        return $isSameOrigin;
    }

    /**
     * @param ParsedUrlInterface $requestOrigin
     * @param ParsedUrlInterface $serverOrigin
     *
     * @return bool
     */
    protected function isCrossOrigin(ParsedUrlInterface $requestOrigin, ParsedUrlInterface $serverOrigin)
    {
        return $this->isSameOrigin($requestOrigin, $serverOrigin) === false;
    }

    /**
     * @param RequestInterface $request
     *
     * @return ParsedUrlInterface|null
     */
    protected function getOrigin(RequestInterface $request)
    {
        $origin = null;
        if ($request->hasHeader(CorsRequestHeaders::ORIGIN) === true) {
            $header = $request->getHeader(CorsRequestHeaders::ORIGIN);
            if (empty($header) === false) {
                $value  = $header[0];
                try {
                    $origin = $this->factory->createParsedUrl($value);
                } catch (InvalidArgumentException $exception) {
                    $this->logWarning('Origin header URL cannot be parsed.', ['url' => $value]);
                }
            }
        }

        return $origin;
    }

    /**
     * @param int   $type
     * @param array $headers
     *
     * @return AnalysisResultInterface
     */
    protected function createResult($type, array $headers = [])
    {
        return $this->factory->createAnalysisResult($type, $headers);
    }

    /**
     * @return FactoryInterface
     */
    protected static function getFactory()
    {
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        return new \Neomerx\Cors\Factory\Factory();
    }

    /**
     * @param RequestInterface $request
     *
     * @return null|string
     */
    private function getRequestHostHeader(RequestInterface $request)
    {
        $hostHeaderValue = $request->getHeader(CorsRequestHeaders::HOST);
        $host            = empty($hostHeaderValue) === true ? null : $hostHeaderValue[0];

        return $host;
    }
}
