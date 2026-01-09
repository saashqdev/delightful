<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\File;

use InvalidArgumentException;

class EasyFileTools
{
    public static function saveFile(string $path, string $stream): void
    {
        $file = fopen($path, 'wb');
        // stream切割become1000kb小piece，eachtimewritefile

        fwrite($file, $stream);
        fclose($file);
    }

    public static function mergeWavFiles(string $file1, string $blob): void
    {
        // iffilenot存in，直接will blob writefornewfile
        if (! file_exists($file1)) {
            self::saveFile($file1, $blob);
            return;
        }

        // open file1 fileby读写mode
        $wav1 = fopen($file1, 'r+b');
        if (! $wav1) {
            throw new InvalidArgumentException('Failed to open the base file.');
        }
        // go掉blobhead
        $blob = substr($blob, 44);

        // will新dataappendtofile末tail
        // getfilesize
        fseek($wav1, 0, SEEK_END);
        fwrite($wav1, $blob);
        $fileSize = ftell($wav1);

        // 修just RIFF piecesize（file总size - 8）
        fseek($wav1, 4);
        fwrite($wav1, pack('V', $fileSize - 8));

        // 修just data piecesize（file总size - 44）
        fseek($wav1, 40);
        fwrite($wav1, pack('V', $fileSize - 44));

        // closefile
        fclose($wav1);
    }

    //    public static function getAudioFormat(string $filePath)
    //    {
    //        $riff = RIFF::fromFilePath($filePath);
    //
    //        foreach ($riff->subChunks as $chunk) {
    //            if ($chunk instanceof FMTChunk) {
    //                return $chunk;
    //            }
    //        }
    //
    //        throw new InvalidArgumentException('Missing FMT chunk in the file');
    //    }
}
