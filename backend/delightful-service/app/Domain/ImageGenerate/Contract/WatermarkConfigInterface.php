<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;

/**
 * 水印configurationinterface
 * useatin开源project中定义水印configurationstandard，由企业projectimplementspecific逻辑.
 */
interface WatermarkConfigInterface
{
    /**
     * get水印configuration.
     *
     * @param null|string $orgCode organizationcode，useat判断whetherenable水印
     * @return null|WatermarkConfig return水印configuration，if为nullthennot添加水印
     */
    public function getWatermarkConfig(?string $orgCode = null): ?WatermarkConfig;
}
