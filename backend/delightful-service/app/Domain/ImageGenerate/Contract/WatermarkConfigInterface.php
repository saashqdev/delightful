<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;

/**
 * 水印configuration接口
 * 用于在开源项目中定义水印configuration规范，由企业项目实现具体逻辑.
 */
interface WatermarkConfigInterface
{
    /**
     * get水印configuration.
     *
     * @param null|string $orgCode organization代码，用于判断是否启用水印
     * @return null|WatermarkConfig return水印configuration，如果为null则不添加水印
     */
    public function getWatermarkConfig(?string $orgCode = null): ?WatermarkConfig;
}
