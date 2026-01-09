<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response;

use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use Delightful\CloudFile\Kernel\Utils\EasyFileTools;

class ImageGenerateResponse
{
    private ImageGenerateType $imageGenerateType;

    // canis base64 alsomaybeis urls
    private array $data;

    public function __construct(ImageGenerateType $imageGenerateType, array $data)
    {
        $this->imageGenerateType = $imageGenerateType;
        $this->data = $data;
        if ($imageGenerateType->isBase64()) {
            $base64Data = [];
            foreach ($data as $base64) {
                if (! EasyFileTools::isBase64Image($base64)) {
                    // check base64 formatwhetherconformstandard, tryaddfront缀
                    $base64 = 'data:image/jpeg;base64,' . $base64;
                    if (EasyFileTools::isBase64Image($base64)) {
                        $base64Data[] = $base64;
                    }
                }
            }
            $this->data = $base64Data;
        }
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getImageGenerateType(): ImageGenerateType
    {
        return $this->imageGenerateType;
    }
}
