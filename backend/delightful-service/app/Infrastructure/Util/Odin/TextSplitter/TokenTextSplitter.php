<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Odin\TextSplitter;

use Exception;
use Hyperf\Context\Context;
use Hyperf\Odin\TextSplitter\TextSplitter;
use Throwable;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

class TokenTextSplitter extends TextSplitter
{
    /**
     * setmost大cachetextlength（character数）
     * 超过此length的text将notwillbecachein协程updown文middle.
     */
    private const int MAX_CACHE_TEXT_LENGTH = 1000;

    protected $chunkSize;

    protected $chunkOverlap;

    protected $keepSeparator;

    private string $fixedSeparator;

    private array $separators;

    /**
     * @var callable token计算闭package
     */
    private $tokenizer;

    /**
     * defaulttoken计算闭packageuseto的encoderProvider.
     */
    private EncoderProvider $defaultEncoderProvider;

    /**
     * defaulttoken计算闭packageuseto的encoder.
     */
    private Encoder $defaultEncoder;

    /**
     * @var bool splitback的text保留minute隔符
     */
    private bool $preserveSeparator = false;

    /**
     * @param null|callable $tokenizer token计算function
     * @param null|array $separators 备选minute隔符list
     * @throws Exception
     */
    public function __construct(
        ?callable $tokenizer = null,
        int $chunkSize = 1000,
        int $chunkOverlap = 200,
        string $fixedSeparator = "\n\n",
        ?array $separators = null,
        bool $keepSeparator = false,
        bool $preserveSeparator = false
    ) {
        $this->chunkSize = $chunkSize;
        $this->chunkOverlap = $chunkOverlap;
        $this->fixedSeparator = $fixedSeparator;
        $this->separators = $separators ?? ["\n\n", "\n", '。', ' ', ''];
        $this->tokenizer = $tokenizer ?? $this->getDefaultTokenizer();
        $this->keepSeparator = $keepSeparator;
        $this->preserveSeparator = $preserveSeparator;
        parent::__construct($chunkSize, $chunkOverlap, $keepSeparator);
    }

    /**
     * splittext.
     *
     * @param string $text 要split的text
     * @return array splitback的textpiecearray
     */
    public function splitText(string $text): array
    {
        $text = $this->ensureUtf8Encoding($text);

        // saveoriginaltext，useatalso原tag
        $originalText = $text;

        // 1. 先把原文middle的0x00替换become0x000x00
        $text = str_replace("\x00", "\x00\x00", $text);

        // 2. 把tag替换become0x00
        $text = preg_replace('/<DelightfulCompressibleContent.*?<\/DelightfulCompressibleContent>/s', "\x00", $text);

        // 3. splittext
        if ($this->fixedSeparator) {
            $chunks = $this->splitBySeparator($text, $this->fixedSeparator);
        } else {
            $chunks = [$text];
        }

        // 计算eachchunk的tokenlength
        $chunksLengths = array_map(function ($chunk) {
            return ($this->tokenizer)($chunk);
        }, $chunks);

        $finalChunks = [];
        foreach ($chunks as $i => $chunk) {
            if ($chunksLengths[$i] > $this->chunkSize) {
                // ifchunktoo大，conduct递归split
                $finalChunks = array_merge($finalChunks, $this->recursiveSplitText($chunk));
            } else {
                $finalChunks[] = $chunk;
            }
        }

        // 4. also原text
        // 先get所havetag
        preg_match_all('/<DelightfulCompressibleContent.*?<\/DelightfulCompressibleContent>/s', $originalText, $matches);
        $tags = $matches[0];
        $tagIndex = 0;

        return array_map(function ($chunk) use ($tags, &$tagIndex) {
            // also原0x000x00为0x00
            $chunk = str_replace("\x00\x00", "\x00", $chunk);
            // also原tag
            return preg_replace_callback('/\x00/', function () use ($tags, &$tagIndex) {
                return $tags[$tagIndex++] ?? '';
            }, $chunk);
        }, $finalChunks);
    }

    /**
     * mergetextpiece.
     *
     * @param array $splits 要merge的textpiece
     * @param string $separator minute隔符
     * @return array mergeback的textpiecearray
     */
    protected function mergeSplits(array $splits, string $separator): array
    {
        $merged = [];
        $currentChunk = '';
        $currentLength = 0;

        foreach ($splits as $split) {
            $length = ($this->tokenizer)($split);

            if ($currentLength + $length > $this->chunkSize) {
                if ($currentChunk !== '') {
                    $merged[] = $currentChunk;
                }
                $currentChunk = $split;
                $currentLength = $length;
            } else {
                if ($currentChunk !== '') {
                    $currentChunk .= $separator;
                }
                $currentChunk .= $split;
                $currentLength += $length;
            }
        }

        if ($currentChunk !== '') {
            $merged[] = $currentChunk;
        }

        return $merged;
    }

    /**
     * usefinger定minute隔符splittext.
     */
    private function splitBySeparator(string $text, string $separator): array
    {
        if ($separator === ' ') {
            $chunks = preg_split('/\s+/', $text);
        } else {
            // ifminute隔符contain0x00，替换become0x000x00
            $separator = str_replace("\x00", "\x00\x00", $separator);
            $chunks = explode($separator, $text);
            if ($this->preserveSeparator) {
                $chunks = $this->preserveSeparator($chunks, $separator);
            }
        }
        return array_values(array_filter($chunks, function ($chunk) {
            return $chunk !== '' && $chunk !== "\n";
        }));
    }

    /**
     * processminute隔符，将minute隔符splicetoeachminutepiece的frontsurface（except了first）.
     */
    private function preserveSeparator(array $chunks, string $separator): array
    {
        return array_map(function ($chunk, $index) use ($separator) {
            return $index > 0 ? $separator . $chunk : $chunk;
        }, $chunks, array_keys($chunks));
    }

    /**
     * 检测并converttextencoding
     */
    private function ensureUtf8Encoding(string $text): string
    {
        $encoding = $this->detectEncoding($text);
        if ($encoding !== 'UTF-8') {
            return mb_convert_encoding($text, 'UTF-8', $encoding);
        }
        return $text;
    }

    /**
     * 按fixedlengthsplittext.
     */
    private function splitByFixedLength(string $text): array
    {
        $chunkSize = (int) floor($this->chunkSize / 2); // usemore小的piecesize
        $length = mb_strlen($text);
        $splits = [];
        for ($i = 0; $i < $length; $i += $chunkSize) {
            $splits[] = mb_substr($text, $i, $chunkSize);
        }
        return $splits;
    }

    /**
     * process无minute隔符的textsplit.
     */
    private function handleNoSeparatorSplits(array $splits, array $splitLengths): array
    {
        $finalChunks = [];
        $currentPart = '';
        $currentLength = 0;
        $overlapPart = '';
        $overlapLength = 0;

        foreach ($splits as $i => $split) {
            $splitLength = $splitLengths[$i];

            if ($currentLength + $splitLength <= $this->chunkSize - $this->chunkOverlap) {
                $currentPart .= $split;
                $currentLength += $splitLength;
            } elseif ($currentLength + $splitLength <= $this->chunkSize) {
                $currentPart .= $split;
                $currentLength += $splitLength;
                $overlapPart .= $split;
                $overlapLength += $splitLength;
            } else {
                $finalChunks[] = $currentPart;
                $currentPart = $overlapPart . $split;
                $currentLength = $splitLength + $overlapLength;
                $overlapPart = '';
                $overlapLength = 0;
            }
        }

        if ($currentPart !== '') {
            $finalChunks[] = $currentPart;
        }

        return $finalChunks;
    }

    /**
     * 递归splittext.
     *
     * @param string $text 要split的text
     * @return array splitback的textpiecearray
     */
    private function recursiveSplitText(string $text, int $separatorBeginIndex = 0): array
    {
        $finalChunks = [];
        $separator = end($this->separators);
        $newSeparators = [];

        // find合适的minute隔符, from$separatorBeginIndexstart
        for ($i = $separatorBeginIndex; $i < count($this->separators); ++$i) {
            $sep = $this->separators[$i];
            if ($sep === '') {
                $separator = $sep;
                break;
            }
            if (str_contains($text, $sep)) {
                $separator = $sep;
                $newSeparators = array_slice($this->separators, $i + 1);
                break;
            }
        }
        $separatorBeginIndex = min($i + 1, count($this->separators));

        // use选定的minute隔符splittext
        if ($separator !== '') {
            $splits = $this->splitBySeparator($text, $separator);
        } else {
            $splits = $this->splitByFixedLength($text);
        }

        // 计算eachsplit的tokenlength
        $splitLengths = array_map(function ($split) {
            return ($this->tokenizer)($split);
        }, $splits);

        if ($separator !== '') {
            // processhaveminute隔符的情况
            $goodSplits = [];
            $goodSplitsLengths = [];
            $actualSeparator = $this->keepSeparator ? $separator : '';

            foreach ($splits as $i => $split) {
                $splitLength = $splitLengths[$i];

                if ($splitLength < $this->chunkSize) {
                    $goodSplits[] = $split;
                    $goodSplitsLengths[] = $splitLength;
                } else {
                    if (! empty($goodSplits)) {
                        $mergedText = $this->mergeSplits($goodSplits, $actualSeparator);
                        $finalChunks = array_merge($finalChunks, $mergedText);
                        $goodSplits = [];
                        $goodSplitsLengths = [];
                    }

                    if (empty($newSeparators)) {
                        $finalChunks[] = $split;
                    } else {
                        $finalChunks = array_merge(
                            $finalChunks,
                            $this->recursiveSplitText($split, $separatorBeginIndex)
                        );
                    }
                }
            }

            if (! empty($goodSplits)) {
                $mergedText = $this->mergeSplits($goodSplits, $actualSeparator);
                $finalChunks = array_merge($finalChunks, $mergedText);
            }
        } else {
            $finalChunks = $this->handleNoSeparatorSplits($splits, $splitLengths);
        }

        return $finalChunks;
    }

    /**
     * 计算text的tokenquantity.
     */
    private function calculateTokenCount(string $text): int
    {
        try {
            if (! isset($this->defaultEncoderProvider)) {
                $this->defaultEncoderProvider = new EncoderProvider();
                $this->defaultEncoder = $this->defaultEncoderProvider->getForModel('gpt-4');
            }
            return count($this->defaultEncoder->encode($text));
        } catch (Throwable $e) {
            // if计算tokenfail，return一估计value
            return (int) ceil(mb_strlen($text) / 4);
        }
    }

    private function getDefaultTokenizer(): callable
    {
        return function (string $text) {
            // iftextlength超过限制，直接计算notcache
            if (mb_strlen($text) > self::MAX_CACHE_TEXT_LENGTH) {
                return $this->calculateTokenCount($text);
            }

            // generateupdown文键
            $contextKey = 'token_count:' . md5($text);

            // 尝试from协程updown文get
            $count = Context::get($contextKey);
            if ($count !== null) {
                return $count;
            }

            // 计算 token quantity
            $count = $this->calculateTokenCount($text);

            // storageto协程updown文
            Context::set($contextKey, $count);

            return $count;
        };
    }

    /**
     * 检测filecontent的encoding
     */
    private function detectEncoding(string $content): string
    {
        // check BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return 'UTF-8';
        }
        if (str_starts_with($content, "\xFF\xFE")) {
            return 'UTF-16LE';
        }
        if (str_starts_with($content, "\xFE\xFF")) {
            return 'UTF-16BE';
        }

        // 尝试检测encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ASCII'], true);
        if ($encoding === false) {
            // if无法检测toencoding，尝试use iconv 检测
            $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ASCII'], false);
            if ($encoding === false) {
                return 'UTF-8'; // defaultuse UTF-8
            }
        }

        return $encoding;
    }
}
