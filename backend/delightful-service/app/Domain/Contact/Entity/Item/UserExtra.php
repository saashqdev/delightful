<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\Item;

use App\Domain\Chat\Entity\AbstractEntity;

class UserExtra extends AbstractEntity
{
    // thethird-partyplatformproperty,platformtypesee ThirdPlatformTypeEnum
    // eg. {
    //        "dingtalk": {
    //            "userid": "1",
    //            "unionid": "2"
    //        },
    //        "wecom": {
    //            "userid": "1",
    //            "open_userid": "2"
    //        },
    //        "feishu": {
    //            "open_id": "1",
    //            "union_id": "2"
    //        }
    //    }
    protected array $thirdPlatformAttrs;

    public function __construct(?array $data = [])
    {
        parent::__construct($data);
    }

    public function getThirdPlatformAttrs(): array
    {
        return $this->thirdPlatformAttrs;
    }

    public function setThirdPlatformAttrs(?array $thirdPlatformAttrs): void
    {
        if (empty($thirdPlatformAttrs)) {
            $thirdPlatformAttrs = [];
        }
        $this->thirdPlatformAttrs = $thirdPlatformAttrs;
    }
}
