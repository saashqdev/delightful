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
# 主标题

这是第一段内容。

## 二级标题1

这是二级标题1下的内容。
这里有一些细节说明。

## 二级标题2

这是二级标题2下的内容。
这里有一些其他说明。

## 二级标题3

这是最后一段内容。
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirst块contain主标题和第一段内容
        $this->assertStringContainsString('# 主标题', $chunks[0]);
        $this->assertStringContainsString('这是第一段内容', $chunks[0]);

        // validate第二个块contain二级标题1及其内容
        $this->assertStringContainsString('## 二级标题1', $chunks[1]);
        $this->assertStringContainsString('这是二级标题1下的内容', $chunks[1]);

        // validate第三个块contain二级标题2及其内容
        $this->assertStringContainsString('## 二级标题2', $chunks[2]);
        $this->assertStringContainsString('这是二级标题2下的内容', $chunks[2]);

        // validate第四个块contain二级标题3及其内容
        $this->assertStringContainsString('## 二级标题3', $chunks[3]);
        $this->assertStringContainsString('这是最后一段内容', $chunks[3]);
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
** 主标题 **

这是第一段内容。

** 二级标题1 **

这是二级标题1下的内容。
这里有一些细节说明。

** 二级标题2 **

这是二级标题2下的内容。
这里有一些其他说明。

** 二级标题3 **

这是最后一段内容。
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirst块contain主标题和第一段内容
        $this->assertStringContainsString('** 主标题 **', $chunks[0]);
        $this->assertStringContainsString('这是第一段内容', $chunks[0]);

        // validate第二个块contain二级标题1及其内容
        $this->assertStringContainsString('** 二级标题1 **', $chunks[1]);
        $this->assertStringContainsString('这是二级标题1下的内容', $chunks[1]);

        // validate第三个块contain二级标题2及其内容
        $this->assertStringContainsString('** 二级标题2 **', $chunks[2]);
        $this->assertStringContainsString('这是二级标题2下的内容', $chunks[2]);

        // validate第四个块contain二级标题3及其内容
        $this->assertStringContainsString('** 二级标题3 **', $chunks[3]);
        $this->assertStringContainsString('这是最后一段内容', $chunks[3]);
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

        // validatetag内容被完整保留
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

        // validate所有tag内容都被完整保留
        $this->assertStringContainsString('第一段文本', $chunks[0]);
        $this->assertStringContainsString('第二段文本', $chunks[1]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>', $chunks[1]);
        $this->assertStringContainsString('第三段文本', $chunks[2]);
    }

    public function testTaggedContentWithChinese()
    {
        $text = <<<'EOT'
中文test
<DelightfulCompressibleContent Type="Image">中文路径/image.png</DelightfulCompressibleContent>
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

        // validate中文内容被correcthandle
        $this->assertStringContainsString('中文test', $chunks[0]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">中文路径/image.png</DelightfulCompressibleContent>', $chunks[0]);
        $this->assertStringContainsString('continuetest', $chunks[1]);
    }
}
