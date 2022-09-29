<?php

declare(strict_types=1);

namespace Neomerx\Tests\Cors;

/*
 * Copyright 2015-2020 info@neomerx.com
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

use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Factory\Factory;

class AnalysisResultTest extends BaseTestCase
{
    /**
     * Test create.
     */
    public function testCreate(): void
    {
        $headers     = ['header-name' => ['header-value1']];
        $requestType = AnalysisResultInterface::ERR_NO_HOST_HEADER;

        $this->assertNotNull($result = (new Factory())->createAnalysisResult($requestType, $headers));

        $this->assertEquals($requestType, $result->getRequestType());
        $this->assertEquals($headers, $result->getResponseHeaders());
    }
}
