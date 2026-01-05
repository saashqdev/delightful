<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Infrastructure\Util;

use App\Infrastructure\Util\SSRF\SSRFUtil;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Mime\MimeTypes;

class FileType
{
    /**
     * Get the extension for a file type.
     */
    public static function getType(string $url): string
    {
        // Try different strategies in order of priority to determine file type
        try {
            // 1. Try deriving from URL path
            $extensionFromUrl = self::getTypeFromUrlPath($url);
            if ($extensionFromUrl) {
                return $extensionFromUrl;
            }

            // 2. Check local file
            if (file_exists($url)) {
                return self::getTypeFromLocalFile($url);
            }

            // 3. Try reading HTTP headers
            $extensionFromHeaders = self::getTypeFromHeaders($url);
            if ($extensionFromHeaders) {
                return $extensionFromHeaders;
            }

            // 4. Download file and inspect MIME type
            return self::getTypeFromDownload($url);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Unable to determine file type: ' . $e->getMessage());
        }
    }

    /**
     * Derive type from a local file.
     */
    private static function getTypeFromLocalFile(string $path): string
    {
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        $extension = self::getExtensionFromMimeType($mimeType);

        if (! $extension) {
            throw new InvalidArgumentException("Cannot determine file extension from MIME type '{$mimeType}'");
        }

        return $extension;
    }

    /**
     * Derive type from URL path.
     */
    private static function getTypeFromUrlPath(string $url): ?string
    {
        $parseUrl = parse_url($url);
        $path = $parseUrl['path'] ?? '';
        $fileExtension = pathinfo($path, PATHINFO_EXTENSION);

        return ! empty($fileExtension) ? strtolower($fileExtension) : null;
    }

    /**
     * Derive type from HTTP headers.
     */
    private static function getTypeFromHeaders(string $url): ?string
    {
        $context = self::createStreamContext();
        $headers = get_headers($url, true, $context);

        if ($headers === false || ! isset($headers['Content-Type'])) {
            return null;
        }

        $mimeType = is_array($headers['Content-Type'])
            ? $headers['Content-Type'][0]
            : $headers['Content-Type'];

        return self::getExtensionFromMimeType($mimeType);
    }

    /**
     * Derive type by downloading the file.
     */
    private static function getTypeFromDownload(string $url): string
    {
        // Validate file safety
        $safeUrl = SSRFUtil::getSafeUrl($url, replaceIp: false);
        $tempFile = tempnam(sys_get_temp_dir(), 'downloaded_');

        try {
            self::downloadFile($safeUrl, $tempFile);
            self::checkFileSize($tempFile);

            // Inspect MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tempFile);
            finfo_close($finfo);

            $extension = self::getExtensionFromMimeType($mimeType);
            if (! $extension) {
                throw new InvalidArgumentException("Cannot determine file extension from MIME type '{$mimeType}'");
            }

            return $extension;
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile); // ensure temp file is removed
            }
        }
    }

    /**
     * Create a stream context that skips SSL verification.
     */
    private static function createStreamContext(): mixed
    {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
    }

    /**
     * Download file to a temporary path.
     */
    private static function downloadFile(string $url, string $tempFile): void
    {
        $context = self::createStreamContext();
        $fileStream = fopen($url, 'r', false, $context);
        $localFile = fopen($tempFile, 'w');

        if (! $fileStream || ! $localFile) {
            throw new Exception('Unable to open file stream');
        }

        stream_copy_to_stream($fileStream, $localFile);

        fclose($fileStream);
        fclose($localFile);
    }

    /**
     * Validate that file size is within limits.
     */
    private static function checkFileSize(string $filePath, int $maxSize = 52428800): void // 50MB
    {
        if (filesize($filePath) > $maxSize) {
            throw new Exception('File too large to download');
        }
    }

    /**
     * Get file extension from MIME type.
     */
    private static function getExtensionFromMimeType(string $mimeType): ?string
    {
        $mimeTypes = new MimeTypes();
        $extensions = $mimeTypes->getExtensions($mimeType);
        return $extensions[0] ?? null; // return the first matching extension
    }
}
