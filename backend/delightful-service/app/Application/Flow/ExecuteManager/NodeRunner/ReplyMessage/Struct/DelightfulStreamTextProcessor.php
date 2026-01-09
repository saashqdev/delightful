<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct;

use Closure;

use function Hyperf\Support\call;

class DelightfulStreamTextProcessor
{
    private const int STATE_NORMAL = 0;    // normaltextstatus

    private const int STATE_TAG_START = 1; // maybeistagstart

    private const int STATE_IN_TAG = 2;    // confirmintaginside

    private Closure $outputCall;

    private string $tag = 'Delightful';

    private string $buffer = '';

    /**
     * @var int status 0 normaltext,1 tagstart,2 intaginside
     */
    private int $state = 0;

    private array $successLengths;

    public function __construct(Closure $outputCall)
    {
        $this->outputCall = $outputCall;
        $this->successLengths = [
            mb_strlen('<DelightfulImage>') + 16 + mb_strlen('</DelightfulImage>'),
            mb_strlen('<DelightfulVideo>') + 16 + mb_strlen('</DelightfulVideo>'),
            mb_strlen('<DelightfulMention>') + 16 + mb_strlen('</DelightfulMention>'),
        ];
    }

    public function start(): void
    {
    }

    public function process(string $current, array $params = []): void
    {
        if (mb_strlen($current) > 1 && (str_contains($current, '<') || str_contains($current, '>'))) {
            $chars = preg_split('//u', $current, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chars as $char) {
                $this->process($char, $params);
            }
            return;
        }

        $this->buffer .= $current;

        if ($this->state === self::STATE_NORMAL) {
            if (mb_substr($this->buffer, 0, 1) === '<') {
                $this->state = self::STATE_TAG_START;
                return;
            }
            $this->output($params);
            return;
        }

        if ($this->state === self::STATE_TAG_START) {
            $tagLen = mb_strlen('<' . $this->tag);
            if (mb_strlen($this->buffer) >= $tagLen) {
                if (mb_substr($this->buffer, 0, $tagLen) === '<' . $this->tag) {
                    $this->state = self::STATE_IN_TAG;
                } else {
                    $this->output($params);
                }
            }
            return;
        }

        if ($this->state === self::STATE_IN_TAG) {
            // ifalready经detectlengthalready经达tomostbiglength,直接response
            if (mb_strlen($this->buffer) > max($this->successLengths)) {
                $this->output($params);
                return;
            }
            if ($compressibleContent = $this->isValidTagContent()) {
                $this->output($params, $compressibleContent);
                return;
            }
            return;
        }
    }

    public function end(array $params = []): void
    {
        if ($this->buffer !== '') {
            $this->output($params);
        }
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function output(array $params = [], array $compressibleContent = []): void
    {
        call($this->outputCall, [$this->buffer, $compressibleContent, $params]);
        $this->buffer = '';
        $this->state = self::STATE_NORMAL;
    }

    private function isValidTagContent(): array
    {
        // justthen挺费performance,先采usefixedstringlength
        if (! in_array(mb_strlen($this->buffer), $this->successLengths)) {
            return [];
        }
        if (preg_match("/<{$this->tag}\\w+>(cp_[a-f0-9]+)<\\/{$this->tag}\\w+>/u", $this->buffer, $matches)) {
            return [
                'tag' => $matches[0],
                'id' => $matches[1],
            ];
        }
        return [];
    }
}
