<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

/** * Password hashing tool class. */

class PasswordHasher 
{
 
    protected string $hashAlgo = PASSWORD_BCRYPT; /** * Password hashing options. */ 
    protected array $hashOptions = [ 'cost' => 10, ]; /** * Hash password. * * @param string $password Original password * @return string Hashed password */ 
    public function hash(string $password): string 
{
 return password_hash($password, $this->hashAlgo, $this->hashOptions); 
}
 /** * Validate Passwordwhether Correct. * * @param string $password Original password * @param string $hash Hashed password * @return bool whether Validate Through */ 
    public function verify(string $password, string $hash): bool 
{
 return password_verify($password, $hash); 
}
 /** * check if password hash needs rehashing. * * @param string $hash Hashed password * @return bool whether rehashing is needed */ 
    public function needsRehash(string $hash): bool 
{
 return password_needs_rehash($hash, $this->hashAlgo, $this->hashOptions); 
}
 
}
 
