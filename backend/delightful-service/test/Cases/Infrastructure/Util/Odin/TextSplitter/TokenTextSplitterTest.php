<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Infrastructure\Util\Odin\TextSplitter;

use App\Infrastructure\Util\Odin\TextSplitter\TokenTextSplitter;
use HyperfTest\Cases\BaseTest;

/**
 * @internal
 */
class TokenTextSplitterTest extends BaseTest
{
    private TokenTextSplitter $splitter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->splitter = new TokenTextSplitter();
    }

    public function testBasicTextSplitting()
    {
        $text = "这是第一段。\n\n这是第二段。\n\n这是第三段。";
        $chunks = $this->splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(3, $chunks);
    }

    public function testCustomSeparator()
    {
        $splitter = new TokenTextSplitter(
            null,
            1000,
            200,
            '。',
            ['。', '，', ' ']
        );

        $text = '这是第一段。这是第二段。这是第三段。';
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
    }

    public function testPreserveSeparator()
    {
        $splitter = new TokenTextSplitter(
            null,
            1000,
            200,
            '。',
            ['。', '，', ' '],
            false,
            true
        );

        $text = '这是第一段。这是第二段。这是第三段。';
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertStringStartsWith('这是第一段', $chunks[0]);
        $this->assertStringStartsWith('。这是第二段', $chunks[1]);
    }

    public function testEncodingHandling()
    {
        $text = mb_convert_encoding("这是test文本。\n\n这是第二段。", 'GBK', 'UTF-8');
        $chunks = $this->splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertEquals('UTF-8', mb_detect_encoding($chunks[0], 'UTF-8', true));
    }

    public function testLongTextSplitting()
    {
        $text = str_repeat('这是一个test句子。', 100);
        $chunks = $this->splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(1000, strlen($chunk));
        }
    }

    public function testCustomTokenizer()
    {
        $customTokenizer = function (string $text) {
            return strlen($text);
        };

        $splitter = new TokenTextSplitter($customTokenizer);
        $text = "这是第一段。\n\n这是第二段。";
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
    }

    public function testMarkdownSplitting1()
    {
        $splitter = new TokenTextSplitter(
            null,
            1000,
            200,
            "\n\n##",
            ["\n\n##", "\n##", "\n\n", "\n", '。', ' ', ''],
            preserveSeparator: true
        );

        $text = <<<'EOT'
# 主title

这是第一段content。

## 二级title1

这是二级title1下的content。
这里have一些detailinstruction。

## 二级title2

这是二级title2下的content。
这里have一些其他instruction。

## 二级title3

这是most后一段content。
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirst块contain主title和第一段content
        $this->assertStringContainsString('# 主title', $chunks[0]);
        $this->assertStringContainsString('这是第一段content', $chunks[0]);

        // validate第二个块contain二级title1及其content
        $this->assertStringContainsString('## 二级title1', $chunks[1]);
        $this->assertStringContainsString('这是二级title1下的content', $chunks[1]);

        // validate第三个块contain二级title2及其content
        $this->assertStringContainsString('## 二级title2', $chunks[2]);
        $this->assertStringContainsString('这是二级title2下的content', $chunks[2]);

        // validate第四个块contain二级title3及其content
        $this->assertStringContainsString('## 二级title3', $chunks[3]);
        $this->assertStringContainsString('这是most后一段content', $chunks[3]);
    }

    public function testMarkdownSplitting2()
    {
        $splitter = new TokenTextSplitter(
            null,
            1000,
            200,
            "\n\n**",
            preserveSeparator: true
        );

        $text = <<<'EOT'
** 主title **

这是第一段content。

** 二级title1 **

这是二级title1下的content。
这里have一些detailinstruction。

** 二级title2 **

这是二级title2下的content。
这里have一些其他instruction。

** 二级title3 **

这是most后一段content。
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirst块contain主title和第一段content
        $this->assertStringContainsString('** 主title **', $chunks[0]);
        $this->assertStringContainsString('这是第一段content', $chunks[0]);

        // validate第二个块contain二级title1及其content
        $this->assertStringContainsString('** 二级title1 **', $chunks[1]);
        $this->assertStringContainsString('这是二级title1下的content', $chunks[1]);

        // validate第三个块contain二级title2及其content
        $this->assertStringContainsString('** 二级title2 **', $chunks[2]);
        $this->assertStringContainsString('这是二级title2下的content', $chunks[2]);

        // validate第四个块contain二级title3及其content
        $this->assertStringContainsString('** 二级title3 **', $chunks[3]);
        $this->assertStringContainsString('这是most后一段content', $chunks[3]);
    }

    public function testTaggedContentProtection()
    {
        $text = <<<'EOT'
testword
<DelightfulCompressibleContent Type="Image">delightful_file_org/open/2c17c6393771ee3048ae34d6b380c5ec/682ea88b4a2b5.png</DelightfulCompressibleContent>
testcache
EOT;

        $splitter = new TokenTextSplitter(
            null,
            6,
            0,
            "\n\n",
            preserveSeparator: true
        );
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        // validatetagcontentbe完整保留
        $this->assertStringContainsString('testword', $chunks[0]);
        $this->assertStringContainsString('<DelightfulCompressibleContent', $chunks[0]);
        $this->assertStringContainsString('</DelightfulCompressibleContent>', $chunks[0]);
        $this->assertStringContainsString('testcache', $chunks[1]);
    }

    public function testMultipleTaggedContent()
    {
        $text = <<<'EOT'
第一段文本
<DelightfulCompressibleContent Type="Image">image1.png</DelightfulCompressibleContent>
第二段文本
<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>
第三段文本
EOT;

        $splitter = new TokenTextSplitter(
            null,
            10,
            0,
            "\n\n",
            preserveSeparator: true
        );
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        // validate所havetagcontentallbe完整保留
        $this->assertStringContainsString('第一段文本', $chunks[0]);
        $this->assertStringContainsString('第二段文本', $chunks[1]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>', $chunks[1]);
        $this->assertStringContainsString('第三段文本', $chunks[2]);
    }

    public function testTaggedContentWithChinese()
    {
        $text = <<<'EOT'
中文test
<DelightfulCompressibleContent Type="Image">中文path/image.png</DelightfulCompressibleContent>
continuetest
EOT;

        $splitter = new TokenTextSplitter(
            null,
            10,
            0,
            "\n\n",
            preserveSeparator: true
        );
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(2, $chunks);

        // validate中文contentbecorrecthandle
        $this->assertStringContainsString('中文test', $chunks[0]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">中文path/image.png</DelightfulCompressibleContent>', $chunks[0]);
        $this->assertStringContainsString('continuetest', $chunks[1]);
    }
}
