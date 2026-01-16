<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use Hyperf\HttpServer\Contract\RequestInterface;

class GetTopicMetricsRequestDTO 
{
 /** * @var string OrganizationCodeOptional */ 
    protected string $organizationCode = ''; /** * FromRequestDTO. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $dto = new self(); $dto->setOrganizationCode($request->input('organization_code', '')); return $dto; 
}
 /** * GetOrganizationCode */ 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 /** * Set OrganizationCode */ 
    public function setOrganizationCode(string $organizationCode): self 
{
 $this->organizationCode = $organizationCode; return $this; 
}
 
}
 
