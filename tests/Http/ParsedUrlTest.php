<?php namespace Neomerx\Tests\Cors\Http;

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

use \Neomerx\Tests\Cors\BaseTestCase;
use \Neomerx\Tests\Cors\Factory\FactoryTest;
use \Neomerx\Cors\Contracts\Http\ParsedUrlInterface;

/**
 * @package Neomerx\Tests\Cors
 */
class ParsedUrlTest extends BaseTestCase
{
    /**
     * Test string input URL.
     */
    public function testParseStringUrlWithDefaultPort()
    {
        $url = $this->createParsedUrl('http://www.host.com/ignore-this-part');

        $this->assertEquals('http', $url->getScheme());
        $this->assertEquals('www.host.com', $url->getHost());
        $this->assertEquals(null, $url->getPort());
        $this->assertEquals('http://www.host.com', $url->getOrigin());
    }

    /**
     * Test string input URL.
     */
    public function testParseStringUrlWithPort()
    {
        $url = $this->createParsedUrl('http://host:100/ignore-this-part');

        $this->assertEquals('http', $url->getScheme());
        $this->assertEquals('host', $url->getHost());
        $this->assertEquals(100, $url->getPort());
        $this->assertEquals('http://host:100', $url->getOrigin());
    }

    /**
     * Test string input URL.
     */
    public function testParseStringUrlWithPortNoScheme()
    {
        $url = $this->createParsedUrl('//host/ignore-this-part');

        $this->assertEquals('', $url->getScheme());
        $this->assertEquals('host', $url->getHost());
        $this->assertEquals(null, $url->getPort());
        $this->assertEquals('//host', $url->getOrigin());
    }

    /**
     * Test string input URL.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testParseInvalidString()
    {
        $this->createParsedUrl('http://:80');
    }

    /**
     * Test compare equal origins.
     */
    public function testCompareEqualStringOrigins()
    {
        $url1 = $this->createParsedUrl('https://host.com:100/query');
        $url2 = $this->createParsedUrl('https://host.com:100/query');

        $this->assertTrue($url1->isSchemeEqual($url2));
        $this->assertTrue($url1->isHostEqual($url2));
        $this->assertTrue($url1->isPortEqual($url2));
    }

    /**
     * Test compare equal origins.
     */
    public function testCompareNonEqualStringOrigins()
    {
        $url1 = $this->createParsedUrl('http://host1.com:100/query');
        $url2 = $this->createParsedUrl('https://host2.com:200/query');

        $this->assertFalse($url1->isSchemeEqual($url2));
        $this->assertFalse($url1->isHostEqual($url2));
        $this->assertFalse($url1->isPortEqual($url2));
    }

    /**
     * Test parse URL in array form.
     */
    public function testParseArrayUrl()
    {
        $url = $this->createParsedUrl([
            'scheme' => 'http',
            'host'   => 'host',
            'query'  => 'ignore-this-part',
        ]);

        $this->assertEquals('http', $url->getScheme());
        $this->assertEquals('host', $url->getHost());
        $this->assertEquals(null, $url->getPort());
        $this->assertEquals('http://host', $url->getOrigin());
    }
    /**
     * Test to string conversion.
     */
    public function testToString()
    {
        $url = $this->createParsedUrl('http://www.host.com/ignore-this-part');
        $this->assertEquals('http://www.host.com', (string)$url);
    }

    /**
     * @param string|array $url
     *
     * @return ParsedUrlInterface
     */
    private function createParsedUrl($url)
    {
        $this->assertNotNull($url = FactoryTest::createFactory()->createParsedUrl($url));
        return $url;
    }
}
