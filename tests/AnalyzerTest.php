<?php namespace Neomerx\Tests\Cors;

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

use \Mockery;
use \Mockery\MockInterface;
use \Neomerx\Cors\Analyzer;
use \InvalidArgumentException;
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Cors\Contracts\AnalyzerInterface;
use \Neomerx\Tests\Cors\Strategies\AppTestSettings;
use \Neomerx\Cors\Contracts\AnalysisResultInterface;
use \Neomerx\Cors\Contracts\Constants\CorsRequestHeaders;
use \Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;
use \Neomerx\Cors\Contracts\Constants\SimpleRequestMethods;

/**
 * NOTE: This test suite uses AppTestSettings and its static properties.
 *
 * @package Neomerx\Tests\JsonApi
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AnalyzerTest extends BaseTestCase
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AnalyzerInterface
     */
    private $analyzer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request  = Mockery::mock(RequestInterface::class);
        $this->assertNotNull($this->analyzer = Analyzer::instance(new AppTestSettings()));

        AppTestSettings::$isCheckHost = true;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        AppTestSettings::$isCheckHost = false;
    }

    /**
     * Test bad request (wrong Host header).
     */
    public function testBadRequestNoHost()
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST => ['evil.com'],
        ]);

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_NO_HOST_HEADER, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test not CORS request (no Origin header).
     */
    public function testNotCorsNoOrigin()
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => [],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test not CORS request (not allowed Origin header).
     */
    public function testNotCorsNotAllowedOrigin()
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => ['http://some-devil-host.com'],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test actual CORS request.
     */
    public function testValidActualCorsRequest()
    {
        $allowedOrigin = $this->getFirstAllowedOriginFromSettings();
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => [$allowedOrigin],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('GET');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_ACTUAL_REQUEST, $result->getRequestType());
        $this->assertEquals([
            CorsResponseHeaders::EXPOSE_HEADERS    => ['Content-Type', 'X-Custom-Header'],
            CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
            CorsResponseHeaders::ALLOW_CREDENTIALS => CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE,
            CorsResponseHeaders::VARY              => CorsRequestHeaders::ORIGIN,
        ], $result->getResponseHeaders());
    }

    /**
     * Test invalid CORS pre-flight request (no 'Access-Control-Request-Method' header).
     */
    public function testNotCorsRequestMethod()
    {
        $allowedOrigin = $this->getFirstAllowedOriginFromSettings();
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => [$allowedOrigin],
            CorsRequestHeaders::METHOD => [],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test CORS pre-flight request with not allowed method.
     */
    public function testPreFlightWithNotAllowedMethod()
    {
        $allowedOrigin    = $this->getFirstAllowedOriginFromSettings();
        $notAllowedMethod = $this->getFirstNotAllowedMethod();
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST    => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
            CorsRequestHeaders::METHOD  => [$notAllowedMethod],
            CorsRequestHeaders::HEADERS => [],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test CORS pre-flight request with not allowed header.
     */
    public function testPreFlightWithNotAllowedHeader()
    {
        $allowedOrigin    = $this->getFirstAllowedOriginFromSettings();
        $allowedMethod    = $this->getFirstAllowedMethod();
        $notAllowedHeader = $this->getFirstNotAllowedHeader();
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST    => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
            CorsRequestHeaders::METHOD  => [$allowedMethod],
            CorsRequestHeaders::HEADERS => [$notAllowedHeader],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test valid CORS pre-flight request.
     */
    public function testValidPreFlight()
    {
        $allowedOrigin      = $this->getFirstAllowedOriginFromSettings();
        $allowedMethod      = $this->getFirstAllowedMethod();
        $allowedHeadersList = $this->getAllowedHeadersList();
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST    => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
            CorsRequestHeaders::METHOD  => [$allowedMethod],
            CorsRequestHeaders::HEADERS => [$allowedHeadersList],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $maxAge            = AppTestSettings::$preFlightCacheMaxAge;
        $isForceAddMethods = AppTestSettings::$isForceAddMethods;
        $isForceAddHeaders = AppTestSettings::$isForceAddHeaders;
        try {
            AppTestSettings::$preFlightCacheMaxAge = 60;
            AppTestSettings::$isForceAddMethods = true;
            AppTestSettings::$isForceAddHeaders = true;

            $result = $this->analyzer->analyze($this->request);
        } finally {
            AppTestSettings::$preFlightCacheMaxAge = $maxAge;
            AppTestSettings::$isForceAddMethods    = $isForceAddMethods;
            AppTestSettings::$isForceAddHeaders    = $isForceAddHeaders;
        }

        $this->assertEquals(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST, $result->getRequestType());
        $this->assertEquals([
            CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
            CorsResponseHeaders::ALLOW_CREDENTIALS => CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE,
            CorsResponseHeaders::VARY              => CorsRequestHeaders::ORIGIN,
            CorsResponseHeaders::MAX_AGE           => 60,
            CorsResponseHeaders::ALLOW_METHODS     => 'GET, POST, DELETE',
            CorsResponseHeaders::ALLOW_HEADERS     => 'content-type, x-enabled-custom-header',
        ], $result->getResponseHeaders());
    }

    /**
     * Test valid CORS pre-flight request.
     */
    public function testValidPreFlightWithNoForceAddingHeaders()
    {
        $allowedOrigin      = $this->getFirstAllowedOriginFromSettings();
        $allowedMethod      = $this->getFirstAllowedNotSimpleMethod();
        $allowedHeadersList = $this->getAllowedHeadersList();
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST    => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
            CorsRequestHeaders::METHOD  => [$allowedMethod],
            CorsRequestHeaders::HEADERS => [$allowedHeadersList],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $maxAge            = AppTestSettings::$preFlightCacheMaxAge;
        $isForceAddMethods = AppTestSettings::$isForceAddMethods;
        $isForceAddHeaders = AppTestSettings::$isForceAddHeaders;
        try {
            AppTestSettings::$preFlightCacheMaxAge = 60;
            AppTestSettings::$isForceAddMethods = false;
            AppTestSettings::$isForceAddHeaders = false;

            $result = $this->analyzer->analyze($this->request);
        } finally {
            AppTestSettings::$preFlightCacheMaxAge = $maxAge;
            AppTestSettings::$isForceAddMethods    = $isForceAddMethods;
            AppTestSettings::$isForceAddHeaders    = $isForceAddHeaders;
        }

        $this->assertEquals(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST, $result->getRequestType());
        $this->assertEquals([
            CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
            CorsResponseHeaders::ALLOW_CREDENTIALS => CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE,
            CorsResponseHeaders::VARY              => CorsRequestHeaders::ORIGIN,
            CorsResponseHeaders::MAX_AGE           => 60,
            CorsResponseHeaders::ALLOW_METHODS     => 'GET, POST, DELETE',
            CorsResponseHeaders::ALLOW_HEADERS     => 'content-type, x-enabled-custom-header',
        ], $result->getResponseHeaders());
    }

    /**
     * @param array $headers
     *
     * @return void
     */
    private function theseHeadersWillBeGotOnce(array $headers)
    {
        /** @var MockInterface $request */
        $request = $this->request;

        foreach ($headers as $headerName => $headerValue) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $request->shouldReceive('getHeader')->once()->withArgs([$headerName])->andReturn($headerValue);
        }
    }

    /**
     * @param string[] $headers
     *
     * @return void
     */
    private function existenceOfTheseHeadersWillBeCheckedOnce(array $headers)
    {
        /** @var MockInterface $request */
        $request = $this->request;

        foreach ($headers as $headerName => $hasHeader) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $request->shouldReceive('hasHeader')->once()->withArgs([$headerName])->andReturn($hasHeader);
        }
    }

    /**
     * @param string $method
     *
     * @return void
     */
    private function thisMethodWillBeGotOnce($method)
    {
        /** @var MockInterface $request */
        $request = $this->request;
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $request->shouldReceive('getMethod')->once()->withNoArgs()->andReturn($method);
    }

    /**
     * @return string
     */
    private function getServerHost()
    {
        return AppTestSettings::HOST . ':' . AppTestSettings::PORT;
    }

    /**
     * @return string
     */
    private function getFirstAllowedOriginFromSettings()
    {
        foreach (AppTestSettings::$allowedOrigins as $origin => $allowed) {
            if ($allowed === true) {
                return $origin;
            }
        }

        throw new InvalidArgumentException('Allowed Origins settings');
    }

    /**
     * @return string
     */
    private function getFirstNotAllowedMethod()
    {
        foreach (AppTestSettings::$allowedMethods as $method => $allowed) {
            if ($allowed !== true) {
                return $method;
            }
        }

        throw new InvalidArgumentException('Allowed Methods settings');
    }

    /**
     * @return string
     */
    private function getFirstAllowedMethod()
    {
        foreach (AppTestSettings::$allowedMethods as $method => $allowed) {
            if ($allowed === true) {
                return $method;
            }
        }

        throw new InvalidArgumentException('Allowed Methods settings');
    }

    /**
     * @return string
     */
    private function getFirstAllowedNotSimpleMethod()
    {
        $simpleMethods = [
            SimpleRequestMethods::GET,
            SimpleRequestMethods::HEAD,
            SimpleRequestMethods::POST,
        ];
        foreach (AppTestSettings::$allowedMethods as $method => $allowed) {
            if ($allowed === true && in_array($method, $simpleMethods) === false) {
                return $method;
            }
        }

        throw new InvalidArgumentException('Allowed Methods settings');
    }

    /**
     * @return string
     */
    private function getFirstNotAllowedHeader()
    {
        foreach (AppTestSettings::$allowedHeaders as $header => $allowed) {
            if ($allowed !== true) {
                return $header;
            }
        }

        throw new InvalidArgumentException('Allowed Headers settings');
    }

    /**
     * @return string
     */
    private function getAllowedHeadersList()
    {
        $allowedHeaders = [];

        foreach (AppTestSettings::$allowedHeaders as $header => $allowed) {
            if ($allowed === true) {
                $allowedHeaders[] = $header;
            }
        }

        if (empty($allowedHeaders) === true) {
            throw new InvalidArgumentException('Allowed Headers settings');
        }

        $result = implode(', ', $allowedHeaders);

        return $result;
    }
}
