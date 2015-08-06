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

use \Neomerx\Cors\Strategies\Settings;

/**
 * NOTE: This class and its static properties are used by more than 1 test.
 *       If you change them in tests please don't forget to revert values back.
 *
 * @package Neomerx\Tests\JsonApi
 */
class AppTestSettings extends Settings
{
    /** Value for scheme */
    const SCHEME = 'http';

    /** Value for host */
    const HOST = 'example.com';

    /** Value for port */
    const PORT = '123';

    /**
     * @inheritdoc
     */
    public static $serverOrigin = [
        'scheme' => self::SCHEME,
        'host'   => self::HOST,
        'port'   => self::PORT,
    ];

    /**
     * @inheritdoc
     */
    public static $allowedOrigins = [
        'http://good.example.com:321' => true,
        'http://evil.example.com:123' => null,
    ];

    /**
     * @inheritdoc
     */
    public static $allowedMethods = [
        'GET'    => true,
        'PATCH'  => null,
        'POST'   => true,
        'PUT'    => null,
        'DELETE' => true,
    ];

    /**
     * @inheritdoc
     */
    public static $allowedHeaders = [
        'content-type'            => true,
        'some-disabled-header'    => null,
        'x-enabled-custom-header' => true,
    ];

    /**
     * @inheritdoc
     */
    public static $exposedHeaders = [
        'Content-Type',
        'X-Custom-Header',
    ];
}
