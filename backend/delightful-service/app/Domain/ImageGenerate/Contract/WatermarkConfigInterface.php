<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;

/**
 * watermarkconfigurationinterface
 * useatinopensourceprojectmiddledefinitionwatermarkconfigurationstandard,byenterpriseprojectimplementspecificlogic.
 */
interface WatermarkConfigInterface
{
    /**
     * getwatermarkconfiguration.
     *
     * @param null|string $orgCode organizationcode,useatjudgewhetherenablewatermark
     * @return null|WatermarkConfig returnwatermarkconfiguration,iffornullthennotaddwatermark
     */
    public function getWatermarkConfig(?string $orgCode = null): ?WatermarkConfig;
}
