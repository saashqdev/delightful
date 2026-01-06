<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct;

use App\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct\DelightfulStreamTextProcessor;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class DelightfulStreamTextProcessorTest extends ExecuteManagerBaseTest
{
    public function testNormal()
    {
        $text = '123456';
        $length = strlen($text);
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        for ($i = 0; $i < $length; ++$i) {
            $current = $text[$i];
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['1', '2', '3', '4', '5', '6'], $result);
    }

    public function testImage()
    {
        $text = '12<DelightfulImage>cp_67b5aac969f26</DelightfulImage>34';
        $length = strlen($text);
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data, array $compressibleContent) use (&$result) {
            $result[] = $data;
            if (! empty($compressibleContent)) {
                var_dump($compressibleContent);
                var_dump($data);
            }
        });
        $processor->start();
        for ($i = 0; $i < $length; ++$i) {
            $current = $text[$i];
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['1', '2', '<DelightfulImage>cp_67b5aac969f26</DelightfulImage>', '3', '4'], $result);
    }

    public function testVideo()
    {
        $text = '<DelightfulVideo>cp_67b5aac969f26</DelightfulVideo>gg';
        $length = strlen($text);
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data, array $compressibleContent) use (&$result) {
            $result[] = $data;
            if (! empty($compressibleContent)) {
                var_dump($compressibleContent);
                var_dump($data);
            }
        });
        $processor->start();
        for ($i = 0; $i < $length; ++$i) {
            $current = $text[$i];
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['<DelightfulVideo>cp_67b5aac969f26</DelightfulVideo>', 'g', 'g'], $result);
    }

    public function testError()
    {
        $text = '<DelightfulV>v<>xr';
        $length = strlen($text);
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        for ($i = 0; $i < $length; ++$i) {
            $current = $text[$i];
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['<DelightfulV>v<>xr'], $result);
    }

    public function testMaxLength()
    {
        $text = '<DelightfulVideo>v<>xr111111111112222222333444</DelightfulVideo>';
        $length = strlen($text);
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        for ($i = 0; $i < $length; ++$i) {
            $current = $text[$i];
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['<DelightfulVideo>v<>xr111111111112222222333444</Mag', 'i', 'c', 'V', 'i', 'd', 'e', 'o', '>'], $result);
    }

    public function testMixedTags()
    {
        $text = '1<DelightfulImage>cp_67b5aac969f26</DelightfulImage>3<DelightfulVideo>cp_67b5aac969f26</DelightfulVideo>5';
        $length = strlen($text);
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        for ($i = 0; $i < $length; ++$i) {
            $current = $text[$i];
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['1', '<DelightfulImage>cp_67b5aac969f26</DelightfulImage>', '3', '<DelightfulVideo>cp_67b5aac969f26</DelightfulVideo>', '5'], $result);
    }

    public function testMore()
    {
        $text = ['1', '2 <M', 'agicImage>cp_67b5aac969f26</DelightfulImage>', '3', '<DelightfulVideo>cp_67b5aac969f26</DelightfulVideo>', '5'];

        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        foreach ($text as $current) {
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['1', '2', ' ', '<DelightfulImage>cp_67b5aac969f26</DelightfulImage>', '3', '<DelightfulVideo>cp_67b5aac969f26</DelightfulVideo>', '5'], $result);
    }

    public function testHtml()
    {
        $text = ['<title>管理', 'bb</title>'];
        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        foreach ($text as $current) {
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals(['<title', '>', '管', '理', 'b', 'b', '</titl', 'e', '>'], $result);
    }

    public function testMultibyteCharacters()
    {
        $text = ['Hello ', '👋 ', '世界', '<DelightfulImage>cp_67b5aac969f26</DelightfulImage>', '🌍'];
        $result = [];
        $processor = new DelightfulStreamTextProcessor(function (string $data) use (&$result) {
            $result[] = $data;
        });
        $processor->start();
        foreach ($text as $current) {
            $processor->process($current);
        }
        $processor->end();
        $this->assertEquals([
            'Hello ',
            '👋 ',
            '世界',
            '<DelightfulImage>cp_67b5aac969f26</DelightfulImage>',
            '🌍',
        ], $result);
    }
}
