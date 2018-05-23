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
use \Psr\Log\LoggerInterface;
use \Neomerx\Cors\Strategies\Settings;
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Cors\Contracts\AnalyzerInterface;
use \Neomerx\Cors\Contracts\AnalysisResultInterface;
use \Neomerx\Cors\Contracts\Constants\CorsRequestHeaders;
use \Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;

/**
 * NOTE: This test suite uses AppTestSettings and its static properties.
 *
 * @package Neomerx\Tests\Cors
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
     * @var Settings
     */
    private $settings;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->settings = new Settings();
        $this->settings->setServerOrigin([
            'scheme' => 'http',
            'host'   => 'example.com',
            'port'   => 123,
        ])->setRequestAllowedOrigins([
            'http://good.example.com:321'                => true,
            'http://evil.example.com:123'                => null,
            CorsResponseHeaders::VALUE_ALLOW_ORIGIN_ALL  => null,
            CorsResponseHeaders::VALUE_ALLOW_ORIGIN_NULL => null,
        ])->setRequestAllowedMethods([
            'GET'    => true,
            'PATCH'  => null,
            'POST'   => true,
            'PUT'    => null,
            'DELETE' => true,
        ])->setRequestAllowedHeaders([
            'content-type'            => true,
            'some-disabled-header'    => null,
            'x-enabled-custom-header' => true,
        ])->setResponseExposedHeaders([
            'Content-Type'      => true,
            'X-Custom-Header'   => true,
            'X-Disabled-Header' => null,
        ])
            ->setCheckHost(true)
            ->setRequestCredentialsSupported(true);
        $this->assertNotNull($this->analyzer = Analyzer::instance($this->settings));

        $this->request  = Mockery::mock(RequestInterface::class);
    }

    /**
     * Test bad request (wrong Host header).
     */
    public function testBadRequestNoHost()
    {
        // 1 time for check...
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST => ['evil.com'],
        ]);
        // ... second time for logs
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
     * Test not CORS request (Origin identical to server's one).
     */
    public function testNotCorsOriginIdenticalToServer()
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => [$this->getServerHost('http')],
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
     * Test if 'file://' as origin.
     */
    public function testNotCorsFileOrigin()
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => ['file://'],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
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
     * Test actual CORS request with default server port (e.g. 80 or 443 which is omitted in Host header).
     */
    public function testValidActualCorsRequestWithOmittedHostPort()
    {
        $allowedOrigin = 'http://good.example.com:321';

        // CORS settings
        $settings = new Settings();
        $settings->setServerOrigin([
            'host' => 'example.com'
        ])->setRequestAllowedOrigins([
            $allowedOrigin => true,
        ])->setRequestAllowedMethods([
            'GET' => true,
        ])
            ->setCheckHost(true);
        $this->assertNotNull($analyzer = Analyzer::instance($settings));

        //
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost('', null)],
            CorsRequestHeaders::ORIGIN => [$allowedOrigin],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $this->thisMethodWillBeGotOnce('GET');

        $result = $analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_ACTUAL_REQUEST, $result->getRequestType());
        $this->assertEquals([
            CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
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

        $this->settings->setPreFlightCacheMaxAge(60);
        $this->settings->setForceAddAllowedMethodsToPreFlightResponse(true);
        $this->settings->setForceAddAllowedHeadersToPreFlightResponse(true);

        $result = $this->analyzer->analyze($this->request);

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

        $this->settings->setPreFlightCacheMaxAge(60);
        $this->settings->setForceAddAllowedMethodsToPreFlightResponse(false);
        $this->settings->setForceAddAllowedHeadersToPreFlightResponse(false);

        $result = $this->analyzer->analyze($this->request);

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
     * Test set logger.
     */
    public function testSetLogger()
    {
        /** @var LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $this->analyzer->setLogger($logger);
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
     * @param string   $schema
     * @param null|int $port
     *
     * @return string
     */
    private function getServerHost($schema = '', $port = 123)
    {
        $value = (empty($schema) === true ? 'example.com' : "$schema://example.com") . ($port === null ? '' : ":$port");

        return $value;
    }

    /**
     * @return string
     */
    private function getFirstAllowedOriginFromSettings()
    {
        return 'http://good.example.com:321';
    }

    /**
     * @return string
     */
    private function getFirstNotAllowedMethod()
    {
        return 'PATCH';
    }

    /**
     * @return string
     */
    private function getFirstAllowedMethod()
    {
        return 'GET';
    }

    /**
     * @return string
     */
    private function getFirstAllowedNotSimpleMethod()
    {
        return 'DELETE';
    }

    /**
     * @return string
     */
    private function getFirstNotAllowedHeader()
    {
        return 'some-disabled-header';
    }

    /**
     * @return string
     */
    private function getAllowedHeadersList()
    {
        $allowedHeaders = [
            'content-type',
            'x-enabled-custom-header',
        ];

        $result = implode(', ', $allowedHeaders);

        return $result;
    }
}
