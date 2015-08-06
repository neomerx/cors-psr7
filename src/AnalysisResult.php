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

use \Neomerx\Cors\Contracts\AnalysisResultInterface;

/**
 * @package Neomerx\Cors
 */
class AnalysisResult implements AnalysisResultInterface
{
    /**
     * @var int
     */
    private $requestType;

    /**
     * @var array
     */
    private $headers;

    /**
     * @inheritdoc
     */
    public function __construct($requestType, array $responseHeaders)
    {
        $this->requestType = $requestType;
        $this->headers     = $responseHeaders;
    }

    /**
     * @inheritdoc
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * @inheritdoc
     */
    public function getResponseHeaders()
    {
        return $this->headers;
    }
}
