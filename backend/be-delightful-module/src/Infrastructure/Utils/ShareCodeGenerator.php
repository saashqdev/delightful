<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

use InvalidArgumentException;
/** * Share code generator utility class. */

class ShareCodeGenerator 
{
 /** * Share code length. */ 
    protected int $codeLength = 18; /** * Allowed character set. */ 
    protected string $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; /** * Last generated timestamp in microseconds. */ 
    protected int $lastMicrotime = 0; /** * Sequence number within the same microsecond. */ 
    protected int $sequence = 0; /** * Generate a unique share code * * Generate unique code based on timestamp and sequence number, ensuring uniqueness in distributed environment * Final code will be in a friendly format like AB12XY89 * * @param string $prefix Optional prefix for business distinction, default is empty * @return string Generate d share code */ 
    public function generate(string $prefix = ''): string 
{
 // Get current timestamp in microseconds $currentMicro = $this->getcurrent Microseconds(); // Handle multiple calls within the same microsecond if ($currentMicro === $this->lastMicrotime) 
{
 ++$this->sequence; 
}
 else 
{
 $this->sequence = 0; $this->lastMicrotime = $currentMicro; 
}
 // Combine unique data source $uniqueData = $currentMicro . $this->sequence; // Add a random seed to increase randomness $randomSeed = random_int(1000, 9999); $uniqueData .= $randomSeed; // Calculate hash value $hash = md5($uniqueData); // Convert hash to share code friendly format $code = $this->hashToReadableCode($hash); // Ensure code length meets requirements $code = substr($code, 0, $this->codeLength); // Add prefix if provided if (! empty($prefix)) 
{
 $code = $prefix . $code; // Ensure total length still meets requirements $code = substr($code, 0, $this->codeLength); 
}
 return $code; 
}
 /** * Generate multiple unique share codes * * @param int $count Number of codes to generate * @param string $prefix Optional prefix for business distinction, default is empty * @return array Array of generated share codes */ 
    public function generateMultiple(int $count, string $prefix = ''): array 
{
 $codes = []; for ($i = 0; $i < $count; ++$i) 
{
 $codes[] = $this->generate($prefix); // Ensure time interval to increase uniqueness if ($i < $count - 1) 
{
 usleep(1); // Sleep for 1 microsecond 
}
 
}
 return $codes; 
}
 /** * Set share code length. * * @param int $length Code length */ 
    public function setCodeLength(int $length): self 
{
 if ($length < 4) 
{
 throw new InvalidArgumentException('Share code length cannot be less than 4'); 
}
 $this->codeLength = $length; return $this; 
}
 /** * Set Character set. * * @param string $charset Character set */ 
    public function setCharset(string $charset): self 
{
 if (empty($charset)) 
{
 throw new InvalidArgumentException('Character set cannot be empty'); 
}
 $this->charset = $charset; return $this; 
}
 /** * Validate if share code is valid. * * @param string $code Share code to be validated * @return bool whether valid */ 
    public function isValid(string $code): bool 
{
 if (empty($code) || strlen($code) !== $this->codeLength) 
{
 return false; 
}
 // check if code only contains characters in character set for ($i = 0; $i < strlen($code); ++$i) 
{
 if (strpos($this->charset, $code[$i]) === false) 
{
 return false; 
}
 
}
 return true; 
}
 /** * Convert hash value to readable share code * * @param string $hash Hash value * @return string Friendly format share code */ 
    protected function hashToReadableCode(string $hash): string 
{
 $result = ''; $charsetLength = strlen($this->charset); // Group hash value for processing, 4 bits per group for ($i = 0; $i < strlen($hash); $i += 2) 
{
 // Extract 2 characters from hash, convert to hexadecimal value $hexVal = hexdec(substr($hash, $i, 2)); // Map to character set range $index = $hexVal % $charsetLength; $result .= $this->charset[$index]; // Stop when target length is reached if (strlen($result) >= $this->codeLength) 
{
 break; 
}
 
}
 return $result; 
}
 /** * Get current microsecond timestamp. * * @return int MicrosecondsTimestamp */ 
    protected function getcurrent Microseconds(): int 
{
 // Get microsecond-level timestamp $microtime = microtime(true); // Convert to integer, multiply by 1000000 to get microsecond-level precision return (int) ($microtime * 1000000); 
}
 
}
 
