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
        $text = "这是the一segment。\n\n这是the二segment。\n\n这是the三segment。";
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

        $text = '这是the一segment。这是the二segment。这是the三segment。';
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

        $text = '这是the一segment。这是the二segment。这是the三segment。';
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertStringStartsWith('这是the一segment', $chunks[0]);
        $this->assertStringStartsWith('。这是the二segment', $chunks[1]);
    }

    public function testEncodingHandling()
    {
        $text = mb_convert_encoding("这是test文本。\n\n这是the二segment。", 'GBK', 'UTF-8');
        $chunks = $this->splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertEquals('UTF-8', mb_detect_encoding($chunks[0], 'UTF-8', true));
    }

    public function testLongTextSplitting()
    {
        $text = str_repeat('这是一testsentence子。', 100);
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
        $text = "这是the一segment。\n\n这是the二segment。";
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

这是the一segmentcontent。

## 二leveltitle1

这是二leveltitle1down的content。
这withinhave一些detailinstruction。

## 二leveltitle2

这是二leveltitle2down的content。
这withinhave一些其他instruction。

## 二leveltitle3

这是mostback一segmentcontent。
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirstpiececontain主title和the一segmentcontent
        $this->assertStringContainsString('# 主title', $chunks[0]);
        $this->assertStringContainsString('这是the一segmentcontent', $chunks[0]);

        // validatethe二piececontain二leveltitle1及其content
        $this->assertStringContainsString('## 二leveltitle1', $chunks[1]);
        $this->assertStringContainsString('这是二leveltitle1down的content', $chunks[1]);

        // validatethe三piececontain二leveltitle2及其content
        $this->assertStringContainsString('## 二leveltitle2', $chunks[2]);
        $this->assertStringContainsString('这是二leveltitle2down的content', $chunks[2]);

        // validatethe四piececontain二leveltitle3及其content
        $this->assertStringContainsString('## 二leveltitle3', $chunks[3]);
        $this->assertStringContainsString('这是mostback一segmentcontent', $chunks[3]);
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

这是the一segmentcontent。

** 二leveltitle1 **

这是二leveltitle1down的content。
这withinhave一些detailinstruction。

** 二leveltitle2 **

这是二leveltitle2down的content。
这withinhave一些其他instruction。

** 二leveltitle3 **

这是mostback一segmentcontent。
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirstpiececontain主title和the一segmentcontent
        $this->assertStringContainsString('** 主title **', $chunks[0]);
        $this->assertStringContainsString('这是the一segmentcontent', $chunks[0]);

        // validatethe二piececontain二leveltitle1及其content
        $this->assertStringContainsString('** 二leveltitle1 **', $chunks[1]);
        $this->assertStringContainsString('这是二leveltitle1down的content', $chunks[1]);

        // validatethe三piececontain二leveltitle2及其content
        $this->assertStringContainsString('** 二leveltitle2 **', $chunks[2]);
        $this->assertStringContainsString('这是二leveltitle2down的content', $chunks[2]);

        // validatethe四piececontain二leveltitle3及其content
        $this->assertStringContainsString('** 二leveltitle3 **', $chunks[3]);
        $this->assertStringContainsString('这是mostback一segmentcontent', $chunks[3]);
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
the一segment文本
<DelightfulCompressibleContent Type="Image">image1.png</DelightfulCompressibleContent>
the二segment文本
<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>
the三segment文本
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
        $this->assertStringContainsString('the一segment文本', $chunks[0]);
        $this->assertStringContainsString('the二segment文本', $chunks[1]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>', $chunks[1]);
        $this->assertStringContainsString('the三segment文本', $chunks[2]);
    }

    public function testTaggedContentWithChinese()
    {
        $text = <<<'EOT'
middle文test
<DelightfulCompressibleContent Type="Image">middle文path/image.png</DelightfulCompressibleContent>
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

        // validatemiddle文contentbecorrecthandle
        $this->assertStringContainsString('middle文test', $chunks[0]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">middle文path/image.png</DelightfulCompressibleContent>', $chunks[0]);
        $this->assertStringContainsString('continuetest', $chunks[1]);
    }
}
