<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request;

class MiracleVisionModelRequest extends ImageGenerateRequest
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
        parent::__construct();
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
