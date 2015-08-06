<?php namespace Neomerx\Cors\Contracts\Factory;

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

use \Neomerx\Cors\Contracts\AnalyzerInterface;
use \Neomerx\Cors\Contracts\AnalysisResultInterface;
use \Neomerx\Cors\Contracts\Http\ParsedUrlInterface;
use \Neomerx\Cors\Contracts\AnalysisStrategyInterface;

/**
 * @package Neomerx\Cors
 */
interface FactoryInterface
{
    /**
     * Create CORS Analyzer.
     *
     * @param AnalysisStrategyInterface $strategy
     *
     * @return AnalyzerInterface
     */
    public function createAnalyzer(AnalysisStrategyInterface $strategy);

    /**
     * Create request analysis result.
     *
     * @param int   $requestType
     * @param array $responseHeaders
     *
     * @return AnalysisResultInterface
     */
    public function createAnalysisResult($requestType, array $responseHeaders = []);

    /**
     * Create URL either from string URL or result from parse_url() function.
     *
     * @param string|array $url
     *
     * @return ParsedUrlInterface
     */
    public function createParsedUrl($url);
}
