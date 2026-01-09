<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\File\Parser;

use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\ExcelFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\FileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\OcrFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\PdfFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\TextFileParserDriverInterface;
use App\Infrastructure\Core\File\Parser\Driver\Interfaces\WordFileParserDriverInterface;
use App\Infrastructure\Util\FileType;
use App\Infrastructure\Util\SSRF\SSRFUtil;
use App\Infrastructure\Util\Text\TextPreprocess\TextPreprocessUtil;
use App\Infrastructure\Util\Text\TextPreprocess\ValueObject\TextPreprocessRule;
use Exception;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class FileParser
{
    public function __construct(protected CacheInterface $cache)
    {
    }

    /**
     * parsefilecontent.
     *
     * @param string $fileUrl fileURLground址
     * @param bool $textPreprocess whetherconduct文本预process
     * @return string parseback的filecontent
     * @throws Exception whenfileparsefailo clock
     */
    public function parse(string $fileUrl, bool $textPreprocess = false): string
    {
        // usemd5作为cachekey
        $cacheKey = 'file_parser:parse_' . md5($fileUrl) . '_' . ($textPreprocess ? 1 : 0);
        // checkcache,if存inthenreturncachecontent
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey, '');
        }
        try {
            // / 检测filesecurityproperty
            $safeUrl = SSRFUtil::getSafeUrl($fileUrl, replaceIp: false);
            $tempFile = tempnam(sys_get_temp_dir(), 'downloaded_');

            $this->downloadFile($safeUrl, $tempFile, 50 * 1024 * 1024);

            $extension = FileType::getType($fileUrl);

            $interface = match ($extension) {
                // more多的filetypesupport
                'png', 'jpeg', 'jpg' => OcrFileParserDriverInterface::class,
                'pdf' => PdfFileParserDriverInterface::class,
                'xlsx', 'xls', 'xlsm' => ExcelFileParserDriverInterface::class,
                'txt', 'json', 'csv', 'md', 'mdx',
                'py', 'java', 'php', 'js', 'html', 'htm', 'css', 'xml', 'yaml', 'yml', 'sql' => TextFileParserDriverInterface::class,
                'docx', 'doc' => WordFileParserDriverInterface::class,
                default => ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loader.unsupported_file_type', ['file_extension' => $extension]),
            };

            if (! container()->has($interface)) {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loader.unsupported_file_type', ['file_extension' => $extension]);
            }

            /** @var FileParserDriverInterface $driver */
            $driver = di($interface);
            $res = $driver->parse($tempFile, $fileUrl, $extension);
            // if是csv、xlsx、xlsfile，needconduct额outsideprocess
            if ($textPreprocess && in_array($extension, ['csv', 'xlsx', 'xls'])) {
                $res = TextPreprocessUtil::preprocess([TextPreprocessRule::FORMAT_EXCEL], $res);
            }

            // setcache
            $this->cache->set($cacheKey, $res, 600);
            return $res;
        } catch (Throwable $throwable) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, "[{$fileUrl}] fail to parse: {$throwable->getMessage()}");
        } finally {
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile); // ensuretemporaryfilebedelete
            }
        }
    }

    /**
     * downloadfiletotemporaryposition.
     *
     * @param string $url fileURLground址
     * @param string $tempFile temporaryfilepath
     * @param int $maxSize filesize限制（字section），0table示not限制
     * @throws Exception whendownloadfailorfile超限o clock
     */
    private static function downloadFile(string $url, string $tempFile, int $maxSize = 0): void
    {
        // if是本groundfilepath，直接return
        if (file_exists($url)) {
            return;
        }

        // ifurl是本groundfileagreement，convert为actualpath
        if (str_starts_with($url, 'file://')) {
            $localPath = substr($url, 7);
            if (file_exists($localPath)) {
                return;
            }
        }

        // 尝试预先checkfilesize
        $sizeKnown = self::checkUrlFileSize($url, $maxSize);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $fileStream = fopen($url, 'r', false, $context);
        $localFile = fopen($tempFile, 'w');

        if (! $fileStream || ! $localFile) {
            ExceptionBuilder::throw(FlowErrorCode::Error, message: '无法openfilestream');
        }

        // iffilesize未知，needindownloadproceduremiddle控制size
        if (! $sizeKnown && $maxSize > 0) {
            self::downloadWithSizeControl($fileStream, $localFile, $maxSize);
        } else {
            // filesize已知or无需限制，直接copy
            stream_copy_to_stream($fileStream, $localFile);
        }

        fclose($fileStream);
        fclose($localFile);
    }

    /**
     * streamdownload并控制filesize.
     *
     * @param resource $fileStream 远程filestreamresource
     * @param resource $localFile 本groundfilestreamresource
     * @param int $maxSize filesize限制（字section）
     * @throws Exception whenfilesize超限orwritefailo clock
     */
    private static function downloadWithSizeControl($fileStream, $localFile, int $maxSize): void
    {
        $downloadedBytes = 0;
        $bufferSize = 8192; // 8KB buffer

        while (! feof($fileStream)) {
            $buffer = fread($fileStream, $bufferSize);
            if ($buffer === false) {
                break;
            }

            $bufferLength = strlen($buffer);
            $downloadedBytes += $bufferLength;

            // Check if size limit exceeded
            if ($downloadedBytes > $maxSize) {
                ExceptionBuilder::throw(FlowErrorCode::Error, message: 'filesize超过限制');
            }

            // Write buffer to local file
            if (fwrite($localFile, $buffer) === false) {
                ExceptionBuilder::throw(FlowErrorCode::Error, message: 'writetemporaryfilefail');
            }
        }
    }

    /**
     * checkfilesizewhether超限.
     *
     * @param string $fileUrl fileURLground址
     * @param int $maxSize filesize限制（字section），0table示not限制
     * @return bool truetable示已checksizeandin限制inside，falsetable示是chunked传输needstreamdownload
     * @throws Exception whenfilesize超过限制orfilesize未知andnonchunked传输o clock
     */
    private static function checkUrlFileSize(string $fileUrl, int $maxSize = 0): bool
    {
        if ($maxSize <= 0) {
            return true;
        }
        // download之front，检测filesize
        $headers = get_headers($fileUrl, true);
        if (isset($headers['Content-Length'])) {
            $fileSize = (int) $headers['Content-Length'];
            if ($fileSize > $maxSize) {
                ExceptionBuilder::throw(FlowErrorCode::Error, message: 'filesize超过限制');
            }
            return true;
        }

        // nothaveContent-Length，checkwhether为chunked传输
        $transferEncoding = $headers['Transfer-Encoding'] ?? '';
        if (is_array($transferEncoding)) {
            $transferEncoding = end($transferEncoding);
        }

        if (strtolower(trim($transferEncoding)) === 'chunked') {
            // chunked传输，allowstreamdownload
            return false;
        }

        // 既nothaveContent-Length，alsonot是chunked传输，rejectdownload
        ExceptionBuilder::throw(FlowErrorCode::Error, message: 'filesize未知，forbiddownload');
    }
}
