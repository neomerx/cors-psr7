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
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Tests\Cors\Factory\FactoryTest;
use \Neomerx\Cors\Contracts\Factory\FactoryInterface;

/**
 * @package Neomerx\Tests\JsonApi
 */
class SettingsTest extends BaseTestCase
{
    /**
     * @var AppTestSettings
     */
    private $appSettings;

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

        $this->appSettings = new AppTestSettings();
        $this->factory     = FactoryTest::createFactory();
        $this->request     = Mockery::mock(RequestInterface::class);
    }

    /**
     * Test get/set methods for simple properties/methods.
     */
    public function testSimpleGetSetSettings()
    {
        $this->assertEquals([
            'scheme' => AppTestSettings::SCHEME,
            'host'   => AppTestSettings::HOST,
            'port'   => AppTestSettings::PORT,
        ], $this->appSettings->getServerOrigin());

        $originalValue = AppTestSettings::$preFlightCacheMaxAge;
        try {
            AppTestSettings::$preFlightCacheMaxAge = 0;
            $this->assertEquals(0, $this->appSettings->getPreFlightCacheMaxAge($this->request));
            $this->assertFalse($this->appSettings->isPreFlightCanBeCached($this->request));

            AppTestSettings::$preFlightCacheMaxAge = 1;
            $this->assertEquals(1, $this->appSettings->getPreFlightCacheMaxAge($this->request));
            $this->assertTrue($this->appSettings->isPreFlightCanBeCached($this->request));
        } finally {
            AppTestSettings::$preFlightCacheMaxAge = $originalValue;
        }

        $originalValue = AppTestSettings::$isForceAddMethods;
        try {
            AppTestSettings::$isForceAddMethods = false;
            $this->assertFalse($this->appSettings->isForceAddAllowedMethodsToPreFlightResponse());
            AppTestSettings::$isForceAddMethods = true;
            $this->assertTrue($this->appSettings->isForceAddAllowedMethodsToPreFlightResponse());
        } finally {
            AppTestSettings::$isForceAddMethods = $originalValue;
        }

        $originalValue = AppTestSettings::$isForceAddHeaders;
        try {
            AppTestSettings::$isForceAddHeaders = false;
            $this->assertFalse($this->appSettings->isForceAddAllowedHeadersToPreFlightResponse());
            AppTestSettings::$isForceAddHeaders = true;
            $this->assertTrue($this->appSettings->isForceAddAllowedHeadersToPreFlightResponse());
        } finally {
            AppTestSettings::$isForceAddHeaders = $originalValue;
        }

        $originalValue = AppTestSettings::$isUsingCredentials;
        try {
            AppTestSettings::$isUsingCredentials = true;
            $this->assertTrue($this->appSettings->isRequestCredentialsSupported($this->request));
            AppTestSettings::$isUsingCredentials = false;
            $this->assertFalse($this->appSettings->isRequestCredentialsSupported($this->request));
        } finally {
            AppTestSettings::$isUsingCredentials = $originalValue;
        }

        $this->assertNotEmpty($exposedHeaders = ['Content-Type', 'X-Custom-Header']);
        $this->assertEquals($exposedHeaders, $this->appSettings->getResponseExposedHeaders($this->request));
    }

    /**
     * Test allowed origins for requests.
     */
    public function testRequestOriginAllowed()
    {
        foreach (AppTestSettings::$allowedOrigins as $url => $enabled) {
            // let's take origins from the settings directly
            $requestOrigin = $this->factory->createParsedUrl($url);
            $this->assertEquals($enabled, $this->appSettings->isRequestOriginAllowed($requestOrigin));
        }

        // and one more not from the white list
        $requestOrigin = $this->factory->createParsedUrl('http://hax.com');
        $this->assertFalse($this->appSettings->isRequestOriginAllowed($requestOrigin));
    }

    /**
     * Test allowed request methods.
     */
    public function testRequestMethodSupported()
    {
        foreach (AppTestSettings::$allowedMethods as $method => $enabled) {
            $this->assertEquals($enabled, $this->appSettings->isRequestMethodSupported($method));
        }

        // and one more not from the white list
        $this->assertFalse($this->appSettings->isRequestMethodSupported('X-DELETE'));
    }

    /**
     * Test allowed request headers.
     */
    public function testRequestHeaderSupported()
    {
        $allowedHeaders = array_filter(AppTestSettings::$allowedHeaders, function ($enabled) {
            return $enabled === true;
        });
        $allowedHeaders = array_keys($allowedHeaders);

        $prohibitedHeaders = array_filter(AppTestSettings::$allowedHeaders, function ($enabled) {
            return $enabled !== true;
        });
        $prohibitedHeaders = array_keys($prohibitedHeaders);

        $this->assertTrue($this->appSettings->isRequestAllHeadersSupported($allowedHeaders));
        $this->assertFalse($this->appSettings->isRequestAllHeadersSupported($prohibitedHeaders));
        $this->assertFalse($this->appSettings->isRequestAllHeadersSupported(
            array_merge($allowedHeaders, $prohibitedHeaders)
        ));
    }

    /**
     * Test get all allowed methods.
     */
    public function testRequestAllowedMethods()
    {
        $this->assertEquals('GET, POST, DELETE', $this->appSettings->getRequestAllowedMethods($this->request, 'GET'));
    }

    /**
     * Test get all allowed headers.
     */
    public function testRequestAllowedHeaders()
    {
        $this->assertEquals(
            'content-type, x-enabled-custom-header',
            $this->appSettings->getRequestAllowedHeaders($this->request, [])
        );
    }
}
