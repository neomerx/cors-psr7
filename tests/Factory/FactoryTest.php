<?php namespace Neomerx\Tests\Cors\Factory;

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

use \Neomerx\Cors\Factory\Factory;
use \Neomerx\Tests\Cors\BaseTestCase;
use \Neomerx\Cors\Contracts\Factory\FactoryInterface;

/**
 * @package Neomerx\Tests\Cors
 */
class FactoryTest extends BaseTestCase
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @return FactoryInterface
     */
    public static function createFactory()
    {
        return new Factory();
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->factory = self::createFactory();
    }

    /**
     * Test create parsed URL.
     */
    public function testCreateParsedUrl()
    {
        $this->assertNotNull($this->factory->createParsedUrl('//host'));
    }
}
