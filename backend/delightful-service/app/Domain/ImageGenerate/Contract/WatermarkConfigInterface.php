<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;

/**
 * watermarkconfigurationinterface
 * useatinopen源projectmiddledefinitionwatermarkconfigurationstandard，by企业projectimplementspecific逻辑.
 */
interface WatermarkConfigInterface
{
    /**
     * getwatermarkconfiguration.
     *
     * @param null|string $orgCode organizationcode，useat判断whetherenablewatermark
     * @return null|WatermarkConfig returnwatermarkconfiguration，iffornullthennotaddwatermark
     */
    public function getWatermarkConfig(?string $orgCode = null): ?WatermarkConfig;
}
