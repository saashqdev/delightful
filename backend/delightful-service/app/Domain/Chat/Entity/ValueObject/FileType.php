<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum FileType: int
{
    // file
    case File = 0;

    // link
    case Link = 1;

    // Word
    case Word = 2;

    // PPT
    case PPT = 3;

    // Excel
    case Excel = 4;

    // image
    case Image = 5;

    // video
    case Video = 6;

    // audio
    case Audio = 7;

    // compresspackage
    case Compress = 8;

    public static function getTypeFromFileExtension(string $fileExtension): self
    {
        // fromfileextensionname,inferenceoutfiletype
        return match (strtolower($fileExtension)) {
            // URL
            'http', 'https' => self::Link,
            // doc
            'doc', 'docx', 'dot' => self::Word,
            // ppt
            'ppt', 'pptx', 'pot', 'pps', => self::PPT,
            // excel
            'xls', 'xlsx', 'xlsm', 'xlsb' => self::Excel,
            // image
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp' => self::Image,
            // video
            'mp4', 'avi', 'rmvb', 'rm', 'mpg', 'mpeg', 'mpe', 'wmv', 'mkv', 'vob', 'mov', 'qt', 'flv', 'f4v', 'swf' => self::Video,
            // audio
            'mp3', 'wma', 'wav', 'mod', 'ra', 'cd', 'md', 'asf', 'aac', 'ape', 'mid', 'ogg', 'm4a', 'vqf' => self::Audio,
            // compresspackage
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'cab', 'iso', 'lzh', 'ace', 'arj', 'uue', 'jar' => self::Compress,
            default => self::File,
        };
    }
}
