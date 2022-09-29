<?php

declare(strict_types=1);

namespace Neomerx\Tests\Cors;

/*
 * Copyright 2015-2020 info@neomerx.com
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

use Mockery;
use Mockery\MockInterface;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Contracts\Constants\CorsRequestHeaders;
use Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * NOTE: This test suite uses AppTestSettings and its static properties.
 */
class AnalyzerTest extends BaseTestCase
{
    private RequestInterface $request;

    private AnalyzerInterface $analyzer;

    private Settings $settings;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $settings = new Settings();

        $settings->enableAllOriginsAllowed()->enableAllMethodsAllowed()->enableAllHeadersAllowed();

        $settings->init(
            'http',
            'example.com',
            123,
        )->setAllowedOrigins([
            'http://good.example.com:321',
        ])->setAllowedMethods([
            'GET',
            'POST',
            'DELETE',
        ])->setAllowedHeaders([
            'Content-Type',
            'X-Enabled-Custom-Header',
        ])->setExposedHeaders([
            'Content-Type',
            'X-Custom-Header',
        ])
            ->enableCheckHost()
            ->setCredentialsSupported();

        $this->settings = (new Settings())->setData($settings->getData());

        $this->assertNotNull($this->analyzer = Analyzer::instance($this->settings));

        $this->request = Mockery::mock(RequestInterface::class);
    }

    /**
     * Test settings hide default ports for HTTP(S).
     */
    public function testSettingsHideDefaultPorts(): void
    {
        $settings = (new Settings())->init('http', 'example.com', 80);
        $this->assertNull($settings->getServerOriginPort());

        $settings = (new Settings())->init('https', 'example.com', 443);
        $this->assertNull($settings->getServerOriginPort());

        // check 80 works only for HTTP and 443 only for HTTPS
        $settings = (new Settings())->init('http', 'example.com', 443);
        $this->assertNotNull($settings->getServerOriginPort());

        $settings = (new Settings())->init('https', 'example.com', 80);
        $this->assertNotNull($settings->getServerOriginPort());
    }

    /**
     * Test bad request (wrong Host header).
     */
    public function testBadRequestNoHost(): void
    {
        // 1 time for check...
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST => ['evil.com'],
            ],
        );

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_NO_HOST_HEADER, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test not CORS request (no Origin header).
     */
    public function testNotCorsNoOrigin(): void
    {
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN => [],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test not CORS request (Origin identical to server's one).
     */
    public function testNotCorsOriginIdenticalToServer(): void
    {
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN => [$this->getServerHost('http')],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test request will be considered as CORS if only port differs.
     */
    public function testIsCrossOriginIfDifferentPort(): void
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => [$this->getServerHost('', 321)],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test request will be considered as CORS if only schema differs.
     */
    public function testIsCrossOriginIfDifferentSchema(): void
    {
        $this->theseHeadersWillBeGotOnce([
            CorsRequestHeaders::HOST   => [$this->getServerHost()],
            CorsRequestHeaders::ORIGIN => [$this->getServerHost('https')],
        ]);

        $this->existenceOfTheseHeadersWillBeCheckedOnce([
            CorsRequestHeaders::ORIGIN => true,
        ]);

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test not CORS request (not allowed Origin header).
     */
    public function testNotCorsNotAllowedOrigin(): void
    {
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN => ['http://some-devil-host.com'],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test if 'file://' as origin.
     */
    public function testNotCorsFileOrigin(): void
    {
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN => ['file://'],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test actual CORS request.
     */
    public function testValidActualCorsRequest(): void
    {
        $allowedOrigin = $this->getFirstAllowedOriginFromSettings();
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN => [$allowedOrigin],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('GET');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_ACTUAL_REQUEST, $result->getRequestType());
        $this->assertEquals(
            [
                CorsResponseHeaders::EXPOSE_HEADERS    => 'X-Custom-Header',
                CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
                CorsResponseHeaders::ALLOW_CREDENTIALS => CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE,
                CorsResponseHeaders::VARY              => CorsRequestHeaders::ORIGIN,
            ],
            $result->getResponseHeaders(),
        );
    }

    /**
     * Test actual CORS request with default server port (e.g. 80 or 443 which is omitted in Host header).
     */
    public function testValidActualCorsRequestWithOmittedHostPort(): void
    {
        $allowedOrigin = 'http://good.example.com:321';

        // CORS settings
        $settings = new Settings();
        $settings->init(
            'http',
            'example.com',
            80,
        )->setAllowedOrigins([
            $allowedOrigin,
        ])->setAllowedMethods([
            'GET',
        ])->enableCheckHost();
        $this->assertNotNull($analyzer = Analyzer::instance($settings));

        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost('', null)],
                CorsRequestHeaders::ORIGIN => [$allowedOrigin],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('GET');

        $result = $analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_ACTUAL_REQUEST, $result->getRequestType());
        $this->assertEquals(
            [
                CorsResponseHeaders::ALLOW_ORIGIN => $allowedOrigin,
                CorsResponseHeaders::VARY         => CorsRequestHeaders::ORIGIN,
            ],
            $result->getResponseHeaders(),
        );
    }

    /**
     * Test invalid CORS pre-flight request (no 'Access-Control-Request-Method' header).
     */
    public function testNotCorsRequestMethod(): void
    {
        $allowedOrigin = $this->getFirstAllowedOriginFromSettings();
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST   => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN => [$allowedOrigin],
                CorsRequestHeaders::METHOD => [],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test CORS pre-flight request with not allowed method.
     */
    public function testPreFlightWithNotAllowedMethod(): void
    {
        $allowedOrigin    = $this->getFirstAllowedOriginFromSettings();
        $notAllowedMethod = $this->getFirstNotAllowedMethod();
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST    => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
                CorsRequestHeaders::METHOD  => [$notAllowedMethod],
                CorsRequestHeaders::HEADERS => [],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test CORS pre-flight request with not allowed header.
     */
    public function testPreFlightWithNotAllowedHeader(): void
    {
        $allowedOrigin    = $this->getFirstAllowedOriginFromSettings();
        $allowedMethod    = $this->getFirstAllowedMethod();
        $notAllowedHeader = $this->getFirstNotAllowedHeader();
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST    => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
                CorsRequestHeaders::METHOD  => [$allowedMethod],
                CorsRequestHeaders::HEADERS => [$notAllowedHeader],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED, $result->getRequestType());
        $this->assertEquals([], $result->getResponseHeaders());
    }

    /**
     * Test valid CORS pre-flight request.
     */
    public function testValidPreFlight(): void
    {
        $allowedOrigin      = $this->getFirstAllowedOriginFromSettings();
        $allowedMethod      = $this->getFirstAllowedMethod();
        $allowedHeadersList = '';
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST    => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
                CorsRequestHeaders::METHOD  => [$allowedMethod],
                CorsRequestHeaders::HEADERS => [$allowedHeadersList],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $this->settings->setPreFlightCacheMaxAge(60);
        $this->settings->enableAddAllowedMethodsToPreFlightResponse();
        $this->settings->enableAddAllowedHeadersToPreFlightResponse();

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST, $result->getRequestType());
        $this->assertEquals(
            [
                CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
                CorsResponseHeaders::ALLOW_CREDENTIALS => CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE,
                CorsResponseHeaders::VARY              => CorsRequestHeaders::ORIGIN,
                CorsResponseHeaders::MAX_AGE           => 60,
                CorsResponseHeaders::ALLOW_METHODS     => 'GET, POST, DELETE',
                CorsResponseHeaders::ALLOW_HEADERS     => 'Content-Type, X-Enabled-Custom-Header',
            ],
            $result->getResponseHeaders(),
        );
    }

    /**
     * Test valid CORS pre-flight request.
     */
    public function testValidPreFlightWithNoForceAddingHeaders(): void
    {
        $allowedOrigin      = $this->getFirstAllowedOriginFromSettings();
        $allowedMethod      = $this->getFirstAllowedNotSimpleMethod();
        $allowedHeadersList = $this->getAllowedHeadersList();
        $this->theseHeadersWillBeGotOnce(
            [
                CorsRequestHeaders::HOST    => [$this->getServerHost()],
                CorsRequestHeaders::ORIGIN  => [$allowedOrigin],
                CorsRequestHeaders::METHOD  => [$allowedMethod],
                CorsRequestHeaders::HEADERS => [$allowedHeadersList],
            ],
        );

        $this->existenceOfTheseHeadersWillBeCheckedOnce(
            [
                CorsRequestHeaders::ORIGIN => true,
            ],
        );

        $this->thisMethodWillBeGotOnce('OPTIONS');

        $this->settings->setPreFlightCacheMaxAge(60);
        $this->settings->disableAddAllowedMethodsToPreFlightResponse();
        $this->settings->disableAddAllowedHeadersToPreFlightResponse();

        $result = $this->analyzer->analyze($this->request);

        $this->assertEquals(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST, $result->getRequestType());
        $this->assertEquals(
            [
                CorsResponseHeaders::ALLOW_ORIGIN      => $allowedOrigin,
                CorsResponseHeaders::ALLOW_CREDENTIALS => CorsResponseHeaders::VALUE_ALLOW_CREDENTIALS_TRUE,
                CorsResponseHeaders::VARY              => CorsRequestHeaders::ORIGIN,
                CorsResponseHeaders::MAX_AGE           => 60,
                CorsResponseHeaders::ALLOW_METHODS     => 'GET, POST, DELETE',
                CorsResponseHeaders::ALLOW_HEADERS     => 'Content-Type, X-Enabled-Custom-Header',
            ],
            $result->getResponseHeaders(),
        );
    }

    /**
     * Test set logger.
     */
    public function testSetLogger(): void
    {
        /** @var LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $this->analyzer->setLogger($logger);
    }

    private function theseHeadersWillBeGotOnce(array $headers): void
    {
        /** @var MockInterface $request */
        $request = $this->request;

        foreach ($headers as $headerName => $headerValue) {
            // @noinspection PhpMethodParametersCountMismatchInspection
            $request->shouldReceive('getHeader')->once()->withArgs([$headerName])->andReturn($headerValue);
        }
    }

    /**
     * @param string[] $headers
     */
    private function existenceOfTheseHeadersWillBeCheckedOnce(array $headers): void
    {
        /** @var MockInterface $request */
        $request = $this->request;

        foreach ($headers as $headerName => $hasHeader) {
            // @noinspection PhpMethodParametersCountMismatchInspection
            $request->shouldReceive('hasHeader')->once()->withArgs([$headerName])->andReturn($hasHeader);
        }
    }

    private function thisMethodWillBeGotOnce(string $method): void
    {
        /** @var MockInterface $request */
        $request = $this->request;
        // @noinspection PhpMethodParametersCountMismatchInspection
        $request->shouldReceive('getMethod')->once()->withNoArgs()->andReturn($method);
    }

    private function getServerHost(string $schema = '', ?int $port = 123): string
    {
        return (true === empty($schema) ?
                'example.com' : "{$schema}://example.com") . (null === $port ? '' : ":{$port}");
    }

    private function getFirstAllowedOriginFromSettings(): string
    {
        return 'http://good.example.com:321';
    }

    private function getFirstNotAllowedMethod(): string
    {
        return 'PATCH';
    }

    private function getFirstAllowedMethod(): string
    {
        return 'GET';
    }

    private function getFirstAllowedNotSimpleMethod(): string
    {
        return 'DELETE';
    }

    private function getFirstNotAllowedHeader(): string
    {
        return 'some-disabled-header';
    }

    private function getAllowedHeadersList(): string
    {
        $allowedHeaders = [
            'content-type',
            'x-enabled-custom-header',
        ];

        return implode(', ', $allowedHeaders);
    }
}
