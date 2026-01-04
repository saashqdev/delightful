<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Dtyq\CodeExecutor\Utils;

/**
 * CRC64 calculation utility class.
 */
class CRC64
{
    /**
     * CRC64 lookup table.
     */
    private static array $crc64tab = [];

    /**
     * Current CRC value
     */
    private int $value = 0;

    /**
     * Constructor, initializes CRC64 lookup table.
     */
    public function __construct()
    {
        // Initialize lookup table only on first instantiation
        if (self::$crc64tab === []) {
            $this->initCrcTable();
        }
    }

    /**
     * Append string content to current CRC calculation.
     */
    public function append(string $string): void
    {
        $len = \strlen($string);

        // Avoid calculating string length each loop iteration
        for ($i = 0; $i < $len; ++$i) {
            $this->value = ~$this->value;
            $this->value = $this->count(\ord($string[$i]), $this->value);
            $this->value = ~$this->value;
        }
    }

    /**
     * Get or set current CRC value
     */
    public function value(?int $value = null): int
    {
        if ($value !== null) {
            $this->value = $value;
        }

        return $this->value;
    }

    /**
     * Get CRC calculation result (string format).
     */
    public function result(): string
    {
        return sprintf('%u', $this->value);
    }

    /**
     * Quickly calculate CRC64 value of content
     */
    public static function calculate(string $content): string
    {
        $crc64 = new static();
        $crc64->append($content);
        return $crc64->result();
    }

    /**
     * Initialize CRC64 lookup table.
     */
    private function initCrcTable(): void
    {
        $poly64rev = (0xC96C5795 << 32) | 0xD7870F42;

        for ($n = 0; $n < 256; ++$n) {
            $crc = $n;
            for ($k = 0; $k < 8; ++$k) {
                if ($crc & 1) {
                    $crc = ($crc >> 1) & ~(0x8 << 60) ^ $poly64rev;
                } else {
                    $crc = ($crc >> 1) & ~(0x8 << 60);
                }
            }
            self::$crc64tab[$n] = $crc;
        }
    }

    /**
     * Calculate CRC value of a single byte
     */
    private function count(int $byte, int $crc): int
    {
        return self::$crc64tab[($crc ^ $byte) & 0xFF] ^ (($crc >> 8) & ~(0xFF << 56));
    }
}
