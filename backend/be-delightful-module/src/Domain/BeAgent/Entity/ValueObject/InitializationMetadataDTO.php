<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * InitializeData DTO. * for Initialize Agent DataConfigurationExtension. */

class InitializationMetadataDTO 
{
 /** * Function. * * @param ?bool $skipInitMessages whether SkipInitializeMessagefor ASR * @param ?string $authorization Authorizeinfo */ 
    public function __construct( private ?bool $skipInitMessages = null, private ?string $authorization = null ) 
{
 
}
 /** * CreateDefaultInstance. */ 
    public 
    static function createDefault(): self 
{
 return new self(); 
}
 /** * Getwhether SkipInitializeMessage. * * @return ?bool whether SkipInitializeMessage */ 
    public function getSkipInitMessages(): ?bool 
{
 return $this->skipInitMessages; 
}
 /** * Set whether SkipInitializeMessage. * * @param ?bool $skipInitMessages whether SkipInitializeMessage * @return self NewInstance */ 
    public function withSkipInitMessages(?bool $skipInitMessages): self 
{
 $clone = clone $this; $clone->skipInitMessages = $skipInitMessages; return $clone; 
}
 /** * GetAuthorizeinfo . * * @return ?string Authorizeinfo */ 
    public function getAuthorization(): ?string 
{
 return $this->authorization; 
}
 /** * Set Authorizeinfo . * * @param ?string $authorization Authorizeinfo * @return self NewInstance */ 
    public function withAuthorization(?string $authorization): self 
{
 $clone = clone $this; $clone->authorization = $authorization; return $clone; 
}
 
}
 
