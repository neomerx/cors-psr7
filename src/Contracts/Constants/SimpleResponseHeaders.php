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
 *
 * @see http://www.w3.org/TR/cors/#terminology
 */
interface SimpleResponseHeaders
{
    /**
     * Header name
     */
    const CACHE_CONTROL = 'Cache-Control';

    /**
     * Header name
     */
    const CONTENT_LANGUAGE = 'Content-Language';

    /**
     * Header name
     */
    const ACCEPT_LANGUAGE = 'Accept-Language';

    /**
     * Header name
     */
    const CONTENT_TYPE = 'Content-Type';

    /**
     * Header name
     */
    const EXPIRES = 'Expires';

    /**
     * Header name
     */
    const LAST_MODIFIED = 'Last-Modified';

    /**
     * Header name
     */
    const PRAGMA = 'Pragma';
}
