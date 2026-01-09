<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\Compressible;

use App\Application\Flow\ExecuteManager\Compressible\CompressibleContent;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;
use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
class CompressibleContentTest extends ExecuteManagerBaseTest
{
    public function testRun()
    {
        $content = '1.when我漫步in这园middle，我才深刻bodywillto，each一处placeall曾留down我的足迹，而那些placealsoallhave过母亲的陪伴。<DelightfulCompressibleContent Type="Image">firstneedbecompress的data</DelightfulCompressibleContent> 2.若by一day喻四季，早晨是春day，middle午是夏day，黄昏是秋day，夜晚then是冬day。\n!\n<DelightfulCompressibleContent Type="Video">the二needbecompress的data</DelightfulCompressibleContent>3.too阳eacho clockeach刻all是落day与旭day，when他落山o clock，正是他爬up山head之o clock。而我，终将沉静ground走down山去。';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals($content, CompressibleContent::deCompress($compressibleContent));
    }

    public function testRun1()
    {
        $content = '1.when我漫步in这园middle，我才深刻bodywillto，each一处placeall曾留down我的足迹，而那些placealsoallhave过母亲的陪伴。<DelightfulCompressibleContent Type="Image">firstneedbecompress的data</DelightfulCompressibleContent> 2.若by一day喻四季，早晨是春day，middle午是夏day，黄昏是秋day，夜晚then是冬day。\n!\n<DelightfulCompressibleContent Type="Video">the二needbecompress的data</DelightfulCompressibleContent>3.too阳eacho clockeach刻all是落day与旭day，when他落山o clock，正是他爬up山head之o clock。而我，终将沉静ground走down山去。';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals('1.when我漫步in这园middle，我才深刻bodywillto，each一处placeall曾留down我的足迹，而那些placealsoallhave过母亲的陪伴。firstneedbecompress的data 2.若by一day喻四季，早晨是春day，middle午是夏day，黄昏是秋day，夜晚then是冬day。\n!\nthe二needbecompress的data3.too阳eacho clockeach刻all是落day与旭day，when他落山o clock，正是他爬up山head之o clock。而我，终将沉静ground走down山去。', CompressibleContent::deCompress($compressibleContent, false));
    }

    public function testRun2()
    {
        $content = '1.when我漫步in这园middle，我才深刻bodywillto，each一处placeall曾留down我的足迹，而那些placealsoallhave过母亲的陪伴。<DELIGHTFUL-COMPRESSABLE-CONTENT TYPE="PICTURE">firstneedbecompress的data</DELIGHTFUL-COMPRESSABLE-CONTENT> 2.若by一day喻四季，早晨是春day，middle午是夏day，黄昏是秋day，夜晚then是冬day。\n!\n<DELIGHTFUL-COMPRESSABLE-CONTENT TYPE="PICTURE">the二needbecompress的data</DELIGHTFUL-COMPRESSABLE-CONTENT>3.too阳eacho clockeach刻all是落day与旭day，when他落山o clock，正是他爬up山head之o clock。而我，终将沉静ground走down山去。';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals($content, CompressibleContent::deCompress($compressibleContent));
    }

    public function testRun3()
    {
        $compressibleContent = '![today葵download](cp_678f7584c980a) ![image](cp_678f7584c9801)';

        $id = 'compressible_content_cp_678f7584c980a';
        di(CacheInterface::class)->set($id, 'compress的link1', 60);
        $id = 'compressible_content_cp_678f7584c9801';
        di(CacheInterface::class)->set($id, 'compress的link2', 60);

        $this->assertEquals('![today葵download](compress的link1) ![image](compress的link2)', CompressibleContent::deCompress($compressibleContent));
    }
}
