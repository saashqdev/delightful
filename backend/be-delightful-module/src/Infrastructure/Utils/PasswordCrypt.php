<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

/** * Reversible password encryption and decryption tool. */

class PasswordCrypt 
{
 /** * EncryptMethod. * * @param string $password Original password * @return string Encrypted password */ 
    public 
    static function encrypt(string $password): string 
{
 if (empty($password)) 
{
 return ''; 
}
 // Using openssl Encrypt $method = 'AES-256-CBC'; $key = self::getEncryptKey(); $iv = substr(hash('sha256', self::getEncryptIv()), 0, 16); $encrypted = openssl_encrypt($password, $method, $key, 0, $iv); return base64_encode($encrypted); 
}
 /** * DecryptMethod. * * @param string $encryptedPassword Encrypted password * @return string Decrypted original password */ 
    public 
    static function decrypt(string $encryptedPassword): string 
{
 if (empty($encryptedPassword)) 
{
 return ''; 
}
 // Using openssl Decrypt $method = 'AES-256-CBC'; $key = self::getEncryptKey(); $iv = substr(hash('sha256', self::getEncryptIv()), 0, 16); $encrypted = base64_decode($encryptedPassword); $result = openssl_decrypt($encrypted, $method, $key, 0, $iv); return $result === false ? '' : $result; 
}
 /** * GetEncryptKey. * * @return string EncryptKey */ 
    private 
    static function getEncryptKey(): string 
{
 return config('super-magic.share.encrypt_key'); 
}
 /** * Get encryption vector. * * @return string Encryption vector */ 
    private 
    static function getEncryptIv(): string 
{
 return config('super-magic.share.encrypt_iv'); 
}
 
}
 
