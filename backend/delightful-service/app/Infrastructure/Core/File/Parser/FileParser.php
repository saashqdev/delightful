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
     * @param string $fileUrl fileURLgroundaddress
     * @param bool $textPreprocess whetherconducttextpreprocess
     * @return string parsebackfilecontent
     * @throws Exception whenfileparsefailo clock
     */
    public function parse(string $fileUrl, bool $textPreprocess = false): string
    {
        // usemd5asforcachekey
        $cacheKey = 'file_parser:parse_' . md5($fileUrl) . '_' . ($textPreprocess ? 1 : 0);
        // checkcache,ifexistsinthenreturncachecontent
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey, '');
        }
        try {
            // / detectfilesecurityproperty
            $safeUrl = SSRFUtil::getSafeUrl($fileUrl, replaceIp: false);
            $tempFile = tempnam(sys_get_temp_dir(), 'downloaded_');

            $this->downloadFile($safeUrl, $tempFile, 50 * 1024 * 1024);

            $extension = FileType::getType($fileUrl);

            $interface = match ($extension) {
                // moremultiplefiletypesupport
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
            // ifiscsv,xlsx,xlsfile,needconductquotaoutsideprocess
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
     * @param string $url fileURLgroundaddress
     * @param string $tempFile temporaryfilepath
     * @param int $maxSize filesizelimit(fieldsection),0tableshownotlimit
     * @throws Exception whendownloadfailorfileexceed limito clock
     */
    private static function downloadFile(string $url, string $tempFile, int $maxSize = 0): void
    {
        // ifisthisgroundfilepath,directlyreturn
        if (file_exists($url)) {
            return;
        }

        // ifurlisthisgroundfileagreement,convertforactualpath
        if (str_starts_with($url, 'file://')) {
            $localPath = substr($url, 7);
            if (file_exists($localPath)) {
                return;
            }
        }

        // tryin advancecheckfilesize
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
            ExceptionBuilder::throw(FlowErrorCode::Error, message: 'nomethodopenfilestream');
        }

        // iffilesizeunknown,needindownloadproceduremiddlecontrolsize
        if (! $sizeKnown && $maxSize > 0) {
            self::downloadWithSizeControl($fileStream, $localFile, $maxSize);
        } else {
            // filesizeknownornoneedlimit,directlycopy
            stream_copy_to_stream($fileStream, $localFile);
        }

        fclose($fileStream);
        fclose($localFile);
    }

    /**
     * streamdownloadandcontrolfilesize.
     *
     * @param resource $fileStream remotefilestreamresource
     * @param resource $localFile thisgroundfilestreamresource
     * @param int $maxSize filesizelimit(fieldsection)
     * @throws Exception whenfilesizeexceed limitorwritefailo clock
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
                ExceptionBuilder::throw(FlowErrorCode::Error, message: 'filesizeexceedspasslimit');
            }

            // Write buffer to local file
            if (fwrite($localFile, $buffer) === false) {
                ExceptionBuilder::throw(FlowErrorCode::Error, message: 'writetemporaryfilefail');
            }
        }
    }

    /**
     * checkfilesizewhetherexceed limit.
     *
     * @param string $fileUrl fileURLgroundaddress
     * @param int $maxSize filesizelimit(fieldsection),0tableshownotlimit
     * @return bool truetableshowalreadychecksizeandinlimitinside,falsetableshowischunkedtransmissionneedstreamdownload
     * @throws Exception whenfilesizeexceedspasslimitorfilesizeunknownandnonchunkedtransmissiono clock
     */
    private static function checkUrlFileSize(string $fileUrl, int $maxSize = 0): bool
    {
        if ($maxSize <= 0) {
            return true;
        }
        // downloadoffront,detectfilesize
        $headers = get_headers($fileUrl, true);
        if (isset($headers['Content-Length'])) {
            $fileSize = (int) $headers['Content-Length'];
            if ($fileSize > $maxSize) {
                ExceptionBuilder::throw(FlowErrorCode::Error, message: 'filesizeexceedspasslimit');
            }
            return true;
        }

        // nothaveContent-Length,checkwhetherforchunkedtransmission
        $transferEncoding = $headers['Transfer-Encoding'] ?? '';
        if (is_array($transferEncoding)) {
            $transferEncoding = end($transferEncoding);
        }

        if (strtolower(trim($transferEncoding)) === 'chunked') {
            // chunkedtransmission,allowstreamdownload
            return false;
        }

        // alreadynothaveContent-Length,alsonotischunkedtransmission,rejectdownload
        ExceptionBuilder::throw(FlowErrorCode::Error, message: 'filesizeunknown,forbiddownload');
    }
}
