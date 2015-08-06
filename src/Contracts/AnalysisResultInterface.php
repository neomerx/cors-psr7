<?php namespace Neomerx\Cors\Contracts;

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

/**
 * @package Neomerx\Cors
 */
interface AnalysisResultInterface
{
    /** Request is out of CORS specification */
    const TYPE_REQUEST_OUT_OF_CORS_SCOPE = 0;

    /** Request is pre-flight */
    const TYPE_PRE_FLIGHT_REQUEST = 1;

    /** Actual request */
    const TYPE_ACTUAL_REQUEST = 2;

    /** Actual request */
    const TYPE_BAD_REQUEST = 3;

    /**
     * Get request type (one of TYPE_XXX constants).
     *
     * @return int
     */
    public function getRequestType();

    /**
     * Get CORS headers to be added to response.
     *
     * @return array
     */
    public function getResponseHeaders();
}
