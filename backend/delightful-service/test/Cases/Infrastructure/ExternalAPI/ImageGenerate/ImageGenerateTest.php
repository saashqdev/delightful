<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Infrastructure\ExternalAPI\ImageGenerate;

use App\Application\Mode\Service\ModeAppService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Service\ProviderConfigDomainService;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Flux\FluxModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\GPT\GPT4oModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Midjourney\MidjourneyModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\MiracleVision\MiracleVisionModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen\QwenImageModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Volcengine\VolcengineModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\FluxModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\GPT4oModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\MidjourneyModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\MiracleVisionModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\VolcengineModelRequest;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use HyperfTest\Cases\BaseTest;

/**
 * @internal
 */
class ImageGenerateTest extends BaseTest
{
    public static function isBase64Image(string $str): bool
    {
        $data = explode(',', $str);
        if (count($data) !== 2) {
            return false;
        }
        $header = $data[0];
        $imageData = $data[1];
        // checkhead部whetherconformBase64encodingimageformat
        if (! preg_match('/^data:image\/(png|jpeg|jpg|gif);base64$/', $header)) {
            return false;
        }
        // checkBase64encodingwhethervalid
        $decodedData = base64_decode($imageData);
        return $decodedData !== false;
    }

    public function testBase64Image()
    {
        $base64 = 'xx';
        $uploadDir = 'DT001/open/' . md5(StorageBucketType::Public->value);
        $uploadFile = new UploadFile($base64, $uploadDir, 'test');

        $fileDomainService = di(FileDomainService::class);
        // uploadfile（finger定notfrom动createdirectory）
        $fileDomainService->uploadByCredential('DT001', $uploadFile);

        // generatecanaccesslink
        $fileLink = $fileDomainService->getLink('DT001', $uploadFile->getKey(), StorageBucketType::Private);
        var_dump($fileLink);
    }

    // 转超清
    public function testImage2ImagePlus()
    {
        //        $this->markTestSkipped();

        // testneedskip
        $url = 'https://p9-aiop-sign.byteimg.com/tos-cn-i-vuqhorh59i/2025012317440606999C578B9234E9F5A4-0~tplv-vuqhorh59i-image.image?rk3s=7f9e702d&x-expires=1737711846&x-signature=5bkTf2E2xzRQVsDhrZZYghlJsUw%3D';
        $MiracleVisionModelRequest = new MiracleVisionModelRequest($url);
        $MiracleVisionModel = new MiracleVisionModel();
        $taskId = $MiracleVisionModel->imageConvertHigh($MiracleVisionModelRequest);
        $miracleVisionModelResponse = $MiracleVisionModel->queryTask($taskId);
        $index = 0;
        while (true) {
            if ($index > 60) {
                break;
            }
            if ($miracleVisionModelResponse->isFinishStatus()) {
                var_dump($miracleVisionModelResponse->getUrls());
                break;
            }
            ++$index;
            sleep(2);
        }
        $this->markTestSkipped();
    }

    public function testText2ImageByVolcengine()
    {
        $volcengineModelRequest = new VolcengineModelRequest();
        $volcengineModelRequest->setPrompt('摄影as品，trueperson写truestyle，one画ten thousand圣section装扮女personhandwithin拿one南瓜灯，thedesign冷color调and暖color调结合，冷color调and暖color调transitionfrom然，color调柔and，电影感，电影海报，高level感，16k，超detailed，UHD');
        $volcengineModelRequest->setGenerateNum(1);
        $volcengineModelRequest->setWidth('1024');
        $volcengineModelRequest->setHeight('1024');
        $volcengineModel = new VolcengineModel();
        $result = $volcengineModel->generateImage($volcengineModelRequest);
        var_dump($result);
        $this->markTestSkipped();
    }

    public function testText2ImageByFluix()
    {
        $FluxModelRequest = new FluxModelRequest();
        $FluxModelRequest->setPrompt('摄影as品，trueperson写truestyle，one画ten thousand圣section装扮女personhandwithin拿one南瓜灯，thedesign冷color调and暖color调结合，冷color调and暖color调transitionfrom然，color调柔and，电影感，电影海报，高level感，16k，超detailed，UHD');
        $FluxModelRequest->setGenerateNum(1);
        $FluxModelRequest->setWidth('1024');
        $FluxModelRequest->setHeight('1024');
        $FluxModel = new FluxModel();
        $result = $FluxModel->generateImage($FluxModelRequest);
        var_dump($result);
        $this->markTestSkipped();
    }

    public function testText2ImageByMJ()
    {
        $MjModelRequest = new MidjourneyModelRequest();
        $MjModelRequest->setPrompt('摄影as品，trueperson写truestyle，one画ten thousand圣section装扮女personhandwithin拿one南瓜灯，thedesign冷color调and暖color调结合，冷color调and暖color调transitionfrom然，color调柔and，电影感，电影海报，高level感，16k，超detailed，UHD');
        $MjModelRequest->setGenerateNum(1);
        $MjModelRequest->setModel('relax');
        $MjModel = new MidjourneyModel();
        $result = $MjModel->generateImage($MjModelRequest);
        var_dump($result);
        $this->markTestSkipped();
    }

    public function testText2ImageByGPT4o()
    {
        // createGPT4omodelinstance
        $gpt4oModel = new GPT4oModel();

        // createrequestinstance
        $gpt4oModelRequest = new GPT4oModelRequest();
        $gpt4oModelRequest->setPrompt('one只小金毛justin草原up欢快奔跑');
        $gpt4oModelRequest->setGenerateNum(4);

        // generateimage
        $result = $gpt4oModel->generateImage($gpt4oModelRequest);

        // verifyresult
        $this->assertNotEmpty($result);
        $this->assertEquals(ImageGenerateType::URL, $result->getImageGenerateType());
        $urls = $result->getData();
        $this->assertIsArray($urls);
        $this->assertCount(1, $urls);
        $this->assertNotEmpty($urls[0]);
        $this->assertStringStartsWith('http', $urls[0]);

        var_dump($result);
        $this->markTestSkipped();
    }

    public function testText2ImageByGPT4oWithReferenceImages()
    {
        // createGPT4omodelinstance
        $gpt4oModel = new GPT4oModel();

        // createrequestinstance
        $gpt4oModelRequest = new GPT4oModelRequest();
        $gpt4oModelRequest->setPrompt('adjustone群女巫handwithin捧南瓜in膜拜oneperson');
        $gpt4oModelRequest->setGenerateNum(1);

        // set参考image
        $gpt4oModelRequest->setReferImages([
            'https://cdn.ttapi.io/gpt/2025-04-01/0a4f0c65-c678-4e4d-a26c-ee7c50398f3f.png',
        ]);

        // generateimage
        $result = $gpt4oModel->generateImage($gpt4oModelRequest);

        // verifyresult
        $this->assertNotEmpty($result);
        $this->assertEquals(ImageGenerateType::URL, $result->getImageGenerateType());
        $urls = $result->getData();
        $this->assertIsArray($urls);
        $this->assertCount(1, $urls);
        $this->assertNotEmpty($urls[0]);
        $this->assertStringStartsWith('http', $urls[0]);

        var_dump($result);
        $this->markTestSkipped();
    }

    public function testText2ImageByQwenImage()
    {
        //        $di = di(ProviderConfigDomainService::class);
        //        $delightfulUserAuthorization = new DelightfulUserAuthorization();
        //        $delightfulUserAuthorization->setOrganizationCode('TGosRaFhvb');
        //
        //        $providerModelsByConfig = $di->getProviderConfig(ProviderDataIsolation::create('TGosRaFhvb'), '814826843393773568');
        //        $config = $providerModelsByConfig->getConfig();
        //        // createservice提供quotientconfiguration
        //
        //        // create通义thousand问modelinstance
        //        $qwenImageModel = new QwenImageModel($config);
        //
        //        // createrequestinstance
        //        $qwenImageRequest = new QwenImageModelRequest();
        //        $qwenImageRequest->setPrompt('one只can爱小猫咪inflower园within玩耍，阳光明媚，color彩丰富，高quality摄影');
        //        $qwenImageRequest->setHeight('1328');
        //        $qwenImageRequest->setWidth('1328');
        //        $qwenImageRequest->setGenerateNum(1);
        //        $qwenImageRequest->setModel('qwen-image');
        //
        //        // generateimage
        //        $result = $qwenImageModel->generateImage($qwenImageRequest);
        //
        //        // verifyresult
        //        $this->assertNotEmpty($result);
        //        $this->assertEquals(ImageGenerateType::URL, $result->getImageGenerateType());
        //        $urls = $result->getData();
        //        $this->assertIsArray($urls);
        //        $this->assertCount(1, $urls);
        //        $this->assertNotEmpty($urls[0]);
        //        $this->assertStringStartsWith('http', $urls[0]);
        //
        //        var_dump($result);
        //        $this->markTestSkipped();
    }

    public function testWatermark()
    {
        $di = di(ModeAppService::class);
        $delightfulUserAuthorization = new DelightfulUserAuthorization();
        $modeByIdentifier = $di->getModeByIdentifier($delightfulUserAuthorization, '94');
        var_dump($modeByIdentifier);
    }
}
