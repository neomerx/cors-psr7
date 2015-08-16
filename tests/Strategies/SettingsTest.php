<?php namespace Neomerx\Tests\Cors\Strategies;

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
use \Neomerx\Tests\Cors\BaseTestCase;
use \Neomerx\Cors\Strategies\Settings;
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Tests\Cors\Factory\FactoryTest;
use \Neomerx\Cors\Contracts\Factory\FactoryInterface;
use \Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;

/**
 * @package Neomerx\Tests\Cors
 */
class SettingsTest extends BaseTestCase
{
    /** Value for scheme */
    const SCHEME = 'http';

    /** Value for host */
    const HOST = 'example.com';

    /** Value for port */
    const PORT = '123';

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->settings = new Settings();
        $sameSettings = $this->settings->setServerOrigin([
            'scheme' => self::SCHEME,
            'host'   => self::HOST,
            'port'   => self::PORT,
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
        ])->setRequestCredentialsSupported(false)
            ->setPreFlightCacheMaxAge(0)
            ->setForceAddAllowedMethodsToPreFlightResponse(true)
            ->setForceAddAllowedHeadersToPreFlightResponse(true)
            ->setCheckHost(true);

        $this->assertSame($this->settings, $sameSettings);

        $this->factory = FactoryTest::createFactory();
        $this->request = Mockery::mock(RequestInterface::class);
    }

    /**
     * Test get/set methods for simple properties/methods.
     */
    public function testSimpleGetSetSettings()
    {
        $this->assertEquals([
            'scheme' => self::SCHEME,
            'host'   => self::HOST,
            'port'   => self::PORT,
        ], $this->settings->getServerOrigin());

        $this->settings->setPreFlightCacheMaxAge(0);
        $this->assertEquals(0, $this->settings->getPreFlightCacheMaxAge($this->request));
        $this->assertFalse($this->settings->isPreFlightCanBeCached($this->request));

        $this->settings->setPreFlightCacheMaxAge(1);
        $this->assertEquals(1, $this->settings->getPreFlightCacheMaxAge($this->request));
        $this->assertTrue($this->settings->isPreFlightCanBeCached($this->request));

        $this->settings->setForceAddAllowedMethodsToPreFlightResponse(false);
        $this->assertFalse($this->settings->isForceAddAllowedMethodsToPreFlightResponse());
        $this->settings->setForceAddAllowedMethodsToPreFlightResponse(true);
        $this->assertTrue($this->settings->isForceAddAllowedMethodsToPreFlightResponse());

        $this->settings->setForceAddAllowedHeadersToPreFlightResponse(false);
        $this->assertFalse($this->settings->isForceAddAllowedHeadersToPreFlightResponse());
        $this->settings->setForceAddAllowedHeadersToPreFlightResponse(true);
        $this->assertTrue($this->settings->isForceAddAllowedHeadersToPreFlightResponse());

        $this->settings->setRequestCredentialsSupported(true);
        $this->assertTrue($this->settings->isRequestCredentialsSupported($this->request));
        $this->settings->setRequestCredentialsSupported(false);
        $this->assertFalse($this->settings->isRequestCredentialsSupported($this->request));

        $this->settings->setCheckHost(true);
        $this->assertTrue($this->settings->isCheckHost());
        $this->settings->setCheckHost(false);
        $this->assertFalse($this->settings->isCheckHost());

        $this->assertNotEmpty($exposedHeaders = ['Content-Type', 'X-Custom-Header']);
        $this->assertEquals($exposedHeaders, $this->settings->getResponseExposedHeaders($this->request));
    }

    /**
     * Test allowed origins for requests.
     */
    public function testRequestOriginAllowed()
    {
        $forbiddenOrigin = 'http://hax.com';
        $requestOrigin   = $this->factory->createParsedUrl($forbiddenOrigin);
        $this->assertFalse($this->settings->isRequestOriginAllowed($requestOrigin));

        $forbiddenOrigin = 'http://evil.example.com:123';
        $requestOrigin   = $this->factory->createParsedUrl($forbiddenOrigin);
        $this->assertFalse($this->settings->isRequestOriginAllowed($requestOrigin));

        $allowedOrigin = 'http://good.example.com:321';
        $requestOrigin = $this->factory->createParsedUrl($allowedOrigin);
        $this->assertTrue($this->settings->isRequestOriginAllowed($requestOrigin));
    }

    /**
     * Test allowed request methods.
     */
    public function testRequestMethodSupported()
    {
        $this->assertTrue($this->settings->isRequestMethodSupported('POST'));
        $this->assertFalse($this->settings->isRequestMethodSupported('PATCH'));
        $this->assertFalse($this->settings->isRequestMethodSupported('X-DELETE'));
    }

    /**
     * Test allowed request headers.
     */
    public function testRequestHeaderSupported()
    {
        $allowedHeaders = [
            'content-type',
            'x-enabled-custom-header',
        ];
        $prohibitedHeaders = [
            'some-disabled-header',
        ];

        $this->assertTrue($this->settings->isRequestAllHeadersSupported($allowedHeaders));
        $this->assertFalse($this->settings->isRequestAllHeadersSupported($prohibitedHeaders));
        $this->assertFalse($this->settings->isRequestAllHeadersSupported(
            array_merge($allowedHeaders, $prohibitedHeaders)
        ));
    }

    /**
     * Test get all allowed methods.
     */
    public function testRequestAllowedMethods()
    {
        $this->assertEquals('GET, POST, DELETE', $this->settings->getRequestAllowedMethods($this->request, 'GET'));
    }

    /**
     * Test get all allowed headers.
     */
    public function testRequestAllowedHeaders()
    {
        $this->assertEquals(
            'content-type, x-enabled-custom-header',
            $this->settings->getRequestAllowedHeaders($this->request, [])
        );
    }
}
