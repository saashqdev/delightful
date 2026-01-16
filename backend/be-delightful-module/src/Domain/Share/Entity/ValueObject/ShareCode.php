<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Entity\ValueObject;

use InvalidArgumentException;
/** * Share codeValueObject * table ShareIdentifier. */

class ShareCode 
{
 /** * Share codeMinimumLength. */ 
    private 
    const int MIN_LENGTH = 6; /** * Share codeMaximumLength. */ 
    private 
    const int MAX_LENGTH = 16; /** * Share codeValue */ 
    private string $value; /** * Function. * * @param string $value Share codeValue * @throws InvalidArgumentException WhenShare codeLegalThrowException */ 
    public function __construct(string $value) 
{
 $this->validate($value); $this->value = $value; 
}
 /** * Convert toString. */ 
    public function __toString(): string 
{
 return $this->value; 
}
 /** * create new Share codeInstance. * * @param string $value Share codeValue */ 
    public 
    static function create(string $value): self 
{
 return new self($value); 
}
 /** * GetShare codeValue */ 
    public function getValue(): string 
{
 return $this->value; 
}
 /** * Determinewhether EqualShare code * * @param ShareCode $other Share code */ 
    public function equals(ShareCode $other): bool 
{
 return $this->value === $other->value; 
}
 /** * Validate Share code * * @param string $value Share codeValue * @throws InvalidArgumentException WhenShare codeLegalThrowException */ 
    private function validate(string $value): void 
{
 // check Length $length = mb_strlen($value); if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) 
{
 throw new InvalidArgumentException( sprintf('ShareCode lengthAt%d%dCharacterBetween', self::MIN_LENGTH, self::MAX_LENGTH) ); 
}
 // check FormatAllowNumberPartialSpecialCharacter if (! preg_match('/^[a-zA-Z0-9_-]+$/', $value)) 
{
 throw new InvalidArgumentException('Share codeincluding NumberUnderlineCharacter'); 
}
 
}
 
}
 
