<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Contract;

interface ResponseInterface 
{
 
    public function isSuccess(): bool; 
    public function getCode(): int; 
    public function getMessage(): string; 
    public function getData(): array; 
}
 
