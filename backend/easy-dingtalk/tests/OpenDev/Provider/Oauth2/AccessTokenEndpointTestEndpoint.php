<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Test\OpenDev\Provider\Oauth2;

use Dtyq\EasyDingTalk\Test\OpenDev\OpenDevEndpointBaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class AccessTokenEndpointTestEndpoint extends OpenDevEndpointBaseTestCase
{
    public function testGet()
    {
        $openDev = $this->createOpenDevFactory('first');
        $accessToken = $openDev->accessTokenEndpoint->get();
        $this->assertIsString($accessToken);
    }

    public function testGetCorp()
    {
        $openDev = $this->createOpenDevFactory('crop');
        $accessToken = $openDev->accessTokenEndpoint->getCorp($this->getCorpId(), $this->getSuitTicket());
        $this->assertIsString($accessToken);
    }
}
