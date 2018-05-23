<?php namespace Neomerx\Cors\Http;

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

use \InvalidArgumentException;
use \Neomerx\Cors\Contracts\Http\ParsedUrlInterface;

/**
 * @package Neomerx\Cors
 */
class ParsedUrl implements ParsedUrlInterface
{
    /** Key for result from parse_url() function */
    const URL_KEY_SCHEME = 'scheme';

    /** Key for result from parse_url() function */
    const URL_KEY_HOST = 'host';

    /** Key for result from parse_url() function */
    const URL_KEY_PORT = 'port';

    /**
     * @var string|null
     */
    private $scheme;

    /**
     * @var string|null
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var null|string
     */
    private $urlAsString;

    /**
     * @param string|array $url
     */
    public function __construct($url)
    {
        if (is_array($url) === true) {
            $parsedUrl = $url;
        } else {
            $parsedUrl = parse_url($url);
        }

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('url');
        }

        $this->scheme = $this->getArrayValue($parsedUrl, self::URL_KEY_SCHEME);
        $this->host   = $this->getArrayValue($parsedUrl, self::URL_KEY_HOST);
        $this->port   = $this->getArrayValue($parsedUrl, self::URL_KEY_PORT);
    }

    /**
     * @inheritdoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritdoc
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @inheritdoc
     */
    public function getOrigin()
    {
        if ($this->urlAsString === null) {
            $url = $this->scheme === null ? '' : $this->scheme . ':';
            $url .= $this->host === null  ? '' : '//' . $this->host;
            $url .= ($this->port === null || $this->port === self::DEFAULT_PORT) ? '' : ':' . $this->port;

            $this->urlAsString = $url;
        }

        return $this->urlAsString;
    }

    /**
     * @inheritdoc
     */
    public function isSchemeEqual(ParsedUrlInterface $rhs)
    {
        return strcasecmp($this->getScheme(), $rhs->getScheme()) === 0;
    }

    /**
     * @inheritdoc
     */
    public function isHostEqual(ParsedUrlInterface $rhs)
    {
        return strcasecmp($this->getHost(), $rhs->getHost()) === 0;
    }

    /**
     * @inheritdoc
     */
    public function isPortEqual(ParsedUrlInterface $rhs)
    {
        return $this->getPort() === $rhs->getPort();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->getOrigin();
    }

    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getArrayValue(array $array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
