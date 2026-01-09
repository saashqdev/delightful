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
        $content = '1.whenIstrollinthis园middle,Ideeplybodywillto,eachonelocationplaceallonce leftdownIfootprint,whilethattheseplacealsoallhavepassmother accompanied.<DelightfulCompressibleContent Type="Image">firstneedbecompressdata</DelightfulCompressibleContent> 2.ifbyonedaymetaphorfourseason,morningisspringday,middlenoonissummerday,duskisautumnday,nighttheniswinterday.\n!\n<DelightfulCompressibleContent Type="Video">thetwoneedbecompressdata</DelightfulCompressibleContent>3.toosuneacho clockeach刻allisfalldayand旭day,whenhesunseto clock,justishecrawlupmountainheadofo clock.whileI,finalwillcalmgroundwalkdownmountaingo.';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals($content, CompressibleContent::deCompress($compressibleContent));
    }

    public function testRun1()
    {
        $content = '1.whenIstrollinthis园middle,Ideeplybodywillto,eachonelocationplaceallonce leftdownIfootprint,whilethattheseplacealsoallhavepassmother accompanied.<DelightfulCompressibleContent Type="Image">firstneedbecompressdata</DelightfulCompressibleContent> 2.ifbyonedaymetaphorfourseason,morningisspringday,middlenoonissummerday,duskisautumnday,nighttheniswinterday.\n!\n<DelightfulCompressibleContent Type="Video">thetwoneedbecompressdata</DelightfulCompressibleContent>3.toosuneacho clockeach刻allisfalldayand旭day,whenhesunseto clock,justishecrawlupmountainheadofo clock.whileI,finalwillcalmgroundwalkdownmountaingo.';

        $compressibleContent = CompressibleContent::compress($content);
        $this->assertEquals('1.whenIstrollinthis园middle,Ideeplybodywillto,eachonelocationplaceallonce leftdownIfootprint,whilethattheseplacealsoallhavepassmother accompanied.firstneedbecompressdata 2.ifbyonedaymetaphorfourseason,morningisspringday,middlenoonissummerday,duskisautumnday,nighttheniswinterday.\n!\nthetwoneedbecompressdata3.toosuneacho clockeach刻allisfalldayand旭day,whenhesunseto clock,justishecrawlupmountainheadofo clock.whileI,finalwillcalmgroundwalkdownmountaingo.', CompressibleContent::deCompress($compressibleContent, false));
    }

    public function testRun2()
    {
        $content = '1.whenIstrollinthis园middle,Ideeplybodywillto,eachonelocationplaceallonce leftdownIfootprint,whilethattheseplacealsoallhavepassmother accompanied.<DELIGHTFUL-COMPRESSABLE-CONTENT TYPE="PICTURE">firstneedbecompressdata</DELIGHTFUL-COMPRESSABLE-CONTENT> 2.ifbyonedaymetaphorfourseason,morningisspringday,middlenoonissummerday,duskisautumnday,nighttheniswinterday.\n!\n<DELIGHTFUL-COMPRESSABLE-CONTENT TYPE="PICTURE">thetwoneedbecompressdata</DELIGHTFUL-COMPRESSABLE-CONTENT>3.toosuneacho clockeach刻allisfalldayand旭day,whenhesunseto clock,justishecrawlupmountainheadofo clock.whileI,finalwillcalmgroundwalkdownmountaingo.';

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
