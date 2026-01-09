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
        $content = '1.whenI漫步inthis园middle，I才深刻bodywillto，eachone处placeall曾留downI足迹，whilethattheseplacealsoallhavepass母亲陪伴。<DelightfulCompressibleContent Type="Image">firstneedbecompressdata</DelightfulCompressibleContent> 2.若byoneday喻four季，早晨is春day，middle午is夏day，黄昏is秋day，夜晚thenis冬day。\n!\n<DelightfulCompressibleContent Type="Video">thetwoneedbecompressdata</DelightfulCompressibleContent>3.too阳eacho clockeach刻allis落dayand旭day，whenhe落山o clock，justishe爬up山head之o clock。whileI，终will沉静ground走down山go。';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals($content, CompressibleContent::deCompress($compressibleContent));
    }

    public function testRun1()
    {
        $content = '1.whenI漫步inthis园middle，I才深刻bodywillto，eachone处placeall曾留downI足迹，whilethattheseplacealsoallhavepass母亲陪伴。<DelightfulCompressibleContent Type="Image">firstneedbecompressdata</DelightfulCompressibleContent> 2.若byoneday喻four季，早晨is春day，middle午is夏day，黄昏is秋day，夜晚thenis冬day。\n!\n<DelightfulCompressibleContent Type="Video">thetwoneedbecompressdata</DelightfulCompressibleContent>3.too阳eacho clockeach刻allis落dayand旭day，whenhe落山o clock，justishe爬up山head之o clock。whileI，终will沉静ground走down山go。';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals('1.whenI漫步inthis园middle，I才深刻bodywillto，eachone处placeall曾留downI足迹，whilethattheseplacealsoallhavepass母亲陪伴。firstneedbecompressdata 2.若byoneday喻four季，早晨is春day，middle午is夏day，黄昏is秋day，夜晚thenis冬day。\n!\nthetwoneedbecompressdata3.too阳eacho clockeach刻allis落dayand旭day，whenhe落山o clock，justishe爬up山head之o clock。whileI，终will沉静ground走down山go。', CompressibleContent::deCompress($compressibleContent, false));
    }

    public function testRun2()
    {
        $content = '1.whenI漫步inthis园middle，I才深刻bodywillto，eachone处placeall曾留downI足迹，whilethattheseplacealsoallhavepass母亲陪伴。<DELIGHTFUL-COMPRESSABLE-CONTENT TYPE="PICTURE">firstneedbecompressdata</DELIGHTFUL-COMPRESSABLE-CONTENT> 2.若byoneday喻four季，早晨is春day，middle午is夏day，黄昏is秋day，夜晚thenis冬day。\n!\n<DELIGHTFUL-COMPRESSABLE-CONTENT TYPE="PICTURE">thetwoneedbecompressdata</DELIGHTFUL-COMPRESSABLE-CONTENT>3.too阳eacho clockeach刻allis落dayand旭day，whenhe落山o clock，justishe爬up山head之o clock。whileI，终will沉静ground走down山go。';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals($content, CompressibleContent::deCompress($compressibleContent));
    }

    public function testRun3()
    {
        $compressibleContent = '![today葵download](cp_678f7584c980a) ![image](cp_678f7584c9801)';

        $id = 'compressible_content_cp_678f7584c980a';
        di(CacheInterface::class)->set($id, 'compresslink1', 60);
        $id = 'compressible_content_cp_678f7584c9801';
        di(CacheInterface::class)->set($id, 'compresslink2', 60);

        $this->assertEquals('![today葵download](compresslink1) ![image](compresslink2)', CompressibleContent::deCompress($compressibleContent));
    }
}
