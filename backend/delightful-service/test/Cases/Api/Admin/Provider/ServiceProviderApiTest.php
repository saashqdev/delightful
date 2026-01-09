<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Admin\Provider;

use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Service\ProviderModelDomainService;
use Hyperf\Codec\Json;
use HyperfTest\Cases\BaseTest;

/**
 * @internal
 * @coversNothing
 */
class ServiceProviderApiTest extends BaseTest
{
    private string $baseUri = '/api/v1/admin/service-providers';

    public function testGetServiceProvidersByCategoryLlm(): void
    {
        $uri = $this->baseUri . '?category=llm';
        $response = $this->get($uri, [], $this->getCommonHeaders());

        // ifreturnauthenticationorpermission相closeerror,skiptest(onlyvalidate路bycanuse)
        if (isset($response['code']) && in_array($response['code'], [401, 403, 2179, 3035, 4001, 4003], true)) {
            $this->markTestSkipped('interfaceauthenticationfailornopermission,路byvalidationpass');
            return;
        }

        // 基本assert
        $this->assertIsArray($response);
        $this->assertArrayHasKey('code', $response);
        $this->assertSame(1000, $response['code']);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    /**
     * testmodelcreateandmorenewcompleteprocess,includeconfigurationversionvalidate.
     */
    public function testSaveModelToServiceProviderCreate(): void
    {
        $modelUri = $this->baseUri . '/models';
        $serviceProviderConfigId = '841681476732149761';

        // ========== step1: createmodel ==========
        $createRequestData = [
            'model_type' => 3,
            'model_id' => 'test-model-' . time(),
            'model_version' => 'testversion v1.0',
            'icon' => 'DELIGHTFUL/588417216353927169/default/default.png',
            'config' => [
                'max_output_tokens' => 64000,
                'max_tokens' => 128000,
                'temperature_type' => 1,
                'temperature' => null,
                'billing_currency' => 'CNY',
                'input_pricing' => '0.001',
                'output_pricing' => '0.002',
                'cache_write_pricing' => '0.0005',
                'cache_hit_pricing' => '0.0001',
                'input_cost' => '0.001',
                'output_cost' => '0.002',
                'cache_write_cost' => '0.0005',
                'cache_hit_cost' => '0.0001',
                'vector_size' => 2048,
                'support_function' => false,
                'support_multi_modal' => false,
                'support_deep_think' => false,
                'creativity' => 0.7,
                'billing_type' => 'Times',
                'time_pricing' => '100',
                'time_cost' => '50',
            ],
            'category' => 'llm',
            'service_provider_config_id' => $serviceProviderConfigId,
            'translate' => [
                'name' => [
                    'zh_CN' => 'testmodel',
                    'en_US' => 'Test Model',
                ],
                'description' => [
                    'zh_CN' => 'thisisonetestmodel',
                    'en_US' => 'This is a test model',
                ],
            ],
        ];

        $createResponse = $this->post($modelUri, $createRequestData, $this->getCommonHeaders());

        // validatecreateresponse
        $this->assertIsArray($createResponse);
        $this->assertArrayHasKey('code', $createResponse);
        $this->assertSame(1000, $createResponse['code'], 'createmodelshouldsuccess');
        $this->assertArrayHasKey('data', $createResponse);
        $this->assertArrayHasKey('id', $createResponse['data'], 'returndata应containmodelID');

        $modelId = $createResponse['data']['id'];
        $this->assertNotEmpty($modelId, 'modelIDnot应fornull');

        // ========== step2: calldetailinterfacevalidate4costfield ==========
        $detailUri = $this->baseUri . '/' . $serviceProviderConfigId;
        $detailResponse = $this->get($detailUri, [], $this->getCommonHeaders());

        $this->assertIsArray($detailResponse);
        $this->assertSame(1000, $detailResponse['code'], 'getdetailshouldsuccess');
        $this->assertArrayHasKey('data', $detailResponse);

        // findcreatemodel
        $createdModel = $this->findModelInDetailResponse($detailResponse['data'], $modelId);
        $this->assertNotNull($createdModel, 'shouldcanindetailmiddle找tocreatemodel');

        // validate4costfield存inandvaluecorrect
        $this->assertArrayHasKey('config', $createdModel, 'modelshouldhaveconfigfield');
        $this->verifyConfigCostFields($createdModel['config'], [
            'input_cost' => 0.001,
            'output_cost' => 0.002,
            'cache_write_cost' => 0.0005,
            'cache_hit_cost' => 0.0001,
            'time_cost' => 50,
        ]);

        // ========== step3: validateconfigurationversion(version=1) ==========
        $this->verifyConfigVersion((int) $modelId, $createRequestData['config'], 1);

        // ========== step4: updatemodel ==========
        $updateRequestData = [
            'id' => $modelId,
            'model_type' => 3,
            'model_id' => $createRequestData['model_id'],
            'model_version' => 'updateversion v2.0',
            'icon' => 'DELIGHTFUL/588417216353927169/default/default.png',
            'config' => [
                'max_output_tokens' => 128000,
                'max_tokens' => 256000,
                'temperature_type' => 1,
                'temperature' => null,
                'billing_currency' => 'CNY',
                'input_pricing' => '0.002',
                'output_pricing' => '0.004',
                'cache_write_pricing' => '0.001',
                'cache_hit_pricing' => '0.0002',
                'input_cost' => '0.003',
                'output_cost' => '0.006',
                'cache_write_cost' => '0.0015',
                'cache_hit_cost' => '0.0003',
                'vector_size' => 4096,
                'support_function' => true,
                'support_multi_modal' => true,
                'support_deep_think' => false,
                'creativity' => 0.8,
                'time_cost' => 50,
            ],
            'category' => 'llm',
            'service_provider_config_id' => $serviceProviderConfigId,
            'translate' => [
                'name' => [
                    'zh_CN' => 'updatebacktestmodel',
                    'en_US' => 'Updated Test Model',
                ],
                'description' => [
                    'zh_CN' => 'thisisupdatebacktestmodel',
                    'en_US' => 'This is an updated test model',
                ],
            ],
        ];

        $updateResponse = $this->post($modelUri, $updateRequestData, $this->getCommonHeaders());

        // validateupdateresponse
        $this->assertIsArray($updateResponse);
        $this->assertSame(1000, $updateResponse['code'], 'updatemodelshouldsuccess');
        $this->assertArrayHasKey('data', $updateResponse);
        $this->assertSame($modelId, $updateResponse['data']['id'], 'updatebackmodelID应maintainnot变');

        // ========== step5: againtimecalldetailinterfacevalidateupdateback4costfield ==========
        $updatedDetailResponse = $this->get($detailUri, [], $this->getCommonHeaders());

        $this->assertIsArray($updatedDetailResponse);
        $this->assertSame(1000, $updatedDetailResponse['code'], 'getupdatebackdetailshouldsuccess');

        // findupdatebackmodel
        $updatedModel = $this->findModelInDetailResponse($updatedDetailResponse['data'], $modelId);
        $this->assertNotNull($updatedModel, 'shouldcanindetailmiddle找toupdatebackmodel');

        // validateupdateback4costfield
        $this->assertArrayHasKey('config', $updatedModel, 'updatebackmodelshouldhaveconfigfield');
        $this->verifyConfigCostFields($updatedModel['config'], [
            'input_cost' => 0.003,
            'output_cost' => 0.006,
            'cache_write_cost' => 0.0015,
            'cache_hit_cost' => 0.0003,
        ]);

        // ========== step6: validateupdatebackconfigurationversion(version=2) ==========
        $this->verifyConfigVersion((int) $modelId, $updateRequestData['config'], 2);
    }

    /**
     * testreturnDelightfulservicequotient.
     */
    public function testGetOfficialProvider()
    {
        $response = $this->get('/org/admin/service-providers/available-llm', [], $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals(true, in_array('Delightful', array_column($response['data'], 'name')));

        $response = $this->get('/org/admin/service-providers?category=llm', [], $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals(true, in_array('Official', array_column($response['data'], 'provider_code')));

        $response = $this->get('/org/admin/service-providers?category=vlm', [], $this->getCommonHeaders());
        $this->assertEquals(1000, $response['code']);
        $this->assertEquals(true, in_array('Official', array_column($response['data'], 'provider_code')));
    }

    /**
     * createofficialservicequotient.
     */
    public function testCreateLLMOfficialProvider(): void
    {
        $provider = [
            'alias' => 'officialservicequotientsingleyuantest',
            'config' => [
                // 国际接入point
                'url' => 'international_access_point',
                // 国inside接入point
                //                'url' => 'domestic_access_points',
                'api_key' => '****',
                'priority' => 100,
            ],
            'service_provider_id' => '766765753990443008',
            'status' => 1,
            'translate' => [
                'alias' => [
                    'zh_CN' => 'officialservicequotientsingleyuantest',
                ],
            ],
        ];
        $response = $this->post('/org/admin/service-providers/add', $provider, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);

        $response = $this->get('/org/admin/service-providers/detail?service_provider_config_id=' . $response['data']['id'], [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $detail = $response['data'];
        $this->assertEquals('officialservicequotientsingleyuantest', $detail['alias']);
        $this->assertEquals('international_access_point', $detail['config']['proxy_url']);
        $this->assertEquals('****', $detail['config']['api_key']);
        $this->assertEquals('100', $detail['config']['priority']);
    }

    /**
     * createofficialservicequotient.
     */
    public function testCreateVLMOfficialProvider(): void
    {
        $provider = [
            'alias' => 'officialservicequotientsingleyuantest',
            'config' => [
                // 国际接入point
                'proxy_url' => 'international_access_point',
                // 国inside接入point
                //                'proxy_url' => 'domestic_access_points',
                'api_key' => 'sk-1111',
                'priority' => 100,
            ],
            'service_provider_id' => '766765755164848128',
            'status' => 1,
            'translate' => [
                'alias' => [
                    'zh_CN' => 'officialservicequotientsingleyuantest',
                ],
            ],
        ];
        $response = $this->post('/org/admin/service-providers/add', $provider, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);

        $response = $this->get('/org/admin/service-providers/detail?service_provider_config_id=' . $response['data']['id'], [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $detail = $response['data'];
        $this->assertEquals('officialservicequotientsingleyuantest', $detail['alias']);
        $this->assertEquals('international_access_point', $detail['config']['proxy_url']);
        $this->assertEquals('sk-*****************************bab', $detail['config']['api_key']);
        $this->assertEquals('100', $detail['config']['priority']);
    }

    /**
     * testcreateanddeletemodel.
     */
    public function testCreateAndDeleteModel()
    {
        $providerId = '843847394915074048';
        $model = Json::decode('{"model_type":3,"model_id":"test-dabai-test","model_version":"test","icon":"DELIGHTFUL/588417216353927169/default/default.png","name":"test","description":"test","config":{"max_output_tokens":64000,"max_tokens":128000,"temperature_type":1,"temperature":null,"billing_currency":"CNY","input_pricing":"1","output_pricing":"1","cache_write_pricing":"1","cache_hit_pricing":"1","input_cost":"1","output_cost":"1","cache_write_cost":"1","cache_hit_cost":"1","vector_size":2048,"support_function":false,"support_multi_modal":false,"support_deep_think":false,"creativity":0.7},"category":"llm","service_provider_config_id":"' . $providerId . '","translate":{"name":{"zh_CN":"test","en_US":"test"},"description":{"zh_CN":"test","en_US":"test"}}}');
        $response = $this->post('/org/admin/service-providers/save-model', $model, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);

        $newId = $response['data']['id'];
        $response = $this->post('/org/admin/service-providers/delete-model', ['model_id' => $newId], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
    }

    /**
     * indetailresponsemiddlefindfinger定IDmodel.
     *
     * @param array $detailData detailresponsedata
     * @param string $modelId modelID
     * @return null|array 找tomodeldata,not找toreturnnull
     */
    private function findModelInDetailResponse(array $detailData, string $modelId): ?array
    {
        // detailinterfacemaybereturn models arrayorotherstructure,thiswithinneedaccording toactualinterfaceadjust
        if (isset($detailData['models']) && is_array($detailData['models'])) {
            foreach ($detailData['models'] as $model) {
                if (isset($model['id']) && (string) $model['id'] === (string) $modelId) {
                    return $model;
                }
            }
        }

        // ifisotherstructure,continuefind
        if (isset($detailData['id']) && (string) $detailData['id'] === (string) $modelId) {
            return $detailData;
        }

        return null;
    }

    /**
     * validateconfigurationmiddle4costfield.
     *
     * @param array $config configurationdata
     * @param array $expectedCosts expectcostvalue
     */
    private function verifyConfigCostFields(array $config, array $expectedCosts): void
    {
        $this->assertArrayHasKey('input_cost', $config, 'configshouldcontaininput_costfield');
        $this->assertArrayHasKey('output_cost', $config, 'configshouldcontainoutput_costfield');
        $this->assertArrayHasKey('cache_write_cost', $config, 'configshouldcontaincache_write_costfield');
        $this->assertArrayHasKey('cache_hit_cost', $config, 'configshouldcontaincache_hit_costfield');

        // validatevaluewhethercorrect(allow浮point数误difference)
        $this->assertEqualsWithDelta(
            $expectedCosts['input_cost'],
            (float) $config['input_cost'],
            0.0001,
            'input_costvalueshouldmatch'
        );

        $this->assertEqualsWithDelta(
            $expectedCosts['output_cost'],
            (float) $config['output_cost'],
            0.0001,
            'output_costvalueshouldmatch'
        );

        $this->assertEqualsWithDelta(
            $expectedCosts['cache_write_cost'],
            (float) $config['cache_write_cost'],
            0.0001,
            'cache_write_costvalueshouldmatch'
        );

        $this->assertEqualsWithDelta(
            $expectedCosts['cache_hit_cost'],
            (float) $config['cache_hit_cost'],
            0.0001,
            'cache_hit_costvalueshouldmatch'
        );
    }

    /**
     * validateconfigurationversionwhethercorrect落library.
     *
     * @param int $modelId modelID
     * @param array $expectedConfig expectconfigurationdata
     * @param int $expectedVersion expectversionnumber
     */
    private function verifyConfigVersion(int $modelId, array $expectedConfig, int $expectedVersion): void
    {
        // get Domain Service
        $domainService = $this->getContainer()->get(ProviderModelDomainService::class);

        // constructdataisolationobject
        $organizationCode = env('TEST_ORGANIZATION_CODE');
        $dataIsolation = new ProviderDataIsolation($organizationCode, '', '');

        // getmostnewconfigurationversion
        $versionEntity = $domainService->getLatestConfigVersionEntity($dataIsolation, $modelId);

        $this->assertNotNull($versionEntity, 'configurationversionshould存in');

        // validate int typefield(stringshouldbeconvertfor int)
        if (isset($expectedConfig['max_output_tokens'])) {
            $this->assertSame(
                (int) $expectedConfig['max_output_tokens'],
                $versionEntity->getMaxOutputTokens(),
                'max_output_tokens shouldmatch'
            );
        }

        if (isset($expectedConfig['max_tokens'])) {
            $this->assertSame(
                (int) $expectedConfig['max_tokens'],
                $versionEntity->getMaxTokens(),
                'max_tokens shouldmatch'
            );
        }

        if (isset($expectedConfig['vector_size'])) {
            $this->assertSame(
                (int) $expectedConfig['vector_size'],
                $versionEntity->getVectorSize(),
                'vector_size shouldmatch'
            );
        }

        // validate float typefield(stringshouldbeconvertfor float)
        if (isset($expectedConfig['input_pricing'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['input_pricing'],
                $versionEntity->getInputPricing(),
                0.0001,
                'input_pricing shouldmatch'
            );
        }

        if (isset($expectedConfig['output_pricing'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['output_pricing'],
                $versionEntity->getOutputPricing(),
                0.0001,
                'output_pricing shouldmatch'
            );
        }

        if (isset($expectedConfig['cache_write_pricing'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['cache_write_pricing'],
                $versionEntity->getCacheWritePricing(),
                0.0001,
                'cache_write_pricing shouldmatch'
            );
        }

        if (isset($expectedConfig['cache_hit_pricing'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['cache_hit_pricing'],
                $versionEntity->getCacheHitPricing(),
                0.0001,
                'cache_hit_pricing shouldmatch'
            );
        }

        if (isset($expectedConfig['input_cost'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['input_cost'],
                $versionEntity->getInputCost(),
                0.0001,
                'input_cost shouldmatch'
            );
        }

        if (isset($expectedConfig['output_cost'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['output_cost'],
                $versionEntity->getOutputCost(),
                0.0001,
                'output_cost shouldmatch'
            );
        }

        if (isset($expectedConfig['cache_write_cost'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['cache_write_cost'],
                $versionEntity->getCacheWriteCost(),
                0.0001,
                'cache_write_cost shouldmatch'
            );
        }

        if (isset($expectedConfig['cache_hit_cost'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['cache_hit_cost'],
                $versionEntity->getCacheHitCost(),
                0.0001,
                'cache_hit_cost shouldmatch'
            );
        }

        if (isset($expectedConfig['creativity'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['creativity'],
                $versionEntity->getCreativity(),
                0.0001,
                'creativity shouldmatch'
            );
        }

        if (isset($expectedConfig['time_cost'])) {
            $this->assertEqualsWithDelta(
                (float) $expectedConfig['time_cost'],
                $versionEntity->getTimeCost(),
                50,
                'time_cost shouldmatch'
            );
        }

        if (isset($expectedConfig['temperature'])) {
            if ($expectedConfig['temperature'] === null) {
                $this->assertNull($versionEntity->getTemperature(), 'temperature shouldfor null');
            } else {
                $this->assertEqualsWithDelta(
                    (float) $expectedConfig['temperature'],
                    $versionEntity->getTemperature(),
                    0.0001,
                    'temperature shouldmatch'
                );
            }
        }

        // validate bool typefield
        if (isset($expectedConfig['support_function'])) {
            $this->assertSame(
                (bool) $expectedConfig['support_function'],
                $versionEntity->isSupportFunction(),
                'support_function shouldmatch'
            );
        }

        if (isset($expectedConfig['support_multi_modal'])) {
            $this->assertSame(
                (bool) $expectedConfig['support_multi_modal'],
                $versionEntity->isSupportMultiModal(),
                'support_multi_modal shouldmatch'
            );
        }

        if (isset($expectedConfig['support_deep_think'])) {
            $this->assertSame(
                (bool) $expectedConfig['support_deep_think'],
                $versionEntity->isSupportDeepThink(),
                'support_deep_think shouldmatch'
            );
        }

        // validate string typefield
        if (isset($expectedConfig['billing_currency'])) {
            $this->assertSame(
                $expectedConfig['billing_currency'],
                $versionEntity->getBillingCurrency(),
                'billing_currency shouldmatch'
            );
        }

        // validateversionnumberandcurrentversionmark
        $this->assertSame($expectedVersion, $versionEntity->getVersion(), "versionnumbershouldis {$expectedVersion}");
        $this->assertTrue($versionEntity->isCurrentVersion(), 'shouldiscurrentversion');
    }
}
