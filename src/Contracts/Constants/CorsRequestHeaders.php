<?php namespace Neomerx\Cors\Contracts\Constants;

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
interface CorsRequestHeaders
{
    /**
     * CORS Header
     *
     * @see http://www.w3.org/TR/cors/#origin-request-header
     */
    const ORIGIN = 'Origin';

    /**
     * CORS Header
     *
     * @see http://www.w3.org/TR/cors/#access-control-request-method-request-header
     */
    const METHOD = 'Access-Control-Request-Method';

    /**
     * CORS Header
     *
     * @see http://www.w3.org/TR/cors/#access-control-request-headers-request-header
     */
    const HEADERS = 'Access-Control-Request-Headers';

    /**
     * Header name separator
     *
     * @see http://www.w3.org/TR/cors/#cross-origin-request-with-preflight-0
     */
    const HEADERS_SEPARATOR = ',';

    /**
     * CORS Header
     *
     * @see http://www.w3.org/TR/cors/#resource-security
     */
    const HOST = 'Host';
}
