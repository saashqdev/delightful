<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

/** * ShareURLBuildertool Class. */

class ShareUrlBuilder 
{
 
    protected string $frontendBaseUrl = 'https://app.domain.com'; 
    protected string $sharePath = '/share'; /** * Build complete share URL. * * @param string $shareCode Share code * @param null|string $resourceType ResourceTypeName * @return string complete share URL */ 
    public function buildShareUrl(string $shareCode, ?string $resourceType = null): string 
{
 $url = rtrim($this->frontendBaseUrl, '/') . $this->sharePath . '/' . $shareCode; if ($resourceType !== null) 
{
 $url .= '?type=' . $resourceType; 
}
 return $url; 
}
 /** * Get share URL prefix part. * * @return string Share URL prefix */ 
    public function getShareUrlPrefix(): string 
{
 return rtrim($this->frontendBaseUrl, '/') . $this->sharePath . '/'; 
}
 /** * Extract share code from complete URL * * @param string $url complete share URL * @return null|string Share code, return null if cannot be extracted */ 
    public function extractShareCodeFromUrl(string $url): ?string 
{
 $prefix = $this->getShareUrlPrefix(); if (strpos($url, $prefix) !== 0) 
{
 return null; 
}
 $remainder = substr($url, strlen($prefix)); $parts = explode('?', $remainder, 2); return $parts[0] ?: null; 
}
 
}
 
