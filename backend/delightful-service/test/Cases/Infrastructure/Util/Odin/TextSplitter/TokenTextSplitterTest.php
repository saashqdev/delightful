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
        $text = "thisistheonesegment.\n\nthisisthetwosegment.\n\nthisisthethreesegment.";
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
            '.',
            ['.', ',', ' ']
        );

        $text = 'thisistheonesegment.thisisthetwosegment.thisisthethreesegment.';
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
            '.',
            ['.', ',', ' '],
            false,
            true
        );

        $text = 'thisistheonesegment.thisisthetwosegment.thisisthethreesegment.';
        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertStringStartsWith('thisistheonesegment', $chunks[0]);
        $this->assertStringStartsWith('.thisisthetwosegment', $chunks[1]);
    }

    public function testEncodingHandling()
    {
        $text = mb_convert_encoding("thisistesttext.\n\nthisisthetwosegment.", 'GBK', 'UTF-8');
        $chunks = $this->splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertEquals('UTF-8', mb_detect_encoding($chunks[0], 'UTF-8', true));
    }

    public function testLongTextSplitting()
    {
        $text = str_repeat('thisisonetestsentence子.', 100);
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
        $text = "thisistheonesegment.\n\nthisisthetwosegment.";
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
            ["\n\n##", "\n##", "\n\n", "\n", '.', ' ', ''],
            preserveSeparator: true
        );

        $text = <<<'EOT'
# 主title

thisistheonesegmentcontent.

## twoleveltitle1

thisistwoleveltitle1downcontent.
thiswithinhaveonethesedetailinstruction.

## twoleveltitle2

thisistwoleveltitle2downcontent.
thiswithinhaveonetheseotherinstruction.

## twoleveltitle3

thisismostbackonesegmentcontent.
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirstpiececontain主titleandtheonesegmentcontent
        $this->assertStringContainsString('# 主title', $chunks[0]);
        $this->assertStringContainsString('thisistheonesegmentcontent', $chunks[0]);

        // validatethetwopiececontaintwoleveltitle1anditscontent
        $this->assertStringContainsString('## twoleveltitle1', $chunks[1]);
        $this->assertStringContainsString('thisistwoleveltitle1downcontent', $chunks[1]);

        // validatethethreepiececontaintwoleveltitle2anditscontent
        $this->assertStringContainsString('## twoleveltitle2', $chunks[2]);
        $this->assertStringContainsString('thisistwoleveltitle2downcontent', $chunks[2]);

        // validatethefourpiececontaintwoleveltitle3anditscontent
        $this->assertStringContainsString('## twoleveltitle3', $chunks[3]);
        $this->assertStringContainsString('thisismostbackonesegmentcontent', $chunks[3]);
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

thisistheonesegmentcontent.

** twoleveltitle1 **

thisistwoleveltitle1downcontent.
thiswithinhaveonethesedetailinstruction.

** twoleveltitle2 **

thisistwoleveltitle2downcontent.
thiswithinhaveonetheseotherinstruction.

** twoleveltitle3 **

thisismostbackonesegmentcontent.
EOT;

        $chunks = $splitter->splitText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertCount(4, $chunks);

        // validatefirstpiececontain主titleandtheonesegmentcontent
        $this->assertStringContainsString('** 主title **', $chunks[0]);
        $this->assertStringContainsString('thisistheonesegmentcontent', $chunks[0]);

        // validatethetwopiececontaintwoleveltitle1anditscontent
        $this->assertStringContainsString('** twoleveltitle1 **', $chunks[1]);
        $this->assertStringContainsString('thisistwoleveltitle1downcontent', $chunks[1]);

        // validatethethreepiececontaintwoleveltitle2anditscontent
        $this->assertStringContainsString('** twoleveltitle2 **', $chunks[2]);
        $this->assertStringContainsString('thisistwoleveltitle2downcontent', $chunks[2]);

        // validatethefourpiececontaintwoleveltitle3anditscontent
        $this->assertStringContainsString('** twoleveltitle3 **', $chunks[3]);
        $this->assertStringContainsString('thisismostbackonesegmentcontent', $chunks[3]);
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

        // validatetagcontentbecompleteretain
        $this->assertStringContainsString('testword', $chunks[0]);
        $this->assertStringContainsString('<DelightfulCompressibleContent', $chunks[0]);
        $this->assertStringContainsString('</DelightfulCompressibleContent>', $chunks[0]);
        $this->assertStringContainsString('testcache', $chunks[1]);
    }

    public function testMultipleTaggedContent()
    {
        $text = <<<'EOT'
theonesegmenttext
<DelightfulCompressibleContent Type="Image">image1.png</DelightfulCompressibleContent>
thetwosegmenttext
<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>
thethreesegmenttext
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

        // validate所havetagcontentallbecompleteretain
        $this->assertStringContainsString('theonesegmenttext', $chunks[0]);
        $this->assertStringContainsString('thetwosegmenttext', $chunks[1]);
        $this->assertStringContainsString('<DelightfulCompressibleContent Type="Image">image2.png</DelightfulCompressibleContent>', $chunks[1]);
        $this->assertStringContainsString('thethreesegmenttext', $chunks[2]);
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
