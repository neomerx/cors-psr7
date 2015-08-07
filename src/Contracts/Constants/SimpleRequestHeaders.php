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
 * Note: Header names must be lower-cased (it allows optimize their comparison).
 *
 * @package Neomerx\Cors
 *
 * @see http://www.w3.org/TR/cors/#terminology
 */
interface SimpleRequestHeaders
{
    /**
     * Header name
     */
    const ACCEPT = 'accept';

    /**
     * Header name
     */
    const ACCEPT_LANGUAGE = 'accept-language';

    /**
     * Header name
     */
    const CONTENT_LANGUAGE = 'content-language';

    /**
     * Header name
     */
    const CONTENT_TYPE = 'content-type';

    /**
     * With this media type header 'Content-Type' considered as simple
     */
    const VALUE_CONTENT_TYPE_FORM_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * With this media type header 'Content-Type' considered as simple
     */
    const VALUE_CONTENT_TYPE_FORM_DATA = 'multipart/form-data';

    /**
     * With this media type header 'Content-Type' considered as simple
     */
    const VALUE_CONTENT_TYPE_TEXT = 'text/plain';
}
